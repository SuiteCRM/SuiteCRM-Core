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
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The report editor of SuiteCRM 8, as the legacy one (AOR_Reports EditView:
 * view.edit.php, AOR_Report_Before.js, fieldLines.js, conditionLines.js, chartLines.js):
 * details of the report, the module tree of the report module (its relationships and their
 * fields), the fields, the conditions (with parentheses) and the charts of the report.
 *
 * The page gets the report and the choices of the editor as data, and the report is saved by
 * AOR_Report::save() with the request the legacy form posts (its fields, conditions and charts
 * are saved by the save_lines() of AOR_Field, AOR_Condition and AOR_Chart).
 */
class ReportEditorHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'report-editor';

    // Choices of the legacy editor by field type (AOR_ReportsController getModuleOperatorField,
    // getFieldTypeOptions).
    protected const NUMBER_TYPES = ['double', 'decimal', 'float', 'currency', 'uint', 'ulong', 'long', 'short', 'tinyint', 'int'];
    protected const DATE_TYPES = ['date', 'datetime', 'datetimecombo'];
    protected const COMPARE_OPERATORS = ['Equal_To', 'Not_Equal_To', 'Greater_Than', 'Less_Than', 'Greater_Than_or_Equal_To', 'Less_Than_or_Equal_To'];
    protected const TEXT_OPERATORS = ['Equal_To', 'Not_Equal_To', 'Contains', 'Starts_With', 'Ends_With'];

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
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
        return self::HANDLER_KEY;
    }

    /**
     * The report to edit (a new report without id; a copy of the report, without ids, to
     * duplicate it) with the choices of the editor; null when the user cannot edit it.
     */
    public function load(string $id, bool $duplicate): ?array
    {
        return $this->inLegacy(function () use ($id, $duplicate) {
            global $current_user;

            $report = $id !== '' ? BeanFactory::getBean('AOR_Reports', $id) : BeanFactory::newBean('AOR_Reports');
            if (!$report instanceof AOR_Report || ($id !== '' && empty($report->id))
                || ($id !== '' && !$report->ACLAccess($duplicate ? 'view' : 'edit'))
                || (($id === '' || $duplicate) && !BeanFactory::newBean('AOR_Reports')->ACLAccess('edit'))) {
                return null;
            }

            $report->load_report_beans();
            $assignedUserId = $id !== '' ? (string)$report->assigned_user_id : (string)$current_user->id;

            return [
                'id' => $duplicate ? '' : (string)$report->id,
                'name' => $this->decode($report->name),
                'assignedUserId' => $assignedUserId,
                'assignedUserName' => $assignedUserId !== '' ? $this->decode(get_assigned_user_name($assignedUserId)) : '',
                'reportModule' => (string)$report->report_module,
                'graphsPerRow' => $id !== '' ? (int)$report->graphs_per_row : 2,
                'description' => $this->decode($report->description),
                'fields' => $id !== '' ? $this->loadFields($report, $duplicate) : [],
                'conditions' => $id !== '' ? $this->loadConditions($report, $duplicate) : [],
                'charts' => $id !== '' ? $this->loadCharts($report, $duplicate) : [],
                'options' => $this->getOptions(),
                'module' => $this->describeModule((string)$report->report_module),
                'labels' => $this->getLabels(),
            ];
        });
    }

    /**
     * The choices of the editor that depend on the report module: its label, its fields (value
     * of a condition comparing to a field) and its dates (base of a relative date).
     */
    public function module(string $reportModule): ?array
    {
        return $this->inLegacy(fn() => $this->isReportModule($reportModule) ? $this->describeModule($reportModule) : null);
    }

    /**
     * The relationships of the module at a path of the module tree (link names separated by
     * ":" from the report module): link, label and module of each (getModuleTreeData()).
     */
    public function tree(string $reportModule, string $path): ?array
    {
        return $this->inLegacy(function () use ($reportModule, $path) {
            if (!$this->isReportModule($reportModule)) {
                return null;
            }

            $nodes = [];
            $module = $this->moduleAt($reportModule, $path);
            foreach (json_decode((string)getModuleTreeData($module), true) ?: [] as $link => $relationship) {
                if (($relationship['type'] ?? '') !== 'relationship') {
                    continue;
                }
                $nodes[] = [
                    'link' => (string)$link,
                    'label' => $this->decode($relationship['module_label'] ?? $link),
                    'module' => (string)($relationship['module'] ?? ''),
                    'path' => $path !== '' ? $path . ':' . $link : (string)$link,
                ];
            }

            return $nodes;
        });
    }

    /**
     * The fields of the module at a path of the module tree (getModuleFields()), with their type.
     */
    public function fields(string $reportModule, string $path): ?array
    {
        return $this->inLegacy(function () use ($reportModule, $path) {
            if (!$this->isReportModule($reportModule)) {
                return null;
            }

            $module = $this->moduleAt($reportModule, $path);
            $definitions = BeanFactory::newBean($module)->field_defs ?? [];
            $fields = [];
            foreach (json_decode((string)getModuleFields($module, 'JSON'), true) ?: [] as $name => $label) {
                if ($name === '') {
                    continue;
                }
                $fields[] = ['name' => (string)$name, 'label' => $this->decode($label), 'type' => (string)($definitions[$name]['type'] ?? '')];
            }

            return $fields;
        });
    }

    /**
     * What a condition on a field offers: its type, operators, value types, the input of a
     * value, and the options of a list field.
     */
    public function field(string $reportModule, string $path, string $field): ?array
    {
        return $this->inLegacy(fn() => $this->isReportModule($reportModule) ? $this->describeField($reportModule, $path, $field) : null);
    }

    /**
     * Saves the report (a new one without id) with its fields, conditions and charts; returns
     * its id, or null when the user cannot save it or a value is not valid.
     */
    public function save(string $id, array $data): ?string
    {
        return $this->inLegacy(function () use ($id, $data) {
            $report = $id !== '' ? BeanFactory::getBean('AOR_Reports', $id) : BeanFactory::newBean('AOR_Reports');
            if (!$report instanceof AOR_Report || ($id !== '' && empty($report->id)) || !$report->ACLAccess('edit')) {
                return null;
            }

            $reportModule = (string)($data['reportModule'] ?? '');
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '' || !$this->isReportModule($reportModule)) {
                return null;
            }

            $post = ['record' => (string)$report->id, 'report_module' => $reportModule];
            $fields = $this->postFields($report, $reportModule, is_array($data['fields'] ?? null) ? array_values($data['fields']) : []);
            $conditions = $this->postConditions($report, $reportModule, is_array($data['conditions'] ?? null) ? array_values($data['conditions']) : []);
            $charts = $this->postCharts(is_array($data['charts'] ?? null) ? array_values($data['charts']) : [], count($data['fields'] ?? []));
            if ($fields === null || $conditions === null || $charts === null) {
                return null;
            }

            $assignedUserId = (string)($data['assignedUserId'] ?? '');
            $report->name = $name;
            $report->report_module = $reportModule;
            $report->assigned_user_id = $assignedUserId !== '' && BeanFactory::getBean('Users', $assignedUserId) ? $assignedUserId : '';
            $report->graphs_per_row = max(1, (int)($data['graphsPerRow'] ?? 1));
            $report->description = (string)($data['description'] ?? '');

            // AOR_Report::save() saves the lines from the request, as posted by the legacy form.
            $previousPost = $_POST;
            $previousRequest = $_REQUEST;
            $_POST = $post + $fields + $conditions + $charts;
            $_REQUEST = $_POST + $_REQUEST;
            try {
                $report->save();
            } finally {
                $_POST = $previousPost;
                $_REQUEST = $previousRequest;
            }

            return (string)$report->id;
        });
    }

    protected function loadFields(AOR_Report $report, bool $duplicate): array
    {
        $lines = [];
        foreach ($this->getLines('aor_fields', 'field_order', $report->id) as $id) {
            $field = BeanFactory::getBean('AOR_Fields', $id);
            if (!$field) {
                continue;
            }
            $path = $this->pathOf((string)$field->module_path);
            $display = getDisplayForField($path, $field->field, $report->report_module);
            $lines[] = [
                'id' => $duplicate ? '' : (string)$field->id,
                'path' => $path,
                'pathLabel' => $this->decode($display['module'] ?? ''),
                'field' => (string)$field->field,
                'fieldLabel' => $this->decode($display['field'] ?? $field->field),
                'type' => (string)($display['type'] ?? ''),
                'display' => (bool)$field->display,
                'link' => (bool)$field->link,
                'label' => $this->decode($field->label),
                'function' => (string)$field->field_function,
                'sort' => (string)$field->sort_by,
                'group' => (bool)$field->group_by,
                'format' => (string)$field->format,
                'total' => (string)$field->total,
                'mainGroup' => (int)$field->group_display === 1,
            ];
        }

        return $lines;
    }

    protected function loadConditions(AOR_Report $report, bool $duplicate): array
    {
        $lines = [];
        foreach ($this->getLines('aor_conditions', 'condition_order', $report->id) as $id) {
            $condition = BeanFactory::getBean('AOR_Conditions', $id);
            if (!$condition) {
                continue;
            }
            $line = ['id' => $duplicate ? '' : (string)$condition->id, 'logic' => (string)$condition->logic_op];
            if ($condition->parenthesis) {
                $lines[] = $line + ['parenthesis' => $condition->parenthesis === 'START' ? 'START' : 'END'];
                continue;
            }

            $path = $this->pathOf((string)$condition->module_path);
            $display = getDisplayForField($path, $condition->field, $report->report_module);
            $info = $this->describeField($report->report_module, $path, (string)$condition->field);
            [$value, $valueLabel] = $this->conditionValue($condition, $info);
            $lines[] = $line + [
                'parenthesis' => '',
                'path' => $path,
                'pathLabel' => $this->decode($display['module'] ?? ''),
                'field' => (string)$condition->field,
                'fieldLabel' => $this->decode($display['field'] ?? $condition->field),
                'operator' => (string)$condition->operator,
                'valueType' => (string)$condition->value_type,
                'value' => $value,
                'valueLabel' => $valueLabel,
                'parameter' => (bool)$condition->parameter,
                'info' => $info,
            ];
        }

        return $lines;
    }

    protected function loadCharts(AOR_Report $report, bool $duplicate): array
    {
        $charts = [];
        foreach ($report->get_linked_beans('aor_charts', 'AOR_Charts') as $chart) {
            $charts[] = [
                'id' => $duplicate ? '' : (string)$chart->id,
                'title' => $this->decode($chart->name),
                'type' => (string)$chart->type,
                'x' => (int)$chart->x_field,
                'y' => (int)$chart->y_field,
            ];
        }

        return $charts;
    }

    /**
     * The value of a condition for the editor, and the name of the record of a relate field.
     *
     * @return array{0: string|string[], 1: string}
     */
    protected function conditionValue(\AOR_Condition $condition, array $info): array
    {
        global $app_list_strings;

        $value = (string)$condition->value;
        switch ($condition->value_type) {
            case 'Date':
                $parts = unserialize(base64_decode($value), ['allowed_classes' => false]);

                return [array_map('strval', array_pad(is_array($parts) ? array_values($parts) : [], 4, '')), ''];
            case 'Multi':
                return [array_values(array_map('strval', unencodeMultienum($value))), ''];
            case 'Period':
                $period = (string)base64_decode($value, true);

                return [array_key_exists($period, $app_list_strings['date_time_period_list'] ?? []) ? $period : $value, ''];
            case 'Value':
                if ($info['input'] === 'datetime' && $value !== '') {
                    return [$this->toUserDateTime($value), ''];
                }
                if ($info['input'] === 'date') {
                    return [substr($value, 0, 10), ''];
                }
                if ($info['input'] === 'relate' && $value !== '') {
                    $record = BeanFactory::getBean($info['legacyModule'], $value);

                    return [$value, $record && !empty($record->id) ? $this->decode($record->get_summary_text()) : $value];
                }

                return [$value, ''];
            default:
                return [$value, ''];
        }
    }

    /**
     * The field lines as the legacy form posts them (aor_fields_*), with the existing lines
     * removed in the page marked deleted; null when a line is not valid.
     */
    protected function postFields(AOR_Report $report, string $reportModule, array $lines): ?array
    {
        global $app_list_strings;

        $post = [];
        $mainGroup = -1;
        $existing = $this->existingLines('aor_fields', $report);
        $kept = [];
        foreach ($lines as $index => $line) {
            $path = (string)($line['path'] ?? '');
            $field = (string)($line['field'] ?? '');
            $id = is_array($line) && in_array((string)($line['id'] ?? ''), $existing, true) ? (string)$line['id'] : '';
            // A saved line keeps a field that is no longer in its module (as the legacy form posts it).
            if (!is_array($line) || $field === '' || ($id === '' && !$this->isField($reportModule, $path, $field))
                || !$this->isOption($line['function'] ?? '', $app_list_strings['aor_function_list'] ?? [])
                || !$this->isOption($line['sort'] ?? '', $app_list_strings['aor_sort_operator'] ?? [])
                || !$this->isOption($line['format'] ?? '', $app_list_strings['aor_format_options'] ?? [])
                || !$this->isOption($line['total'] ?? '', $app_list_strings['aor_total_options'] ?? [])) {
                return null;
            }

            $kept[] = $id;
            $post['aor_fields_id'][$index] = $id;
            $post['aor_fields_deleted'][$index] = '0';
            $post['aor_fields_field_order'][$index] = (string)$index;
            $post['aor_fields_module_path'][$index] = $path;
            $post['aor_fields_field'][$index] = $field;
            $post['aor_fields_display'][$index] = empty($line['display']) ? '0' : '1';
            $post['aor_fields_link'][$index] = empty($line['link']) ? '0' : '1';
            $post['aor_fields_label'][$index] = (string)($line['label'] ?? '');
            $post['aor_fields_field_function'][$index] = (string)($line['function'] ?? '');
            $post['aor_fields_sort_by'][$index] = (string)($line['sort'] ?? '');
            $post['aor_fields_group_by'][$index] = empty($line['group']) ? '0' : '1';
            $post['aor_fields_format'][$index] = (string)($line['format'] ?? '');
            $post['aor_fields_total'][$index] = (string)($line['total'] ?? '');
            if (!empty($line['mainGroup'])) {
                $mainGroup = $index;
            }
        }

        $index = count($lines);
        foreach (array_diff($existing, $kept) as $id) {
            $post['aor_fields_id'][$index] = $id;
            $post['aor_fields_deleted'][$index] = '1';
            $post['aor_fields_field'][$index] = '';
            $index++;
        }
        // Main group (the legacy form has a second, hidden, group select).
        $post['aor_fields_group_display'] = [$mainGroup, -1];

        return $post;
    }

    /**
     * The condition lines as the legacy form posts them (aor_conditions_*): parentheses (START,
     * then END for the last open one), logic with the previous line (none for the first line
     * and after an open parenthesis), and the existing lines removed in the page marked
     * deleted; null when a line is not valid.
     */
    protected function postConditions(AOR_Report $report, string $reportModule, array $lines): ?array
    {
        $post = [];
        $existing = $this->existingLines('aor_conditions', $report);
        $kept = [];
        $open = 0;
        $previous = null;
        foreach ($lines as $index => $line) {
            if (!is_array($line)) {
                return null;
            }
            $parenthesis = (string)($line['parenthesis'] ?? '');
            // No logic for the first line, after an open parenthesis, and for a close one.
            $logic = $previous === null || $previous === 'START' || $parenthesis === 'END'
                ? '' : (in_array($line['logic'] ?? '', ['AND', 'OR'], true) ? $line['logic'] : 'AND');
            $id = in_array((string)($line['id'] ?? ''), $existing, true) ? (string)$line['id'] : '';
            $kept[] = $id;
            $post['aor_conditions_id'][$index] = $id;
            $post['aor_conditions_deleted'][$index] = '0';
            $post['aor_conditions_order'][$index] = (string)$index;
            $post['aor_conditions_logic_op'][$index] = $logic;

            if ($parenthesis === 'START' || $parenthesis === 'END') {
                $open += $parenthesis === 'START' ? 1 : -1;
                if ($open < 0) {
                    return null;
                }
                $post['aor_conditions_field'][$index] = '';
                $post['aor_conditions_parenthesis'][$index] = $parenthesis;
                $previous = $parenthesis;
                continue;
            }

            $path = (string)($line['path'] ?? '');
            $field = (string)($line['field'] ?? '');
            $operator = (string)($line['operator'] ?? '');
            $valueType = (string)($line['valueType'] ?? '');
            if ($this->isField($reportModule, $path, $field)) {
                $info = $this->describeField($reportModule, $path, $field);
                // A saved condition may have no operator or type (left empty in the legacy editor).
                if ((!in_array($operator, array_column($info['operators'], 'value'), true) && ($id === '' || $operator !== ''))
                    || (!in_array($valueType, array_column($info['valueTypes'], 'value'), true) && ($id === '' || $valueType !== ''))) {
                    return null;
                }
                $value = $this->isSavedListValue($id, $info, $valueType, $line['value'] ?? '')
                    ? $this->keptConditionValue($valueType, $line['value'] ?? '')
                    : $this->postConditionValue($reportModule, $info, $valueType, $line['value'] ?? '');
            } else {
                // A saved condition keeps a field that is no longer in its module, with its values.
                $value = $id !== '' && $field !== '' ? $this->keptConditionValue($valueType, $line['value'] ?? '') : null;
            }
            if ($value === null) {
                return null;
            }

            $post['aor_conditions_field'][$index] = $field;
            $post['aor_conditions_module_path'][$index] = $path;
            $post['aor_conditions_operator'][$index] = $operator;
            $post['aor_conditions_value_type'][$index] = $valueType;
            $post['aor_conditions_value'][$index] = $value;
            if (!empty($line['parameter'])) {
                $post['aor_conditions_parameter'][$index] = '1';
            }
            $previous = 'line';
        }
        if ($open !== 0) {
            return null;
        }

        $index = count($lines);
        foreach (array_diff($existing, $kept) as $id) {
            $post['aor_conditions_id'][$index] = $id;
            $post['aor_conditions_deleted'][$index] = '1';
            $post['aor_conditions_field'][$index] = '';
            $index++;
        }

        return $post;
    }

    /**
     * The value of a condition as the legacy form posts it (the parts of a relative date, the
     * values of a "Multi" condition, a date and time in the database format); null when it is
     * not one the editor offers.
     *
     * @return string|string[]|null
     */
    protected function postConditionValue(string $reportModule, array $info, string $valueType, $value)
    {
        global $app_list_strings;

        switch ($valueType) {
            case 'Date':
                $parts = array_values(array_map('strval', array_pad(is_array($value) ? $value : [], 4, '')));
                $bases = array_column($this->getDateBases($reportModule), 'value');
                if (!in_array($parts[0], $bases, true) || !isset(($app_list_strings['aow_date_operator'] ?? [])[$parts[1]])
                    || ($parts[1] !== 'now' && (!preg_match('/^\d*$/', $parts[2]) || !isset(($app_list_strings['aow_date_type_list'] ?? [])[$parts[3]])))) {
                    return null;
                }

                return array_slice($parts, 0, 4);
            case 'Multi':
                $values = array_values(array_map('strval', is_array($value) ? $value : []));

                return array_diff($values, array_column($info['options'], 'value')) ? null : $values;
            case 'Period':
                return isset(($app_list_strings['date_time_period_list'] ?? [])[(string)$value]) ? (string)$value : null;
            case 'Field':
                // '' is the "None" option of the legacy field list.
                return (string)$value === '' || in_array((string)$value, array_column($this->getReportFields($reportModule), 'value'), true)
                    ? (string)$value : null;
            case 'CurrentUserID':
                return '';
            default:
                if (!is_scalar($value)) {
                    return null;
                }
                $value = (string)$value;
                if ($info['input'] === 'select' && $value !== '' && !in_array($value, array_column($info['options'], 'value'), true)) {
                    return null;
                }
                if ($info['input'] === 'datetime' && $value !== '') {
                    return $this->toDbDateTime($value);
                }
                if ($info['input'] === 'date' && $value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return null;
                }
                if ($info['input'] === 'bool') {
                    return $value === '1' ? '1' : '0';
                }

                return $value;
        }
    }

    /**
     * The list value of a saved condition, unchanged: kept even when the list no longer offers it
     * (an option removed from the list since the report was saved).
     */
    protected function isSavedListValue(string $id, array $info, string $valueType, $value): bool
    {
        if ($id === '' || !($valueType === 'Multi' || ($valueType === 'Value' && $info['input'] === 'select'))) {
            return false;
        }

        $condition = BeanFactory::getBean('AOR_Conditions', $id);
        if (!$condition || $condition->value_type !== $valueType) {
            return false;
        }

        $saved = $this->conditionValue($condition, $info)[0];
        $given = is_array($value) ? array_values(array_map('strval', $value)) : (string)$value;

        return $given === $saved;
    }

    /**
     * The value of a saved condition on a field no longer in its module, as it was loaded.
     *
     * @return string|string[]|null
     */
    protected function keptConditionValue(string $valueType, $value)
    {
        if (is_array($value)) {
            return in_array($valueType, ['Date', 'Multi'], true) ? array_values(array_map('strval', $value)) : null;
        }

        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * The chart lines as the legacy form posts them (aor_chart_*: the charts not posted are
     * deleted); null when a line is not valid.
     */
    protected function postCharts(array $lines, int $fieldCount): ?array
    {
        global $app_list_strings;

        $post = [];
        foreach ($lines as $line) {
            $type = (string)($line['type'] ?? '');
            $x = (int)($line['x'] ?? -1);
            $y = (int)($line['y'] ?? -1);
            if (!isset(($app_list_strings['aor_chart_types'] ?? [])[$type]) || $x < 0 || $y < 0 || $x >= $fieldCount || $y >= $fieldCount) {
                return null;
            }
            $post['aor_chart_id'][] = (string)($line['id'] ?? '');
            $post['aor_chart_title'][] = (string)($line['title'] ?? '');
            $post['aor_chart_type'][] = $type;
            $post['aor_chart_x_field'][] = (string)$x;
            $post['aor_chart_y_field'][] = (string)$y;
        }

        return $post;
    }

    /**
     * The choices of a condition on a field (legacy getModuleOperatorField, getFieldTypeOptions,
     * getModuleFieldType): type, operators, value types, input of a value ("Value" type), list
     * options, and the module of a relate field (record picker).
     */
    protected function describeField(string $reportModule, string $path, string $field): array
    {
        global $app_list_strings;

        $module = $this->moduleAt($reportModule, $path);
        $bean = BeanFactory::newBean($module);
        $definition = $bean ? ($bean->field_defs[$field] ?? []) : [];
        $type = (string)($definition['type'] ?? '');

        if (in_array($type, self::NUMBER_TYPES, true) || in_array($type, self::DATE_TYPES, true)) {
            $operators = self::COMPARE_OPERATORS;
        } elseif ($type === 'enum') {
            $operators = ['Equal_To', 'Not_Equal_To'];
        } elseif ($type === 'multienum') {
            $operators = ['Equal_To', 'Not_Equal_To', 'Contains'];
        } else {
            $operators = self::TEXT_OPERATORS;
        }

        if (in_array($type, self::NUMBER_TYPES, true)) {
            $valueTypes = ['Value', 'Field'];
        } elseif (in_array($type, self::DATE_TYPES, true)) {
            $valueTypes = ['Value', 'Field', 'Date', 'Period'];
        } elseif (in_array($type, ['enum', 'dynamicenum', 'multienum'], true)) {
            $valueTypes = ['Value', 'Field', 'Multi'];
        } else {
            // The legacy editor offers the current user for all the other fields.
            $valueTypes = ['Value', 'Field', 'CurrentUserID'];
        }

        $relatedModule = '';
        if (($type === 'relate' && !empty($definition['id_name']) && $definition['id_name'] !== $definition['name'])
            || $type === 'link') {
            $relatedModule = (string)($definition['module'] ?? '');
            if ($relatedModule === '' && $bean && $bean->load_relationship($field)) {
                $relatedModule = (string)$bean->$field->getRelatedModuleName();
            }
        }

        $input = 'text';
        if ($relatedModule !== '') {
            $input = 'relate';
        } elseif (in_array($type, ['enum', 'dynamicenum', 'radioenum', 'parent_type'], true)) {
            $input = 'select';
        } elseif ($type === 'multienum') {
            $input = 'multi';
        } elseif ($type === 'bool') {
            $input = 'bool';
        } elseif ($type === 'date') {
            $input = 'date';
        } elseif (in_array($type, ['datetime', 'datetimecombo'], true)) {
            $input = 'datetime';
        } elseif (in_array($type, self::NUMBER_TYPES, true)) {
            $input = 'number';
        }

        $optionsName = $type === 'parent_type' ? ($bean->field_defs[$definition['group'] ?? '']['options'] ?? '') : ($definition['options'] ?? '');
        $options = is_string($optionsName) ? ($app_list_strings[$optionsName] ?? []) : [];

        return [
            'type' => $type,
            'input' => $input,
            'module' => $relatedModule !== '' ? $this->moduleNameMapper->toFrontEnd($relatedModule) : '',
            'legacyModule' => $relatedModule,
            'operators' => $this->choices(array_intersect_key($app_list_strings['aor_operator_list'] ?? [], array_flip($operators))),
            'valueTypes' => $this->choices(array_intersect_key($app_list_strings['aor_condition_type_list'] ?? [], array_flip($valueTypes))),
            'options' => $this->choices(is_array($options) ? $options : []),
        ];
    }

    /**
     * The choices of the editor that depend on the report module.
     */
    protected function describeModule(string $reportModule): array
    {
        global $app_list_strings;

        if ($reportModule === '') {
            return ['name' => '', 'label' => '', 'fields' => [], 'dateBases' => []];
        }

        return [
            'name' => $reportModule,
            'label' => $this->decode($app_list_strings['moduleList'][$reportModule] ?? $reportModule),
            'fields' => $this->getReportFields($reportModule),
            'dateBases' => $this->getDateBases($reportModule),
        ];
    }

    /**
     * The fields of the report module (a condition comparing to a field, legacy getModuleFields()).
     */
    protected function getReportFields(string $reportModule): array
    {
        $fields = json_decode((string)getModuleFields($reportModule, 'JSON'), true) ?: [];
        unset($fields['']);

        return $this->choices($fields);
    }

    /**
     * Bases of a relative date: now, today, and the dates of the report module (legacy
     * getDateFields() without the field option).
     */
    protected function getDateBases(string $reportModule): array
    {
        global $app_list_strings;

        $bases = $app_list_strings['aow_date_options'] ?? [];
        unset($bases['field']);
        foreach (BeanFactory::newBean($reportModule)->field_defs ?? [] as $name => $definition) {
            if (in_array($definition['type'] ?? '', self::DATE_TYPES, true)) {
                $bases[$name] = trim((string)translate($definition['vname'] ?? $name, $reportModule), ':');
            }
        }

        return $this->choices($bases);
    }

    protected function getOptions(): array
    {
        global $app_list_strings;

        $dateUnits = $app_list_strings['aow_date_type_list'] ?? [];
        if (!file_exists('modules/AOBH_BusinessHours/AOBH_BusinessHours.php')) {
            unset($dateUnits['business_hours']);
        }

        return [
            'modules' => $this->choices(array_filter($app_list_strings['aor_moduleList'] ?? [], static fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH)),
            'functions' => $this->choices($app_list_strings['aor_function_list'] ?? []),
            'sorts' => $this->choices($app_list_strings['aor_sort_operator'] ?? []),
            'formats' => $this->choices($app_list_strings['aor_format_options'] ?? []),
            'totals' => $this->choices($app_list_strings['aor_total_options'] ?? []),
            'chartTypes' => $this->choices($app_list_strings['aor_chart_types'] ?? []),
            'periods' => $this->choices($app_list_strings['date_time_period_list'] ?? []),
            'dateOperators' => $this->choices($app_list_strings['aow_date_operator'] ?? []),
            'dateUnits' => $this->choices($dateUnits),
        ];
    }

    /**
     * Labels of the legacy editor (AOR_Reports, AOR_Fields and AOR_Conditions strings).
     */
    protected function getLabels(): array
    {
        $labels = [];
        $keys = [
            'AOR_Reports' => ['LBL_NAME', 'LBL_ASSIGNED_TO_NAME', 'LBL_REPORT_MODULE', 'LBL_GRAPHS_PER_ROW', 'LBL_DESCRIPTION',
                'LBL_AOR_MODULETREE_SUBPANEL_TITLE', 'LBL_AOR_FIELDS_SUBPANEL_TITLE', 'LBL_AOR_CONDITIONS_SUBPANEL_TITLE',
                'LBL_AOR_CHARTS_SUBPANEL_TITLE', 'LBL_ADD_CHART', 'LBL_ADD_PARENTHESIS', 'LBL_CHART_TITLE', 'LBL_CHART_TYPE',
                'LBL_CHART_X_FIELD', 'LBL_CHART_Y_FIELD', 'LBL_MAIN_GROUPS', 'LBL_TOOLTIP_DRAG_DROP_ELEMS'],
            'AOR_Fields' => ['LBL_MODULE_PATH', 'LBL_FIELD', 'LBL_DISPLAY', 'LBL_LINK', 'LBL_LABEL', 'LBL_FUNCTION', 'LBL_SORT',
                'LBL_GROUP', 'LBL_FORMAT', 'LBL_TOTAL'],
            'AOR_Conditions' => ['LBL_LOGIC_OP', 'LBL_OPERATOR', 'LBL_VALUE_TYPE', 'LBL_VALUE', 'LBL_PARAMETER', 'LBL_CONDITION_AND',
                'LBL_CONDITION_OR'],
        ];
        foreach ($keys as $module => $moduleKeys) {
            foreach ($moduleKeys as $key) {
                $labels[$module . '.' . $key] = trim($this->decode(translate($key, $module)), ':');
            }
        }

        return $labels;
    }

    /**
     * Ids of the lines of a report (aor_fields, aor_conditions) in their order.
     *
     * @return string[]
     */
    protected function getLines(string $table, string $order, string $reportId): array
    {
        $db = \DBManagerFactory::getInstance();
        $ids = [];
        $result = $db->query("SELECT id FROM $table WHERE aor_report_id = " . $db->quoted($reportId) . " AND deleted = 0 ORDER BY $order ASC");
        while ($row = $db->fetchByAssoc($result, false)) {
            $ids[] = (string)$row['id'];
        }

        return $ids;
    }

    /**
     * Ids of the lines (aor_fields, aor_conditions) of a saved report: a line of the page keeps
     * its id only when it is one of them, the others are removed.
     *
     * @return string[]
     */
    protected function existingLines(string $table, AOR_Report $report): array
    {
        return empty($report->id) ? [] : $this->getLines($table, 'id', $report->id);
    }

    protected function isReportModule(string $module): bool
    {
        global $app_list_strings;

        BeanFactory::newBean('AOR_Reports')->load_report_beans();

        return $module !== '' && isset(($app_list_strings['aor_moduleList'] ?? [])[$module]);
    }

    /**
     * A field the module tree offers at a path (legacy getModuleFields()).
     */
    protected function isField(string $reportModule, string $path, string $field): bool
    {
        if ($field === '' || ($path !== '' && !preg_match('/^[\w:]+$/', $path))) {
            return false;
        }
        $fields = json_decode((string)getModuleFields($this->moduleAt($reportModule, $path), 'JSON'), true) ?: [];

        return isset($fields[$field]);
    }

    protected function moduleAt(string $reportModule, string $path): string
    {
        return $path !== '' ? (string)getRelatedModule($reportModule, $path) : $reportModule;
    }

    /**
     * The path of a line (module_path: the link names from the report module, stored
     * serialized), as the editor uses it: link names separated by ":".
     */
    protected function pathOf(string $modulePath): string
    {
        $path = unserialize(base64_decode($modulePath), ['allowed_classes' => false]);

        return is_array($path) ? implode(':', array_filter(array_map('strval', $path), static fn($link) => $link !== '')) : '';
    }

    protected function isOption($value, array $options): bool
    {
        return is_scalar($value) && ((string)$value === '' || array_key_exists((string)$value, $options));
    }

    /**
     * A date and time of the user (datetime-local input) in the database format.
     */
    protected function toDbDateTime(string $value): ?string
    {
        global $timedate, $current_user;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return null;
        }
        $date = new DateTime(str_replace('T', ' ', $value), new DateTimeZone($timedate->userTimezone($current_user)));

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * A date and time in the database format, for a datetime-local input of the user.
     */
    protected function toUserDateTime(string $value): string
    {
        global $timedate, $current_user;

        try {
            $date = new DateTime($value, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            return '';
        }

        return $date->setTimezone(new DateTimeZone($timedate->userTimezone($current_user)))->format('Y-m-d\TH:i');
    }

    /**
     * Options of a list as value / label pairs.
     */
    protected function choices(array $options): array
    {
        $choices = [];
        foreach ($options as $value => $label) {
            $choices[] = ['value' => (string)$value, 'label' => $this->decode(is_scalar($label) ? $label : '')];
        }

        return $choices;
    }

    protected function decode($value): string
    {
        return html_entity_decode(str_replace('&nbsp;', ' ', (string)$value), ENT_QUOTES);
    }

    /**
     * Runs in the legacy application; the legacy code does not stop on its warnings, nor print
     * into the response.
     */
    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();
        $ignored = E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED;
        set_error_handler(static fn(int $level): bool => ($level & $ignored) !== 0);
        ob_start();

        try {
            require_once 'modules/AOW_WorkFlow/aow_utils.php';
            require_once 'modules/AOR_Reports/aor_utils.php';

            return $callback();
        } finally {
            ob_end_clean();
            restore_error_handler();
            $this->close();
        }
    }
}
