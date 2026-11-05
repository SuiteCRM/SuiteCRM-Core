<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Module\Schedulers\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Schedulers in the record view, as the legacy pages: the jobs to choose
 * from (Scheduler::getJobsList), the interval in words (Scheduler::setIntervalHumanReadable), the
 * job of a scheduler split into a job function and a job URL and joined again as the legacy
 * Save.php, and the job log of a scheduler (job_queue, legacy Job Log subpanel).
 */
class SchedulerFieldsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'scheduler-fields';

    public const URL_JOB = 'url::';

    public const LOG_SORTS = ['status', 'resolution', 'execute_time', 'date_modified'];

    public const LOG_PAGE_SIZE = 20;

    protected const LOG_LABELS = ['LBL_NAME', 'LBL_STATUS', 'LBL_RESOLUTION', 'LBL_EXECUTE_TIME', 'LBL_MESSAGE'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The jobs to choose from (job functions, SuiteCRM 8 schedulers and URL), as the legacy edit
     * view.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getJobs(): array
    {
        return $this->inLegacy(function () {
            global $current_language, $sugar_config;

            require_once 'modules/Schedulers/Scheduler.php';

            // The field definitions are built with the legacy translations disabled (the labels of
            // getJobsList are their keys), and the front end translates the extra options with the
            // application strings only: the labels of the jobs.
            $strings = $this->translated(static function () use ($current_language, $sugar_config) {
                return return_module_language($current_language ?: ($sugar_config['default_language'] ?? 'en_us'), 'Schedulers');
            });

            $jobs = [];
            foreach (\Scheduler::getJobsList() as $value => $label) {
                $label = (string)$label;
                if (str_starts_with($label, 'LBL_') && !empty($strings[$label])) {
                    $label = (string)$strings[$label];
                }
                $jobs[] = ['value' => (string)$value, 'label' => $this->decode($label)];
            }

            return $jobs;
        });
    }

    /**
     * The job function and job URL of a stored job, as the legacy edit view.
     *
     * @return array{job_function: string, job_url: string}
     */
    public function splitJob(?string $job): array
    {
        $job = (string)$job;
        if (str_starts_with($job, self::URL_JOB)) {
            return ['job_function' => self::URL_JOB, 'job_url' => substr($job, strlen(self::URL_JOB))];
        }

        return ['job_function' => $job, 'job_url' => ''];
    }

    /**
     * The stored job of a job function and job URL, as the legacy Save.php (null: unchanged, a URL
     * job without URL).
     */
    public function joinJob(?string $jobFunction, ?string $jobUrl): ?string
    {
        $jobFunction = (string)$jobFunction;
        $jobUrl = trim((string)$jobUrl);

        if ($jobFunction !== self::URL_JOB) {
            return $jobFunction !== '' ? $jobFunction : null;
        }

        return $jobUrl !== '' && $jobUrl !== 'http://' ? self::URL_JOB . $jobUrl : null;
    }

    /**
     * The interval in words, as the legacy detail view ("every 10 minutes"...).
     */
    public function readable(?string $interval): string
    {
        if (empty($interval)) {
            return '';
        }

        return $this->inLegacy(function () use ($interval) {
            global $mod_strings, $current_language, $sugar_config;

            require_once 'modules/Schedulers/Scheduler.php';

            // The legacy detail view runs with the strings of the module (day names).
            $previous = $mod_strings ?? null;
            $mod_strings = $this->translated(static function () use ($current_language, $sugar_config) {
                return return_module_language($current_language ?: ($sugar_config['default_language'] ?? 'en_us'), 'Schedulers');
            });

            try {
                $scheduler = \BeanFactory::newBean('Schedulers');
                $scheduler->job_interval = $interval;
                $scheduler->parseInterval();
                $scheduler->setIntervalHumanReadable();

                return $this->decode(strip_tags((string)$scheduler->intervalHumanReadable));
            } finally {
                $mod_strings = $previous;
            }
        });
    }

    /**
     * A page of the job log of a scheduler (legacy Job Log subpanel), newest first by default.
     */
    public function getJobLog(string $schedulerId, int $offset, string $sort, string $direction): ?array
    {
        return $this->inLegacy(function () use ($schedulerId, $offset, $sort, $direction) {
            global $app_list_strings, $app_strings, $current_language, $timedate;

            $scheduler = \BeanFactory::getBean('Schedulers', $schedulerId);
            if (!$scheduler || empty($scheduler->id)) {
                return null;
            }

            $sort = in_array($sort, self::LOG_SORTS, true) ? $sort : 'execute_time';
            $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
            $offset = max(0, $offset);

            $db = \DBManagerFactory::getInstance();
            $where = 'scheduler_id = ' . $db->quoted($scheduler->id) . ' AND deleted = 0';
            $total = (int)$db->getOne("SELECT COUNT(*) FROM job_queue WHERE $where");

            $result = $db->limitQuery(
                "SELECT id, name, status, resolution, execute_time, date_modified, message FROM job_queue WHERE $where ORDER BY $sort $direction, date_entered DESC",
                $offset,
                self::LOG_PAGE_SIZE
            );

            $rows = [];
            while ($row = $db->fetchByAssoc($result, false)) {
                $rows[] = [
                    'id' => $row['id'],
                    'name' => $this->decode((string)$row['name']),
                    'status' => $this->decode((string)($app_list_strings['schedulers_times_dom'][$row['status']] ?? $row['status'])),
                    'resolution' => $this->decode((string)($app_list_strings['schedulers_resolution_dom'][$row['resolution']] ?? $row['resolution'])),
                    'resolutionKey' => (string)$row['resolution'],
                    'executeTime' => $row['execute_time'] ? $timedate->to_display_date_time($row['execute_time']) : '',
                    'dateModified' => $row['date_modified'] ? $timedate->to_display_date_time($row['date_modified']) : '',
                    'message' => trim($this->decode(strip_tags((string)$row['message']))),
                ];
            }

            $strings = return_module_language($current_language, 'SchedulersJobs');
            $labels = [];
            foreach (self::LOG_LABELS as $label) {
                $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
            }
            $schedulerStrings = return_module_language($current_language, 'Schedulers');
            $labels['LBL_JOBS_SUBPANEL_TITLE'] = $this->decode((string)($schedulerStrings['LBL_JOBS_SUBPANEL_TITLE'] ?? 'Job Log'));
            $labels['LBL_DATE_MODIFIED'] = rtrim($this->decode((string)($app_strings['LBL_DATE_MODIFIED'] ?? 'Date Modified')), ': ');

            return [
                'rows' => $rows,
                'total' => $total,
                'offset' => $offset,
                'pageSize' => self::LOG_PAGE_SIZE,
                'sort' => $sort,
                'direction' => strtolower($direction),
                'labels' => $labels,
            ];
        });
    }

    /**
     * Runs with the legacy translations on (the core turns them off for the metadata).
     */
    protected function translated(callable $callback)
    {
        global $sugar_config;

        $disabled = $sugar_config['disable_translations'] ?? null;
        unset($sugar_config['disable_translations']);

        try {
            return $callback();
        } finally {
            if ($disabled !== null) {
                $sugar_config['disable_translations'] = $disabled;
            }
        }
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
