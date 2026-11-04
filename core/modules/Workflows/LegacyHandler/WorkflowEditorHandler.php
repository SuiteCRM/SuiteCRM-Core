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

namespace App\Module\Workflows\LegacyHandler;

use AOW_WorkFlow;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The workflow editor of SuiteCRM 8, as the legacy one (AOW_WorkFlow EditView:
 * conditionLines.js, actionLines.js and the action classes of AOW_Actions/actions): details of
 * the workflow, its conditions (on a field of the workflow module or of a related module) and its
 * actions (Create Record, Modify Record, Send Email, Calculate Fields) with their parameters.
 *
 * The page gets the workflow and the choices of the editor as data, and the workflow is saved by
 * AOW_WorkFlow::save() with the request the legacy form posts (its conditions and actions are
 * saved by the save_lines() of AOW_Condition and AOW_Action).
 */
class WorkflowEditorHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'workflow-editor';

    // Choices of the legacy editor by field type (AOW_WorkFlowController getModuleOperatorField,
    // getFieldTypeOptions, getActionFieldTypeOptions; getValidFieldsTypes()).
    protected const NUMBER_TYPES = ['double', 'decimal', 'float', 'currency', 'uint', 'ulong', 'long', 'short', 'tinyint', 'int'];
    protected const DATE_TYPES = ['date', 'datetime', 'datetimecombo'];
    protected const COMPARE_OPERATORS = ['Equal_To', 'Not_Equal_To', 'Greater_Than', 'Less_Than', 'Greater_Than_or_Equal_To', 'Less_Than_or_Equal_To', 'is_null'];
    protected const LIST_OPERATORS = ['Equal_To', 'Not_Equal_To', 'is_null'];
    protected const TEXT_OPERATORS = ['Equal_To', 'Not_Equal_To', 'Contains', 'Starts_With', 'Ends_With', 'is_null'];

    /** The actions of the editor (AOW_Actions/actions.php); a line of another action is kept as it is. */
    protected const ACTIONS = ['CreateRecord', 'ModifyRecord', 'SendEmail', 'ComputeField'];

    /** Value types of an action field that take the users to assign (getAssignField()). */
    protected const ASSIGN_TYPES = ['Round_Robin', 'Least_Busy', 'Random'];

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
     * The workflow to edit (a new workflow without id; a copy of the workflow, without ids, to
     * duplicate it) with the choices of the editor; null when the user cannot edit it.
     */
    public function load(string $id, bool $duplicate): ?array
    {
        return $this->inLegacy(function () use ($id, $duplicate) {
            global $current_user;

            $workflow = $id !== '' ? BeanFactory::getBean('AOW_WorkFlow', $id) : BeanFactory::newBean('AOW_WorkFlow');
            if (!$workflow instanceof AOW_WorkFlow || ($id !== '' && empty($workflow->id))
                || ($id !== '' && !$workflow->ACLAccess($duplicate ? 'view' : 'edit'))
                || (($id === '' || $duplicate) && !BeanFactory::newBean('AOW_WorkFlow')->ACLAccess('edit'))) {
                return null;
            }

            $workflow->load_flow_beans();
            $flowModule = (string)$workflow->flow_module;
            $assignedUserId = $id !== '' ? (string)$workflow->assigned_user_id : (string)$current_user->id;

            return [
                'id' => $duplicate ? '' : (string)$workflow->id,
                'name' => $this->decode($workflow->name),
                'assignedUserId' => $assignedUserId,
                'assignedUserName' => $assignedUserId !== '' ? $this->decode(get_assigned_user_name($assignedUserId)) : '',
                'flowModule' => $flowModule,
                'status' => $id !== '' ? (string)$workflow->status : 'Active',
                'runWhen' => $id !== '' ? (string)$workflow->run_when : 'Always',
                'flowRunOn' => $id !== '' ? (string)$workflow->flow_run_on : 'All_Records',
                'multipleRuns' => $id !== '' && !empty($workflow->multiple_runs),
                'runOnImport' => $id !== '' && !empty($workflow->run_on_import),
                'description' => $this->decode($workflow->description),
                'conditions' => $id !== '' ? $this->loadConditions($workflow, $duplicate) : [],
                'actions' => $id !== '' ? $this->loadActions($workflow, $duplicate) : [],
                'options' => $this->getOptions(),
                'module' => $this->describeModule($flowModule),
                'labels' => $this->getLabels(),
            ];
        });
    }

    /**
     * The workflow with its conditions and actions, for the record view (view access).
     */
    public function view(string $id): ?array
    {
        return $this->inLegacy(function () use ($id) {
            $workflow = BeanFactory::getBean('AOW_WorkFlow', $id);
            if (!$workflow instanceof AOW_WorkFlow || empty($workflow->id) || !$workflow->ACLAccess('view')) {
                return null;
            }

            $workflow->load_flow_beans();

            return [
                'id' => (string)$workflow->id,
                'flowModule' => (string)$workflow->flow_module,
                'conditions' => $this->loadConditions($workflow, false),
                'actions' => $this->loadActions($workflow, false),
                'options' => $this->getOptions(),
                'module' => $this->describeModule((string)$workflow->flow_module),
                'labels' => $this->getLabels(),
            ];
        });
    }

    /**
     * The choices of the editor that depend on the workflow module.
     */
    public function module(string $flowModule): ?array
    {
        return $this->inLegacy(fn() => $this->isFlowModule($flowModule) ? $this->describeModule($flowModule) : null);
    }

    /**
     * The module a Create Record or Modify Record action changes: its fields and relationships.
     * For Modify Record, the module is given by the relationship of the workflow module.
     */
    public function target(string $flowModule, string $module, string $relType): ?array
    {
        return $this->inLegacy(function () use ($flowModule, $module, $relType) {
            if (!$this->isFlowModule($flowModule)) {
                return null;
            }
            if ($relType !== '') {
                if (!$this->isPath($flowModule, $relType)) {
                    return null;
                }
                $module = (string)getRelatedModule($flowModule, $relType);
            } elseif (!$this->isFlowModule($module)) {
                return null;
            }

            return $this->describeTarget($module);
        });
    }

    /**
     * What a line on a field offers: a condition (operators, value types, input of a value), a
     * field of an action (value types, input) or a relationship of an action (input).
     */
    public function field(string $flowModule, string $kind, string $module, string $field): ?array
    {
        return $this->inLegacy(function () use ($flowModule, $kind, $module, $field) {
            if (!$this->isFlowModule($flowModule)) {
                return null;
            }

            switch ($kind) {
                case 'condition':
                    return $this->isPath($flowModule, $module) && $this->isField((string)getRelatedModule($flowModule, $module), $field)
                        ? $this->describeConditionField($flowModule, $module, $field) : null;
                case 'action':
                    return $this->isFlowModule($module) && $this->isField($module, $field)
                        ? $this->describeActionField($flowModule, $module, $field) : null;
                case 'relation':
                    return $this->isFlowModule($module) && $this->isRelationship($module, $field)
                        ? $this->describeRelation($flowModule, $module, $field) : null;
                default:
                    return null;
            }
        });
    }

    /**
     * Saves the workflow (a new one without id) with its conditions and actions; returns its id,
     * or null when the user cannot save it or a value is not valid.
     */
    public function save(string $id, array $data): ?string
    {
        return $this->inLegacy(function () use ($id, $data) {
            global $app_list_strings;

            $workflow = $id !== '' ? BeanFactory::getBean('AOW_WorkFlow', $id) : BeanFactory::newBean('AOW_WorkFlow');
            if (!$workflow instanceof AOW_WorkFlow || ($id !== '' && empty($workflow->id)) || !$workflow->ACLAccess('edit')) {
                return null;
            }

            $workflow->load_flow_beans();
            $flowModule = (string)($data['flowModule'] ?? '');
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '' || !$this->isFlowModule($flowModule)
                || !$this->isOption($data['status'] ?? '', $app_list_strings['aow_status_list'] ?? [])
                || !$this->isOption($data['runWhen'] ?? '', $app_list_strings['aow_run_when_list'] ?? [])
                || !$this->isOption($data['flowRunOn'] ?? '', $app_list_strings['aow_run_on_list'] ?? [])) {
                return null;
            }

            $conditions = $this->postConditions($workflow, $flowModule, is_array($data['conditions'] ?? null) ? array_values($data['conditions']) : []);
            $actions = $this->postActions($workflow, $flowModule, is_array($data['actions'] ?? null) ? array_values($data['actions']) : []);
            if ($conditions === null || $actions === null) {
                return null;
            }

            $assignedUserId = (string)($data['assignedUserId'] ?? '');
            $workflow->name = $name;
            $workflow->flow_module = $flowModule;
            $workflow->status = (string)$data['status'];
            $workflow->run_when = (string)$data['runWhen'];
            $workflow->flow_run_on = (string)$data['flowRunOn'];
            $workflow->multiple_runs = !empty($data['multipleRuns']) ? 1 : 0;
            $workflow->run_on_import = !empty($data['runOnImport']) ? 1 : 0;
            $workflow->assigned_user_id = $assignedUserId !== '' && BeanFactory::getBean('Users', $assignedUserId) ? $assignedUserId : '';
            $workflow->description = (string)($data['description'] ?? '');

            // AOW_WorkFlow::save() saves the lines from the request, as posted by the legacy form
            // (the module of a condition value from flow_module of the request).
            $previousPost = $_POST;
            $previousRequest = $_REQUEST;
            $_POST = ['record' => (string)$workflow->id, 'flow_module' => $flowModule] + $conditions + $actions;
            $_REQUEST = $_POST + $_REQUEST;
            try {
                $workflow->save();
            } finally {
                $_POST = $previousPost;
                $_REQUEST = $previousRequest;
            }

            return (string)$workflow->id;
        });
    }

    protected function loadConditions(AOW_WorkFlow $workflow, bool $duplicate): array
    {
        $flowModule = (string)$workflow->flow_module;
        $relationships = array_column($this->getRelationships($flowModule, true), 'label', 'value');
        $lines = [];
        foreach ($this->getLines('aow_conditions', 'condition_order', $workflow->id) as $id) {
            $condition = BeanFactory::getBean('AOW_Conditions', $id);
            if (!$condition) {
                continue;
            }
            $path = $this->pathOf((string)$condition->module_path, $flowModule);
            $module = (string)getRelatedModule($flowModule, $path);
            $field = (string)$condition->field;
            $info = $this->describeConditionField($flowModule, $path, $field);
            [$value, $valueLabel] = $this->lineValue((string)$condition->value_type, $condition->value, $info);
            $lines[] = [
                'id' => $duplicate ? '' : (string)$condition->id,
                'path' => $path,
                'pathLabel' => (string)($relationships[$path] ?? $path),
                'field' => $field,
                'fieldLabel' => $this->fieldLabel($module, $field),
                'operator' => (string)$condition->operator,
                'valueType' => (string)$condition->value_type,
                'value' => $value,
                'valueLabel' => $valueLabel,
                'info' => $info,
            ];
        }

        return $lines;
    }

    protected function loadActions(AOW_WorkFlow $workflow, bool $duplicate): array
    {
        $lines = [];
        foreach ($this->getLines('aow_actions', 'action_order', $workflow->id) as $id) {
            $action = BeanFactory::getBean('AOW_Actions', $id);
            if (!$action) {
                continue;
            }
            $params = unserialize(base64_decode((string)$action->parameters), ['allowed_classes' => false]);
            $params = is_array($params) ? $params : [];
            $type = (string)$action->action;
            $lines[] = [
                'id' => $duplicate ? '' : (string)$action->id,
                'name' => $this->decode($action->name),
                'action' => $type,
                'supported' => in_array($type, self::ACTIONS, true),
                'params' => $this->actionParams($type, $params, (string)$workflow->flow_module),
            ];
        }

        return $lines;
    }

    /**
     * The parameters of an action for the editor.
     */
    protected function actionParams(string $type, array $params, string $flowModule): array
    {
        switch ($type) {
            case 'SendEmail':
                return $this->emailParams($params);
            case 'CreateRecord':
                $module = (string)($params['record_type'] ?? '');

                return [
                    'recordType' => $module,
                    'relateToWorkflow' => !isset($params['relate_to_workflow']) || !empty($params['relate_to_workflow']),
                    'copyEmailAddresses' => !empty($params['copy_email_addresses']),
                    'fields' => $module !== '' ? $this->fieldLines($flowModule, $module, $params) : [],
                    'relationships' => $module !== '' ? $this->relationLines($flowModule, $module, $params) : [],
                    'target' => $module !== '' && $this->isFlowModule($module) ? $this->describeTarget($module) : null,
                ];
            case 'ModifyRecord':
                $relType = (string)($params['rel_type'] ?? '') ?: $flowModule;
                $module = (string)getRelatedModule($flowModule, $relType);

                return [
                    'relType' => $relType,
                    'fields' => $this->fieldLines($flowModule, $module, $params),
                    'relationships' => $this->relationLines($flowModule, $module, $params),
                    'target' => $this->describeTarget($module),
                ];
            case 'ComputeField':
                return [
                    'parameters' => $this->computeLines($params, ['field' => 'parameter', 'type' => 'parameterType']),
                    'relationParameters' => $this->computeLines($params, [
                        'relation' => 'relationParameter',
                        'field' => 'relationParameterField',
                        'type' => 'relationParameterType',
                    ]),
                    'formulas' => $this->computeLines($params, ['field' => 'formula', 'formula' => 'formulaContent']),
                ];
            default:
                // An action the editor does not offer: its parameters are kept as they are.
                return ['raw' => base64_encode(serialize($params))];
        }
    }

    /**
     * The lines of Calculate Fields: by line, its values from the parameters of the given names
     * (the first one lists the lines).
     *
     * @param array<string, string> $names line key => parameter name
     */
    protected function computeLines(array $params, array $names): array
    {
        $lines = [];
        foreach (array_keys((array)($params[reset($names)] ?? [])) as $index) {
            $line = [];
            foreach ($names as $key => $name) {
                $line[$key] = (string)($params[$name][$index] ?? '');
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * The recipients of a Send Email action (also the single recipient of the older actions).
     */
    protected function emailParams(array $params): array
    {
        if (isset($params['email_target_type']) && !is_array($params['email_target_type'])) {
            $email = match ($params['email_target_type']) {
                'Specify User' => $params['email_user_id'] ?? '',
                'Related Field' => $params['email_target'] ?? '',
                default => $params['email'] ?? '',
            };
            $params['email_target_type'] = [$params['email_target_type']];
            $params['email_to_type'] = ['to'];
            $params['email'] = [$email];
        }

        $recipients = [];
        foreach ((array)($params['email_target_type'] ?? []) as $key => $type) {
            $value = $params['email'][$key] ?? '';
            $label = '';
            if ($type === 'Users') {
                $value = array_map('strval', array_pad(is_array($value) ? array_values($value) : [], 3, ''));
            } else {
                $value = (string)$value;
                if ($type === 'Specify User' && $value !== '') {
                    $label = $this->decode(get_assigned_user_name($value));
                }
            }
            $recipients[] = [
                'to' => (string)($params['email_to_type'][$key] ?? 'to'),
                'type' => (string)$type,
                'value' => $value,
                'valueLabel' => $label,
            ];
        }

        return [
            'individualEmail' => !empty($params['individual_email']),
            'emailTemplate' => (string)($params['email_template'] ?? ''),
            'recipients' => $recipients,
        ];
    }

    /**
     * The field lines of a Create Record or Modify Record action.
     */
    protected function fieldLines(string $flowModule, string $module, array $params): array
    {
        $lines = [];
        foreach ((array)($params['field'] ?? []) as $key => $field) {
            $field = (string)$field;
            $info = $this->describeActionField($flowModule, $module, $field);
            [$value, $valueLabel] = $this->lineValue((string)($params['value_type'][$key] ?? 'Value'), $params['value'][$key] ?? '', $info);
            $lines[] = [
                'field' => $field,
                'fieldLabel' => $this->fieldLabel($module, $field),
                'valueType' => (string)($params['value_type'][$key] ?? 'Value'),
                'value' => $value,
                'valueLabel' => $valueLabel,
                'info' => $info,
            ];
        }

        return $lines;
    }

    /**
     * The relationship lines of a Create Record or Modify Record action.
     */
    protected function relationLines(string $flowModule, string $module, array $params): array
    {
        $relationships = array_column($this->getRelationships($module), 'label', 'value');
        $lines = [];
        foreach ((array)($params['rel'] ?? []) as $key => $link) {
            $link = (string)$link;
            $info = $this->describeRelation($flowModule, $module, $link);
            [$value, $valueLabel] = $this->lineValue((string)($params['rel_value_type'][$key] ?? 'Value'), $params['rel_value'][$key] ?? '', $info);
            $lines[] = [
                'rel' => $link,
                'relLabel' => (string)($relationships[$link] ?? $link),
                'valueType' => (string)($params['rel_value_type'][$key] ?? 'Value'),
                'value' => $value,
                'valueLabel' => $valueLabel,
                'info' => $info,
            ];
        }

        return $lines;
    }

    /**
     * The value of a line for the editor (the parts of a date, the users to assign, the values
     * of a list, a date and time of the user), and the name of the record of a relate field.
     *
     * @return array{0: string|string[], 1: string}
     */
    protected function lineValue(string $valueType, $raw, array $info): array
    {
        switch ($valueType) {
            case 'Date':
                $parts = is_array($raw) ? $raw : unserialize(base64_decode((string)$raw), ['allowed_classes' => false]);

                return [array_map('strval', array_pad(is_array($parts) ? array_values($parts) : [], 4, '')), ''];
            case 'Round_Robin':
            case 'Least_Busy':
            case 'Random':
                return [array_map('strval', array_pad(is_array($raw) ? array_values($raw) : [], 3, '')), ''];
            case 'Multi':
                return [is_array($raw) ? array_values(array_map('strval', $raw)) : array_values(array_map('strval', unencodeMultienum($this->decode($raw)))), ''];
            case 'Any_Change':
                return ['', ''];
            case 'SecurityGroup':
                $id = $this->decode(is_scalar($raw) ? $raw : '');

                return [$id, $id !== '' ? $this->recordName('SecurityGroups', $id) : ''];
            case 'Field':
                return [$this->decode(is_scalar($raw) ? $raw : ''), ''];
            default:
                if ($info['input'] === 'multi') {
                    return [is_array($raw) ? array_values(array_map('strval', $raw)) : array_values(array_map('strval', unencodeMultienum($this->decode($raw)))), ''];
                }
                $value = is_scalar($raw) ? $this->decode($raw) : '';
                if ($info['input'] === 'bool') {
                    return [$value !== '' && $value !== '0' && strtolower($value) !== 'false' ? '1' : '0', ''];
                }
                if ($info['input'] === 'datetime' && $value !== '') {
                    return [$this->toUserDateTime($value), ''];
                }
                if ($info['input'] === 'date') {
                    return [substr($value, 0, 10), ''];
                }
                if ($info['input'] === 'relate' && $value !== '') {
                    return [$value, $this->recordName($info['legacyModule'], $value)];
                }

                return [$value, ''];
        }
    }

    /**
     * The condition lines as the legacy form posts them (aow_conditions_*), with the existing
     * lines removed in the page marked deleted; null when a line is not valid.
     */
    protected function postConditions(AOW_WorkFlow $workflow, string $flowModule, array $lines): ?array
    {
        $post = [];
        $existing = $this->existingLines('aow_conditions', $workflow);
        $kept = [];
        foreach ($lines as $index => $line) {
            if (!is_array($line)) {
                return null;
            }
            $id = in_array((string)($line['id'] ?? ''), $existing, true) ? (string)$line['id'] : '';
            $path = (string)($line['path'] ?? '') ?: $flowModule;
            $field = (string)($line['field'] ?? '');
            $operator = (string)($line['operator'] ?? '');
            $valueType = (string)($line['valueType'] ?? '');
            if (!$this->isPath($flowModule, $path) || !$this->isField((string)getRelatedModule($flowModule, $path), $field)) {
                return null;
            }
            $info = $this->describeConditionField($flowModule, $path, $field);
            if (!in_array($operator, array_column($info['operators'], 'value'), true)
                || !in_array($valueType, array_column($info['valueTypes'], 'value'), true)) {
                return null;
            }
            $value = $this->postValue($flowModule, $info, $valueType, $line['value'] ?? '');
            if ($value === null) {
                return null;
            }

            $kept[] = $id;
            $post['aow_conditions_id'][$index] = $id;
            $post['aow_conditions_deleted'][$index] = '0';
            $post['aow_conditions_module_path'][$index] = [$path];
            $post['aow_conditions_field'][$index] = $field;
            $post['aow_conditions_operator'][$index] = $operator;
            $post['aow_conditions_value_type'][$index] = $valueType;
            $post['aow_conditions_value'][$index] = $value;
        }

        $index = count($lines);
        foreach (array_diff($existing, $kept) as $id) {
            $post['aow_conditions_id'][$index] = $id;
            $post['aow_conditions_deleted'][$index] = '1';
            $post['aow_conditions_field'][$index] = '';
            $index++;
        }

        return $post;
    }

    /**
     * The action lines as the legacy form posts them (aow_actions_*), with the existing lines
     * removed in the page marked deleted; null when a line is not valid.
     */
    protected function postActions(AOW_WorkFlow $workflow, string $flowModule, array $lines): ?array
    {
        $post = [];
        $existing = $this->existingLines('aow_actions', $workflow);
        $kept = [];
        foreach ($lines as $index => $line) {
            if (!is_array($line)) {
                return null;
            }
            $id = in_array((string)($line['id'] ?? ''), $existing, true) ? (string)$line['id'] : '';
            $type = (string)($line['action'] ?? '');
            $params = is_array($line['params'] ?? null) ? $line['params'] : [];
            if (in_array($type, self::ACTIONS, true)) {
                $posted = $this->postActionParams($type, $flowModule, $params);
            } else {
                // An action the editor does not offer keeps its parameters, as they were loaded.
                $posted = $id !== '' ? $this->keptParams($id, (string)($params['raw'] ?? '')) : null;
            }
            if ($posted === null) {
                return null;
            }

            $kept[] = $id;
            $post['aow_actions_id'][$index] = $id;
            $post['aow_actions_deleted'][$index] = '0';
            $post['aow_actions_action'][$index] = $type;
            $post['aow_actions_name'][$index] = trim((string)($line['name'] ?? ''));
            $post['aow_actions_param'][$index] = $posted;
        }

        $index = count($lines);
        foreach (array_diff($existing, $kept) as $id) {
            $post['aow_actions_id'][$index] = $id;
            $post['aow_actions_deleted'][$index] = '1';
            $post['aow_actions_action'][$index] = '';
            $index++;
        }

        return $post;
    }

    /**
     * The parameters of an action as the legacy form posts them (aow_actions_param[line]); null
     * when one is not valid.
     */
    protected function postActionParams(string $type, string $flowModule, array $params): ?array
    {
        switch ($type) {
            case 'SendEmail':
                return $this->postEmailParams($flowModule, $params);
            case 'CreateRecord':
                $module = (string)($params['recordType'] ?? '');
                if (!$this->isFlowModule($module)) {
                    return null;
                }
                $lines = $this->postRecordLines($flowModule, $module, $params);

                return $lines === null ? null : [
                    'record_type' => $module,
                    'relate_to_workflow' => !empty($params['relateToWorkflow']) ? '1' : '0',
                    'copy_email_addresses' => !empty($params['copyEmailAddresses']) ? '1' : '0',
                ] + $lines;
            case 'ModifyRecord':
                $relType = (string)($params['relType'] ?? '') ?: $flowModule;
                if (!$this->isPath($flowModule, $relType)) {
                    return null;
                }
                $lines = $this->postRecordLines($flowModule, (string)getRelatedModule($flowModule, $relType), $params);

                return $lines === null ? null : ['record_type' => $flowModule, 'rel_type' => $relType] + $lines;
            default:
                return $this->postComputeParams($flowModule, $params);
        }
    }

    protected function postEmailParams(string $flowModule, array $params): ?array
    {
        $template = (string)($params['emailTemplate'] ?? '');
        $recipients = is_array($params['recipients'] ?? null) ? array_values($params['recipients']) : [];
        if ($template === '' || !$this->recordExists('EmailTemplates', $template) || !$recipients) {
            return null;
        }

        $types = array_column($this->getEmailTypes($flowModule), 'value');
        $relatedFields = array_column($this->choices(getRelatedEmailableFields($flowModule)), 'value');
        $post = ['individual_email' => !empty($params['individualEmail']) ? '1' : '0', 'email_template' => $template];
        foreach ($recipients as $index => $recipient) {
            $to = (string)($recipient['to'] ?? 'to');
            $type = (string)($recipient['type'] ?? '');
            $value = $recipient['value'] ?? '';
            if (!in_array($to, ['to', 'cc', 'bcc'], true) || !in_array($type, $types, true)) {
                return null;
            }
            switch ($type) {
                case 'Users':
                    $value = $this->postAssignValue($value);
                    break;
                case 'Related Field':
                    $value = in_array((string)$value, $relatedFields, true) ? (string)$value : null;
                    break;
                case 'Specify User':
                    $value = is_scalar($value) && $this->recordExists('Users', (string)$value) ? (string)$value : null;
                    break;
                case 'Email Address':
                    $value = is_scalar($value) && trim((string)$value) !== '' ? trim((string)$value) : null;
                    break;
                default:
                    $value = '';
            }
            if ($value === null) {
                return null;
            }
            $post['email_to_type'][$index] = $to;
            $post['email_target_type'][$index] = $type;
            $post['email'][$index] = $value;
        }

        return $post;
    }

    /**
     * The fields and relationships of a Create Record or Modify Record action as posted.
     */
    protected function postRecordLines(string $flowModule, string $module, array $params): ?array
    {
        $post = [];
        foreach (is_array($params['fields'] ?? null) ? array_values($params['fields']) : [] as $index => $line) {
            $field = (string)($line['field'] ?? '');
            $valueType = (string)($line['valueType'] ?? '');
            if (!$this->isField($module, $field)) {
                return null;
            }
            $info = $this->describeActionField($flowModule, $module, $field);
            if (!in_array($valueType, array_column($info['valueTypes'], 'value'), true)) {
                return null;
            }
            $value = in_array($valueType, self::ASSIGN_TYPES, true)
                ? $this->postAssignValue($line['value'] ?? [])
                : $this->postValue($flowModule, $info, $valueType, $line['value'] ?? '');
            if ($value === null) {
                return null;
            }
            $post['field'][$index] = $field;
            $post['value_type'][$index] = $valueType;
            $post['value'][$index] = $value;
        }

        foreach (is_array($params['relationships'] ?? null) ? array_values($params['relationships']) : [] as $index => $line) {
            $link = (string)($line['rel'] ?? '');
            $valueType = (string)($line['valueType'] ?? '');
            if (!$this->isRelationship($module, $link) || !in_array($valueType, ['Value', 'Field'], true)) {
                return null;
            }
            $value = $line['value'] ?? '';
            if (!is_scalar($value) || ($valueType === 'Field' && (string)$value !== ''
                    && !in_array((string)$value, array_column($this->getModuleFieldChoices($flowModule), 'value'), true))) {
                return null;
            }
            $post['rel'][$index] = $link;
            $post['rel_value_type'][$index] = $valueType;
            $post['rel_value'][$index] = (string)$value;
        }

        return $post;
    }

    protected function postComputeParams(string $flowModule, array $params): ?array
    {
        $fields = array_column($this->getModuleFieldChoices($flowModule), 'value');
        $relations = [];
        foreach ($this->getComputeRelations($flowModule) as $relation) {
            $relations[$relation['value']] = array_column($relation['fields'], 'value');
        }

        $post = [];
        foreach (is_array($params['parameters'] ?? null) ? array_values($params['parameters']) : [] as $index => $line) {
            if (!in_array((string)($line['field'] ?? ''), $fields, true) || !in_array($line['type'] ?? '', ['raw', 'formatted'], true)) {
                return null;
            }
            $post['parameter'][$index] = (string)$line['field'];
            $post['parameterType'][$index] = (string)$line['type'];
        }
        foreach (is_array($params['relationParameters'] ?? null) ? array_values($params['relationParameters']) : [] as $index => $line) {
            $relation = (string)($line['relation'] ?? '');
            if (!in_array((string)($line['field'] ?? ''), $relations[$relation] ?? [], true)
                || !in_array($line['type'] ?? '', ['raw', 'formatted'], true)) {
                return null;
            }
            $post['relationParameter'][$index] = $relation;
            $post['relationParameterField'][$index] = (string)$line['field'];
            $post['relationParameterType'][$index] = (string)$line['type'];
        }
        foreach (is_array($params['formulas'] ?? null) ? array_values($params['formulas']) : [] as $index => $line) {
            if (!in_array((string)($line['field'] ?? ''), $fields, true) || !is_scalar($line['formula'] ?? null)) {
                return null;
            }
            $post['formula'][$index] = (string)$line['field'];
            $post['formulaContent'][$index] = (string)$line['formula'];
        }

        return $post;
    }

    /**
     * The value of a line as the legacy form posts it; null when it is not one the editor offers.
     *
     * @return string|string[]|null
     */
    protected function postValue(string $flowModule, array $info, string $valueType, $value)
    {
        global $app_list_strings;

        switch ($valueType) {
            case 'Date':
                $parts = array_values(array_map('strval', array_pad(is_array($value) ? $value : [], 4, '')));
                $bases = array_column($info['dateBases'] ?? [], 'value');
                if (!in_array($parts[0], $bases, true) || !isset(($app_list_strings['aow_date_operator'] ?? [])[$parts[1]])
                    || ($parts[1] !== 'now' && (!preg_match('/^\d*$/', $parts[2]) || !isset(($app_list_strings['aow_date_type_list'] ?? [])[$parts[3]])))) {
                    return null;
                }

                return array_slice($parts, 0, 4);
            case 'Multi':
                $values = array_values(array_map('strval', is_array($value) ? $value : []));

                return array_diff($values, array_column($info['options'], 'value')) ? null : $values;
            case 'Field':
                return (string)$value === '' || in_array((string)$value, array_column($info['fields'] ?? [], 'value'), true)
                    ? (string)$value : null;
            case 'Any_Change':
                return '';
            case 'SecurityGroup':
                return is_scalar($value) && ((string)$value === '' || $this->recordExists('SecurityGroups', (string)$value)) ? (string)$value : null;
            default:
                if ($info['input'] === 'multi') {
                    $values = array_values(array_map('strval', is_array($value) ? $value : []));

                    return array_diff($values, array_column($info['options'], 'value')) ? null : $values;
                }
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
                if ($info['input'] === 'number' && $value !== '') {
                    // fixUpFormatting() reads a number in the format of the user.
                    if (!is_numeric($value)) {
                        return null;
                    }
                    global $current_user;
                    $separator = $current_user->getPreference('dec_sep') ?: '.';

                    return str_replace('.', $separator, $value);
                }

                return $value;
        }
    }

    /**
     * The users to assign or to email (getAssignField()): all users, those of a role, or those of
     * a security group (and of a role).
     */
    protected function postAssignValue($value): ?array
    {
        global $app_list_strings;

        $parts = array_values(array_map('strval', array_pad(is_array($value) ? $value : [], 3, '')));
        if (!isset(($app_list_strings['aow_assign_options'] ?? [])[$parts[0]])
            || ($parts[0] === 'security_group' && !$this->recordExists('SecurityGroups', $parts[1]))
            || ($parts[0] === 'role' && !$this->recordExists('ACLRoles', $parts[2]))
            || ($parts[2] !== '' && !$this->recordExists('ACLRoles', $parts[2]))) {
            return null;
        }

        return array_slice($parts, 0, 3);
    }

    /**
     * The parameters of a saved action the editor does not offer, unchanged.
     */
    protected function keptParams(string $id, string $raw): ?array
    {
        $action = BeanFactory::getBean('AOW_Actions', $id);
        if (!$action || base64_encode(serialize($this->savedParams($action))) !== $raw) {
            return null;
        }

        return $this->savedParams($action);
    }

    protected function savedParams(\AOW_Action $action): array
    {
        $params = unserialize(base64_decode((string)$action->parameters), ['allowed_classes' => false]);

        return is_array($params) ? $params : [];
    }

    /**
     * The choices of a condition on a field (legacy getModuleOperatorField, getFieldTypeOptions,
     * getModuleFieldType): type, operators, value types, input of a value, list options, the
     * module of a relate field (record picker), the fields of the workflow module (a value of the
     * "Field" type) and the bases of a date.
     */
    protected function describeConditionField(string $flowModule, string $path, string $field): array
    {
        global $app_list_strings;

        $info = $this->describeInput((string)getRelatedModule($flowModule, $path), $field);
        $type = $info['type'];

        if (in_array($type, self::NUMBER_TYPES, true) || in_array($type, self::DATE_TYPES, true)) {
            $operators = self::COMPARE_OPERATORS;
        } elseif (in_array($type, ['enum', 'multienum'], true)) {
            $operators = self::LIST_OPERATORS;
        } else {
            $operators = self::TEXT_OPERATORS;
        }

        $valueTypes = ['Value', 'Field', 'Any_Change'];
        if (in_array($type, self::DATE_TYPES, true)) {
            $valueTypes[] = 'Date';
        } elseif (in_array($type, ['enum', 'dynamicenum', 'multienum'], true)) {
            $valueTypes[] = 'Multi';
        } elseif (in_array($type, ['relate', 'id'], true)) {
            $valueTypes[] = 'SecurityGroup';
        }

        return $info + [
            'operators' => $this->choices(array_intersect_key($app_list_strings['aow_operator_list'] ?? [], array_flip($operators))),
            'valueTypes' => $this->choices(array_intersect_key($app_list_strings['aow_condition_type_list'] ?? [], array_flip($valueTypes))),
            'fields' => $this->getModuleFieldChoices($flowModule, $this->validTypes($type)),
            'dateBases' => $this->getDateBases($flowModule, false),
        ];
    }

    /**
     * The choices of a field set by a Create Record or Modify Record action (legacy
     * getActionFieldTypeOptions, getModuleFieldTypeSet).
     */
    protected function describeActionField(string $flowModule, string $module, string $field): array
    {
        global $app_list_strings;

        $info = $this->describeInput($module, $field);
        $type = $info['type'];

        $valueTypes = ['Value', 'Field'];
        if (in_array($type, self::DATE_TYPES, true)) {
            $valueTypes[] = 'Date';
        } elseif ($type === 'relate' && $info['legacyModule'] === 'Users') {
            $valueTypes = array_merge($valueTypes, self::ASSIGN_TYPES);
        }

        return $info + [
            'valueTypes' => $this->choices(array_intersect_key($app_list_strings['aow_action_type_list'] ?? [], array_flip($valueTypes))),
            'fields' => $this->getModuleFieldChoices($flowModule, $this->validTypes($type)),
            'dateBases' => $this->getDateBases($flowModule, true),
        ];
    }

    /**
     * The choices of a relationship set by a Create Record or Modify Record action (legacy
     * getRelActionFieldTypeOptions, getRelFieldTypeSet): a record of the related module, or a
     * field of the workflow module.
     */
    protected function describeRelation(string $flowModule, string $module, string $link): array
    {
        global $app_list_strings;

        return $this->describeInput($module, $link) + [
            'valueTypes' => $this->choices($app_list_strings['aow_rel_action_type_list'] ?? []),
            'fields' => $this->getModuleFieldChoices($flowModule),
        ];
    }

    /**
     * The input of a value of a field (legacy getModuleField()): type, input, list options and
     * the module of a relate field.
     */
    protected function describeInput(string $module, string $field): array
    {
        global $app_list_strings;

        $bean = BeanFactory::newBean($module);
        $definition = $bean ? ($bean->field_defs[$field] ?? []) : [];
        $type = (string)($definition['type'] ?? '');

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
        } elseif ($type === 'text') {
            $input = 'textarea';
        }

        $optionsName = $type === 'parent_type' ? ($bean->field_defs[$definition['group'] ?? '']['options'] ?? '') : ($definition['options'] ?? '');
        $options = is_string($optionsName) ? ($app_list_strings[$optionsName] ?? []) : [];

        return [
            'type' => $type,
            'input' => $input,
            'module' => $relatedModule !== '' ? $this->moduleNameMapper->toFrontEnd($relatedModule) : '',
            'legacyModule' => $relatedModule,
            'options' => $this->choices(is_array($options) ? $options : []),
        ];
    }

    /**
     * The choices of the editor that depend on the workflow module: its label, its relationships
     * (the module of a condition, the record of Modify Record), the recipients of an email, and
     * the fields and relations of Calculate Fields.
     */
    protected function describeModule(string $flowModule): array
    {
        global $app_list_strings;

        if ($flowModule === '' || !$this->isFlowModule($flowModule)) {
            return ['name' => '', 'label' => '', 'relationships' => [], 'emailTypes' => [], 'emailFields' => [], 'fields' => [], 'computeRelations' => []];
        }

        return [
            'name' => $flowModule,
            'label' => $this->decode($app_list_strings['moduleList'][$flowModule] ?? $flowModule),
            'relationships' => $this->getRelationships($flowModule, true),
            'emailTypes' => $this->getEmailTypes($flowModule),
            'emailFields' => $this->choices(getRelatedEmailableFields($flowModule)),
            'fields' => $this->getModuleFieldChoices($flowModule),
            'computeRelations' => $this->getComputeRelations($flowModule),
        ];
    }

    /**
     * The module a Create Record or Modify Record action changes: its fields (the email fields
     * too, legacy override) and its relationships.
     */
    protected function describeTarget(string $module): array
    {
        global $app_list_strings;

        $fields = json_decode((string)getModuleFields($module, 'JSON', '', [], ['email1', 'email2']), true) ?: [];
        unset($fields['']);

        return [
            'name' => $module,
            'label' => $this->decode($app_list_strings['moduleList'][$module] ?? $module),
            'fields' => $this->choices($fields),
            'relationships' => $this->getRelationships($module),
        ];
    }

    /**
     * The relationships of a module (legacy getModuleRelationships()): the module itself first
     * (when asked), then its links with their module, by label.
     */
    protected function getRelationships(string $module, bool $withModule = false): array
    {
        global $app_list_strings;

        $bean = $module !== '' ? BeanFactory::newBean($module) : null;
        if (!$bean) {
            return [];
        }

        $choices = [];
        foreach ($bean->get_linked_fields() as $name => $definition) {
            $related = (string)($definition['module'] ?? '');
            if ($related === '' && $bean->load_relationship($name)) {
                $related = (string)$bean->$name->getRelatedModuleName();
            }
            $relatedLabel = $app_list_strings['moduleList'][$related] ?? $related;
            $label = !empty($definition['vname']) ? translate($definition['vname'], $bean->module_dir) : $name;
            $choices[] = ['value' => (string)$name, 'label' => $this->decode($relatedLabel . ' : ' . $label), 'module' => $related];
        }
        usort($choices, static fn($a, $b) => strcmp($a['label'], $b['label']));
        if ($withModule) {
            array_unshift($choices, ['value' => $module, 'label' => $this->decode($app_list_strings['moduleList'][$module] ?? $module), 'module' => $module]);
        }

        return $choices;
    }

    /**
     * The fields of a module (getModuleFields()), only those of the given types when given.
     */
    protected function getModuleFieldChoices(string $module, array $types = []): array
    {
        if ($module === '') {
            return [];
        }
        $fields = json_decode((string)getModuleFields($module, 'JSON', '', $types), true) ?: [];
        unset($fields['']);
        $definitions = BeanFactory::newBean($module)->field_defs ?? [];
        $choices = [];
        foreach ($fields as $name => $label) {
            $choices[] = ['value' => (string)$name, 'label' => $this->decode($label), 'type' => (string)($definitions[$name]['type'] ?? '')];
        }

        return $choices;
    }

    /**
     * The types of the fields a value of the "Field" type can come from (getValidFieldsTypes()).
     *
     * @return string[]
     */
    protected function validTypes(string $type): array
    {
        if (in_array($type, ['double', 'decimal', 'float', 'currency'], true)) {
            return ['double', 'decimal', 'float', 'currency'];
        }
        if (in_array($type, ['uint', 'ulong', 'long', 'short', 'tinyint', 'int'], true)) {
            return ['uint', 'ulong', 'long', 'short', 'tinyint', 'int'];
        }
        if (in_array($type, self::DATE_TYPES, true)) {
            return self::DATE_TYPES;
        }
        if (in_array($type, ['id', 'relate', 'link'], true)) {
            return ['relate', 'id'];
        }

        return [];
    }

    /**
     * Bases of a relative date: now, today, the field itself (actions) and the dates of the
     * workflow module (getDateFields()).
     */
    protected function getDateBases(string $flowModule, bool $fieldOption): array
    {
        global $app_list_strings;

        $bases = $app_list_strings['aow_date_options'] ?? [];
        if (!$fieldOption) {
            unset($bases['field']);
        }
        foreach (BeanFactory::newBean($flowModule)->field_defs ?? [] as $name => $definition) {
            if (in_array($definition['type'] ?? '', self::DATE_TYPES, true)) {
                $bases[$name] = trim((string)translate($definition['vname'] ?? $name, $flowModule), ':');
            }
        }

        return $this->choices($bases);
    }

    /**
     * The kinds of recipients of an email (actionSendEmail::edit_display()): the email of the
     * record only for a module with emails, a related field only when the module has one.
     */
    protected function getEmailTypes(string $flowModule): array
    {
        global $app_list_strings;

        $types = $app_list_strings['aow_email_type_list'] ?? [];
        if (!in_array($flowModule, getEmailableModules(), true)) {
            unset($types['Record Email']);
        }
        if (!getRelatedEmailableFields($flowModule)) {
            unset($types['Related Field']);
        }

        return $this->choices($types);
    }

    /**
     * The relations of Calculate Fields (actionComputeField::getOneRelationOptions()): the links
     * to one record and the relate fields, with the fields of their module.
     */
    protected function getComputeRelations(string $flowModule): array
    {
        $bean = BeanFactory::newBean($flowModule);
        if (!$bean) {
            return [];
        }

        $relations = [];
        $added = [];
        foreach ($bean->get_linked_fields() as $definition) {
            $name = (string)($definition['name'] ?? '');
            if (($definition['link_type'] ?? '') !== 'one' || in_array($name, $added, true) || !$bean->load_relationship($name)) {
                continue;
            }
            $relationship = $bean->$name->relationship->def ?? [];
            $module = ($relationship['lhs_module'] ?? '') === $bean->module_name ? ($relationship['rhs_module'] ?? '') : ($relationship['lhs_module'] ?? '');
            $relations[] = $this->computeRelation($name, (string)($definition['vname'] ?? $name), $flowModule, (string)$module);
            $added[] = $name;
        }
        foreach ($bean->field_defs as $name => $definition) {
            if (($definition['type'] ?? '') !== 'relate' || empty($definition['module'])
                || (isset($definition['link']) && in_array($definition['link'], $added, true))
                || (isset($definition['group']) && in_array($bean->field_defs[$definition['group']]['link'] ?? '', $added, true))
                || ($definition['link_type'] ?? '') === 'relationship_info') {
                continue;
            }
            $relations[] = $this->computeRelation((string)$name, (string)($definition['vname'] ?? $name), $flowModule, (string)$definition['module']);
            if (isset($definition['link'])) {
                $added[] = $definition['link'];
            }
        }

        return array_values(array_filter($relations, static fn($relation) => $relation['fields'] !== []));
    }

    protected function computeRelation(string $name, string $vname, string $flowModule, string $module): array
    {
        global $app_list_strings;

        $label = translate($vname);
        if ($label === $vname) {
            $label = translate($vname, $flowModule);
        }
        if ($label === $vname && $module !== '') {
            $label = translate($vname, $module);
        }
        $moduleLabel = $app_list_strings['moduleList'][$module] ?? $module;

        return [
            'value' => $name,
            'label' => $this->decode(trim((string)$label, ':') . ' (' . $moduleLabel . ')'),
            'module' => $module,
            'fields' => $module !== '' && BeanFactory::newBean($module) ? $this->getModuleFieldChoices($module) : [],
        ];
    }

    /**
     * The lists of the editor that do not depend on the workflow module.
     */
    protected function getOptions(): array
    {
        global $app_list_strings;

        $dateUnits = $app_list_strings['aow_date_type_list'] ?? [];
        if (!file_exists('modules/AOBH_BusinessHours/AOBH_BusinessHours.php')) {
            unset($dateUnits['business_hours']);
        }
        $actions = [];
        foreach (self::ACTIONS as $action) {
            $actions[$action] = translate('LBL_' . strtoupper($action), 'AOW_Actions');
        }

        return [
            'modules' => $this->choices(array_filter($app_list_strings['aow_moduleList'] ?? [], static fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH)),
            'statuses' => $this->choices($app_list_strings['aow_status_list'] ?? []),
            'runWhen' => $this->choices($app_list_strings['aow_run_when_list'] ?? []),
            'runOn' => $this->choices($app_list_strings['aow_run_on_list'] ?? []),
            'actions' => $this->choices($actions),
            'dateOperators' => $this->choices($app_list_strings['aow_date_operator'] ?? []),
            'dateUnits' => $this->choices($dateUnits),
            'assignOptions' => $this->choices($app_list_strings['aow_assign_options'] ?? []),
            'emailTo' => $this->choices($app_list_strings['aow_email_to_list'] ?? []),
            'emailTemplates' => $this->choices(array_filter(get_bean_select_array(true, 'EmailTemplate', 'name', '', 'name'), static fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH)),
            'roles' => $this->choices(array_filter(get_bean_select_array(true, 'ACLRole', 'name', '', 'name', true), static fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH)),
            'securityGroups' => $this->choices(array_filter(get_bean_select_array(true, 'SecurityGroup', 'name', '', 'name', true), static fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH)),
            'computeTypes' => $this->choices([
                'raw' => translate('LBL_COMPUTEFIELD_RAW_VALUE', 'AOW_Actions'),
                'formatted' => translate('LBL_COMPUTEFIELD_FORMATTED_VALUE', 'AOW_Actions'),
            ]),
        ];
    }

    /**
     * Labels of the legacy editor (AOW_WorkFlow, AOW_Conditions and AOW_Actions strings).
     */
    protected function getLabels(): array
    {
        $labels = [];
        $keys = [
            'AOW_WorkFlow' => ['LBL_NAME', 'LBL_ASSIGNED_TO_NAME', 'LBL_FLOW_MODULE', 'LBL_STATUS', 'LBL_RUN_WHEN', 'LBL_FLOW_RUN_ON',
                'LBL_MULTIPLE_RUNS', 'LBL_RUN_ON_IMPORT', 'LBL_DESCRIPTION', 'LBL_CONDITION_LINES', 'LBL_ADD_CONDITION',
                'LBL_ACTION_LINES', 'LBL_ADD_ACTION'],
            'AOW_Conditions' => ['LBL_MODULE_PATH', 'LBL_FIELD', 'LBL_OPERATOR', 'LBL_VALUE_TYPE', 'LBL_VALUE'],
            'AOW_Actions' => ['LBL_NAME', 'LBL_ACTION', 'LBL_PARAMETERS', 'LBL_SELECT_ACTION', 'LBL_RECORD_TYPE', 'LBL_RELATE_WORKFLOW',
                'LBL_COPY_EMAIL_ADDRESSES_WORKFLOW', 'LBL_ADD_FIELD', 'LBL_ADD_RELATIONSHIP', 'LBL_INDIVIDUAL_EMAILS',
                'LBL_EMAIL_TEMPLATE', 'LBL_EMAIL', 'LBL_EDIT_EMAIL_TEMPLATE', 'LBL_CREATE_EMAIL_TEMPLATE',
                'LBL_COMPUTEFIELD_PARAMETERS', 'LBL_COMPUTEFIELD_FIELD_NAME', 'LBL_COMPUTEFIELD_IDENTIFIER',
                'LBL_COMPUTEFIELD_ADD_PARAMETER', 'LBL_COMPUTEFIELD_RELATION_PARAMETERS', 'LBL_COMPUTEFIELD_RELATION',
                'LBL_COMPUTEFIELD_ADD_RELATION_PARAMETER', 'LBL_COMPUTEFIELD_FORMULA', 'LBL_COMPUTEFIELD_FORMULAS',
                'LBL_COMPUTEFIELD_ADD_FORMULA', 'LBL_COMPUTEFIELD_VALUE_TYPE'],
        ];
        foreach ($keys as $module => $moduleKeys) {
            foreach ($moduleKeys as $key) {
                $labels[$module . '.' . $key] = trim($this->decode(translate($key, $module)), ':');
            }
        }

        return $labels;
    }

    /**
     * Ids of the lines of a workflow (aow_conditions, aow_actions) in their order.
     *
     * @return string[]
     */
    protected function getLines(string $table, string $order, string $workflowId): array
    {
        $db = \DBManagerFactory::getInstance();
        $ids = [];
        $result = $db->query("SELECT id FROM $table WHERE aow_workflow_id = " . $db->quoted($workflowId) . " AND deleted = 0 ORDER BY $order ASC");
        while ($row = $db->fetchByAssoc($result, false)) {
            $ids[] = (string)$row['id'];
        }

        return $ids;
    }

    /**
     * Ids of the lines of a saved workflow: a line of the page keeps its id only when it is one of
     * them, the others are removed.
     *
     * @return string[]
     */
    protected function existingLines(string $table, AOW_WorkFlow $workflow): array
    {
        return empty($workflow->id) ? [] : $this->getLines($table, 'id', $workflow->id);
    }

    protected function isFlowModule(string $module): bool
    {
        global $app_list_strings;

        BeanFactory::newBean('AOW_WorkFlow')->load_flow_beans();

        return $module !== '' && isset(($app_list_strings['aow_moduleList'] ?? [])[$module]);
    }

    /**
     * The workflow module itself or one of its relationships (getModuleRelationships()).
     */
    protected function isPath(string $flowModule, string $path): bool
    {
        return $path === $flowModule || in_array($path, array_column($this->getRelationships($flowModule), 'value'), true);
    }

    protected function isRelationship(string $module, string $link): bool
    {
        return $link !== '' && in_array($link, array_column($this->getRelationships($module), 'value'), true);
    }

    /**
     * A field of a module the editor offers (getModuleFields(), with the email fields).
     */
    protected function isField(string $module, string $field): bool
    {
        if ($field === '' || $module === '') {
            return false;
        }
        $fields = json_decode((string)getModuleFields($module, 'JSON', '', [], ['email1', 'email2']), true) ?: [];

        return isset($fields[$field]);
    }

    /**
     * The module path of a condition (stored serialized: [link or module]) as the editor uses it:
     * the workflow module itself, or one of its links.
     */
    protected function pathOf(string $modulePath, string $flowModule): string
    {
        $path = unserialize(base64_decode($modulePath), ['allowed_classes' => false]);
        $link = is_array($path) ? (string)($path[0] ?? '') : '';

        return $link !== '' ? $link : $flowModule;
    }

    protected function fieldLabel(string $module, string $field): string
    {
        $definition = BeanFactory::newBean($module)->field_defs[$field] ?? [];

        return trim($this->decode(translate($definition['vname'] ?? $field, $module)), ':') ?: $field;
    }

    protected function recordName(string $module, string $id): string
    {
        $record = $module !== '' ? BeanFactory::getBean($module, $id) : null;

        return $record && !empty($record->id) ? $this->decode($record->get_summary_text()) : $id;
    }

    protected function recordExists(string $module, string $id): bool
    {
        if ($id === '') {
            return false;
        }
        $record = BeanFactory::getBean($module, $id);

        return $record && !empty($record->id);
    }

    protected function isOption($value, array $options): bool
    {
        return is_scalar($value) && (string)$value !== '' && array_key_exists((string)$value, $options);
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

            return $callback();
        } finally {
            ob_end_clean();
            restore_error_handler();
            $this->close();
        }
    }
}
