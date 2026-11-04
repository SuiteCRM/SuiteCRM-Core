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

namespace App\Module\MailAccounts\Service\ProcessHandlers;

use App\Module\MailAccounts\LegacyHandler\InboundEmailEditor;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process `inbound-email-editor` of the inbound email account editor (InboundEmailEditor);
 * the access to an account is the one of the legacy pages (InboundEmail::ACLAccess(): a personal
 * account by its owner or an admin, a group or bounce account by an admin).
 *
 * Options, by action:
 * - load: id (empty for a new account), type (of a new account: personal, group, bounce);
 * - test, folders: id, values (the connection settings of the form);
 * - fill: field, record (the related record chosen);
 * - save: id, values;
 * - setDefault: id.
 */
class InboundEmailEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = InboundEmailEditor::HANDLER_KEY;

    protected const ACTIONS = ['load', 'test', 'folders', 'fill', 'save', 'setDefault'];

    public function __construct(protected InboundEmailEditor $editor)
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
        $action = ($process->getOptions()['action'] ?? '') === 'setDefault' ? 'view' : 'edit';

        return ['inbound-email' => [['action' => $action]]];
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
            || (in_array($action, ['test', 'folders', 'save'], true) && !is_array($options['values'] ?? null))
            || ($action === 'fill' && (empty($options['field']) || empty($options['record'])))
            || ($action === 'setDefault' && empty($options['id']))) {
            throw new InvalidArgumentException('Inbound email editor options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');
        $values = is_array($options['values'] ?? null) ? $options['values'] : [];

        switch ($options['action']) {
            case 'load':
                $data = $this->editor->load($id, (string)($options['type'] ?? ''));
                break;
            case 'test':
                $data = $this->editor->test($id, $values);
                break;
            case 'folders':
                $data = $this->editor->folders($id, $values);
                break;
            case 'fill':
                $data = $this->editor->fill((string)$options['field'], (string)$options['record']);
                break;
            case 'save':
                $data = $this->editor->save($id, $values);
                break;
            default:
                $data = $this->editor->setDefault($id) ? ['id' => $id] : null;
        }

        $process->setStatus($data !== null && empty($data['error']) ? 'success' : 'error');
        $process->setMessages($data === null ? ['LBL_ACTION_ERROR'] : []);
        $process->setData($data ?? []);
    }
}
