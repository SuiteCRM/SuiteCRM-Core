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

namespace App\AsyncTask\Service\TaskProgressedHandler;

use App\AsyncTask\Message\AsyncTaskProgressed;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AsyncTaskProgressedHandler implements AsyncTaskProgressedHandlerInterface
{
    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @throws Throwable
     */
    public function onProgress(AsyncTaskProgressed $message): void
    {
        $taskId = $message->getTaskId();

        $this->log('debug', 'onProgress() called', [
            'taskId' => $taskId,
            'module' => $message->getModule(),
            'percent' => $message->getProgress()['percent'] ?? 'n/a',
            'phase' => $message->getProgress()['phase'] ?? 'n/a',
        ]);

        $task = $this->getAsyncTask($taskId);
        if ($task === null) {
            $this->log('error', 'Async task with ID ' . $taskId . ' not found.');
            return;
        }

        $progress = $message->getProgress() ?? [];

        $this->updateTaskProgress($task, $progress);
    }

    /**
     * @throws Throwable
     */
    protected function getAsyncTask(string $taskId): ?Record
    {
        $this->log('debug', 'Fetching async task record', ['taskId' => $taskId]);

        try {
            $task = $this->recordProvider->getRecord($this->getType(), $taskId);
        } catch (Throwable $inner) {
            $this->log('error', 'Unable to get async task with ID ' . $taskId . ': ' . $inner->getMessage());
            throw $inner;
        }

        return $task;
    }


    /**
     * @throws Throwable
     */
    protected function updateTaskProgress(Record $task, array $updatedProgress): void
    {
        $this->log('debug', 'Updating task progress', [
            'taskId' => $task->getId(),
            'phase' => $updatedProgress['phase'] ?? '',
            'percent' => $updatedProgress['percent'] ?? 'n/a',
            'completed' => $updatedProgress['completed'] ?? 'n/a',
            'failed' => $updatedProgress['failed'] ?? 'n/a',
        ]);

        try {
            $attributes = $task->getAttributes();

            $attributes['progress'] = $updatedProgress;
            $attributes['status'] = 'running';
            $attributes['phase'] = $updatedProgress['phase'] ?? '';
            $attributes['last_run_datetime'] = (new \DateTime())->format('Y-m-d H:i:s');

            $task->setAttributes($attributes);

            $this->recordProvider->saveRecord($task);

            $this->log('debug', 'Task progress updated successfully', ['taskId' => $task->getId()]);
        } catch (Throwable $inner) {
            $this->log('error', 'Failed to update task progress: ' . $inner->getMessage());
            throw $inner;
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message, array $extra = []): void
    {
        $this->logger->$level($message, array_merge(['component' => 'async-task-progressed-handler', 'type' => $this->getType(), 'handlerKey' => $this->getHandlerKey()], $extra));
    }
}
