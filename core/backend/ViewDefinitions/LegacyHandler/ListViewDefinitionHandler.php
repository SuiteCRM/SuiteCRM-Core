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
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Filters\Service\FilterDefinitionProviderInterface;
use App\Process\Service\BulkActionDefinitionProviderInterface;
use App\Process\Service\LineActionDefinitionProviderInterface;
use App\Process\Service\TableActionDefinitionProviderInterface;
use App\ViewDefinitions\Service\FieldAliasMapper;
use App\ViewDefinitions\Service\WidgetDefinitionProviderInterface;
use Exception;
use ListViewFacade;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ListViewDefinitionHandler extends LegacyHandler
{
    use FieldDefinitionsInjectorTrait;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var BulkActionDefinitionProviderInterface
     */
    protected $bulkActionDefinitionProvider;

    /**
     * @var WidgetDefinitionProviderInterface
     */
    protected $widgetDefinitionProvider;

    /**
     * @var LineActionDefinitionProviderInterface
     */
    protected $lineActionDefinitionProvider;

    /**
     * @var TableActionDefinitionProviderInterface
     */
    protected $tableActionDefinitionProvider;

    /**
     * @var FilterDefinitionProviderInterface
     */
    protected $filterDefinitionProvider;

    /**
     * @var array
     */
    protected $listViewSidebarWidgets;

    /**
     * @var FieldAliasMapper
     */
    protected $fieldAliasMapper;
    protected ViewConfigMappers $viewConfigMappers;

    /**
     * RecordViewDefinitionHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param LoggerInterface $logger
     * @param BulkActionDefinitionProviderInterface $bulkActionDefinitionProvider
     * @param WidgetDefinitionProviderInterface $widgetDefinitionProvider
     * @param LineActionDefinitionProviderInterface $lineActionDefinitionProvider
     * @param TableActionDefinitionProviderInterface $tableActionDefinitionProvider
     * @param FilterDefinitionProviderInterface $filterDefinitionProvider
     * @param FieldAliasMapper $fieldAliasMapper
     * @param RequestStack $session
     * @param array $listViewSidebarWidgets
     * @param ViewConfigMappers $viewDefsConfigMappers
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        LoggerInterface $logger,
        BulkActionDefinitionProviderInterface $bulkActionDefinitionProvider,
        WidgetDefinitionProviderInterface $widgetDefinitionProvider,
        LineActionDefinitionProviderInterface $lineActionDefinitionProvider,
        TableActionDefinitionProviderInterface $tableActionDefinitionProvider,
        FilterDefinitionProviderInterface $filterDefinitionProvider,
        FieldAliasMapper $fieldAliasMapper,
        RequestStack $session,
        array $listViewSidebarWidgets,
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
        $this->logger = $logger;
        $this->bulkActionDefinitionProvider = $bulkActionDefinitionProvider;
        $this->widgetDefinitionProvider = $widgetDefinitionProvider;
        $this->lineActionDefinitionProvider = $lineActionDefinitionProvider;
        $this->tableActionDefinitionProvider = $tableActionDefinitionProvider;
        $this->filterDefinitionProvider = $filterDefinitionProvider;
        $this->listViewSidebarWidgets = $listViewSidebarWidgets;
        $this->fieldAliasMapper = $fieldAliasMapper;
        $this->viewConfigMappers = $viewDefsConfigMappers;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'listview-view-definitions';
    }

    /**
     * Get record view defs array. Using Session swapping.
     * @param string $module
     * @param string $legacyModuleName
     * @param FieldDefinition $fieldDefinition
     * @return array
     * @throws Exception
     */
    public function get(
        string $module,
        string $legacyModuleName,
        FieldDefinition $fieldDefinition
    ): array {
        $this->init();

        $metadata = $this->fetch($module, $legacyModuleName, $fieldDefinition);

        $this->close();

        return $metadata;
    }

    /**
     * Get list view defs array
     * @param string $module
     * @param string $legacyModuleName
     * @param FieldDefinition $fieldDefinition
     * @return array
     * @throws Exception
     */
    public function fetch(
        string $module,
        string $legacyModuleName,
        FieldDefinition $fieldDefinition
    ): array {
        $metadata = [
            'columns' => [],
            'bulkActions' => [],
            'lineActions' => [],
            'tableActions' => [],
            'availableFilters' => [],
            'sidebarWidgets' => [],
            'paginationType' => '',
            'maxHeight' => '',
        ];

        /* @noinspection PhpIncludeInspection */
        include_once 'include/ListView/ListViewFacade.php';

        $vardefs = $fieldDefinition->getVardef();

        try {
            $displayColumns = ListViewFacade::getAllColumns($legacyModuleName);
        } catch (Exception $e) {
            $displayColumns = [];
        }

        try {
            $listMeta = ListViewFacade::getMetadata($legacyModuleName);
        } catch (Exception $e) {
            $listMeta = [];
        }

        $listMeta['columns'] = $displayColumns;

        $listMeta = $this->viewConfigMappers->run($module, 'list', $listMeta) ?? [];

        $data = [];
        foreach ($listMeta['columns'] as $key => $column) {
            if (!isset($vardefs[strtolower($key)])) {
                $this->logger->debug("ListViewDefinitions: '$key' not set on vardefs. Ignoring.");
                continue;
            }

            $data[] = $this->buildListViewColumn($column, $key, $vardefs);
        }


        $metadata['columns'] = $data;
        $metadata['bulkActions'] = $this->bulkActionDefinitionProvider->getBulkActions(
            $module,
            $listMeta['bulkActions'] ?? []
        ) ?? [];

        $metadata['lineActions'] = $this->lineActionDefinitionProvider->getLineActions($module) ?? [];
        $metadata['tableActions'] = $this->tableActionDefinitionProvider->getActions($module, $listMeta['tableActions'] ?? []);
        $metadata['sidebarWidgets'] = $this->widgetDefinitionProvider->getSidebarWidgets(
            $this->listViewSidebarWidgets,
            $module,
            ['widgets' => $listMeta['sidebarWidgets'] ?? []]
        ) ?? [];

        $metadata['availableFilters'] = $this->filterDefinitionProvider->getFilters($module) ?? [];

        $metadata['paginationType'] = $listMeta['paginationType'] ?? null;

        $metadata['maxHeight'] = $listMeta['maxHeight'] ?? null;

        return $metadata;
    }

    /**
     * Build list view column
     * @param $column
     * @param $key
     * @param array|null $vardefs
     * @return array
     */
    protected function buildListViewColumn($column, $key, ?array $vardefs): array
    {
        $column = array_merge($this->listViewColumnInterface, $column);
        $column['name'] = strtolower($key);

        $column = $this->addFieldDefinition($vardefs, strtolower($key), $column);

        if ($column['name'] === 'email1') {
            $column['type'] = 'email';
            $column['link'] = false;
        }

        return $column;
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
}
