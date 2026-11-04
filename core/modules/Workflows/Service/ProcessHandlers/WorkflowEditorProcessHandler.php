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

namespace App\Module\Workflows\Service\ProcessHandlers;

use App\Module\Workflows\LegacyHandler\WorkflowEditorHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process `workflow-editor` of the workflow editor (WorkflowEditorHandler), with the access
 * of the legacy edit view (edit access to workflows, and to the workflow edited).
 *
 * Options, by action:
 * - load: id (empty for a new workflow), duplicate;
 * - view: id (the conditions and actions shown in the record view, with view access);
 * - module: flowModule;
 * - target: flowModule, module (Create Record) or relType (Modify Record);
 * - field: flowModule, kind (condition, action, relation), module (the path of a condition, the
 *   module of an action), field;
 * - save: id, workflow.
 */
class WorkflowEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = WorkflowEditorHandler::HANDLER_KEY;

    protected const ACTIONS = ['load', 'view', 'module', 'target', 'field', 'save'];

    public function __construct(protected WorkflowEditorHandler $editor)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');
        if (($options['action'] ?? '') === 'view') {
            return ['workflow' => [['action' => 'view', 'record' => $id]]];
        }
        $acls = [['action' => 'edit']];
        if ($id !== '' && in_array($options['action'] ?? '', ['load', 'save'], true)) {
            $acls[] = ['action' => !empty($options['duplicate']) ? 'view' : 'edit', 'record' => $id];
        }

        return ['workflow' => $acls];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();
        $action = $options['action'] ?? '';

        if (!in_array($action, self::ACTIONS, true)
            || (in_array($action, ['module', 'target', 'field'], true) && empty($options['flowModule']))
            || ($action === 'field' && (empty($options['field']) || empty($options['module']) || empty($options['kind'])))
            || ($action === 'view' && empty($options['id']))
            || ($action === 'save' && !is_array($options['workflow'] ?? null))) {
            throw new InvalidArgumentException('Workflow editor options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');
        $flowModule = (string)($options['flowModule'] ?? '');

        switch ($options['action']) {
            case 'load':
                $data = $this->editor->load($id, !empty($options['duplicate']));
                break;
            case 'view':
                $data = $this->editor->view($id);
                break;
            case 'module':
                $data = $this->editor->module($flowModule);
                break;
            case 'target':
                $data = $this->editor->target($flowModule, (string)($options['module'] ?? ''), (string)($options['relType'] ?? ''));
                break;
            case 'field':
                $data = $this->editor->field($flowModule, (string)$options['kind'], (string)$options['module'], (string)$options['field']);
                break;
            default:
                $saved = $this->editor->save($id, $options['workflow']);
                $data = $saved !== null ? ['id' => $saved] : null;
        }

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages($data !== null ? [] : ['LBL_WORKFLOW_EDITOR_SAVE_ERROR']);
        $process->setData($data ?? []);
    }
}
