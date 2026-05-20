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

use App\Data\LegacyHandler\FilterMapper\LegacyFilterMapper;
use BeanFactory;
use InvalidArgumentException;
use ListViewDataPort;
use SearchForm;
use SugarBean;
use ViewList;

abstract class BaseListDataHandler
{
    /**
     * @var LegacyFilterMapper
     */
    protected $legacyFilterMapper;

    /**
     * @var RecordMapper
     */
    protected $recordMapper;

    /**
     * ListDataHandler constructor.
     * @param LegacyFilterMapper $legacyFilterMapper
     * @param RecordMapper $recordMapper
     */
    public function __construct(
        LegacyFilterMapper $legacyFilterMapper,
        RecordMapper $recordMapper
    )
    {
        $this->legacyFilterMapper = $legacyFilterMapper;
        $this->recordMapper = $recordMapper;
    }

    /**
     * @return ListViewDataPort
     */
    protected function getListDataPort(): ListViewDataPort
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ListView/ListViewDataPort.php';

        return new ListViewDataPort();
    }

    /**
     * @param string $module
     *
     * @return SugarBean
     * @throws InvalidArgumentException When the module is invalid.
     */
    protected function getBean(string $module): SugarBean
    {
        $bean = BeanFactory::newBean($module);

        if (!$bean instanceof SugarBean) {
            throw new InvalidArgumentException(sprintf('Module %s does not exist', $module));
        }

        return $bean;
    }

    /**
     * @param array $criteria
     * @param array $sort
     * @param string $type
     * @return array
     */
    protected function mapCriteria(array $criteria, array $sort, string $type): array
    {
        $mapped = $this->legacyFilterMapper->mapFilters($criteria, $type);

        $baseCriteria = [
            'searchFormTab' => "{$type}_search",
            'query' => 'true',
            'orderBy' => $this->legacyFilterMapper->getOrderBy($sort),
            'sortOrder' => $this->legacyFilterMapper->getSortOrder($sort)
        ];

        return array_merge($baseCriteria, $mapped);
    }

    /**
     * @param string $type
     * @param SugarBean $bean
     * @param array $criteria
     * @return array
     */
    protected function prepareQueryData(string $type, SugarBean $bean, array $criteria): array
    {
        $legacyListView = $this->getLegacyListView($bean);
        $listViewDefs = $this->getListViewDefs($legacyListView);
        $searchForm = $this->getSearchForm($type, $bean, $listViewDefs, $criteria);
        $params = $this->getSortingParams($criteria);

        $where = $this->buildFilterClause($bean, $searchForm);

        $queryFields = $this->buildFilterFields($bean);

        $filter_fields = $legacyListView->lv->setupFilterFields($queryFields);

        return [$params, $where, $filter_fields];
    }

    /**
     * @param SugarBean $bean
     * @return ViewList
     */
    protected function getLegacyListView(SugarBean $bean): ViewList
    {
        $legacyListView = new ViewList();
        $legacyListView->bean = $bean;
        $legacyListView->module = $bean->module_name;
        $legacyListView->preDisplay();

        return $legacyListView;
    }

    /**
     * Get list view defs
     * @param ViewList $legacyListView
     * @return array
     */
    protected function getListViewDefs(ViewList $legacyListView): array
    {
        $listViewDefs = [];
        $metadataFile = $legacyListView->getMetaDataFile();

        /* @noinspection PhpIncludeInspection */
        require $metadataFile;

        return $listViewDefs;
    }

    /**
     * @param string $type
     * @param SugarBean $bean
     * @param array $listViewDefs
     * @param array $criteria
     * @return SearchForm
     */
    protected function getSearchForm(
        string $type,
        SugarBean $bean,
        array $listViewDefs,
        array $criteria = []
    ): SearchForm
    {

        /* @noinspection PhpIncludeInspection */
        require_once 'include/SearchForm/SearchForm2.php';
        $searchMetaData = SearchForm::retrieveSearchDefs($bean->module_name);
        $searchForm = new SearchForm($bean, $bean->module_name, 'index');
        $searchForm->setup(
            $searchMetaData['searchdefs'],
            $searchMetaData['searchFields'],
            'SearchFormGeneric.tpl',
            $type . '_search',
            $listViewDefs
        );

        $searchForm->populateFromArray($criteria);

        return $searchForm;
    }

    /**
     * Get Legacy sorting parameters
     * @param array $criteria
     * @return array
     */
    protected function getSortingParams(array $criteria): array
    {
        $params = [];
        if (!empty($criteria['orderBy'])) {
            $params = [
                'orderBy' => strtoupper($criteria['orderBy']),
                'sortOrder' => $criteria['sortOrder'] ?? '',
                'skipOrderSave' => true,
                'overrideOrder' => true,
                'custom_order' => true
            ];
        }

        return $params;
    }

    /**
     * @param SugarBean $bean
     * @param SearchForm $searchForm
     * @return string
     */
    protected function buildFilterClause(SugarBean $bean, SearchForm $searchForm): string
    {
        $where_clauses = $searchForm->generateSearchWhere(true, $bean->module_dir);

        $where = '';
        if (count($where_clauses) > 0) {
            $where = '(' . implode(' ) AND ( ', $where_clauses) . ')';
        }

        return $where;
    }


    /**
     * @param SugarBean $bean
     * @return array
     */
    protected function buildFilterFields(SugarBean $bean): array
    {
        $parsedFilterFields = array();
        $excludedRelationshipFields = array();

        foreach ($bean->field_defs as $fieldName => $fieldDefinition) {
            $type = $fieldDefinition['type'] ?? '';
            $listShow = $fieldDefinition['list-show'] ?? true;
            if ($type === 'link' || $listShow === false) {
                continue;
            }

            $linkType = $fieldDefinition['link_type'] ?? '';
            if ($linkType === 'relationship_info') {
                $excludedRelationshipFields[] = $fieldName;
                $relationshipFields = $fieldDefinition['relationship_fields'] ?? [];
                if (!empty($relationshipFields)) {
                    foreach ($relationshipFields as $relationshipField) {
                        $excludedRelationshipFields[] = $relationshipField;
                    }
                }
            }

            if ($type === 'relate' && !$this->isValidRelate($fieldDefinition)) {
                continue;
            }

            $parsedFilterFields[] = $fieldName;
        }

        return array_diff($parsedFilterFields, $excludedRelationshipFields);
    }

    /**
     * @param $fieldDefinition
     * @return void
     */
    protected function isNonDbField($fieldDefinition): bool
    {
        $relateBean = BeanFactory::newBean($fieldDefinition['module']);

        if (empty($relateBean)) {
            return true;
        }

        $relateFieldDef = $relateBean->field_defs[$fieldDefinition['rname']] ?? [];
        $relateFieldSource = $relateFieldDef['source'] ?? '';

        return empty($relateFieldDef) || $relateFieldSource === 'non-db';
    }

    /**
     * @param $fieldDefinition
     * @return bool
     */
    protected function isValidRelate($fieldDefinition): bool
    {
        if (isset($fieldDefinition['table']) && $fieldDefinition['table'] === '') {
            return false;
        }

        if (isset($fieldDefinition['join_name']) && $fieldDefinition['join_name'] === '') {
            return false;
        }

        if (!empty($fieldDefinition['module']) && !empty($fieldDefinition['rname'])) {
            $isNonDb = $this->isNonDbField($fieldDefinition);

            if ($isNonDb === true && empty($fieldDefinition['id_name'])) {
                return false;
            }
        }

        return true;
    }
}
