<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

namespace App\Data\LegacyHandler;

use App\Data\Service\AlertProviderInterface;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperRunner;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerRunnerInterface;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Service\AclManagerInterface;
use App\Module\Service\FavoriteProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AlertHandler
 */
class AlertHandler extends RecordHandler implements AlertProviderInterface
{
    public const HANDLER_KEY = 'alert-handler';

    /**
     * @var RecordDeletionServiceInterface
     */
    protected $recordDeletionProvider;

    /**
     * RecordViewHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     * @param AclManagerInterface $aclHandler
     * @param FavoriteProviderInterface $favorites
     * @param RecordDeletionServiceInterface $recordDeletionProvider
     * @param EntityRecordMapperRunner $entityRecordMapperRunner
     * @param RecordSaveHandlerRunnerInterface $saveHandlerRunner
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session,
        AclManagerInterface $aclHandler,
        FavoriteProviderInterface $favorites,
        RecordDeletionServiceInterface $recordDeletionProvider,
        EntityRecordMapperRunner $entityRecordMapperRunner,
        RecordSaveHandlerRunnerInterface $saveHandlerRunner
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $moduleNameMapper,
            $session,
            $aclHandler,
            $favorites,
            $entityRecordMapperRunner,
            $saveHandlerRunner
        );
        $this->recordDeletionProvider = $recordDeletionProvider;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Dismiss alert
     * @param string $id
     * @return bool
     */
    public function dismissAlert(string $id): bool
    {
        $this->init();
        $this->startLegacyApp();

        /** @var \Alert $bean */
        $alert = BeanFactory::getBean('Alerts', $id, ['encode' => false]);

        $this->dismissReminder($alert);

        $result = $this->recordDeletionProvider->deleteRecord(
            'Alerts',
            $id
        );

        $this->close();

        return $result;
    }

    /**
     * Delete records
     *
     * @param array $ids
     * @return bool
     */
    public function dismissAllAlerts(array $ids = []): bool
    {
        $this->init();
        $this->startLegacyApp();

        $success = true;
        foreach ($ids as $id) {
            if (!$this->dismissAlert($id)) {
                $success = false;
            }
        }

        $this->close();

        return $success;
    }

    /**
     * Dismiss reminder related with alert
     * @param $alert
     * @return void
     */
    protected function dismissReminder($alert): void
    {
        $reminderId = $alert->reminder_id ?? '';
        if (empty($reminderId)) {
            return;
        }

        /** @var \Reminder $bean */
        $reminder = BeanFactory::getBean('Reminders', $reminderId, ['encode' => false]);

        if (empty($reminder)) {
            return;
        }
        $reminder->popup_viewed = 1;
        $reminder->save();
    }

}
