<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Module\Reports\LegacyHandler;

use AOR_Report;

/**
 * The data of a report (no html): its columns, rows, totals and pages, and the groups of a
 * grouped report. The rows come from the legacy report query (AOR_Report::build_report_query():
 * conditions, parameters, grouping, sorting, functions and access rules), with the pages and
 * page totals of the legacy table (AOR_Report::build_report_html()).
 *
 * Each column comes with the definition of its field, and each row with its values in the
 * SuiteCRM 8 record format, by record of the row (the report module, or a related module of
 * the report), so the page shows them with the SuiteCRM 8 field components: dates and amounts
 * in the user's format, list labels, links to the records. Shared by the report page and the
 * report dashlet; needs $this->moduleNameMapper.
 */
trait ReportDataTrait
{
    // Definition properties the page needs to show a value.
    protected static array $reportDefinitionKeys = ['type', 'options', 'module', 'precision', 'dbType', 'len'];

    // Field types shown with their own field component (some under another type); the others as text.
    protected static array $reportFieldTypes = [
        'bool' => 'bool', 'currency' => 'currency', 'date' => 'date', 'datetime' => 'datetime',
        'datetimecombo' => 'datetime', 'decimal' => 'float', 'double' => 'float', 'dynamicenum' => 'dynamicenum',
        'email' => 'email', 'enum' => 'enum', 'float' => 'float', 'int' => 'int', 'multienum' => 'multienum',
        'phone' => 'phone', 'radioenum' => 'radioenum', 'relate' => 'relate', 'text' => 'text', 'url' => 'url',
    ];

    /**
     * The report: a table, or a table per value of its grouping field (first page of each).
     *
     * @return array{groups: array, table: ?array}
     */
    protected function readReportTables(AOR_Report $report): array
    {
        $groupField = $this->getReportGroupField($report);
        if ($groupField === null) {
            return ['groups' => [], 'table' => $this->readReportTable($report, 0, '')];
        }

        $groups = [];
        foreach ($this->getReportGroups($report, $groupField) as $group) {
            $groups[] = ['label' => $group['label']] + $this->readReportTable($report, 0, $group['value']);
        }

        return ['groups' => $groups, 'table' => null];
    }

    /**
     * A page of the table of a report, or of the table of a group (value of the grouping field,
     * `_empty` for no value, as the legacy page).
     *
     * @return array{columns: array, rows: array, totals: array, pager: ?array}
     */
    protected function readReportTable(AOR_Report $report, int $offset, string $group): array
    {
        global $sugar_config;

        $columns = $this->getReportColumns($report);
        $shown = array_values(array_filter($columns, static fn(array $column) => $column['display']));
        $table = [
            'columns' => array_map(fn(array $column) => $this->describeReportColumn($column), $shown),
            'rows' => [],
            'totals' => [],
            'pager' => null,
            'group' => $group,
        ];

        $query = $columns ? $report->build_report_query($report->db->quote($group)) : '';
        if (!$query) {
            return $table;
        }

        $db = $report->db;
        $count = $db->fetchByAssoc($db->query('SELECT count(*) c FROM (' . explode('ORDER BY', $query)[0] . ') as n'));
        $total = (int)($count['c'] ?? 0);
        $pageSize = (int)($sugar_config['list_max_entries_per_page'] ?? 0) ?: 20;
        if ($offset >= $total) {
            // Past the end (rows deleted since): the last page.
            $offset = $total > 0 ? (int)(floor(($total - 1) / $pageSize) * $pageSize) : 0;
        }
        $offset = max(0, $offset);

        $totals = [];
        $result = $db->limitQuery($query, $offset, $pageSize);
        while ($row = $db->fetchByAssoc($result, false)) {
            $table['rows'][] = $this->describeReportRow($report, $shown, $row);
            foreach ($shown as $column) {
                if ($column['total'] && $column['key'] !== null && isset($row[$column['key']])) {
                    $totals[$column['key']][] = $row[$column['key']];
                }
            }
        }

        $table['totals'] = $this->describeReportTotals($report, $shown, $totals);
        $table['pager'] = [
            'start' => $total > 0 ? $offset + 1 : 0,
            'end' => min($offset + $pageSize, $total),
            'total' => $total,
            'first' => $offset > 0 ? 0 : null,
            'prev' => $offset > 0 ? max(0, $offset - $pageSize) : null,
            'next' => $offset + $pageSize < $total ? $offset + $pageSize : null,
            'last' => $offset + $pageSize < $total ? (int)(floor(($total - 1) / $pageSize) * $pageSize) : null,
        ];

        return $table;
    }

    /**
     * The fields of the report, in their order, with the column of their value in the report
     * query (`key`: label and position of the field among the fields still in their module, as
     * AOR_Report::build_report_query_select() names them; null for a field no longer in its
     * module), their module and table alias, and their definition.
     */
    protected function getReportColumns(AOR_Report $report): array
    {
        global $beanList;

        if (empty($beanList[$report->report_module])) {
            return [];
        }

        $mainAlias = (new $beanList[$report->report_module]())->table_name;
        $columns = [];
        $position = 0;
        foreach ($report->getReportFields() as $field) {
            $path = unserialize(base64_decode((string)$field->module_path), ['allowed_classes' => false]) ?: [];
            $module = $report->report_module;
            $alias = $mainAlias;
            if (!empty($path[0]) && $path[0] !== $report->report_module) {
                foreach ($path as $relationship) {
                    if (!empty($relationship)) {
                        $module = getRelatedModule($module, $relationship);
                        $alias .= ':' . $relationship;
                    }
                }
            }

            $bean = \BeanFactory::newBean($module);
            $definition = $bean ? ($bean->field_defs[$field->field] ?? null) : null;
            $key = $definition !== null ? str_replace(' ', '_', (string)$field->label) . $position++ : null;

            $columns[] = [
                'key' => $key,
                'field' => (string)$field->field,
                'label' => html_entity_decode((string)$field->label, ENT_QUOTES),
                'display' => (bool)$field->display,
                'function' => strtoupper((string)$field->field_function),
                'format' => (string)$field->format,
                'link' => (bool)$field->link,
                'total' => strtoupper((string)$field->total),
                'groupDisplay' => (bool)$field->group_display,
                'module' => $module,
                'alias' => $alias,
                'definition' => is_array($definition) ? $definition : null,
            ];
        }

        return $columns;
    }

    /**
     * A column of the page: its key, label, record (table alias), whether it links to its
     * record, its total function, and the field definition the page shows its values with.
     */
    protected function describeReportColumn(array $column): array
    {
        $type = $this->getReportColumnType($column);
        // A currency is shown by its name.
        $type = $type === 'currency-name' ? 'varchar' : $type;
        $definition = ['name' => (string)$column['key'], 'type' => $type];
        if (in_array($type, ['enum', 'dynamicenum', 'radioenum', 'multienum', 'currency', 'float', 'relate'], true)) {
            foreach (self::$reportDefinitionKeys as $property) {
                if (isset($column['definition'][$property]) && !is_array($column['definition'][$property])) {
                    $definition[$property] = $column['definition'][$property];
                }
            }
            $definition['type'] = $type;
        }
        if ($type === 'relate') {
            // The value is {id, name}; the id is also the link of the field.
            $definition = array_merge($definition, ['source' => 'non-db', 'rname' => 'name', 'id_name' => $column['key'] . '_id']);
        }

        return [
            'key' => (string)$column['key'],
            'label' => $column['label'],
            'record' => $column['alias'],
            'module' => $this->moduleNameMapper->toFrontEnd((string)$column['module']),
            'link' => $column['link'],
            'total' => $column['total'],
            'totalType' => $column['total'] === 'COUNT' ? 'int' : ($column['total'] === 'AVG' && $type === 'int' ? 'float' : $type),
            'fieldDefinition' => $definition,
        ];
    }

    /**
     * Type a column is shown with: the type of its field, int for a count, text for a value
     * formatted by the query (dates by month...) or a field no longer in its module.
     */
    protected function getReportColumnType(array $column): string
    {
        if ($column['function'] === 'COUNT') {
            return 'int';
        }
        if ($column['format'] !== '' || $column['definition'] === null) {
            return 'varchar';
        }

        $type = (string)($column['definition']['type'] ?? 'varchar');
        if (($column['definition']['function']['name'] ?? $column['definition']['function'] ?? '') === 'getCurrencyDropDown') {
            return 'currency-name';
        }
        if ($type === 'relate' && !empty($column['definition']['id_name']) && !empty($column['definition']['module'])) {
            return 'relate';
        }
        if ($column['function'] !== '' && in_array($type, ['enum', 'dynamicenum', 'radioenum', 'multienum', 'bool'], true)) {
            return 'varchar';
        }

        return self::$reportFieldTypes[$type] ?? 'varchar';
    }

    /**
     * A row of the page: its records (by table alias: id, module and attributes in the
     * SuiteCRM 8 format), each column being a field of one of them.
     */
    protected function describeReportRow(AOR_Report $report, array $columns, array $row): array
    {
        $records = [];
        foreach ($columns as $column) {
            $alias = $column['alias'];
            if (!isset($records[$alias])) {
                $records[$alias] = [
                    'id' => (string)($row[$alias . '_id'] ?? ''),
                    'module' => $this->moduleNameMapper->toFrontEnd((string)$column['module']),
                    'attributes' => [],
                ];
                if (isset($row[$alias . '_currency_id'])) {
                    $records[$alias]['attributes']['currency_id'] = (string)$row[$alias . '_currency_id'];
                }
            }

            if ($column['key'] === null) {
                continue;
            }
            $value = $row[$column['key']] ?? null;
            $type = $this->getReportColumnType($column);
            $records[$alias]['attributes'] += $this->toReportAttributes((string)$column['key'], $type, $column, $value);
        }

        return ['records' => $records];
    }

    /**
     * A value of the report query as a record attribute of the page (the SuiteCRM 8 API format:
     * dates in the database format, lists as arrays, booleans, relate fields as {id, name}).
     */
    protected function toReportAttributes(string $key, string $type, array $column, $value): array
    {
        if ($value === null) {
            return [$key => $type === 'multienum' ? [] : ''];
        }

        switch ($type) {
            case 'bool':
                return [$key => in_array(strtolower((string)$value), ['1', 'on', 'true', 'yes'], true)];
            case 'multienum':
                return [$key => array_values(array_filter(unencodeMultienum((string)$value), static fn($item) => $item !== ''))];
            case 'relate':
                $id = (string)$value;

                return [
                    $key => ['id' => $id, 'name' => $this->getReportRecordName((string)$column['definition']['module'], $id)],
                    $key . '_id' => $id,
                ];
            case 'currency-name':
                return [$key => $this->getReportCurrencyName((string)$value)];
            default:
                return [$key => (string)$value];
        }
    }

    /**
     * The totals of a page (sum, count or average of the rows of the page, as the legacy table),
     * by column key. Amounts are in the user's currency, as the legacy table shows them.
     */
    protected function describeReportTotals(AOR_Report $report, array $columns, array $values): array
    {
        global $current_user;

        $totals = [];
        foreach ($columns as $column) {
            if (!$column['total'] || $column['key'] === null || !isset($values[$column['key']])) {
                continue;
            }

            $numbers = array_map('floatval', $values[$column['key']]);
            $totals[$column['key']] = match ($column['total']) {
                'SUM' => (string)array_sum($numbers),
                'AVG' => (string)(array_sum($numbers) / max(1, count($numbers))),
                'COUNT' => (string)count($numbers),
                default => '',
            };
        }

        if ($totals !== []) {
            $totals['currency_id'] = (string)($current_user->getPreference('currency') ?: '-99');
        }

        return $totals;
    }

    /**
     * The name of a record of a relate column (cached for the request).
     */
    protected function getReportRecordName(string $module, string $id): string
    {
        static $names = [];

        if ($id === '' || $module === '') {
            return '';
        }
        if (!isset($names[$module][$id])) {
            $bean = \BeanFactory::getBean($module, $id);
            $names[$module][$id] = $bean && !empty($bean->id)
                ? html_entity_decode((string)$bean->get_summary_text(), ENT_QUOTES)
                : '';
        }

        return $names[$module][$id];
    }

    /**
     * The name of a currency (the system currency for -99), as the legacy report shows it.
     */
    protected function getReportCurrencyName(string $id): string
    {
        if ($id === '') {
            return '';
        }

        $currency = \BeanFactory::newBean('Currencies');
        $currency->retrieve($id);

        return html_entity_decode((string)($currency->name ?: $id), ENT_QUOTES);
    }

    /**
     * The grouping field of a report (the first field shown as a group), or null.
     */
    protected function getReportGroupField(AOR_Report $report): ?array
    {
        foreach ($this->getReportColumns($report) as $column) {
            if ($column['groupDisplay'] && $column['definition'] !== null) {
                return $column;
            }
        }

        return null;
    }

    /**
     * The values of the grouping field of a report, as the legacy page lists its groups
     * (AOR_Report::build_group_report(): same query, conditions and order), each with its
     * label. An empty value is the `_empty` group (label None).
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getReportGroups(AOR_Report $report, array $groupField): array
    {
        global $beanList, $timedate, $app_strings;

        $db = $report->db;
        $module = new $beanList[$report->report_module]();
        $field = \BeanFactory::getBean('AOR_Fields', $this->getReportGroupFieldId($report));
        if (!$field || empty($field->id)) {
            return [];
        }

        $label = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', (string)$field->label));
        $path = unserialize(base64_decode((string)$field->module_path), ['allowed_classes' => false]) ?: [];
        $query = [];
        $fieldModule = $module;
        $alias = $fieldModule->table_name;
        if (!empty($path[0]) && $path[0] !== $module->module_dir) {
            foreach ($path as $relationship) {
                $relatedModule = new $beanList[getRelatedModule($fieldModule->module_dir, $relationship)]();
                $parentAlias = $alias;
                $alias .= ':' . $relationship;
                $query = $report->build_report_query_join($relationship, $alias, $parentAlias, $fieldModule, 'relationship', $query, $relatedModule);
                $fieldModule = $relatedModule;
            }
        }

        $data = $fieldModule->field_defs[$field->field];
        $name = $data['type'] === 'relate' && isset($data['id_name']) ? $data['id_name'] : $field->field;
        if (($data['source'] ?? '') === 'custom_fields') {
            $select = $db->quoteIdentifier($alias . '_cstm') . '.' . $name;
            $query = $report->build_report_query_join($alias . '_cstm', $alias . '_cstm', $alias, $fieldModule, 'custom', $query);
        } else {
            $select = $db->quoteIdentifier($alias) . '.' . $name;
        }
        if ($field->format && in_array($data['type'], ['date', 'datetime', 'datetimecombo'], true)) {
            if ($data['type'] !== 'date') {
                $select = $db->convert($select, 'add_tz_offset');
            }
            $select = $db->convert($select, 'date_format', [$timedate->getCalFormat($field->format)]);
        }
        if ($field->field_function != null && in_array(strtoupper((string)$field->field_function), getAorAllowedFieldFunctions(), true)) {
            $select = strtoupper((string)$field->field_function) . '(' . $select . ')';
        }

        $query['select'][] = $select . " AS '" . $label . "'";
        $query['where'][] = $select . ' IS NOT NULL AND ';
        $query = $report->build_report_query_where($query);

        $sql = 'SELECT ' . implode(', ', $query['select']) . ' FROM ' . $module->table_name . ' ' . implode('', $query['join'] ?? []);
        if (!empty($query['where'])) {
            $sql .= ' ' . $this->repairReportWhere('WHERE ' . implode(' ', $query['where']));
        }
        $sql .= ' GROUP BY ' . $select;
        if ($field->sort_by != '' && in_array(strtoupper((string)$field->sort_by), getAorAllowedSortDirections(), true)) {
            $sql .= ' ORDER BY ' . $label . ' ' . (strtoupper((string)$field->sort_by) === 'DESC' ? 'DESC' : 'ASC');
        }

        $groups = [];
        $result = $db->query($sql);
        while ($row = $db->fetchByAssoc($result, false)) {
            $value = (string)($row[$label] ?? '');
            $groups[] = trim($value) === ''
                ? ['value' => '_empty', 'label' => (string)($app_strings['LBL_NONE'] ?? '')]
                : ['value' => $value, 'label' => $this->getReportValueText($groupField, $value)];
        }

        return $groups;
    }

    protected function getReportGroupFieldId(AOR_Report $report): string
    {
        $db = $report->db;

        return (string)$db->getOne(
            'SELECT id FROM aor_fields WHERE aor_report_id = ' . $db->quoted($report->id)
            . ' AND group_display = 1 AND deleted = 0 ORDER BY field_order ASC'
        );
    }

    /**
     * A value of a report column as text (group labels, chart labels): list labels, record
     * names, dates in the user's format; the value itself for a count or a formatted value.
     */
    protected function getReportValueText(array $column, $value): string
    {
        global $app_list_strings, $timedate, $app_strings;

        $value = (string)$value;
        $type = $this->getReportColumnType($column);
        $options = (string)($column['definition']['options'] ?? '');

        switch ($type) {
            case 'enum':
            case 'dynamicenum':
            case 'radioenum':
                return (string)($app_list_strings[$options][$value] ?? $value);
            case 'multienum':
                return implode(', ', array_map(
                    static fn($item) => (string)($app_list_strings[$options][$item] ?? $item),
                    array_filter(unencodeMultienum($value), static fn($item) => $item !== '')
                ));
            case 'relate':
                return $this->getReportRecordName((string)$column['definition']['module'], $value);
            case 'currency-name':
                return $this->getReportCurrencyName($value);
            case 'bool':
                return in_array(strtolower($value), ['1', 'on', 'true', 'yes'], true)
                    ? (string)($app_strings['LBL_YES'] ?? 'Yes')
                    : (string)($app_strings['LBL_NO'] ?? 'No');
            case 'date':
                return $value !== '' ? (string)$timedate->to_display_date($value, false) : '';
            case 'datetime':
                return $value !== '' ? (string)$timedate->to_display_date_time($value, true, true) : '';
            case 'currency':
                return $value !== '' ? (string)currency_format_number((float)$value, ['currency_symbol' => false]) : '';
            default:
                return html_entity_decode($value, ENT_QUOTES);
        }
    }

    /**
     * Removes the empty conditions of a report query (AOR_Report::queryWhereRepair()).
     */
    protected function repairReportWhere(string $where): string
    {
        $tries = 0;
        do {
            $previous = $where;
            $where = (string)preg_replace('/\b(AND|OR)\s*\(\s*\)|[^\w+\s*]\(\s*\)/i', '', $where);
        } while ($where !== $previous && ++$tries < 100);

        return $where;
    }
}
