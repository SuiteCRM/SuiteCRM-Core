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

namespace App\Logging\LogViewer\Service;

use App\Logging\LogViewer\LegacyHandler\LogViewerHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Process `log-viewer` of the log viewer page of the administration (LogViewerHandler). Admins
 * only, as the legacy page.
 *
 * Options, by action:
 * - sources: the parts of the page, their logs and the labels;
 * - read: part, sources, positions, floors, level, search, limit (LogViewerHandler::read()): a step of
 *   the reading of the logs, that the page repeats with the positions returned.
 */
class LogViewerProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = LogViewerHandler::HANDLER_KEY;

    protected const ACTIONS = ['sources', 'read'];

    public function __construct(protected LogViewerHandler $viewer, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_ADMIN';
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

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = $options['action'] === 'read' ? $this->viewer->read($options) : $this->viewer->load();
        } catch (\Throwable $throwable) {
            $this->logger->error('Log viewer ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = null;
        }

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages($data !== null ? [] : ['LBL_ACTION_ERROR']);
        $process->setData($data ?? []);
    }
}
