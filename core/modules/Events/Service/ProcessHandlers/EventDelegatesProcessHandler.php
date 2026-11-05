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

namespace App\Module\Events\Service\ProcessHandlers;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;
use App\Module\Events\LegacyHandler\EventDelegatesHandler;

/**
 * Process `event-delegates` of the delegates widget of the event record view
 * (EventDelegatesHandler, which checks the event access). Every action but invite returns a page of the
 * delegates (filters: search, module, invite, accept; offset).
 *
 * Options, by action:
 * - list: id, filters, offset;
 * - status: id, field (invite or accept), status, delegates [{module, id}] or all (the
 *   delegates matching the filters);
 * - add: id, module (Contacts, Leads, Prospects or ProspectLists), ids;
 * - remove: id, module, delegate;
 * - invite: id → the invitations sent and failed.
 */
class EventDelegatesProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'event-delegates';

    protected const ACTIONS = ['list', 'status', 'add', 'remove', 'invite'];

    public function __construct(protected EventDelegatesHandler $delegates, protected LoggerInterface $logger)
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
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true) || empty($options['id'])) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)$options['id'];
        $filters = is_array($options['filters'] ?? null) ? $options['filters'] : [];
        $data = [];

        try {
            $done = match ($options['action']) {
                'status' => $this->delegates->setStatus(
                    $id,
                    (string)($options['field'] ?? ''),
                    (string)($options['status'] ?? ''),
                    is_array($options['delegates'] ?? null) ? $options['delegates'] : [],
                    !empty($options['all']) ? $filters : null
                ),
                'add' => $this->delegates->add($id, (string)($options['module'] ?? ''), is_array($options['ids'] ?? null) ? $options['ids'] : []),
                'remove' => $this->delegates->remove($id, (string)($options['module'] ?? ''), (string)($options['delegate'] ?? '')),
                default => true,
            };

            if ($options['action'] === 'invite') {
                $result = $this->delegates->sendInvitations($id);
                $done = $result !== null && empty($result['error']);
                $data['invitations'] = $result;
            }

            $page = $this->delegates->list($id, $filters, (int)($options['offset'] ?? 0));
            $data += $page ?? [];
            $done = $done && $page !== null;
        } catch (\Throwable $throwable) {
            $this->logger->error('Event delegates ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $done = false;
        }

        $process->setStatus($done ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data);
    }
}
