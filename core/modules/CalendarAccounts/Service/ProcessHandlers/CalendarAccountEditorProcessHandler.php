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

namespace App\Module\CalendarAccounts\Service\ProcessHandlers;

use App\Module\CalendarAccounts\LegacyHandler\CalendarAccountEditor;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * `Calendar-account-editor` process of the calendar account editor and of Sync Now
 * (CalendarAccountEditor); the access to an account is the one of the legacy pages
 * (CalendarAccount::ACLAccess()).
 *
 * Options, by action:
 * - load: id (empty for a new account), type (of a new account: personal, group);
 * - test, save: id, values (the settings of the form);
 * - sync: id.
 */
class CalendarAccountEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = CalendarAccountEditor::HANDLER_KEY;

    protected const ACTIONS = ['load', 'test', 'save', 'sync'];

    public function __construct(protected CalendarAccountEditor $editor)
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
        $action = ($process->getOptions()['action'] ?? '') === 'sync' ? 'view' : 'edit';

        return ['calendar-account' => [['action' => $action]]];
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
            || (in_array($action, ['test', 'save'], true) && !is_array($options['values'] ?? null))
            || ($action === 'sync' && empty($options['id']))) {
            throw new InvalidArgumentException('Calendar account editor options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');
        $values = is_array($options['values'] ?? null) ? $options['values'] : [];

        $data = match ($options['action']) {
            'load' => $this->editor->load($id, (string)($options['type'] ?? '')),
            'test' => $this->editor->test($id, $values),
            'save' => $this->editor->save($id, $values),
            default => $this->editor->sync($id),
        };

        $process->setStatus($data !== null && empty($data['error']) ? 'success' : 'error');
        $process->setMessages($data === null ? ['LBL_ACTION_ERROR'] : []);
        $process->setData($data ?? []);
    }
}
