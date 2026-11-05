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

namespace App\Module\Service\Fields\EventInvitees\ProcessHandlers;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\Service\Fields\EventInvitees\LegacyHandler\EventInviteesHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Process of the event-invitees field:
 *  - action `search` (query): users, contacts and leads to invite;
 *  - action `defaults`: the invitees of a new event (the current user).
 */
class EventInviteesProcessHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';
    public const PROCESS_TYPE = 'event-invitees';
    protected const ACTIONS = ['search', 'defaults'];

    public function __construct(protected EventInviteesHandler $inviteesHandler)
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
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $action = $process->getOptions()['action'] ?? '';

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        if (($options['action'] ?? '') === 'defaults') {
            $data = ['invitees' => $this->inviteesHandler->getDefaultInvitees()];
        } else {
            $data = ['invitees' => $this->inviteesHandler->search((string)($options['query'] ?? ''))];
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($data);
    }
}
