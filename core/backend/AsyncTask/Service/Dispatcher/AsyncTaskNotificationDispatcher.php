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

use App\Data\Entity\Record;
use App\Languages\Service\LanguageManagerInterface;
use App\Notifier\Service\NotificationDispatcher;
use Psr\Log\LoggerInterface;

class AsyncTaskNotificationDispatcher implements AsyncTaskNotificationDispatcherInterface
{
    public function __construct(
        protected NotificationDispatcher $notificationDispatcher,
        protected LanguageManagerInterface $languageManager,
        protected LoggerInterface $logger
    ) {
    }

    public function dispatchNotification(Record $task, string $status, string $module): void
    {
        $attrs = $task->getAttributes();
        $assignedUserId = $attrs['assigned_user_id'] ?? '';

        if (empty($assignedUserId)) {
            $this->logger->warning('No assigned_user_id on task — skipping notification', ['taskId' => $task->getId()]);
            return;
        }

        $taskName = $attrs['name'] ?? '';
        $labelKey = $status === 'completed' ? 'LBL_TASK_NOTIFICATION_COMPLETED' : 'LBL_TASK_NOTIFICATION_FAILED';
        $prefix   = $this->languageManager->getAppLabel($labelKey);
        $subject  = trim($prefix . ' ' . $taskName);
        $type = $status === 'completed' ? 'info' : 'warning';

        $this->notificationDispatcher->dispatch(
            subject: $subject,
            assignedUserId: $assignedUserId,
            targetModule: $module,
            targetRecordId: $task->getId(),
            type: $type
        );
    }
}
