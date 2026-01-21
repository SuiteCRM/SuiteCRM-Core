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

namespace App\AsyncTask\Service\Runner;

use App\AsyncTask\Message\AsyncTaskRun;
use App\AsyncTask\Service\Dispatcher\AsyncTaskDispatcherInterface;
use App\AsyncTask\Service\TaskHandler\AsyncTaskHandlerInterface;
use App\AsyncTask\Service\TaskHandler\AsyncTaskHandlerRegistryInterface;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\Engine\Model\Feedback;
use App\MediaObjects\Repository\DefaultMediaObjectManager;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Psr\Log\LoggerInterface;

abstract class AsyncTaskRunner implements AsyncTaskRunnerInterface
{

    public function __construct(
        protected DefaultMediaObjectManager $defaultMediaObjectManager,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected AsyncTaskDispatcherInterface $asyncTaskDispatcher,
        protected AsyncTaskHandlerRegistryInterface $handlerRegistry,
        protected RecordProviderInterface $recordProvider,
        protected LoggerInterface $logger
    ) {
    }

    abstract protected function getMaxItemsPerRunConfigKey(): string;

    public function getHandlerKey(): string
    {
        return 'async-task-runner-' . $this->getType();
    }

    /**
     * @throws \Exception
     */
    public function run(AsyncTaskRun $message): void
    {
        $taskId = $message->getTaskId() ?? '';
        $task = $this->getAsyncTask($taskId);

        if ($task === null) {
            $this->log('error', 'Unable to run async task. No task with ID found: ' . $taskId);
            return;
        }

        $handlerKey = $message->getHandlerKey() ?? '';
        $handler = $this->getTaskHandler($handlerKey);
        if ($handler === null) {
            $this->log('error', 'Unable to run async task. No service found for key: ' . $handlerKey);
            return;
        }

        try {
            $result = $this->runTask($message, $task, $handler);
        } catch (\Exception $e) {
            $this->log('error', 'Async task ID ' . $taskId . ' for handler ' . $handlerKey . ' failed with error: ' . $e->getMessage());
            throw $e;
        }

        if ($result['status'] === 'completed') {
            $this->dispatchTaskCompleted($message);
            return;
        }

        $this->dispatchTaskProgressed($message, $result['progress']);
        $this->dispatchNextRun($message, $result['progress']);
    }

    protected function getTaskHandler(string $handlerKey): ?AsyncTaskHandlerInterface
    {
        return $this->handlerRegistry->getHandler($this->getType(), $handlerKey);
    }

    protected function getAsyncTask(string $taskId): ?Record
    {
        $task = null;
        try {
            $task = $this->recordProvider->getRecord($this->getType(), $taskId);
        } catch (\Throwable $inner) {
            $this->log('error', 'Unable to get async task with ID ' . $taskId . ': ' . $inner->getMessage());
        }

        return $task;
    }

    protected function getMaxItemsPerRun(): int
    {
        $configKey = $this->getMaxItemsPerRunConfigKey();
        return (int)($this->systemConfigProvider->getSystemConfig($configKey)?->getValue());
    }

    protected function runTask(AsyncTaskRun $message, Record $task, AsyncTaskHandlerInterface $handler): array
    {
        $maxItemsPerRun = $this->getMaxItemsPerRun();
        if (empty($maxItemsPerRun) || !is_numeric($maxItemsPerRun) || $maxItemsPerRun <= 0) {
            $maxItemsPerRun = 100;
        }

        $currentProgress = $message->getProgress();

        $batch = $handler->getBatch($task, $currentProgress, $maxItemsPerRun) ?? [];

        $result = [
            'status' => 'in_progress',
            'progress' => [],
        ];

        if (empty($batch)) {
            $result['status'] = 'completed';
            return $result;
        }

        foreach ($batch as $record) {
            $this->processRecord($task, $record, $batch, $handler);
        }

        $updatedProgress = $handler->getAsyncProgress($task, $batch);
        $remaining = $handler->getBatch($task, $updatedProgress, $maxItemsPerRun) ?? [];

        if (empty($remaining)) {
            $result['status'] = 'completed';
            return $result;
        }

        $result['progress'] = $updatedProgress;

        return $result;
    }

    /**
     * @param Record $task
     * @param mixed $recordToProcess
     * @param array $batch
     * @param AsyncTaskHandlerInterface $handler
     * @return void
     */
    protected function processRecord(Record $task, Record $recordToProcess, array $batch, AsyncTaskHandlerInterface $handler): void
    {
        $this->log('info', 'Processing record ID: ' . $recordToProcess->getId());
        $result = $this->runService($task, $recordToProcess, $batch, $handler);

        if ($result !== null && $result->isSuccess()) {
            $this->markRecordAsProcessed($handler, $task, $recordToProcess);
        } else {
            $this->markRecordAsFailed($handler, $task, $recordToProcess);
        }
    }


    /**
     * @param Record $task
     * @param mixed $recordToProcess
     * @param array $batch
     * @param AsyncTaskHandlerInterface $service
     * @return Feedback|null
     */
    protected function runService(Record $task, Record $recordToProcess, array $batch, AsyncTaskHandlerInterface $service): ?Feedback
    {
        try {
            $result = $service->asyncRun($task, $recordToProcess, $batch);

        } catch (\Throwable $e) {
            $result = null;
            $this->log('error', 'Error processing record ID: ' . $recordToProcess->getId() . ' Error: ' . $e->getMessage());
        }
        return $result;
    }

    protected function dispatchNextRun(AsyncTaskRun $message, array $updatedProgress): void
    {
        $this->asyncTaskDispatcher->dispatchTaskRun(
            $message->getModule() ?? 'default',
            $message->getTaskId(),
            $message->getTaskType(),
            $message->getHandlerKey(),
            $message->getData(),
            $updatedProgress
        );
    }

    protected function dispatchTaskCompleted(AsyncTaskRun $message): void
    {
        $this->asyncTaskDispatcher->dispatchTaskCompleted(
            $message->getModule() ?? 'default',
            $message->getTaskId(),
            $message->getTaskType(),
            $message->getHandlerKey(),
            $message->getData()
        );
    }

    protected function dispatchTaskProgressed(AsyncTaskRun $message, array $updatedProgress): void
    {
        $this->asyncTaskDispatcher->dispatchTaskProgressed(
            $message->getModule() ?? 'default',
            $message->getTaskId(),
            $message->getTaskType(),
            $message->getHandlerKey(),
            $message->getData(),
            $updatedProgress
        );
    }

    protected function markRecordAsProcessed(AsyncTaskHandlerInterface $service, Record $task, Record $record): void
    {
        try {
            $service->setAsyncStatusProcessed($task, $record);
        } catch (\Throwable $inner) {
            $this->log('error', 'Failed to mark record as processed: ' . $inner->getMessage());
        }
    }

    protected function markRecordAsFailed(AsyncTaskHandlerInterface $service, Record $task, Record $record): void
    {
        try {
            $service->setAsyncStatusFailed($task, $record);
        } catch (\Throwable $inner) {
            $this->log('error', 'Failed to mark record as failed: ' . $inner->getMessage());
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message): void
    {
        $this->logger->$level($message, ['component' => 'async-task-runner', 'type' => $this->getType()]);
    }
}
