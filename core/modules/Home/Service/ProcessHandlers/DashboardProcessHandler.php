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

namespace App\Module\Home\Service\ProcessHandlers;

use App\Module\Home\LegacyHandler\DashboardHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `dashboard` process, called by the SuiteCRM 8 dashboard page (see DashboardHandler).
 *
 * Options, by action:
 * - load: page (dashboard tab) → tabs, columns and dashlets, with the
 *   columns and first records of the list dashlets of that tab;
 * - dashlet: id, offset, sort {orderBy, sortOrder} → one list dashlet;
 * - accept: module (Calls, Meetings), id, status (accept, tentative,
 *   decline) → answers the user's invitation;
 * - close: module (Calls, Meetings), id → sets a planned activity to Held;
 * - feed: id (Activity Stream dashlet), feedAction (post, reply,
 *   delete), text, parentId, recordId → posts, replies to or deletes a post.
 */
class DashboardProcessHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'dashboard';

    protected const ACTIONS = ['load', 'dashlet', 'accept', 'close', 'feed'];

    public function __construct(
        protected DashboardHandler $dashboard,
        protected LoggerInterface $logger
    ) {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     * The dashboard is the user's own; module and record access is checked by
     * the legacy dashlets and by the runner.
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $options = $process->getOptions();

        try {
            $data = $this->runAction($options);
        } catch (\Throwable $throwable) {
            // The page shows a "not available" message.
            $this->logger->error('Dashboard ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages([]);
            $process->setData(['error' => true]);

            return;
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($data);
    }

    protected function runAction(array $options): array
    {
        return match ($options['action']) {
            'load' => $this->dashboard->getDashboard((int)($options['page'] ?? 0)),
            'dashlet' => $this->dashboard->getDashlet(
                (string)($options['id'] ?? ''),
                max(0, (int)($options['offset'] ?? 0)),
                is_array($options['sort'] ?? null) ? $options['sort'] : []
            ),
            'accept' => ['done' => $this->dashboard->setAcceptStatus(
                (string)($options['module'] ?? ''),
                (string)($options['id'] ?? ''),
                (string)($options['status'] ?? '')
            )],
            'close' => ['done' => $this->dashboard->closeActivity(
                (string)($options['module'] ?? ''),
                (string)($options['id'] ?? '')
            )],
            'feed' => ['done' => $this->dashboard->feedAction(
                (string)($options['id'] ?? ''),
                (string)($options['feedAction'] ?? ''),
                [
                    'text' => (string)($options['text'] ?? ''),
                    'parentId' => (string)($options['parentId'] ?? ''),
                    'recordId' => (string)($options['recordId'] ?? ''),
                ]
            )],
        };
    }
}
