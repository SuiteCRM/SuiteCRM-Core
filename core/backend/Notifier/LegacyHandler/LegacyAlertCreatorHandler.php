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

namespace App\Notifier\LegacyHandler;

use App\AsyncTask\Service\LegacyBridge\AsyncTaskLegacyHandler;
use App\DateTime\LegacyHandler\DateTimeHandlerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Notifier\Message\Notification;
use BeanFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class LegacyAlertCreatorHandler
{
    public function __construct(
        protected AsyncTaskLegacyHandler $legacyHandler,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected LoggerInterface $logger,
        protected DateTimeHandlerInterface $dateTimeHandler
    ) {
    }

    public function createAlert(Notification $notification): void
    {
        $legacyModule = $this->moduleNameMapper->toLegacy($notification->getTargetModule());
        $urlRedirect = $notification->getUrlRedirect();

        if (empty($urlRedirect) && !empty($notification->getTargetRecordId())) {
            $urlRedirect = 'index.php?action=DetailView' . '&module=' . $legacyModule . '&record=' . $notification->getTargetRecordId();
        }

        $this->legacyHandler->startLegacy();

        try {
            /** @var \Alert $alert */
            $alert = BeanFactory::newBean('Alerts');
            $alert->name = $notification->getSubject();
            $alert->description = $notification->getDescription();
            $alert->url_redirect = $urlRedirect;
            $alert->target_module = $legacyModule;
            $alert->is_read = false;
            $alert->assigned_user_id = $notification->getAssignedUserId();
            $alert->type = $notification->getType();
            $alert->reminder_id = '';
            $alert->snooze = $this->dateTimeHandler->nowDb();
            $alert->status = $notification->getData()['status'] ?? '';
            $alert->save();
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to create alert notification', [
                    'error' => $e->getMessage(),
                    'assignedUserId' => $notification->getAssignedUserId(),
                    'subject' => $notification->getSubject(),
                    'targetModule' => $notification->getTargetModule(),
                ]
            );
        } finally {
            $this->legacyHandler->stopLegacy();
        }
    }
}
