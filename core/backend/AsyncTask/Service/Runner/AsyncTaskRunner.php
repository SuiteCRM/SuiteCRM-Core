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
use App\AsyncTask\Service\Repository\AsyncTaskItemRepository;
use App\AsyncTask\Service\TaskHandler\AsyncTaskHandlerInterface;
use App\AsyncTask\Service\TaskHandler\AsyncTaskHandlerRegistryInterface;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\MediaObjects\Repository\DefaultMediaObjectManager;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Psr\Log\LoggerInterface;

abstract class AsyncTaskRunner implements AsyncTaskRunnerInterface
{
    public const PHASE_QUEUEING = 'queueing';
    public const PHASE_PROCESSING = 'processing';
    public const PHASE_FINALIZING = 'finalizing';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        protected DefaultMediaObjectManager $defaultMediaObjectManager,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected AsyncTaskDispatcherInterface $asyncTaskDispatcher,
        protected AsyncTaskHandlerRegistryInterface $handlerRegistry,
        protected RecordProviderInterface $recordProvider,
        protected AsyncTaskItemRepository $itemRepository,
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
            $this->cleanupItems($message->getTaskId());
            $this->dispatchTaskCompleted($message);
            return;
        }

        if ($result['status'] === 'failed') {
            $this->cleanupItems($message->getTaskId());
            $this->dispatchTaskFailure($message);
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
        $value = (int)($this->systemConfigProvider->getSystemConfig($configKey)?->getValue());

        if (empty($value) || $value <= 0) {
            return 100;
        }

        return $value;
    }

    /**
     * Phase-aware task execution.
     * Dispatches to the appropriate phase handler based on progress['phase'].
     */
    protected function runTask(AsyncTaskRun $message, Record $task, AsyncTaskHandlerInterface $handler): array
    {
        $maxItems = $this->getMaxItemsPerRun();
        $progress = $message->getProgress();
        $phase = $progress['phase'] ?? self::PHASE_QUEUEING;

        $this->log('info', 'Running task ' . $task->getId() . ' in phase: ' . $phase);

        return match ($phase) {
            self::PHASE_QUEUEING => $this->runQueueingPhase($task, $handler, $progress, $maxItems),
            self::PHASE_PROCESSING => $this->runProcessingPhase($task, $handler, $progress, $maxItems),
            self::PHASE_FINALIZING => $this->runFinalizingPhase($task, $handler, $progress),
            default => ['status' => 'completed', 'progress' => $progress],
        };
    }

    /**
     * QUEUEING phase: ask handler for items to enqueue, insert them into the items table.
     */
    protected function runQueueingPhase(Record $task, AsyncTaskHandlerInterface $handler, array $progress, int $maxItems): array
    {
        $taskId = $task->getId();
        $items = $handler->enqueueItems($task, $progress, $maxItems);

        if (!empty($items)) {
            $this->itemRepository->insertItems($taskId, $items);
            $this->log('info', 'Enqueued ' . count($items) . ' items for task ' . $taskId);
        }

        $enqueuedCount = count($items);
        $progress['enqueue_offset'] = ($progress['enqueue_offset'] ?? 0) + $enqueuedCount;

        // If fewer items returned than batch size, queueing is done
        if ($enqueuedCount < $maxItems) {
            $total = $this->itemRepository->getTotal($taskId);

            if ($total > 0) {
                $progress['phase'] = self::PHASE_PROCESSING;
                $progress['total'] = $total;
                $progress['completed'] = 0;
                $progress['failed'] = 0;
                $progress['skipped'] = 0;
                $progress['percent'] = 0;
                $this->log('info', 'Queueing complete for task ' . $taskId . '. Total items: ' . $total . '. Transitioning to processing.');

                return ['status' => 'in_progress', 'progress' => $progress];
            }

            // Nothing was enqueued at all
            $this->log('info', 'No items enqueued for task ' . $taskId . '. Completing.');

            return ['status' => 'completed', 'progress' => $progress];
        }

        $progress['phase'] = self::PHASE_QUEUEING;

        return ['status' => 'in_progress', 'progress' => $progress];
    }

    /**
     * PROCESSING phase: fetch queued items from the items table, process each via handler.
     */
    protected function runProcessingPhase(Record $task, AsyncTaskHandlerInterface $handler, array $progress, int $maxItems): array
    {
        $taskId = $task->getId();
        $batch = $this->itemRepository->fetchBatch($taskId, self::STATUS_QUEUED, $maxItems);

        if (empty($batch)) {
            return $this->transitionFromProcessing($task, $handler, $progress);
        }

        $this->log('info', 'Processing batch of ' . count($batch) . ' items for task ' . $taskId);

        foreach ($batch as $item) {
            $this->processItem($task, $handler, $item);
        }

        // Update progress from DB counts
        $progress = $this->calculateProgress($taskId, $progress);

        // Check if more queued items remain
        $queued = $progress['queued'] ?? 0;
        $processing = $progress['processing_count'] ?? 0;

        if ($queued === 0 && $processing === 0) {
            return $this->transitionFromProcessing($task, $handler, $progress);
        }

        return ['status' => 'in_progress', 'progress' => $progress];
    }

    /**
     * Process a single item: mark as processing, call handler, update status.
     */
    protected function processItem(Record $task, AsyncTaskHandlerInterface $handler, array $item): void
    {
        $itemId = $item['id'] ?? '';
        $itemKey = $item['item_key'] ?? '';
        $this->log('info', 'Processing item: ' . $itemId . ' (key: ' . $itemKey . ')');

        $this->itemRepository->updateItemStatus($itemId, self::STATUS_PROCESSING);

        try {
            $result = $handler->processItem($task, $item);

            if ($result !== null && $result->isSuccess()) {

                $resultData = $result->getData();
                if (empty($resultData)) {
                    $resultData = null;
                }

                $this->itemRepository->updateItem($itemId, self::STATUS_COMPLETED, $resultData);

            } else {
                $errorMsg = 'Processing failed';
                $messages = $result?->getMessages() ?? [];
                if (!empty($messages)) {
                    $errorMsg = $messages[0];
                }

                $this->itemRepository->updateItemStatus($itemId, self::STATUS_FAILED, $errorMsg);
            }

        } catch (\Throwable $e) {
            $this->log('error', 'Error processing item ' . $itemId . ': ' . $e->getMessage());
            $this->itemRepository->updateItemStatus($itemId, self::STATUS_FAILED, $e->getMessage());
        }
    }

    /**
     * Transition from processing phase: retry failed items, then move to finalizing or completed/failed.
     */
    protected function transitionFromProcessing(Record $task, AsyncTaskHandlerInterface $handler, array $progress): array
    {
        $taskId = $task->getId();
        $maxRetries = $handler->getMaxRetries();

        // Retry failed items if the handler supports it
        if ($maxRetries > 0) {
            $retriedCount = $this->itemRepository->retryFailedItems($taskId, $maxRetries);

            if ($retriedCount > 0) {
                $this->log('info', 'Retrying ' . $retriedCount . ' failed items for task ' . $taskId . ' (max retries: ' . $maxRetries . ').');
                $progress = $this->calculateProgress($taskId, $progress);

                return ['status' => 'in_progress', 'progress' => $progress];
            }
        }

        // Recalculate progress to get final counts
        $progress = $this->calculateProgress($taskId, $progress);
        $failedCount = $progress['failed'] ?? 0;
        $total = $progress['total'] ?? 0;

        // If ALL items failed, mark task as failed
        if ($failedCount > 0 && $failedCount >= $total) {
            $this->log('error', 'All ' . $failedCount . ' items failed for task ' . $taskId . '. Marking as failed.');
            $progress['percent'] = 100;

            return ['status' => 'failed', 'progress' => $progress];
        }

        if ($handler->hasFinalization()) {
            $progress['phase'] = self::PHASE_FINALIZING;
            $this->log('info', 'All items processed for task ' . $taskId . '. Transitioning to finalizing.');

            return ['status' => 'in_progress', 'progress' => $progress];
        }

        $this->log('info', 'All items processed for task ' . $taskId . '. Completing.');
        $progress['percent'] = 100;

        return ['status' => 'completed', 'progress' => $progress];
    }

    /**
     * FINALIZING phase: call handler's finalize() method.
     */
    protected function runFinalizingPhase(Record $task, AsyncTaskHandlerInterface $handler, array $progress): array
    {
        $this->log('info', 'Running finalization for task: ' . $task->getId());

        try {
            $result = $handler->finalize($task);

            if ($result === null || !$result->isSuccess()) {
                $this->log('error', 'Finalization failed for task: ' . $task->getId());
            }
        } catch (\Throwable $e) {
            $this->log('error', 'Finalization error for task ' . $task->getId() . ': ' . $e->getMessage());
        }

        $progress['percent'] = 100;

        return ['status' => 'completed', 'progress' => $progress];
    }

    /**
     * Calculate progress from DB item counts.
     */
    protected function calculateProgress(string $taskId, array $progress): array
    {
        $counts = $this->itemRepository->countByStatus($taskId);
        $total = array_sum($counts);
        $completed = $counts[self::STATUS_COMPLETED] ?? 0;
        $failed = $counts[self::STATUS_FAILED] ?? 0;
        $skipped = $counts[self::STATUS_SKIPPED] ?? 0;
        $queued = $counts[self::STATUS_QUEUED] ?? 0;
        $processingCount = $counts[self::STATUS_PROCESSING] ?? 0;
        $done = $completed + $failed + $skipped;

        $progress['phase'] = self::PHASE_PROCESSING;
        $progress['total'] = $total;
        $progress['completed'] = $completed;
        $progress['failed'] = $failed;
        $progress['skipped'] = $skipped;
        $progress['queued'] = $queued;
        $progress['processing_count'] = $processingCount;
        $progress['percent'] = $total > 0 ? (int)round($done / $total * 100) : 0;

        return $progress;
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

    protected function dispatchTaskFailure(AsyncTaskRun $message): void
    {
        $this->asyncTaskDispatcher->dispatchTaskFailure(
            $message->getModule() ?? 'default',
            $message->getTaskId(),
            $message->getTaskType(),
            $message->getHandlerKey(),
            $message->getData()
        );
    }

    /**
     * Clean up items after task completion.
     * Uses selective purge: failed items are preserved for user review.
     */
    protected function cleanupItems(string $taskId): void
    {
        try {
            $this->itemRepository->purgeCompletedItems($taskId);
            $this->log('info', 'Cleaned up non-failed items for task ' . $taskId);
        } catch (\Throwable $e) {
            $this->log('error', 'Failed to clean up items for task ' . $taskId . ': ' . $e->getMessage());
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
