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


namespace App\Process\Service;

use App\Authentication\LegacyHandler\UserHandler;
use App\Engine\Service\AclManagerInterface;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Languages\LegacyHandler\AppListStringsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;

class LineActionDefinitionProvider implements LineActionDefinitionProviderInterface
{
    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;
    /**
     * @var AppListStringsProviderInterface
     */
    protected $appListStringProvider;
    /**
     * @var array
     */
    protected $appListStrings = [];
    /**
     * @var array
     */
    private $listViewLineActions;
    /**
     * @var AclManagerInterface
     */
    private $aclManager;
    /**
     * @var FieldDefinitionsProviderInterface
     */
    private $fieldDefinitionProvider;
    /**
     * @var UserHandler
     */
    private $userHandler;

    /**
     * LineActionDefinitionProvider constructor.
     * @param array $listViewLineActions
     * @param AclManagerInterface $aclManager
     * @param FieldDefinitionsProviderInterface $fieldDefinitionProvider
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param AppListStringsProviderInterface $appListStringProvider
     * @param UserHandler $userHandler
     */
    public function __construct(
        array $listViewLineActions,
        AclManagerInterface $aclManager,
        FieldDefinitionsProviderInterface $fieldDefinitionProvider,
        ModuleNameMapperInterface $moduleNameMapper,
        AppListStringsProviderInterface $appListStringProvider,
        UserHandler $userHandler
    ) {
        $this->listViewLineActions = $listViewLineActions;
        $this->aclManager = $aclManager;
        $this->fieldDefinitionProvider = $fieldDefinitionProvider;
        $this->moduleNameMapper = $moduleNameMapper;
        $this->appListStringProvider = $appListStringProvider;
        $this->userHandler = $userHandler;
    }

    /**
     * @inheritDoc
     */
    public function getLineActions(string $module): array
    {
        $language = $this->userHandler->getCurrentLanguage();

        $appListStringsObject = $this->appListStringProvider->getAppListStrings($language);
        if ($appListStringsObject !== null) {
            $this->appListStrings = $appListStringsObject->getItems();
        }

        $defaults = $this->listViewLineActions['default'] ?? [];
        $defaultActions = $defaults['actions'] ?? [];

        $allProcessedActions = [];
        foreach ($defaultActions as $actionKey => $actionDef) {
            switch ($actionKey) {
                case 'create':
                    if (isset($this->listViewLineActions[$module]) && in_array($actionKey, $this->listViewLineActions[$module]['exclude'])) {
                        break;
                    }
                    $allProcessedActions[] = $this->filterCreateActions($module, $actionDef);
                    break;
                case 'modal-create':
                    if (isset($this->listViewLineActions[$module]) && in_array($actionKey, $this->listViewLineActions[$module]['exclude'])) {
                        break;
                    }
                    $allProcessedActions[] = $this->filterModalCreateActions($module, $actionDef);
                    break;
            }
        }

        return array_reduce($allProcessedActions, 'array_merge', []);
    }

    /**
     * @param string $module
     * @param array $actionDefinition
     * @return array
     */
    protected function filterCreateActions(string $module, array $actionDefinition): array
    {
        $relatedModules = $actionDefinition['related_modules'] ?? [];
        $actionTemplate = $actionDefinition;
        unset($actionTemplate['related_modules']);
        $createActions = [];
        foreach ($relatedModules as $relatedModuleDef) {
            $relatedModuleName = $relatedModuleDef['module'];
            $relatedModuleDef['mapping'] = $this->parentFieldMapping($module, $relatedModuleName);
            $relatedModuleDef['legacyModuleName'] = $this->moduleNameMapper->toLegacy($module);
            $relatedModuleDef['action'] = $relatedModuleDef['action'] ?? 'edit';
            if (!count($relatedModuleDef['mapping'])) {
                continue;
            }
            if ($this->checkAccess($relatedModuleName, $actionDefinition['acl']) === false) {
                continue;
            }

            $action = array_merge($actionTemplate, $relatedModuleDef);
            $action['modes'] = $action['modes'] ?? ['list'];
            $action['params'] = $action['params'] ?? [];
            $action['params']['create'] = $action['params']['create'] ?? [];

            $action['params']['create']['module'] = $action['module'] ?? '';
            $action['params']['create']['mapping'] = $action['mapping'] ?? [];
            $action['params']['create']['legacyModuleName'] =  $action['legacyModuleName'] ?? '';
            $action['params']['create']['action'] = $action['action'] ?? 'edit';
            $createActions[] = $action;
        }

        return $createActions;
    }

    /**
     * Filter modal create actions
     * @param string $module
     * @param array $actionDefinition
     * @return array
     */
    protected function filterModalCreateActions(string $module, array $actionDefinition): array
    {
        $actions = [];

        $relatedModules = $actionDefinition['related_modules'] ?? [];
        $actionTemplate = $actionDefinition;
        unset($actionTemplate['related_modules']);

        foreach ($relatedModules as $relatedModuleDef) {
            $relatedModuleName = $relatedModuleDef['module'];

            if ($relatedModuleName !== 'emails') {
                continue;
            }

            if ($this->checkAccess($relatedModuleName, $actionDefinition['acl'] ?? []) === false) {
                continue;
            }

            $relatedModuleDef['mapping'] = [];
            $relatedModuleDef['legacyModuleName'] = $this->moduleNameMapper->toLegacy($module);
            $relatedModuleDef['action'] = $relatedModuleDef['action'] ?? $actionDefinition['key'];

            // Create email modal action object
            $action = array_merge($actionTemplate, $relatedModuleDef);
            $action['modes'] = $action['modes'] ?? ['list'];

            $actions[] = $action;
        }

        return $actions;
    }

    /**
     * @param string $module
     * @param string $relatedModule
     * @return array
     */
    protected function parentFieldMapping(string $module, string $relatedModule): array
    {
        $vardefs = $this->fieldDefinitionProvider->getVardef($relatedModule)->getVardef();
        $vardefModuleName = $this->moduleNameMapper->toLegacy($module);
        foreach ($vardefs as $vardef) {
            $type = $vardef['type'] ?? '';
            $typeName = $vardef['type_name'] ?? '';
            if ($type !== 'parent_type' && $typeName !== 'parent_type') {
                continue;
            }
            $options = $vardef['options'] ?? '';
            $allowedParentModules = $this->appListStrings[$options] ?? [];
            if (!array_key_exists($vardefModuleName, $allowedParentModules)) {
                continue;
            }

            return [
                'moduleName' => 'parent_type',
                'name' => 'parent_name',
                'id' => 'parent_id',
            ];
        }

        return [];
    }

    /**
     * Check access
     *
     * @param string $module
     * @param array $aclList
     * @return bool
     */
    public function checkAccess(string $module, array $aclList): bool
    {
        if (empty($aclList)) {
            return true;
        }

        foreach ($aclList as $acl) {
            if ($this->aclManager->checkAccess($module, $acl, true) === false) {
                return false;
            }
        }

        return true;
    }
}
