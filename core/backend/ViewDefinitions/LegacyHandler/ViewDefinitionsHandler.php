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

namespace App\ViewDefinitions\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Service\DefinitionEntryHandlingTrait;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\Service\FieldAliasMapper;
use App\ViewDefinitions\Service\MassUpdateDefinitionProviderInterface;
use App\ViewDefinitions\Service\SubPanelDefinitionProviderInterface;
use App\ViewDefinitions\Service\ViewDefinitionsProviderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SearchForm;
use Symfony\Component\HttpFoundation\RequestStack;
use function in_array;

/**
 * Class ViewDefinitionsHandler
 */
class ViewDefinitionsHandler extends LegacyHandler implements ViewDefinitionsProviderInterface
{
    use DefinitionEntryHandlingTrait;

    public const HANDLER_KEY = 'view-definitions';

    /**
     * @var array
     */
    protected $listViewColumnInterface = [
        'name' => '',
        'width' => '',
        'label' => '',
        'link' => false,
        'default' => false,
        'module' => '',
        'id' => '',
        'sortable' => true,
        'type' => ''
    ];

    /**
     * @var array
     */
    protected $defaultFields = [
        'type' => 'type',
        'label' => 'vname',
    ];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FieldDefinitionsProviderInterface
     */
    protected $fieldDefinitionProvider;

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var RecordViewDefinitionHandler
     */
    protected $recordViewDefinitionHandler;

    /**
     * @var RecordModalDefinitionHandler
     */
    protected $recordModalDefinitionHandler;

    /**
     * @var SubPanelDefinitionHandler
     */
    protected $subPanelDefinitionHandler;

    /**
     * @var ListViewDefinitionHandler
     */
    protected $listViewDefinitionsHandler;

    /**
     * @var ViewDefinitionMappers
     */
    protected $mappers;

    /**
     * @var MassUpdateDefinitionProviderInterface
     */
    protected $massUpdateDefinitionProvider;

    /**
     * @var FieldAliasMapper
     */
    protected $fieldAliasMapper;
    protected array $metadataEntries;
    protected ViewConfigMappers $viewConfigMappers;

    /**
     * ViewDefinitionsHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param FieldDefinitionsProviderInterface $fieldDefinitionProvider
     * @param RecordViewDefinitionHandler $recordViewDefinitionHandler
     * @param RecordModalDefinitionHandler $recordModalDefinitionHandler
     * @param SubPanelDefinitionProviderInterface $subPanelDefinitionHandler
     * @param ListViewDefinitionHandler $listViewDefinitionsHandler
     * @param MassUpdateDefinitionProviderInterface $massUpdateDefinitionProvider
     * @param FieldAliasMapper $fieldAliasMapper
     * @param LoggerInterface $logger
     * @param ViewDefinitionMappers $mappers
     * @param RequestStack $session
     * @param array $metadataEntries
     * @param ViewConfigMappers $viewDefsConfigMappers
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        FieldDefinitionsProviderInterface $fieldDefinitionProvider,
        RecordViewDefinitionHandler $recordViewDefinitionHandler,
        RecordModalDefinitionHandler $recordModalDefinitionHandler,
        SubPanelDefinitionProviderInterface $subPanelDefinitionHandler,
        ListViewDefinitionHandler $listViewDefinitionsHandler,
        MassUpdateDefinitionProviderInterface $massUpdateDefinitionProvider,
        FieldAliasMapper $fieldAliasMapper,
        LoggerInterface $logger,
        ViewDefinitionMappers $mappers,
        RequestStack $session,
        array $metadataEntries,
        ViewConfigMappers $viewDefsConfigMappers
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
        $this->fieldDefinitionProvider = $fieldDefinitionProvider;
        $this->recordViewDefinitionHandler = $recordViewDefinitionHandler;
        $this->recordModalDefinitionHandler = $recordModalDefinitionHandler;
        $this->subPanelDefinitionHandler = $subPanelDefinitionHandler;
        $this->listViewDefinitionsHandler = $listViewDefinitionsHandler;
        $this->massUpdateDefinitionProvider = $massUpdateDefinitionProvider;
        $this->logger = $logger;
        $this->mappers = $mappers;
        $this->fieldAliasMapper = $fieldAliasMapper;
        $this->metadataEntries = $metadataEntries;
        $this->viewConfigMappers = $viewDefsConfigMappers;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getViewDefs(string $moduleName, array $views = []): ViewDefinition
    {
        $this->init();

        if (empty($views)) {
            $views = [
                'detailView',
                'recordView',
                'listView',
                'search',
                'massUpdate',
                'subPanel',
                'recordModal'
            ];
        }

        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($moduleName);

        $legacyModuleName = $this->validateModuleName($moduleName);

        $this->startLegacyApp($legacyModuleName);

        $viewDef = new ViewDefinition();
        $viewDef->setId($moduleName);

        $metadataEntries = $this->getMetadataEntries($moduleName);
        $defaultEntries = [
            'listView',
            'search',
            'recordView',
            'recordModal',
            'subPanel',
            'massUpdate',
        ];
        $nonDefaultEntries = array_diff_key($metadataEntries, array_flip($defaultEntries));

        if (in_array('listView', $views, true)) {
            $listViewDef = $this->listViewDefinitionsHandler->fetch($moduleName, $legacyModuleName, $fieldDefinition);
            $viewDef->setListView($listViewDef);
        }

        if (in_array('search', $views, true)) {
            $viewDef->setSearch($this->fetchSearchDefs($legacyModuleName, $fieldDefinition));
        }

        if (in_array('recordView', $views, true)) {
            $recordViewDefs = $this->recordViewDefinitionHandler->fetch(
                $moduleName,
                $legacyModuleName,
                $fieldDefinition
            );
            $viewDef->setRecordView($recordViewDefs);
        }


        if (in_array('recordModal', $views, true)) {
            $recordModalDefs = $this->recordModalDefinitionHandler->fetch(
                $moduleName,
                $legacyModuleName,
                $fieldDefinition
            );
            $viewDef->setRecordModal($recordModalDefs);
        }

        if (in_array('subPanel', $views, true)) {
            $subPanelViewDefs = $this->subPanelDefinitionHandler->getSubPanelDef($legacyModuleName);
            $viewDef->setSubPanel($subPanelViewDefs);
        }

        if (in_array('massUpdate', $views, true)) {
            $massUpdateDefinitions = $this->massUpdateDefinitionProvider->getDefinitions($moduleName);
            $viewDef->setMassUpdate($massUpdateDefinitions);
        }


        foreach ($nonDefaultEntries as $entryKey => $entry) {
            $entryType = $entry['type'] ?? '';
            if (!$entryType) {
                continue;
            }

            $legacyType = $entry['legacyType'] ?? '';
            if ($entryType === 'record' && $legacyType !== '') {
                $entryDefinition = $this->recordViewDefinitionHandler->fetch(
                    $moduleName,
                    $legacyModuleName,
                    $fieldDefinition,
                    $legacyType
                );
                $viewDef->setExtraEntry($entryKey, $entryDefinition);
            }
        }

        $mappers = $this->mappers->get($moduleName) ?? [];

        foreach ($mappers as $mapper) {
            $mapper->map($viewDef, $fieldDefinition);
        }

        $this->close();

        return $viewDef;
    }

    /**
     * @inheritDoc
     */
    public function getListViewDef(string $moduleName): ViewDefinition
    {
        $this->init();

        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($moduleName);
        $legacyModuleName = $this->validateModuleName($moduleName);

        $viewDef = new ViewDefinition();
        $viewDef->setId($moduleName);
        $definitions = $this->listViewDefinitionsHandler->fetch($moduleName, $legacyModuleName, $fieldDefinition);
        $viewDef->setListView($definitions);

        $this->close();

        return $viewDef;
    }

    /**
     * @inheritDoc
     */
    public function getSearchDefs(string $moduleName): ViewDefinition
    {
        $this->init();

        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($moduleName);

        $legacyModuleName = $this->validateModuleName($moduleName);

        $viewDef = new ViewDefinition();
        $viewDef->setId($moduleName);
        $viewDef->setSearch($this->fetchSearchDefs($legacyModuleName, $fieldDefinition));

        $this->close();

        return $viewDef;
    }

    /**
     * @inheritDoc
     */
    public function getRecordViewDefs(string $moduleName): ViewDefinition
    {
        $this->init();

        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($moduleName);

        $legacyModuleName = $this->validateModuleName($moduleName);

        $viewDef = new ViewDefinition();
        $viewDef->setId($moduleName);
        $recordViewDefs = $this->recordViewDefinitionHandler->fetch($moduleName, $legacyModuleName, $fieldDefinition);
        $viewDef->setRecordView($recordViewDefs);

        $this->close();

        return $viewDef;
    }

    /**
     * @inheritDoc
     */
    public function getRecordModalDefs(string $moduleName): ViewDefinition
    {
        $this->init();

        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($moduleName);

        $legacyModuleName = $this->validateModuleName($moduleName);

        $viewDef = new ViewDefinition();
        $viewDef->setId($moduleName);
        $recordModalDefs = $this->recordModalDefinitionHandler->fetch($moduleName, $legacyModuleName, $fieldDefinition);
        $viewDef->setRecordModal($recordModalDefs);

        $this->close();

        return $viewDef;
    }

    /**
     * Internal API
     */

    /**
     * Get search defs array
     * @param string $module
     * @param FieldDefinition $fieldDefinition
     * @return array
     */
    protected function fetchSearchDefs(string $module, FieldDefinition $fieldDefinition): array
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/SearchForm/SearchForm2.php';

        $searchDefs = SearchForm::retrieveSearchDefs($module);

        $definition = [];

        if (empty($searchDefs) || !isset($searchDefs['searchdefs'][$module])) {
            return $definition;
        }

        $definition = $searchDefs['searchdefs'][$module];

        $definition = $this->viewConfigMappers->run($module, 'search', $definition) ?? [];


        if (isset($definition['templateMeta'])) {
            unset($definition['templateMeta']);
        }

        $this->mergeSearchInfo($module, $definition, $fieldDefinition, $searchDefs, 'basic_search');
        $this->mergeSearchInfo($module, $definition, $fieldDefinition, $searchDefs, 'advanced_search');

        $this->mergeFieldDefinition($definition, $fieldDefinition, 'basic_search');
        $this->mergeFieldDefinition($definition, $fieldDefinition, 'advanced_search');

        $this->renameSearchLayout($definition, 'basic_search', 'basic');
        $this->renameSearchLayout($definition, 'advanced_search', 'advanced');

        return $definition;
    }

    /**
     * Merge searchFields defs info into vardefs
     * @param array $definition
     * @param FieldDefinition $fieldDefinition
     * @param string $type
     */
    protected function mergeFieldDefinition(array &$definition, FieldDefinition $fieldDefinition, string $type): void
    {
        $vardefs = $fieldDefinition->getVardef();
        if (!isset($definition['layout'][$type])) {
            return;
        }
        foreach ($definition['layout'][$type] as $key => $field) {
            $fieldName = $this->getFieldName($key, $field);

            if (empty($vardefs[$fieldName])) {
                continue;
            }

            $merged = $this->addFieldDefinition($vardefs, $fieldName, $field);
            $merged = $this->injectEmptyOption($merged);
            $aliasKey = $merged['name'] ?? $key;
            $definition['layout'][$type][$aliasKey] = $merged;

            if (empty($definition['layout'][$type][$aliasKey]['vardefBased'])) {
                $definition['layout'][$type][$aliasKey]['vardefBased'] = false;
                $definition['layout'][$type][$aliasKey]['readonly'] = false;
            }
        }
    }

    /**
     * Add field definition to current field metadata
     * @param array|null $vardefs
     * @param $key
     * @param $field
     * @return array
     */
    protected function addFieldDefinition(array $vardefs, $key, $field): array
    {
        $baseField = $this->getField($field);

        $field = array_merge($this->listViewColumnInterface, $baseField);

        if (!isset($vardefs[$key])) {
            return $field;
        }

        $alias = $this->fieldAliasMapper->map($vardefs[$key]);
        $aliasDefs = $vardefs[$alias] ?? $vardefs[$key];

        $field['fieldDefinition'] = $aliasDefs;
        $field['name'] = $aliasDefs['name'] ?? $field['name'];

        $field = $this->applyDefaults($field);

        return $field;
    }

    /**
     * Merge searchFields defs info into searchDefs
     * @param string $module
     * @param array $definition
     * @param array $searchDefs
     * @param string $type
     */
    protected function mergeSearchInfo(string $module, array &$definition, FieldDefinition $fieldDefinition, array $searchDefs, string $type): void
    {
        if (!isset($definition['layout'][$type])) {
            return;
        }

        foreach ($definition['layout'][$type] as $key => $field) {
            $name = $field['name'] ?? '';

            if ($this->useRangeSearch($module, $searchDefs, $name)) {
                $definition['layout'][$type][$key]['enable_range_search'] = true;
            }
        }

        $layoutKeys = array_keys($definition['layout'][$type]);
        $layoutValues = array_values($definition['layout'][$type]);

        $vardefs = $fieldDefinition->getVardef();
        foreach ($vardefs as $fieldVardefKey => $fieldVardefDefinition) {
            if (
                in_array($fieldVardefKey, $layoutKeys)
                || in_array($fieldVardefKey, $layoutValues)
            ) {
                continue;
            }

            $definition['layout'][$type][$fieldVardefKey] = [
                'name' => $fieldVardefKey,
                'vardefBased' => true,
                'display' => 'none',
                'readonly' => true,
            ];
        }
    }

    /**
     * If to use range search
     *
     * @param string $module
     * @param array $searchDefs
     * @param $fieldName
     * @return bool
     */
    protected function useRangeSearch(string $module, array $searchDefs, $fieldName): bool
    {
        if (isset($searchDefs['searchFields'][$module]["range_$fieldName"])) {
            return true;
        }

        return false;
    }

    /**
     * @param $moduleName
     * @return string
     */
    private function validateModuleName($moduleName): string
    {
        $moduleName = $this->moduleNameMapper->toLegacy($moduleName);

        if (!$this->moduleNameMapper->isValidModule($moduleName)) {
            throw new InvalidArgumentException('Invalid module name: ' . $moduleName);
        }

        return $moduleName;
    }

    /**
     * Rename layout entry
     * @param array $definition
     * @param string $type
     * @param string $newName
     */
    protected function renameSearchLayout(array &$definition, string $type, string $newName): void
    {
        if (isset($definition['layout'][$type])) {
            $definition['layout'][$newName] = $definition['layout'][$type];
            unset($definition['layout'][$type]);
        }
    }

    /**
     * Extract field name
     * @param $key
     * @param $field
     * @return string
     */
    protected function getFieldName($key, $field): string
    {
        if (is_numeric($key) && is_string($field)) {
            return $field;
        }

        if (is_numeric($key)) {
            return $field['name'];
        }

        return $key;
    }

    /**
     * Get base field structure
     * @param $field
     * @return array
     */
    protected function getField($field): array
    {
        $baseField = $field;

        if (is_string($field)) {
            $baseField = [
                'name' => $field,
            ];
        }

        return $baseField;
    }

    /**
     * Apply defaults
     * @param array $field
     * @return array
     */
    protected function applyDefaults(array $field): array
    {
        foreach ($this->defaultFields as $attribute => $default) {
            if (empty($field[$attribute])) {
                $defaultValue = $field['fieldDefinition'][$default] ?? '';
                $field[$attribute] = $defaultValue;
            }
        }

        return $field;
    }

    /**
     * Injects an empty option into the field options if it's not already present.
     *
     * @param array $merged The merged array containing field definition and metadata
     * @return array The updated merged array with the empty option injected
     */
    protected function injectEmptyOption(array $merged): array
    {
        if (empty($merged['fieldDefinition']['options'])) {
            return $merged;
        }

        $metadata = $merged['fieldDefinition']['metadata'] ?? [];
        $extraOptions = $metadata['extraOptions'] ?? [];
        $extraOptions[] = [
            'value' => '__SuiteCRMEmptyString__',
            'labelKey' => 'LBL_EMPTY',
        ];

        $metadata['extraOptions'] = $extraOptions;
        $merged['fieldDefinition']['metadata'] = $metadata;

        return $merged;
    }

    protected function getMetadataEntries(string $module): array
    {
        $config = $this->metadataEntries ?? [];
        $config['modules'][$module] = $config['modules'][$module] ?? [];
        $config['modules'][$module]['entries'] = $config['modules'][$module]['entries'] ?? [];
        $config['modules'][$module]['exclude'] = $config['modules'][$module]['exclude'] ?? [];

        $config['modules'][$module]['entries'] = array_merge(
            $moduleDefaults['entries'] ?? [],
            $config['modules'][$module]['entries']
        );
        $config['modules'][$module]['exclude'] = array_merge(
            $moduleDefaults['exclude'] ?? [],
            $config['modules'][$module]['exclude']
        );

        return $this->filterDefinitionEntries($module, 'entries', $config, null);
    }
}
