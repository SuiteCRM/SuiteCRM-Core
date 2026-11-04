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

use App\Module\MailAccounts\LegacyHandler\ExternalOAuthConnectionEditor;
use App\Module\MailAccounts\LegacyHandler\ExternalOAuthProviderEditor;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process of the editor of the external OAuth providers or connections
 * (ExternalOAuthProviderEditor, ExternalOAuthConnectionEditor); the access to a record is the one
 * of the legacy pages (ACLAccess() of the bean: a personal record by its owner or an admin, a group
 * record by an admin).
 *
 * Options, by action:
 * - load: id (empty for a new record), type (of a new record: personal, group);
 * - save: id, values.
 */
abstract class ExternalOAuthEditorProcessHandler implements ProcessHandlerInterface
{
    protected const ACTIONS = ['load', 'save'];

    /** The editor of the records. */
    abstract protected function editor(): ExternalOAuthProviderEditor|ExternalOAuthConnectionEditor;

    /** The frontend module of the records. */
    abstract protected function module(): string;

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        return [$this->module() => [['action' => 'edit']]];
    }

    public function configure(Process $process): void
    {
        $process->setId($this->getProcessType());
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();
        $action = $options['action'] ?? '';

        if (!in_array($action, self::ACTIONS, true) || ($action === 'save' && !is_array($options['values'] ?? null))) {
            throw new InvalidArgumentException('External OAuth editor options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)($options['id'] ?? '');

        $data = $options['action'] === 'load'
            ? $this->editor()->load($id, (string)($options['type'] ?? ''))
            : $this->editor()->save($id, $options['values']);

        $process->setStatus($data !== null && empty($data['error']) ? 'success' : 'error');
        $process->setMessages($data === null ? ['LBL_ACTION_ERROR'] : []);
        $process->setData($data ?? []);
    }
}
