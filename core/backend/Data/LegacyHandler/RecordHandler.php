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

use ApiBeanMapper;
use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperRunner;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerRunnerInterface;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Service\AclManagerInterface;
use App\Module\Service\FavoriteProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use InvalidArgumentException;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class RecordViewHandler
 * @package App\Legacy
 */
class RecordHandler extends LegacyHandler implements RecordProviderInterface
{
    public const HANDLER_KEY = 'record';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var AclManagerInterface
     */
    protected $acl;

    /**
     * @var FavoriteProviderInterface
     */
    protected $favorites;

    protected EntityRecordMapperRunner $entityRecordMapperRunner;
    protected RecordSaveHandlerRunnerInterface $saveHandlerRunner;

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
        EntityRecordMapperRunner $entityRecordMapperRunner,
        RecordSaveHandlerRunnerInterface $saveHandlerRunner
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->acl = $aclHandler;
        $this->favorites = $favorites;
        $this->entityRecordMapperRunner = $entityRecordMapperRunner;
        $this->saveHandlerRunner = $saveHandlerRunner;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string $module
     * @param string $id
     * @return Record
     */
    public function getRecord(string $module, string $id): Record
    {
        $this->init();
        $this->startLegacyApp();

        $bean = $this->retrieveBean($module, $id);

        $record = $this->buildRecord($id, $module, $bean, 'retrieve');
        $this->close();

        $this->entityRecordMapperRunner->toExternal($record, 'retrieve');

        return $record;
    }

    /**
     * @param string $module
     * @param string $id
     * @return SugarBean
     */
    protected function retrieveBean(string $module, string $id): SugarBean
    {
        $moduleName = $this->validateModuleName($module);

        if (empty($id)) {
            return $this->newBeanSafe($moduleName);
        }

        BeanFactory::unregisterBean($moduleName, $id);

        global $disable_date_format;
        $disableOriginal = $disable_date_format;
        $disable_date_format = true;

        /** @var SugarBean $bean */
        $bean = BeanFactory::getBean($moduleName, $id, ['encode' => false]);

        $disable_date_format = $disableOriginal;

        if (!$bean) {
            $bean = $this->newBeanSafe($moduleName);
        }

        return $bean;
    }

    /**
     * @param $moduleName
     * @return string
     */
    private function validateModuleName($moduleName): string
    {
        $legacyModuleName = $this->moduleNameMapper->toLegacy($moduleName);

        if (!$this->moduleNameMapper->isValidModule($legacyModuleName)) {
            throw new InvalidArgumentException('Invalid module name | legacy - ' . $legacyModuleName . '| input - ' . $moduleName);
        }

        return $legacyModuleName;
    }

    /**
     * @param string $module
     *
     * @return SugarBean
     * @throws InvalidArgumentException When the module is invalid.
     */
    private function newBeanSafe(string $module): SugarBean
    {
        $bean = BeanFactory::newBean($module);

        if (!$bean instanceof SugarBean) {
            throw new InvalidArgumentException(sprintf('Module %s does not exist', $module));
        }

        return $bean;
    }

    /**
     * @param string $id
     * @param string $module
     * @param SugarBean $bean
     * @param string|null $mode
     * @return Record
     */
    public function buildRecord(string $id, string $module, SugarBean $bean, ?string $mode = ''): Record
    {
        $activeScope = $this->state->getActiveScope();
        if (!$activeScope) {
            $this->init();
        }

        $record = new Record();
        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ApiBeanMapper/ApiBeanMapper.php';
        $mapper = new ApiBeanMapper();
        $mappedBean = $mapper->toApi($bean);


        if ($bean->ACLAccess('edit')) {
            $mappedBean['editable'] = true;
        } else {
            $mappedBean['editable'] = false;
        }

        $record->setId($id);
        $record->setModule($module);
        $record->setType($bean->object_name);
        $record->setAttributes($mappedBean);
        $record->setAcls($this->acl->getRecordAcls($bean));
        $record->setFavorite($this->favorites->isFavorite($module, $id));

        return $record;
    }

    /**
     * @param Record $record
     * @return Record
     */
    public function saveRecord(Record $record): Record
    {
        global $current_user;
        $this->init();
        $this->startLegacyApp();

        $id = $record->getAttributes()['id'] ?? '';
        /** @var SugarBean $bean */
        $bean = $this->retrieveBean($record->getModule(), $id);

        if (empty($id)) {
            $bean->assigned_user_id = $current_user->id;
        }

        if (!$bean->ACLAccess('save')) {
            throw new AccessDeniedHttpException();
        }

        $previousVersion = null;
        if (!empty($bean->id) && empty($bean->new_with_id)) {
            $previousVersion = $this->buildRecord($bean->id, $record->getModule(), $bean, 'retrieve');
            $this->close();
            $this->entityRecordMapperRunner->toExternal($previousVersion, 'retrieve');
            $this->init();
        }

        $this->close();
        $this->entityRecordMapperRunner->toInternal($record, 'save');
        $this->saveHandlerRunner->run($previousVersion, $record, null, 'before-save');
        $this->init();

        $this->setFields($bean, $record->getAttributes());
        $this->setUpdatedFields($bean, $record->getAttributes());

        $this->save($bean);

        $refreshedBean = $this->retrieveBean($record->getModule(), $bean->id);

        $savedRecord = $this->buildRecord($bean->id, $record->getModule(), $refreshedBean, 'save');
        $this->close();

        $this->entityRecordMapperRunner->toExternal($savedRecord, 'save');
        $this->saveHandlerRunner->run($previousVersion, $record, $savedRecord, 'after-save');

        return $savedRecord;
    }

    /**
     * Map record to bean
     * @param Record $record
     * @return SugarBean
     */
    public function mapToBean(Record $record): SugarBean
    {
        $this->init();
        $this->startLegacyApp();

        $legacyModuleName = $this->moduleNameMapper->toLegacy($record->getModule());

        $bean = BeanFactory::newBean($legacyModuleName);
        $this->setFields($bean, $record->getAttributes());
        $this->setUpdatedFields($bean, $record->getAttributes());

        $this->close();

        return $bean;
    }

    /**
     * Map bean to record
     * @param SugarBean $bean
     * @return Record
     */
    public function mapToRecord(SugarBean $bean): Record
    {
        $this->init();
        $this->startLegacyApp();

        $moduleName = $this->moduleNameMapper->toFrontEnd($bean->module_name);

        $record = $this->buildRecord($bean->id, $moduleName, $bean, 'map');
        $this->close();

        $this->entityRecordMapperRunner->toExternal($record, 'map');

        return $record;
    }

    /**
     * Do some processing before saving the bean to the database.
     * @param SugarBean $bean
     * @param array $values
     */
    public function setFields(SugarBean $bean, array $values): void
    {
        if ($this->isToNotifyOnSave($bean, $values)) {
            $bean->notify_on_save = true;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ApiBeanMapper/ApiBeanMapper.php';
        $mapper = new ApiBeanMapper();

        $mapper->toBean($bean, $values);
    }

    /**
     * @param SugarBean $bean
     * @param array $values
     * @return bool
     */
    protected function isToNotifyOnSave(SugarBean $bean, array $values): bool
    {
        global $current_user, $sugar_config;

        return !empty($values['assigned_user_id']) &&
            $values['assigned_user_id'] !== $bean->assigned_user_id &&
            $values['assigned_user_id'] !== $current_user->id &&
            empty($sugar_config['exclude_notifications'][$bean->module_dir]);
    }

    /**
     * @param SugarBean $bean
     */
    public function save(SugarBean $bean)
    {
        $bean->save($bean->notify_on_save ?? false);
    }

    /**
     * @param SugarBean $bean
     * @param array|null $attributes
     * @return void
     */
    public function setUpdatedFields(SugarBean $bean, ?array $attributes): void
    {
        if (!$attributes) {
            return;
        }

        foreach ($attributes as $key => $attribute) {
            $bean->updated_fields[] = $key;
        }
    }

    public function getTable(string $module): string
    {
        $this->init();

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);

        $bean = BeanFactory::newBean($legacyModuleName);
        $table = $bean->table_name ?? '';

        $this->close();

        return $table;
    }
}
