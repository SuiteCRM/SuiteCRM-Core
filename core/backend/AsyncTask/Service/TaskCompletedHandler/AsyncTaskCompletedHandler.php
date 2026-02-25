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

namespace App\AsyncTask\Service\TaskCompletedHandler;

use App\AsyncTask\Message\AsyncTaskCompleted;
use App\AsyncTask\Service\Dispatcher\AsyncTaskNotificationDispatcherInterface;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AsyncTaskCompletedHandler implements AsyncTaskCompletedHandlerInterface
{

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected LoggerInterface $messengerLogger,
        protected AsyncTaskNotificationDispatcherInterface $notificationDispatcher
    ) {
    }

    /**
     * @throws Throwable
     */
    public function onComplete(AsyncTaskCompleted $message): void
    {
        $taskId = $message->getTaskId();

        $this->log('debug', 'onComplete() called', ['taskId' => $taskId, 'module' => $message->getModule()]);

        $task = $this->getAsyncTask($taskId);
        if ($task === null) {
            $this->log('error', 'Async task with ID ' . $taskId . ' not found.');
            return;
        }

        $this->markTaskAsCompleted($task, $message->getProgress());
        $this->notificationDispatcher->dispatchNotification($task, 'completed', $this->getType());
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
    protected function markTaskAsCompleted(Record $task, array $progress = []): void
    {
        $this->log('debug', 'Marking task as completed', ['taskId' => $task->getId()]);

        try {
            $attributes = $task->getAttributes();

            $progress['percent'] = 100;
            $attributes['progress'] = $progress;
            $attributes['status'] = 'completed';
            $attributes['phase'] = 'completed';
            $attributes['completed_with_failures'] = ($progress['failed'] ?? 0) > 0;
            $attributes['last_run_datetime'] = (new \DateTime())->format('Y-m-d H:i:s');

            $task->setAttributes($attributes);

            $this->recordProvider->saveRecord($task);

            $this->log('debug', 'Task marked as completed successfully', ['taskId' => $task->getId()]);
        } catch (Throwable $inner) {
            $this->log('error', 'Failed to mark task as completed: ' . $inner->getMessage());
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
        $this->messengerLogger->$level($message, array_merge(['component' => 'async-task-completed-handler', 'type' => $this->getType(), 'handlerKey' => $this->getHandlerKey()], $extra));
    }
}
