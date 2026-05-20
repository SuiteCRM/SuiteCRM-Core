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

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\LegacyToFrontendDefinitionMapper;
use App\ViewDefinitions\Entity\ViewDefinition;

class LegacyToFrontendViewDefinitionMapper implements ViewDefinitionMapperInterface
{

    /**
     * @var array
     */
    private $legacyToFrontEndFieldsMap;

    /**
     * LegacyToFrontendViewDefinitionMapper constructor.
     * @param array $legacyToFrontEndFieldsMap
     */
    public function __construct(array $legacyToFrontEndFieldsMap)
    {
        $this->legacyToFrontEndFieldsMap = $legacyToFrontEndFieldsMap;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'legacy-to-frontend-view-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @return array: returns array containing legacy and front-end field maps
     */
    public function getLegacyToFrontendFieldsMap(): array
    {
        return $this->legacyToFrontEndFieldsMap ?? [];
    }

    /**
     * @inheritDoc
     */
    public function map(ViewDefinition $viewDefinition, FieldDefinition $fieldDefinition): void
    {
        $legacyToFrontEndFieldsMap = $this->getLegacyToFrontendFieldsMap();
        if (empty($legacyToFrontEndFieldsMap)) {
            return;
        }

        foreach ($legacyToFrontEndFieldsMap as $mapKey => $mapDefinition) {
            $legacyFieldsMap = $mapDefinition['from'] ?? [];
            $frontendFieldsMap = $mapDefinition['to'] ?? [];

            if (empty($mapDefinition) || empty($legacyFieldsMap) || empty($frontendFieldsMap)) {
                continue;
            }

            $this->mapRecordView($viewDefinition, $legacyFieldsMap, $frontendFieldsMap);
            $this->mapListView($viewDefinition, $legacyFieldsMap, $frontendFieldsMap);
            $this->mapSearchView($viewDefinition, $legacyFieldsMap, $frontendFieldsMap);
            $this->mapSubpanelView($viewDefinition, $legacyFieldsMap, $frontendFieldsMap);
        }
    }

    /**
     * @param ViewDefinition $viewDefinition
     * @param array $legacyMap
     * @param array $frontendMap
     * @return void
     * @desc maps legacy to front-end field format in Record View
     */
    public function mapRecordView(ViewDefinition $viewDefinition, array $legacyMap, array $frontendMap): void
    {
        $recordView = $viewDefinition->getRecordView() ?? [];
        $panels = $recordView['panels'] ?? [];

        if (empty($recordView) || empty($panels)) {
            return;
        }
        $service = new LegacyToFrontendDefinitionMapper();

        foreach ($panels as $panelKey => $panel) {
            foreach ($panel['rows'] as $rowKey => $row) {
                $vardef = $service->transformVardef($row['cols'], $legacyMap, $frontendMap);
                $panels[$panelKey]['rows'][$rowKey]['cols'] = $vardef;
            }
        }
        $recordView['panels'] = $panels;
        $viewDefinition->setRecordView($recordView);
    }

    /**
     * @param ViewDefinition $viewDefinition
     * @param array $legacyMap
     * @param array $frontendMap
     * @return void
     * @desc maps legacy to front-end field format in List View
     */
    public function mapListView(ViewDefinition $viewDefinition, array $legacyMap, array $frontendMap): void
    {
        $listView = $viewDefinition->getListView() ?? [];
        $columns = $listView['columns'] ?? [];

        if (empty($listView) || empty($columns)) {
            return;
        }

        $service = new LegacyToFrontendDefinitionMapper();
        $transformedVardefs = $service->getTransformedVardefs($columns, $legacyMap, $frontendMap);

        $listView['columns'] = $transformedVardefs;
        $viewDefinition->setListView($listView);
    }

    /**
     * @param ViewDefinition $viewDefinition
     * @param array $legacyMap
     * @param array $frontendMap
     * @return void
     * @desc maps legacy to front-end field format in Search View
     */
    public function mapSearchView(ViewDefinition $viewDefinition, array $legacyMap, array $frontendMap): void
    {
        $searchView = $viewDefinition->getSearch() ?? [];
        $vardefs = $searchView['layout']['advanced'] ?? [];

        if (empty($searchView) || empty($vardefs)) {
            return;
        }

        $parsed = [];

        foreach ($vardefs as $key => $vardef) {
            if (is_array($vardef)) {
                $parsed[$key] = $vardef;
                continue;
            }

            if (is_string($vardef)) {
                $parsed[$vardef] = ['name' => $vardef];
                continue;
            }

            $parsed[$key] = $vardef;
        }

        $service = new LegacyToFrontendDefinitionMapper();
        $transformedVardefs = $service->getTransformedVardefs($parsed, $legacyMap, $frontendMap);

        $searchView['layout']['advanced'] = $transformedVardefs;
        $viewDefinition->setSearch($searchView);
    }

    /**
     * @param ViewDefinition $viewDefinition
     * @param array $legacyMap
     * @param array $frontendMap
     * @return void
     * @desc maps legacy to front-end field format in Subpanel View
     */
    public function mapSubpanelView(ViewDefinition $viewDefinition, array $legacyMap, array $frontendMap): void
    {
        $subpanelView = $viewDefinition->getSubPanel() ?? [];

        if (empty($subpanelView)) {
            return;
        }

        $service = new LegacyToFrontendDefinitionMapper();

        foreach ($subpanelView as $subpanelModule => $subPanelColumns) {
            $vardefs = $subPanelColumns['columns'] ?? [];

            if (empty($vardefs)) {
                continue;
            }

            $transformedVardefs = $service->getTransformedVardefs($vardefs, $legacyMap, $frontendMap);

            $subpanelView[$subpanelModule]['columns'] = $transformedVardefs;
        }

        $viewDefinition->setSubPanel($subpanelView);
    }
}
