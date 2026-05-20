<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordDeleteHandlers\RecordDeleteHandlerRunnerInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Data\Service\RecordListProviderInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ListViewHandler
 * @package App\Legacy
 */
class RecordDeletionHandler extends LegacyHandler implements RecordDeletionServiceInterface
{
    public const HANDLER_KEY = 'delete-records';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var RecordListProviderInterface
     */
    private $listViewProvider;

    /**
     * RecordDeletionHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RecordListProviderInterface $listViewProvider
     * @param RequestStack $session
     * @param RecordDeleteHandlerRunnerInterface $deleteHandlerRunner
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RecordListProviderInterface $listViewProvider,
        RequestStack $session,
        protected RecordDeleteHandlerRunnerInterface $deleteHandlerRunner
    )
    {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->listViewProvider = $listViewProvider;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Delete record
     *
     * @param Record $record
     * @return bool
     */
    public function delete(Record $record): bool
    {
        $moduleName = $this->moduleNameMapper->toLegacy($record->getModule() ?? '');
        return $this->deleteRecord($moduleName, $record->getId() ?? '');
    }

    /**
     * Delete record
     *
     * @param string $moduleName
     * @param string $id
     * @return bool
     */
    public function deleteRecord(string $moduleName, string $id): bool
    {
        $success = true;
        if (!$this->internalDelete($moduleName, $id)) {
            $success = false;
        }

        return $success;
    }

    /**
     * @param string $legacyModuleName
     * @param string $id
     * @return bool
     */
    protected function internalDelete(string $legacyModuleName, string $id): bool
    {
        $moduleName = $this->moduleNameMapper->toFrontEnd($legacyModuleName);

        $this->init();
        $this->startLegacyApp();

        // NOTE: Do not use BeanFactory::getBean($moduleName, $id) with mark_deleted
        // may cause errors when there are related records.
        $bean = BeanFactory::newBean($legacyModuleName);
        $bean->retrieve($id);
        if ($bean && $bean->id && $bean->ACLAccess('Delete')) {

            $this->close();
            $this->deleteHandlerRunner->run($moduleName, $id, 'before-delete');
            $this->init();

            $bean->mark_deleted($id);

            $this->close();

            $this->deleteHandlerRunner->run($moduleName, $id, 'after-delete');

            return true;
        }

        $this->close();
        return false;
    }

    /**
     * Delete records
     *
     * @param string $moduleName
     * @param array $ids
     * @return bool
     */
    public function deleteRecords(string $moduleName, array $ids = []): bool
    {
        $success = true;
        foreach ($ids as $id) {
            if (!$this->internalDelete($moduleName, $id)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param string $moduleName
     * @param array $criteria
     * @param array $sort
     * @return bool
     */
    public function deleteRecordsFromCriteria(
        string $moduleName,
        array $criteria,
        array $sort
    ): bool {

        $listView = $this->listViewProvider->getList(
            $this->moduleNameMapper->toFrontEnd($moduleName),
            $criteria,
            -1,
            // Hardcoded limit - passing 0 as limit throws a fatal
            100000,
            $sort
        );

        $success = true;
        foreach ($listView->getRecords() as $record) {
            if (!$this->internalDelete($moduleName, $record['id'])) {
                $success = false;
            }
        }

        return $success;
    }
}
