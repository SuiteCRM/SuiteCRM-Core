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
use BeanFactory;

/**
 * Parameters of a report (its conditions marked as parameters), shared by the options of the
 * report dashlet and the report page: their description for the page,
 * the check of the values the page sends, and these values in the forms the report reads.
 * Runs in the legacy application.
 */
trait ReportParametersTrait
{
    /**
     * Conditions of the report marked as parameters (getConditionsAsParameters()), with the
     * input the page shows: a period list, the list of the field (several values for a
     * "Multi" condition), a date or a text. A relative date ("Date" condition: now + 1 day...)
     * is shown and kept as is, as the legacy form could not change it either.
     */
    protected function describeReportParameters(AOR_Report $report, array $override): array
    {
        global $app_list_strings;

        require_once 'modules/AOR_Reports/aor_utils.php';

        $parameters = [];
        foreach ($report->get_linked_beans('aor_conditions', 'AOR_Conditions') as $key => $condition) {
            if (!$condition->parameter) {
                continue;
            }

            $path = unserialize(base64_decode((string)$condition->module_path), ['allowed_classes' => false]) ?: [];
            $module = $report->report_module;
            if (($path[0] ?? '') !== $report->report_module) {
                foreach ($path as $relationship) {
                    if (!empty($relationship)) {
                        $module = getRelatedModule($module, $relationship);
                    }
                }
            }
            $field = getDisplayForField($path, $condition->field, $report->report_module);
            $definition = BeanFactory::newBean($module)->field_defs[$condition->field] ?? [];

            $value = (string)($override[$condition->id]['value'] ?? $condition->value);
            $kind = 'text';
            $options = [];
            $display = '';
            if ($condition->value_type === 'Period') {
                $kind = 'select';
                $options = $app_list_strings['date_time_period_list'] ?? [];
                // Stored encoded by the report; the dashlet keeps the period key (AOR_Report reads both).
                if (!array_key_exists($value, $options) && array_key_exists((string)base64_decode($value, true), $options)) {
                    $value = (string)base64_decode($value, true);
                }
            } elseif ($condition->value_type === 'Date') {
                $kind = 'fixed';
                $display = $this->describeRelativeDate($value);
            } elseif ($condition->value_type === 'Multi') {
                $kind = 'multi';
                $options = $app_list_strings[$definition['options'] ?? ''] ?? [];
                $value = array_values(array_map('strval', unencodeMultienum($value)));
            } else {
                $type = $definition['type'] ?? '';
                if (in_array($type, ['enum', 'multienum', 'dynamicenum', 'radioenum'], true) && !empty($definition['options'])) {
                    $kind = 'select';
                    $options = $app_list_strings[$definition['options']] ?? [];
                } elseif (in_array($type, ['date', 'datetime', 'datetimecombo'], true)) {
                    $kind = 'date';
                    $value = substr($value, 0, 10);
                }
            }

            $parameters[] = [
                'key' => (string)$key,
                'id' => (string)$condition->id,
                'operator' => (string)$condition->operator,
                'type' => (string)$condition->value_type,
                'label' => html_entity_decode(strip_tags(implode(' - ', array_filter([
                    $field['module'] ?? '',
                    $field['field'] ?? '',
                    $app_list_strings['aor_operator_list'][$condition->operator] ?? $condition->operator,
                ]))), ENT_QUOTES),
                'kind' => $kind,
                'display' => $display,
                'options' => array_map(
                    static fn($optionValue, $label) => ['value' => (string)$optionValue, 'label' => (string)$label],
                    array_keys($options),
                    array_values($options)
                ),
                'value' => $value,
            ];
        }

        return $parameters;
    }

    /**
     * A relative date of a report condition ("now + 1 day"), with the labels of the report editor.
     */
    protected function describeRelativeDate(string $value): string
    {
        global $app_list_strings;

        $parts = unserialize(base64_decode($value), ['allowed_classes' => false]);
        if (!is_array($parts)) {
            return $value;
        }

        return trim(implode(' ', [
            $app_list_strings['aor_date_options'][$parts[0] ?? ''] ?? ($parts[0] ?? ''),
            $app_list_strings['aor_date_operator'][$parts[1] ?? ''] ?? ($parts[1] ?? ''),
            $parts[2] ?? '',
            $app_list_strings['aor_date_type_list'][$parts[3] ?? ''] ?? ($parts[3] ?? ''),
        ]));
    }

    /**
     * The values the page gives for the parameters (by parameter key), in the format of the
     * report conditions (the values of a "Multi" parameter as a multi-select value); a
     * parameter not given keeps its value. Null when a value is not one the page offers.
     */
    protected function checkReportParameters(array $parameters, array $given): ?array
    {
        $values = [];
        foreach ($parameters as $parameter) {
            $key = $parameter['key'];
            $choices = array_column($parameter['options'], 'value');
            $value = $given[$key] ?? $parameter['value'];

            // A relative date is kept as is; an unchanged value is kept even when the page
            // could not offer it (value stored by an older version of the report).
            if ($parameter['kind'] === 'fixed' || $value === $parameter['value']) {
                $value = is_array($parameter['value']) ? encodeMultienumValue($parameter['value']) : (string)$parameter['value'];
            } elseif ($parameter['kind'] === 'multi') {
                $value = array_values(array_map('strval', (array)$value));
                // The empty value ("none") of the report condition is kept.
                if (array_diff($value, $choices, [''])) {
                    return null;
                }
                $value = encodeMultienumValue($value);
            } else {
                if (!is_scalar($value)) {
                    return null;
                }
                $value = (string)$value;
                if ($parameter['kind'] === 'select' && $value !== '' && !in_array($value, $choices, true)) {
                    return null;
                }
                if ($parameter['kind'] === 'date' && $value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return null;
                }
                if ($parameter['kind'] === 'text') {
                    $value = securexss($value);
                }
            }

            $values[] = [
                'key' => $key,
                'id' => $parameter['id'],
                'operator' => $parameter['operator'],
                'type' => $parameter['type'],
                'value' => $value,
            ];
        }

        return $values;
    }

    /**
     * Checked parameter values as the report dashlet saves them (inputs of its legacy
     * options form). The lists are there without parameters too: the dashlet reads them.
     */
    protected function toParameterRequest(array $values): array
    {
        $request = ['parameter_id' => [], 'parameter_operator' => [], 'parameter_type' => [], 'parameter_value' => []];
        foreach ($values as $value) {
            $request['parameter_id'][$value['key']] = $value['id'];
            $request['parameter_operator'][$value['key']] = $value['operator'];
            $request['parameter_type'][$value['key']] = $value['type'];
            $request['parameter_value'][$value['key']] = $value['value'];
        }

        return $request;
    }

    /**
     * Checked parameter values as the report reads them (AOR_Report::$user_parameters, as the
     * report dashlet sets them).
     */
    protected function toUserParameters(array $values): array
    {
        $parameters = [];
        foreach ($values as $value) {
            $parameters[$value['id']] = [
                'id' => $value['id'],
                'operator' => $value['operator'],
                'type' => $value['type'],
                'value' => $value['value'],
            ];
        }

        return $parameters;
    }
}
