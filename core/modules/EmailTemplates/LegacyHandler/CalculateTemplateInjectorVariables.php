<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Module\EmailTemplates\LegacyHandler;

use App\Authentication\LegacyHandler\UserHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Languages\LegacyHandler\AppListStringsHandler;
use App\Module\Service\ModuleRegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CalculateTemplateInjectorVariables extends LegacyHandler
{

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected $variableInjectorBadFields,
        protected $variableInjectorExcludedModules,
        protected $variableInjectorMappedModuleLabels,
        protected $variableInjectorShowOnlyModules,
        protected AppListStringsHandler $appListStringsHandler,
        protected UserHandler $userHandler,
        protected ModuleRegistryInterface $moduleRegistry
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return 'calculate-template-injector-variables';
    }

    public function getModules(string $baseModule, array $modulesToShow = []): array
    {
        if (!empty($modulesToShow)) {
            $mappedModules = [];

            foreach ($modulesToShow as $key => $name) {
                $label = $this->getMappedLabel($baseModule, $name);
                $mappedModules[$name] = $label ?? $name;
            }

            return $mappedModules;
        }

        $modulesToShow = $this->getModulesToShow($baseModule);

        return $this->filterModules($modulesToShow, $baseModule, false);
    }

    public function getDefaultModules(): array
    {
        $moduleList = $this->getModuleList();
        return $this->filterModules($moduleList);
    }

    public function getFieldDefs(string $baseModule, $moduleList = []): array
    {

        $beanList = $this->getBeanList();
        $beanFiles = $this->getBeanFiles();
        $badFields = $this->getBadFields($baseModule);

        $modules = [];
        $prefixes = [];

        if (empty($moduleList)) {
            $moduleList = $this->getModuleList();
        }

        foreach ($moduleList as $key => $name) {
            if (!isset($beanList[$key]) || !isset($beanFiles[$beanList[$key]])) {
                continue;
            }

            $this->init();
            require_once($beanFiles[$beanList[$key]]);

            $focus = new $beanList[$key];
            $this->close();
            $modules[$name][$key] = $focus;
            $prefixes[$key] = strtolower($focus->object_name) . '_';
            if ($focus->object_name == 'Case') {
                $prefixes[$key] = 'a' . strtolower($focus->object_name) . '_';
            }
        }

        if (array_key_exists('Contacts', $moduleList)) {
            $prefixes['Users'] = 'contact_user_';
        }

        $collection = [];
        foreach ($modules as $moduleName => $moduleBeans) {

            $collection[$moduleName] = [];
            foreach ($moduleBeans as $beanKey => $bean) {
                foreach ($bean->field_defs as $fieldName => $fieldDefinition) {
                    if ($fieldDefinition['type'] === 'assigned_user_name' || $fieldDefinition['type'] === 'link') {
                        continue;
                    }

                    if ($fieldDefinition['type'] === 'bool' || in_array($fieldDefinition['name'], $badFields)) {
                        continue;
                    }

                    $optionKey = strtolower("{$prefixes[$beanKey]}{$fieldName}");

                    if (isset($fieldDefinition['vname'])) {
                        $optionLabel = preg_replace('/:$/', "", $fieldDefinition['vname']);
                    } else {
                        $optionLabel = preg_replace('/:$/', "", $fieldDefinition['name']);
                    }

                    $dup = 1;
                    foreach ($collection[$moduleName] as $value) {
                        if ($value['name'] === $optionKey) {
                            $dup = 0;
                            break;
                        }
                    }
                    if (!$dup) {
                        continue;
                    }

                    $collection[$moduleName][] = ['name' => $optionKey, 'value' => $optionLabel];
                }
            }
        }

        return $collection;
    }

    protected function getModuleList(): array
    {
        $appListStrings = $this->getAppListStrings();

        return $appListStrings['moduleList'];
    }

    protected function getBadFields(string $module): array
    {
        $badFields = $this-> variableInjectorBadFields ?? [];

        if (empty($badFields)) {
            return [];
        }

        $badFieldsDefault = $badFields['default'] ?? [];
        $moduleBadFields = $badFields[$module] ?? [];

        if (empty($moduleBadFields)) {
            return $badFieldsDefault;
        }

        return array_merge($badFieldsDefault, $moduleBadFields);
    }

    protected function getBeanList(): array
    {
        $this->init();
        global $beanList;
        $this->close();

        return $beanList;
    }

    protected function getBeanFiles(): array
    {
        $this->init();
        global $beanFiles;
        $this->close();

        return $beanFiles;
    }

    /**
     * @param array $moduleList
     * @return array
     */
    public function filterModules(array $moduleList, $baseModule = '', $checkExcludedModules = true): array
    {
        $beanFiles = $this->getBeanFiles();
        $beanList = $this->getBeanList();
        $excludedModules = $this->getExcludedModules($baseModule);

        foreach ($moduleList as $key => $name) {
            if (!isset($beanList[$key]) || !isset($beanFiles[$beanList[$key]])) {
                unset($moduleList[$key]);
                continue;
            }

            if ($checkExcludedModules && in_array($name, $excludedModules)) {
                unset($moduleList[$key]);
            }

            if (!in_array($key, $this->moduleRegistry->getUserAccessibleModules())) {
                unset($moduleList[$key]);
            }
        }

        asort($moduleList);

        return $moduleList;
    }

    /**
     * @return array|null
     */
    public function getAppListStrings(): ?array
    {
        return $this->appListStringsHandler->getAppListStrings($this->userHandler->getCurrentLanguage())->getItems();
    }

    protected function getExcludedModules(string $module = ''): array
    {
        $excludedModules = $this-> variableInjectorExcludedModules ?? [];

        if (empty($excludedModules)) {
            return [];
        }

        $defaultExcludedModules = $excludedModules['default'] ?? [];

        if (empty($module)) {
            return $defaultExcludedModules;
        }

        if (!isset($excludedModules[$module])) {
            return $defaultExcludedModules;
        }

        return array_merge($defaultExcludedModules, $excludedModules[$module]);

    }

    protected function getMappedModuleLabels($module = ''): array
    {
        $mappedModuleLabels = $this-> variableInjectorMappedModuleLabels ?? [];

        if (empty($mappedModuleLabels)) {
            return [];
        }

        if (empty($module)) {
            return $mappedModuleLabels['default'] ?? [];
        }

        if (!isset($mappedModuleLabels[$module])) {
            return $mappedModuleLabels['default'] ?? [];
        }

        return array_merge($mappedModuleLabels['default'] ?? [], $mappedModuleLabels[$module]);
    }

    protected function getModulesToShow(string $baseModule): array
    {
        $showOnlyModules = $this->variableInjectorShowOnlyModules ?? [];
        $baseModules = $this->getDefaultModules();
        if (empty($showOnlyModules)) {
            return $baseModules;
        }

        if (!isset($showOnlyModules[$baseModule])) {
            return $baseModules;
        }

        $mappedModules = [];

        foreach ($showOnlyModules[$baseModule] as $module) {
            $label = $this->getMappedLabel($baseModule, $module);
            $mappedModules[$module] = $label ?? $module;
        }

        return $mappedModules;
    }

    protected function getMappedLabel(string $baseModule, mixed $module)
    {
        $appStrings = $this->getAppListStrings();
        $mappedModuleLabels = $this->getMappedModuleLabels($baseModule);

        if (!isset($mappedModuleLabels[$module])) {
            return $appStrings['moduleListSingular'][$module] ?? $appStrings['moduleList'][$module] ?? $module;
        }

        $labelConfig = $mappedModuleLabels[$module];

        if (isset($labelConfig['moduleLabel'])){
            return $appStrings['moduleListSingular'][$labelConfig['moduleLabel']] ?? $module;
        }

        $labelKeys = $labelConfig['moduleLabels'] ?? [];
        $separator = $labelConfig['separator'] ?? '/';

        $label = '';
        foreach ($labelKeys as $key) {
            $part = $appStrings['moduleListSingular'][$key] ?? $key;
            if (!empty($label)) {
                $label .= $separator;
            }
            $label .= $part;
        }


        return $label;
    }

    protected function userHasAccessToModule(string $key): bool
    {
        $user = $this->userHandler->getCurrentUser();
    }
}
