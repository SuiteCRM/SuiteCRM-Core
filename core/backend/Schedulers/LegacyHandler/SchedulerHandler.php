<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Schedulers\LegacyHandler;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Schedulers\Runners\LegacySchedulerRunner;
use App\Schedulers\Runners\SchedulerRunner;
use App\Schedulers\Service\SchedulerRegistry;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SchedulerHandler extends LegacyHandler
{

    public const HANDLER_KEY = 'scheduler-handler';

    protected SchedulerRegistry $schedulerRegistry;
    protected SystemConfigHandler $systemConfigHandler;
    protected PreparedStatementHandler $preparedStatementHandler;
    protected LoggerInterface $logger;
    protected LegacySchedulerRunner $legacySchedulerRunner;
    protected SchedulerRunner $schedulerRunner;

    protected int $maxJobs = 10;
    protected int $maxRuntime = 60;
    protected int $minInterval = 30;

    protected int $jobTries = 5;
    protected int $timeout = 86400; // seconds
    protected int $successLifetime = 30; // days
    protected int $failureLifetime = 180; // days

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        SchedulerRegistry $schedulerRegistry,
        SystemConfigHandler $systemConfigHandler,
        PreparedStatementHandler $preparedStatementHandler,
        LegacySchedulerRunner $legacySchedulerRunner,
        SchedulerRunner $schedulerRunner,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
        $this->schedulerRegistry = $schedulerRegistry;
        $this->systemConfigHandler = $systemConfigHandler;
        $this->preparedStatementHandler = $preparedStatementHandler;
        $this->legacySchedulerRunner = $legacySchedulerRunner;
        $this->schedulerRunner = $schedulerRunner;
        $this->logger = $logger;

        $this->setCronConfig();
        $this->setJobsConfig();
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    public function runSchedulers(): array
    {

        if (!$this->throttle()) {
            $this->logger->error('Jobs run too frequently, throttled to protect the system');
            return [];
        }

        $this->cleanup();

        $this->clearHistoricJobs();

        $schedulerTable = $this->getSchedulerTable();
        $jobQueueTable = $this->getJobQueueTable();

        $query = "SELECT * FROM $schedulerTable sched WHERE status = 'Active' ";
        $query .= "AND NOT EXISTS(SELECT id FROM $jobQueueTable WHERE scheduler_id = sched.id AND status != 'done')";
        $schedulers = [];

        try {
            $schedulers = $this->preparedStatementHandler->fetchAll($query, []);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if (empty($schedulers)) {
            $this->logger->debug('Schedulers:SchedulerHandler::runSchedulers | No schedulers found');
            return [];
        }

        foreach ($schedulers as $scheduler) {
            $this->init();
            $schedulerBean = \BeanFactory::getBean('Schedulers', $scheduler['id']);

            $this->close();

            $this->createJob($schedulerBean);
        }

        $response = [];
        $cutoff = time() + $this->maxRuntime;

        if (empty($this->maxJobs)) {
            $this->logger->error('Cron hit max jobs');
        }

        for ($count = 0; $count < $this->maxJobs; $count++) {

            $job = $this->getNextScheduler();

            if ($job === null) {
                break;
            }

            if (empty($job->id)) {
                $this->logger->error('Unable to get job id');
                break;
            }

            try {
                if (str_contains($job->target, 'function::') || str_contains($job->target, 'class::') || str_contains($job->target, 'url::')) {
                    $status = $this->legacySchedulerRunner->run($job);
                } else {
                    $status = $this->schedulerRunner->run($job);
                }
            } catch (\Exception $e) {
                $this->logger->error('Schedulers:SchedulerHandler::runSchedulers -  Exception running job -  ' . $job->target . ' | job id - ' . $job->id ?? '' . ' | message | ' . $e->getMessage(), [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ]);
                $status = false;
            }

            $this->resolveJob($job->id, $status);

            $response[] = [
                'name' => $job->name,
                'result' => $status,
            ];

            if (time() >= $cutoff) {
                $this->logger->info('Timeout');
                break;
            }
        }

        return $response;
    }

    public function resolveJob($id, $result, $messages = null): void
    {

        $this->init();

        $job = \BeanFactory::getBean('SchedulersJobs', $id);
        $job->resolution = 'success';
        $job->status = 'done';

        if (!$result) {
            $job->resolution = 'failed';
            $job->failure_count++;

            if ($job->requeue && $job->retry_count > 0) {
                $this->requeueJob($job);
            }
        }

        if (!empty($messages)) {
            $job->addMessages($messages);
        }

        $job->save();

        $this->close();

        if ($job->resolution === 'success' && $job->status === 'done') {
            $this->updateLastRun($job->scheduler_id);
        }
    }

    public function buildJob($scheduler): \SugarBean|bool
    {
        $this->init();

        global $timedate, $current_user;

        $job = \BeanFactory::newBean('SchedulersJobs');
        $job->scheduler_id = $scheduler->id;
        $job->name = $scheduler->name ?? '';
        $job->execute_time = $timedate->nowDb();
        $job->target = $scheduler->job;

        $job->assigned_user_id = $current_user->id ?? '';
        $this->close();
        return $job;
    }

    protected function submitJob(\SugarBean|bool $job): void
    {
        $this->init();

        global $timedate;

        $job->id = create_guid();
        $job->new_with_id = true;
        $job->status = 'queued';
        $job->resolution = 'queued';

        if (empty($job->execute_time ?? '')) {
            $job->execute_time = $timedate->nowDb();
        }

        $job->save();

        $this->close();
    }

    protected function getNextScheduler(): \SugarBean|bool|null
    {
        $this->init();

        global $timedate;

        $this->close();
        $tries = $this->jobTries;
        $cronId = $this->getCronId();

        $jobQueueTable = $this->getJobQueueTable();

        $query = "SELECT id FROM $jobQueueTable WHERE execute_time <= :now AND status = 'queued' ORDER BY date_entered ASC";

        while ($tries--) {
            try {
                $result = $this->preparedStatementHandler->fetch(
                    $query, [
                    'now' => $timedate->nowDb()
                ]
                );
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            if (empty($result['id'])) {
                return null;
            }

            $this->init();
            $job = \BeanFactory::getBean('SchedulersJobs');
            $job->retrieve($result['id']);

            if (empty($job->id)) {
                return null;
            }

            $job->status = 'running';
            $job->client = $cronId;

            $update = "UPDATE $jobQueueTable SET status = :job_status, date_modified = :now, client = :client_id ";
            $update .= 'WHERE id = :job_id AND status = :status';

            $result = [];

            try {
                $result = $this->preparedStatementHandler->update(
                    $update, [
                    'job_status' => $job->status,
                    'now' => $timedate->nowDb(),
                    'client_id' => $job->client,
                    'job_id' => $job->id,
                    'status' => 'queued'
                ], [
                    ['param' => 'job_status', 'type' => 'string'],
                    ['param' => 'now', 'type' => 'string'],
                    ['param' => 'client_id', 'type' => 'string'],
                    ['param' => 'job_id', 'type' => 'string'],
                    ['param' => 'status', 'type' => 'string']
                ]
                );
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            if (empty($result)) {
                continue;
            }

            $job->save();
            $this->close();

            break;
        }

        return $job;
    }


    protected function getCronId(): string
    {
        $this->init();

        global $sugar_config;

        $key = $sugar_config['unique_key'];

        $id = "CRON$key:" . getmypid();

        $this->close();

        return $id;
    }

    public function getCronConfig(): ?array
    {
        return $this->systemConfigHandler->getSystemConfig('cron')?->getItems();
    }

    public function throttle(): bool
    {
        $minInterval = $this->getCronConfig()['min_cron_interval'];

        if ($minInterval === 0) {
            return true;
        }
        $this->init();
        $lockfile = sugar_cached('modules/Schedulers/lastrun');

        create_cache_directory($lockfile);

        if (!file_exists($lockfile)) {
            $this->markLastRun($lockfile);
            return true;
        }

        $contents = file_get_contents($lockfile);
        $this->markLastRun($lockfile);

        $this->close();

        return time() - $contents >= $minInterval;
    }

    protected function markLastRun($lockfile = null): void
    {
        if (!file_put_contents($lockfile, time())) {
            $this->logger->error('Scheduler cannot write PID file.  Please check permissions on ' . $lockfile);
        }
    }

    public function createJob(\SugarBean $scheduler): void
    {
        $this->init();

        global $timedate;

        if (!$scheduler->fireQualified()) {
            $this->logger->debug('Scheduler did NOT find valid job (' . $scheduler->name . ') for time GMT (' . $timedate->now() . ')');
            return;
        }

        $job = $this->buildJob($scheduler);
        $this->submitJob($job);
        $this->logger->debug('Schedulers:SchedulerHandler::createJob - job added | name - ' . $job->name . ' | target - ' . $job->target);

        $this->close();
    }

    public function cleanup(): void
    {
        $this->init();

        global $timedate, $app_strings;

        $date = '';

        try {
            $date = $timedate->getNow()->modify("-$this->timeout seconds")->asDb();
        } catch (\DateMalformedStringException $e) {
            $this->logger->error($e->getMessage());
        }

        $this->close();

        $table = $this->getJobQueueTable();

        $query = "SELECT id from $table WHERE status = 'running' AND date_modified <= :date ";

        $results = [];

        try {
            $results = $this->preparedStatementHandler->fetchAll($query, ["date" => $date]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        foreach ($results as $result) {
            $this->resolveJob($result['id'], false, $app_strings['ERR_TIMEOUT']);
        }
    }

    public function clearHistoricJobs(): void
    {
        $this->processJobs($this->successLifetime, true);
        $this->processJobs($this->failureLifetime, false);
    }

    protected function processJobs(string $days, bool $success): void
    {
        $this->init();

        global $timedate;

        try {
            $date = $timedate->getNow()->modify("-$days days")->asDb();
        } catch (\DateMalformedStringException $e) {
            $this->logger->error($e->getMessage());
        }

        $this->close();

        $resolution = "AND resolution = 'success'";

        if (!$success) {
            $resolution = "AND resolution != 'success'";
        }

        $table = $this->getJobQueueTable();

        $query = "SELECT id FROM $table WHERE status = 'done' AND date_modified <= :date " . $resolution;

        $results = [];

        try {
            $results = $this->preparedStatementHandler->fetchAll($query, ["date" => $date]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        foreach ($results as $result) {
            $this->deleteJob($result['id']);
        }
    }

    protected function deleteJob(string $id): void
    {
        $this->init();

        $job = \BeanFactory::newBean('SchedulersJobs');

        if (empty($job)) {
            return;
        }

        $job->mark_deleted($id);

        $this->close();
    }

    protected function setCronConfig(): void
    {
        $cronConfig = $this->systemConfigHandler->getSystemConfig('cron')?->getItems() ?? [];

        $this->maxJobs = $cronConfig['max_cron_jobs'] ?? $this->maxJobs;
        $this->maxRuntime = $cronConfig['max_cron_runtime'] ?? $this->maxRuntime;
        $this->minInterval = $cronConfig['min_cron_interval'] ?? $this->minInterval;
    }

    protected function setJobsConfig(): void
    {
        $jobConfig = $this->systemConfigHandler->getSystemConfig('jobs')?->getItems() ?? [];

        $this->successLifetime = $jobConfig['success_lifetime'] ?? $this->successLifetime;
        $this->failureLifetime = $jobConfig['failure_lifetime'] ?? $this->failureLifetime;
        $this->timeout = $jobConfig['timeout'] ?? $this->timeout;
        $this->jobTries = $jobConfig['max_retries'] ?? $this->jobTries;
    }

    protected function updateLastRun(string $id): void
    {
        $this->init();

        global $timedate;

        $this->close();

        $table = $this->getSchedulerTable();

        $query = "UPDATE $table SET last_run = :now WHERE id = :id";

        try {
            $this->preparedStatementHandler->update(
                $query, ["now" => $timedate->nowDb(), "id" => $id], [
                ['param' => 'now', 'type' => 'string'],
                ['param' => 'id', 'type' => 'string']
            ]
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param \SugarBean|bool $job
     * @return void
     */
    public function requeueJob(\SugarBean|bool $job): void
    {
        global $timedate;

        $job->status = 'queued';

        if ($job->job_delay < $job->min_interval) {
            $job->job_delay = $job->min_interval;
        }

        try {
            $job->execute_time = $timedate->getNow()->modify("+{$job->job_delay} seconds")->asDb();
        } catch (\DateMalformedStringException $e) {
            $this->logger->error($e->getMessage());
        }

        $job->retry_count--;

        $this->logger->info("Will retry job $job->name at $job->execute_time $job->retry_count");
    }

    protected function getJobQueueTable(): string
    {

        $this->init();

        $table = \BeanFactory::newBean('SchedulersJobs')->getTableName();

        $this->close();

        return $table;
    }

    protected function getSchedulerTable(): string
    {

        $this->init();

        $table = \BeanFactory::newBean('Schedulers')->getTableName();

        $this->close();

        return $table;
    }
}
