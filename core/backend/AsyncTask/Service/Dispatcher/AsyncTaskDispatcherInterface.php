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


namespace App\AsyncTask\Service\Dispatcher;

interface AsyncTaskDispatcherInterface
{
    /**
     * Dispatches a new async task.
     * @param string $module module name, use default if not specified
     * @param string $taskId unique id of the task
     * @param string $type type of the task
     * @param string $handlerKey key of the task
     * @param array $taskData data to be sent with the task
     * @param array $progress
     * @return void
     */
    public function dispatchTaskRun(string $module, string $taskId, string $type, string $handlerKey, array $taskData, array $progress = []): void;

    /**
     * Re-dispatches an async task message.
     * @param object $message
     * @return void
     */
    public function reDispatch(object $message): void;

    /**
     * Dispatches a task completed message.
     * @param string $module module name, use default if not specified
     * @param string $taskId unique id of the task
     * @param string $type type of the task
     * @param string $handlerKey key of the task
     * @param array $taskData data to be sent with the task
     * @return void
     */
    public function dispatchTaskCompleted(string $module, string $taskId, string $type, string $handlerKey, array $taskData): void;

    /**
     * Dispatches a task progressed message.
     * @param string $module module name, use default if not specified
     * @param string $taskId unique id of the task
     * @param string $type type of the task
     * @param string $handlerKey key of the task
     * @param array $taskData data to be sent with the task
     * @param array $progress
     * @return void
     */
    public function dispatchTaskProgressed(string $module, string $taskId, string $type, string $handlerKey, array $taskData, array $progress = []): void;

    /**
     * Dispatches a task failure message.
     * @param string $module module name, use default if not specified
     * @param string $taskId unique id of the task
     * @param string $type type of the task
     * @param string $handlerKey key of the task
     * @param array $taskData data to be sent with the task
     * @return void
     */
    public function dispatchTaskFailure(string $module, string $taskId, string $type, string $handlerKey, array $taskData): void;
}
