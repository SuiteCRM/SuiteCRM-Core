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
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Service\SubpanelLineActionDefinitionProviderInterface;
use App\Process\Service\SubpanelTopActionDefinitionProviderInterface;
use App\ViewDefinitions\Service\FieldAliasMapper;
use App\ViewDefinitions\Service\SubPanelDefinitionProviderInterface;
use aSubPanel;
use Exception;
use SearchForm;
use SubPanelDefinitions;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SubPanelDefinitionHandler
 */
class SubPanelDefinitionHandler extends LegacyHandler implements SubPanelDefinitionProviderInterface
{
    use FieldDefinitionsInjectorTrait;

    public const HANDLER_KEY = 'subpanel-definitions';

    protected $defaultDefinition = [
        'name' => '',
        'label' => '',
        'sortable' => true,
    ];
    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;
    /**
     * @var FieldDefinitionsProviderInterface
     */
    protected $fieldDefinitionProvider;

    /**
     * @var SubpanelTopActionDefinitionProviderInterface
     */
    protected $subpanelTopActionDefinitionProvider;

    /**
     * @var SubpanelLineActionDefinitionProviderInterface
     */
    protected $subpanelLineActionDefinitionProvider;
    /**
     * @var FieldAliasMapper
     */
    protected $fieldAliasMapper;
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
     * @param SubpanelTopActionDefinitionProviderInterface $subpanelTopActionDefinitionProvider
     * @param SubpanelLineActionDefinitionProviderInterface $subpanelLineActionDefinitionProvider
     * @param FieldAliasMapper $fieldAliasMapper
     * @param RequestStack $session
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
        SubpanelTopActionDefinitionProviderInterface $subpanelTopActionDefinitionProvider,
        SubpanelLineActionDefinitionProviderInterface $subpanelLineActionDefinitionProvider,
        FieldAliasMapper $fieldAliasMapper,
        RequestStack $session,
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
        $this->subpanelTopActionDefinitionProvider = $subpanelTopActionDefinitionProvider;
        $this->subpanelLineActionDefinitionProvider = $subpanelLineActionDefinitionProvider;
        $this->fieldAliasMapper = $fieldAliasMapper;
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
    public function getSubPanelDef(string $moduleName): array
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/SubPanel/SubPanel.php';

        return $this->getModuleSubpanels($moduleName);
    }

    /**
     * Get module subpanels
     * @param string $module
     * @return array
     * @description This function gets all the available legacy subpanels/tabs for a module
     * and re-format it to match the front-end requirements
     */
    protected function getModuleSubpanels(string $module): array
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/SubPanel/SubPanelDefinitions.php';

        global $beanList, $beanFiles;
        if (empty($beanList[$module]) || empty($beanFiles[$beanList[$module]])) {
            return [];
        }

        $class = $beanList[$module];
        require_once $beanFiles[$class];
        $mod = new $class();
        $spd = new SubPanelDefinitions($mod);

        // all subpanels defined for a module
        $allTabs = $spd->layout_defs['subpanel_setup'] ?? [];

        // subpanels available after filtering the hidden subpanels
        // via [Admin => Modules and Subpanels] and [User > ACL Roles] settings
        try {
            $availableTabs = $spd->get_available_tabs() ?? [];
        } catch (Exception $e) {
            return [];
        }

        //filtered tabs
        $tabs = array_filter(
            $allTabs,
            static function ($key) use ($availableTabs) {
                return in_array($key, $availableTabs, true);
            },
            ARRAY_FILTER_USE_KEY
        );

        $resultingTabs = [];

        $tabs = $this->viewConfigMappers->run('module', 'subpanel', $tabs);

        foreach ($tabs as $key => $tab) {

            if (($tab['hidden'] ?? false) === true) {
                continue;
            }

            try {
                /** @var aSubPanel $subpanel */
                $subpanel = $spd->load_subpanel($key);
            } catch (Exception $e) {
                continue;
            }


            if ($subpanel === false) {
                continue;
            }

            $columnSubpanel = $subpanel;
            $extraModuleVardefs = [];
            if (!empty($tab['collection_list'])) {
                $columnSubpanel = $subpanel->get_header_panel_def();
                $headerModule = $this->moduleNameMapper->toFrontEnd($columnSubpanel->get_module_name());
                $extraModuleVardefs = $this->getCollectionListVardefs($tab['collection_list']);

            } else {
                $headerModule = $this->getHeaderModule($tab);
            }

            $vardefs = $this->getSubpanelModuleVardefs($headerModule);

            $tabs[$key]['icon'] = $tab['module'];
            $tabs[$key]['name'] = $key;
            $tabs[$key]['module'] = $this->moduleNameMapper->toFrontEnd($tab['module']);
            $tabs[$key]['legacyModule'] = $tab['module'];
            $tabs[$key]['headerModule'] = $headerModule;
            $tabs[$key]['top_buttons'] = $this->mapButtons($subpanel, $tab);
            $tabs[$key]['subpanelWidget'] = $this->mapInsightWidget($subpanel, $tabs, $key, $tab);
            $tabs[$key]['lineActions'] = $this->getSubpanelLineActions($subpanel, $tabs[$key]['module']);
            $tabs[$key]['searchdefs'] = $this->getSearchdefs($subpanel);
            $tabs[$key]['order'] = $tab['order'];

            if (empty($columnSubpanel)) {
                continue;
            }

            $resultingTabs[$key] = $tabs[$key];

            $mapColumns = $this->mapColumns($columnSubpanel, $vardefs, $extraModuleVardefs);

            if (!empty($tab['collection_list'])) {
                $iconColumn = $this->buildIconColumn($tab['module']);
                array_unshift($mapColumns, $iconColumn);
            }


            $resultingTabs[$key]['columns'] = $mapColumns;

        }

        return $resultingTabs;
    }

    /**
     * @param array $tab
     * @return mixed|string
     */
    protected function getHeaderModule(array $tab)
    {
        $vardefModule = $tab['module'];

        if (empty($tab['header_definition_from_subpanel']) || empty($tab['collection_list'])) {
            return $this->moduleNameMapper->toFrontEnd($vardefModule);
        }

        $headerModule = $tab['header_definition_from_subpanel'];
        $vardefModule = $tab['collection_list'][$headerModule]['module'] ?? '';

        if ($vardefModule) {
            return $this->moduleNameMapper->toFrontEnd($vardefModule);
        }

        $vardefModule = reset($tab['collection_list'])['module'] ?? '';
        if ($vardefModule) {
            return $this->moduleNameMapper->toFrontEnd($vardefModule);
        }

        $vardefModule = $tab['module'];

        return $this->moduleNameMapper->toFrontEnd($vardefModule);
    }

    /**
     * @param string $vardefModule
     * @return array
     */
    protected function getSubpanelModuleVardefs(string $vardefModule): array
    {
        return $this->fieldDefinitionProvider->getVardef($vardefModule)->getVardef();
    }

    /**
     * @param $subpanel
     * @param $tab
     * @return array
     */
    protected function mapButtons(aSubPanel $subpanel, $tab): array
    {

        $module = $tab['module'] ?? '';

        $searchDefs = SearchForm::retrieveSearchDefs($module);

        $topButtons = [];

        $topButtonDefinitions = $this->getButtonDefinitions($subpanel);

        foreach ($topButtonDefinitions as $key => $value) {
            if (stripos($value['widget_class'], 'topfilter')) {
                if (!$this->getSearchdefs($subpanel) && !$searchDefs['searchdefs']) {
                    unset($topButtonDefinitions[$key]);
                    break;
                }
                $topButtonDefinitions[] = [
                    'widget_class' => 'SubPanelFilterClearButton',
                ];
            }
        }

        foreach ($topButtonDefinitions as $top_button) {
            $topButton = $this->subpanelTopActionDefinitionProvider->getTopAction(
                $this->moduleNameMapper->toFrontEnd($tab['module']),
                $top_button
            );

            if (!empty($topButton)) {
                $topButtons[] = $topButton;
            }
        }

        return $topButtons;
    }

    protected function getSearchdefs(aSubPanel $subpanel) {
        $searchDefs = $subpanel->_instance_properties['searchdefs'] ?? '';

        if (!empty($searchDefs)) {
            foreach ($searchDefs as &$field) {
                $fieldDefinition = [
                    'name' => $field['name'],
                    'type' => $field['type'] ?? 'varchar',
                    'vname' => $field['label'] ?? '',
                    'options' => $field['options'] ?? [],
                    'default' => $field['default'] ?? null,
                    'width' => $field['width'] ?? '',
                    'enable_range_search' => $field['enable_range_search'] ?? '',
                ];

                if (!empty($field['name'] ?? false) && str_contains($field['name'], '_only')) {
                    $fieldDefinition['displayType'] = 'checkbox';
                }

                $field['fieldDefinition'] = $fieldDefinition;
            }

            return $searchDefs;
        }
        return null;
    }

    /**
     * @param aSubPanel $subpanel
     * @return array
     */
    protected function getButtonDefinitions(aSubPanel $subpanel): array
    {
        return $subpanel->get_buttons() ?? [];
    }

    /**
     * @param $subpanel
     * @param array $vardefs
     * @return array
     */
    protected function mapColumns(aSubPanel $subpanel, array $vardefs, array $extraModuleVardefs): array
    {
        $panelDefinition = $subpanel->panel_definition ?? [];
        $listFields = $panelDefinition['list_fields'] ?? [];
        $definitions = [];

        if (empty($listFields)) {
            return [];
        }

        foreach ($listFields as $key => $column) {
            $usage = $column['usage'] ?? '';

            if ($usage === 'query_only') {
                continue;
            }

            $name = $column['name'] ?? $key;

            if (!isset($vardefs[$key]) && !isset($vardefs[$name])) {
                continue;
            }

            $definition = $this->buildColumn($column, $key, $vardefs);

            if (!empty($extraModuleVardefs)) {
                $definition = $this->addMultiModuleVardefs($extraModuleVardefs, $key, $definition);
            }

            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * Build column
     * @param $column
     * @param $key
     * @param array|null $vardefs
     * @return array
     */
    protected function buildColumn($column, $key, ?array $vardefs): array
    {
        if (!empty($column)) {
            $column['label'] = $column['vname'] ?? '';
            $column['name'] = $column['name'] ?? $key;
        }

        $widgetClass = $column['widget_class'] ?? '';
        if ($widgetClass === 'SubPanelDetailViewLink') {
            $column['link'] = true;
        }

        return $this->addFieldDefinition(
            $vardefs,
            strtolower($key),
            $column,
            $this->defaultDefinition,
            $this->fieldAliasMapper
        );
    }

    /**
     * @param $module
     * @return array
     */
    protected function buildIconColumn($module): array {
        return $iconColumn = [
            "name" => "module_name",
            "label" => "",
            "sortable" => false,
            "vname" => "",
            "fieldDefinition" => [
                "name" => "module_name",
                "vname" => "",
                "type" => "icon",
                "default" => $module,
                "required" => false
            ],
            "type" => "icon"
        ];
    }

    /**
     * @param $subpanel
     * @param array $tabs
     * @param $key
     * @param $tab
     * @return array
     */
    protected function mapInsightWidget($subpanel, array $tabs, $key, $tab): array
    {
        if (!empty($subpanel->panel_definition['subpanelWidget'])) {
            $widgetConfig = [
                'type' => 'statistics',
                'options' => [
                    'subpanelWidget' => $subpanel->panel_definition['subpanelWidget']
                ]
            ];

            $this->replaceVariables($tabs, $key, $widgetConfig, $widgetRows);

            return $widgetConfig;
        }

        if (empty($tabs[$key]['subpanelWidget'])) {
            return $this->getDefaultWidgetConfig($tabs, $key, $tab);
        }

        return [];
    }

    /**
     * @param array $tabs
     * @param $key
     * @param $tab
     * @return array
     */
    protected function getDefaultWidgetConfig(array $tabs, $key, $tab): array
    {
        return [
            'type' => 'statistics',
            'options' => [
                'subpanelWidget' => [
                    'rows' => [
                        [
                            'justify' => 'end',
                            'cols' => [
                                [
                                    'icon' => $tab['module'],
                                ],
                                [
                                    'labelKey' => $tabs[$key]['title_key'],
                                    'class' => 'sub-panel-banner-button-title',
                                    'bold' => true,
                                ],
                                [
                                    'statistic' => 'default',
                                    'class' => 'sub-panel-banner-value',
                                    'bold' => true,
                                ],
                            ]
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $titleKey
     * @param $col
     * @param array $widgetRows
     * @param $rowKey
     * @param $colKey
     */
    protected function replaceLabelKey(string $titleKey, $col, array &$widgetRows, $rowKey, $colKey): void
    {
        $labelKey = $col['labelKey'] ?? '';
        if ($labelKey !== '') {
            $labelKey = str_replace('{{title_key}}', $titleKey, $labelKey);
            $widgetRows[$rowKey]['cols'][$colKey]['labelKey'] = $labelKey;
        }
    }

    /**
     * @param array $tabs
     * @param $key
     * @param array $widgetConfig
     * @param $widgetRows
     */
    protected function replaceVariables(array $tabs, $key, array &$widgetConfig, &$widgetRows): void
    {
        $widgetRows = $widgetConfig['options']['subpanelWidget']['rows'] ?? [];

        foreach ($widgetRows as $rowKey => $row) {
            $cols = $row['cols'] ?? [];
            foreach ($cols as $colKey => $col) {
                $this->replaceLabelKey($tabs[$key]['title_key'], $col, $widgetRows, $rowKey, $colKey);
            }
        }

        if (isset($widgetConfig['options']['subpanelWidget']['rows'])) {
            $widgetConfig['options']['subpanelWidget']['rows'] = $widgetRows;
        }
    }

    /**
     * @param aSubPanel $subpanelDef
     * @param string $subpanelModule
     * @description  this function fetches all the line actions defined for a subpanel
     * for now, only the remove action is filtered from all available line actions
     * @return array
     */
    public function getSubpanelLineActions(aSubPanel $subpanelDef, string $subpanelModule): array
    {
        $lineActions = [];
        $subpanelLineActions = ['edit_button' => 'edit', 'close_button' => 'close', 'remove_button' => 'unlink'];

        $thepanel = $subpanelDef->isCollection() ? $subpanelDef->get_header_panel_def() : $subpanelDef;

        foreach ($thepanel->get_list_fields() as $field_name => $list_field) {
            $usage = empty($list_field['usage']) ? '' : $list_field['usage'];

            if ($usage !== 'query_only') {
                $list_field['name'] = $field_name;

                if (isset($list_field['alias'])) {
                    $list_field['name'] = $list_field['alias'];
                } else {
                    $list_field['name'] = $field_name;
                }

                if (array_key_exists($list_field['name'], $subpanelLineActions)
                    &&
                    false !== stripos($list_field['name'], 'button')
                ) {
                    $lineAction = $subpanelLineActions[$list_field['name']];
                    $moduleName = $this->moduleNameMapper->toFrontEnd($subpanelModule);
                    $lineActions[] = $this->subpanelLineActionDefinitionProvider->getLineAction(
                        $moduleName,
                        $lineAction
                    );
                }
            }
        }

        return $lineActions;
    }

    /**
     * @param $collection_list
     * @return array
     */
    protected function getCollectionListVardefs($collection_list): array
    {
        $extraModuleVardefs = [];
        if (!is_array($collection_list)) {
            return $extraModuleVardefs;
        }

        foreach ($collection_list as $item) {
            $itemLegacyModuleName = $item['module'] ?? '';
            if (empty($itemLegacyModuleName)) {
                continue;
            }

            $itemModule = $this->moduleNameMapper->toFrontEnd($itemLegacyModuleName);
            if (empty($itemModule)) {
                continue;
            }

            $itemVardefs = $this->getSubpanelModuleVardefs($itemModule);
            if (empty($itemVardefs)) {
                continue;
            }

            $extraModuleVardefs[$itemModule] = $itemVardefs;
        }

        return $extraModuleVardefs;
    }

    /**
     * @param array $extraModuleVardefs
     * @param int|string $key
     * @param array $definition
     * @return array
     */
    protected function addMultiModuleVardefs(array $extraModuleVardefs, $key, array $definition): array
    {


        $multiModuleFieldVardefs = [];
        foreach ($extraModuleVardefs as $module => $extraModuleVardef) {
            $alias = $definition['alias'] ?? '';

            if (empty($extraModuleVardef[$alias])) {
                $alias = $key;
            }

            $fieldDefs = $this->getAliasDefinitions($this->fieldAliasMapper, $extraModuleVardef, $alias);

            if (!empty($fieldDefs)) {

                $fieldDefs['name'] = $definition['name'];
                $fieldDefs['alias'] = $alias;

                $multiModuleFieldVardefs[$module] = $fieldDefs;
            }
        }

        $definition['multiModuleDefinitions'] = $multiModuleFieldVardefs;
        return $definition;
    }
}
