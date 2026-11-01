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

namespace App\Module\Reports\Service\ProcessHandlers;

use App\Module\Reports\LegacyHandler\ReportEditorHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process `report-editor` of the report editor (ReportEditorHandler):
 *  - action `load` (id, duplicate): the report (new without id, a copy to duplicate it) and
 *    the choices of the editor;
 *  - action `module` (reportModule): the choices that depend on the report module;
 *  - action `tree` (reportModule, path): the relationships of a module of the module tree;
 *  - action `fields` (reportModule, path): the fields of a module of the module tree;
 *  - action `field` (reportModule, path, field): the choices of a condition on a field;
 *  - action `save` (id, report): saves the report, its fields, conditions and charts.
 */
class ReportEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'report-editor';

    protected const ACTIONS = ['load', 'module', 'tree', 'fields', 'field', 'save'];

    public function __construct(protected ReportEditorHandler $editor)
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
        $acls = [['action' => 'edit']];
        if ($id !== '' && in_array($options['action'] ?? '', ['load', 'save'], true)) {
            $acls[] = ['action' => !empty($options['duplicate']) ? 'view' : 'edit', 'record' => $id];
        }

        return ['reports' => $acls];
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
            || (in_array($action, ['module', 'tree', 'fields', 'field'], true) && empty($options['reportModule']))
            || ($action === 'field' && empty($options['field']))
            || ($action === 'save' && !is_array($options['report'] ?? null))) {
            throw new InvalidArgumentException('Report editor options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');
        $reportModule = (string)($options['reportModule'] ?? '');
        $path = (string)($options['path'] ?? '');

        switch ($options['action']) {
            case 'load':
                $data = $this->editor->load($id, !empty($options['duplicate']));
                break;
            case 'module':
                $data = $this->editor->module($reportModule);
                break;
            case 'tree':
                $data = $this->editor->tree($reportModule, $path);
                $data = $data !== null ? ['nodes' => $data] : null;
                break;
            case 'fields':
                $data = $this->editor->fields($reportModule, $path);
                $data = $data !== null ? ['fields' => $data] : null;
                break;
            case 'field':
                $data = $this->editor->field($reportModule, $path, (string)$options['field']);
                break;
            default:
                $saved = $this->editor->save($id, $options['report']);
                $data = $saved !== null ? ['id' => $saved] : null;
        }

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages($data !== null ? [] : ['LBL_REPORT_EDITOR_SAVE_ERROR']);
        $process->setData($data ?? []);
    }
}
