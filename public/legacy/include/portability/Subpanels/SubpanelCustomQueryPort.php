<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

class SubpanelCustomQueryPort
{

    /**
     * @param SugarBean $bean
     * @param string $subpanel
     * @return array
     */
    public function getQueries(SugarBean $bean, string $subpanel = ''): array
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/SubPanel/SubPanelDefinitions.php';

        $spd = new SubPanelDefinitions($bean);
        $subpanel_def = $spd->load_subpanel($subpanel);

        if (!method_exists($subpanel_def, 'isCollection')) {
            $GLOBALS['log']->fatal('Subpanel definition should be an aSubPanel');

            return [];
        }

        if ($this->isNonGenerateSelectFunction($subpanel_def)) {
            return $this->buildFunctionQueries($bean, $subpanel_def);
        }

        $subpanel_list = [];
        if ($subpanel_def->isCollection()) {
            if ($subpanel_def->load_sub_subpanels() !== false) {
                $subpanel_list = $subpanel_def->sub_subpanels;
            }
        } else {
            $subpanel_list[] = $subpanel_def;
        }

        return SugarBean::getUnionRelatedListQueries($subpanel_list, $subpanel_def, $bean, '');
    }

    /**
     * Check if the subpanel uses a datasource function without generate_select.
     * These functions return raw SQL strings and cannot be routed through
     * getUnionRelatedListQueries (which skips them).
     *
     * @param aSubPanel $subpanel_def
     * @return bool
     */
    protected function isNonGenerateSelectFunction($subpanel_def): bool
    {
        if (!method_exists($subpanel_def, 'isDatasourceFunction')) {
            return false;
        }

        if (!$subpanel_def->isDatasourceFunction()) {
            return false;
        }

        return empty($subpanel_def->_instance_properties['generate_select']);
    }

    /**
     * Call the datasource function and return decomposed query parts.
     * Mirrors the invocation logic in SugarBean::get_union_related_list_query_params
     * for non-generate_select function subpanels.
     *
     * @param SugarBean $bean
     * @param aSubPanel $subpanel_def
     * @return array
     */
    protected function buildFunctionQueries(SugarBean $bean, $subpanel_def): array
    {
        $func_query = $this->callDatasourceFunction($bean, $subpanel_def);

        if (empty($func_query)) {
            return [];
        }

        if (is_array($func_query)) {
            return [$this->buildPartsFromArray($func_query, $subpanel_def, $bean)];
        }

        return [$this->decomposeQueryString($func_query)];
    }

    /**
     * Invoke the subpanel's datasource function, following the same logic as
     * SugarBean::get_union_related_list_query_params.
     *
     * @param SugarBean $bean
     * @param aSubPanel $subpanel_def
     * @return array|string|null
     */
    protected function callDatasourceFunction(SugarBean $bean, $subpanel_def)
    {
        $shortcut_function_name = $subpanel_def->get_data_source_name();
        $parameters = $subpanel_def->get_function_parameters();

        if (!empty($parameters)) {
            if (is_array($parameters) && isset($parameters['import_function_file'])) {
                if (!function_exists($shortcut_function_name)) {
                    require_once($parameters['import_function_file']);
                }

                return $shortcut_function_name($parameters);
            }

            return $bean->$shortcut_function_name($parameters);
        }

        return $bean->$shortcut_function_name();
    }

    /**
     * Build decomposed query parts from an array-returning datasource function.
     * Mirrors build_sub_queries_for_union: extracts where/join from the function result,
     * then delegates to create_new_list_query on the submodule bean.
     *
     * @param array $queryArray
     * @param aSubPanel $subpanel_def
     * @param SugarBean $parentBean
     * @return array
     */
    protected function buildPartsFromArray(array $queryArray, $subpanel_def, SugarBean $parentBean): array
    {
        $subpanelModule = $subpanel_def->get_module_name();
        $submodule = BeanFactory::newBean($subpanelModule);

        if (empty($submodule)) {
            return [];
        }

        $tableWhere = preg_replace('/^\s*WHERE/i', '', (string)$subpanel_def->get_where());
        $whereDefinition = preg_replace('/^\s*WHERE/i', '', (string)($queryArray['where'] ?? ''));

        if (!empty($tableWhere)) {
            if (empty($whereDefinition)) {
                $whereDefinition = $tableWhere;
            } else {
                $whereDefinition .= ' AND ' . $tableWhere;
            }
        }

        $listFields = $subpanel_def->get_list_fields();
        foreach ($listFields as $key => $field) {
            if (isset($field['usage']) && $field['usage'] === 'display_only') {
                unset($listFields[$key]);
            }
        }

        $params = [];
        $params['distinct'] = $subpanel_def->distinct_query();
        $params['joined_tables'] = $queryArray['join_tables'] ?? null;
        $params['include_custom_fields'] = true;

        $subquery = $submodule->create_new_list_query(
            '',
            $whereDefinition,
            $listFields,
            $params,
            0,
            '',
            true,
            $parentBean
        );

        $subquery['from'] .= $queryArray['join'] ?? '';

        return $subquery;
    }

    /**
     * Decompose a raw SQL string into select/from/where/order_by parts.
     *
     * Uses strrpos for WHERE and ORDER BY to correctly handle subqueries
     * in FROM/JOIN clauses that may contain their own WHERE keywords
     * (e.g. track_log_entries with INNER JOIN (SELECT ... WHERE ...) subquery).
     *
     * @param string $query
     * @return array
     */
    protected function decomposeQueryString(string $query): array
    {
        $parts = [
            'select' => '',
            'from' => '',
            'where' => '',
            'order_by' => '',
        ];

        $fromPos = stripos($query, ' FROM ');
        if ($fromPos !== false) {
            $parts['select'] = trim(substr($query, 0, $fromPos));
            $remainder = substr($query, $fromPos);
        } else {
            $parts['select'] = $query;

            return $parts;
        }

        $orderByPos = strripos($remainder, ' ORDER BY ');
        if ($orderByPos !== false) {
            $parts['order_by'] = trim(substr($remainder, $orderByPos));
            $remainder = substr($remainder, 0, $orderByPos);
        }

        $wherePos = strripos($remainder, ' WHERE ');
        if ($wherePos !== false) {
            $parts['from'] = trim(substr($remainder, 0, $wherePos));
            $parts['where'] = trim(substr($remainder, $wherePos));
        } else {
            $parts['from'] = trim($remainder);
        }

        return $parts;
    }

    /**
     * @param string $query
     * @return array
     */
    public function fetchRow(string $query): array
    {
        $db = DBManagerFactory::getInstance('listviews');
        $result = $db->query($query, true, "SubpanelCustomQueryPort: Error executing custom query");
        $rows = $db->fetchByAssoc($result);
        if (empty($rows)) {
            return [];
        }

        return $rows;
    }

    /**
     * @param string $query
     * @return array
     */
    public function fetchAll(string $query): array
    {
        $db = DBManagerFactory::getInstance('listviews');
        $result = $db->query($query, true, "SubpanelCustomQueryPort: Error executing custom query");

        $rows = [];

        while (($row = $db->fetchByAssoc($result))) {
            $rows[] = $row;
        }

        return $rows;
    }

}
