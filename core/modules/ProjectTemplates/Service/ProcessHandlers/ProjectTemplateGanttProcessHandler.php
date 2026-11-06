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

namespace App\Module\ProjectTemplates\Service\ProcessHandlers;

use App\Module\ProjectTemplates\LegacyHandler\ProjectTemplateGanttHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * `project-template-gantt` process of the Gantt chart widget and of Create Project of
 * the project templates (ProjectTemplateGanttHandler), with the template access of the legacy page.
 *
 * Options, by action (project = the template id):
 * - load: project;
 * - save: project, task (taskId, name, milestone, predecessor, relationType, duration, resource,
 *   percent, note);
 * - delete: project, taskId;
 * - order: project, order (task id => order number);
 * - createProject: project, name, start (Y-m-d or empty), copyAll, tasks (task template ids).
 */
class ProjectTemplateGanttProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = ProjectTemplateGanttHandler::HANDLER_KEY;

    protected const ACTIONS = ['load', 'save', 'delete', 'order', 'createProject'];

    public function __construct(protected ProjectTemplateGanttHandler $gantt, protected LoggerInterface $logger)
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

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)
            || empty($options['project']) || !is_string($options['project'])) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $template = $options['project'];

        try {
            $data = match ($options['action']) {
                'save' => $this->gantt->save($template, is_array($options['task'] ?? null) ? $options['task'] : []),
                'delete' => $this->gantt->delete($template, (string)($options['taskId'] ?? '')),
                'order' => $this->gantt->order($template, is_array($options['order'] ?? null) ? $options['order'] : []),
                'createProject' => $this->gantt->createProject(
                    $template,
                    (string)($options['name'] ?? ''),
                    (string)($options['start'] ?? ''),
                    !empty($options['copyAll']),
                    array_values(array_filter(is_array($options['tasks'] ?? null) ? $options['tasks'] : [], 'is_string'))
                ),
                default => $this->gantt->load($template),
            };
            $process->setStatus('success');
            $process->setMessages([]);
            $process->setData($data);
        } catch (HttpExceptionInterface $exception) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACCESS_DENIED']);
            $process->setData([]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Project template Gantt ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);
        }
    }
}
