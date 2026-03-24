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

namespace App\AsyncTask\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\AsyncTask\Service\Dispatcher\AsyncTaskDispatcherInterface;
use App\AsyncTask\Service\Repository\AsyncTaskItemRepository;
use App\AsyncTask\Service\Runner\AsyncTaskRunnerRegistryInterface;
use App\Data\Service\RecordProviderInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class RetryFailedAsyncTaskAction implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'record-retry-failed-async-task';

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected AsyncTaskItemRepository $itemRepository,
        protected AsyncTaskDispatcherInterface $asyncTaskDispatcher,
        protected AsyncTaskRunnerRegistryInterface $runnerRegistry
    ) {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';

        return [
            $module => [
                [
                    'action' => 'view',
                    'record' => $options['id'] ?? '',
                ],
            ],
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['action']) || empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';
        $id = $options['id'] ?? '';

        if ($this->runnerRegistry->getRunner($module) === null) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ASYNC_TASK_UNSUPPORTED_MODULE']);
            return;
        }

        $task = $this->recordProvider->getRecord($module, $id);
        $attrs = $task->getAttributes();
        $serviceKey = $attrs['service_key'] ?? '';

        $status = $attrs['status'] ?? '';
        if (empty($attrs['allow_failure_retry_action']) || !in_array($status, ['completed_with_failures', 'failed'], true)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_RETRY_FAILED_NOT_ELIGIBLE']);
            return;
        }

        $this->itemRepository->resetFailedItems($id);

        $progress = $this->recalculateProgress($id);

        $attrs['status'] = 'running';
        $attrs['phase'] = 'processing';
        $attrs['progress'] = $progress;

        $task->setAttributes($attrs);
        $this->recordProvider->saveRecord($task);

        $this->asyncTaskDispatcher->dispatchTaskRun(
            $module,
            $id,
            $module, // taskType == module for these task types
            $serviceKey,
            [], // items are already in DB, no init data needed
            $progress
        );

        $process->setStatus('success');
        $process->setMessages(['LBL_RETRY_FAILED_SUCCESS']);
        $process->setData(
            [
                'reload' => true,
                'dataUpdated' => true,
            ]
        );
    }

    protected function recalculateProgress(string $id): array
    {
        $counts = $this->itemRepository->countByStatus($id);
        $total = array_sum($counts);
        $completed = $counts['completed'] ?? 0;
        $failed = $counts['failed'] ?? 0;
        $queued = $counts['queued'] ?? 0;
        $skipped = $counts['skipped'] ?? 0;
        $percent = $total > 0 ? (int)round($completed / $total * 100) : 0;

        return [
            'phase' => 'processing',
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'queued' => $queued,
            'skipped' => $skipped,
            'percent' => $percent,
        ];
    }
}
