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

namespace App\Module\Projects\Service\RecordActions;

use App\Module\Projects\LegacyHandler\ProjectGanttHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Duplicate of a project in its record view (record action project-duplicate, in
 * place of the core Duplicate, which copies the fields only): the project and its tasks are copied,
 * as the legacy Duplicate (ProjectGanttHandler::duplicate), and the new project opens.
 */
class ProjectDuplicateAction implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'record-project-duplicate';

    public function __construct(protected ProjectGanttHandler $gantt, protected LoggerInterface $logger)
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
        return ['Project' => [['action' => 'edit']]];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        if (empty($process->getOptions()['id'])) {
            throw new \InvalidArgumentException('The project is not defined');
        }
    }

    public function run(Process $process): void
    {
        try {
            $copy = $this->gantt->duplicate((string)$process->getOptions()['id']);
            $process->setStatus('success');
            $process->setMessages([]);
            $process->setData(['handler' => 'redirect', 'params' => ['route' => 'project/record/' . $copy['id'], 'queryParams' => []]]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Project duplicate failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);
        }
    }
}
