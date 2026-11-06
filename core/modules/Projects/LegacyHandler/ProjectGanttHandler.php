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

namespace App\Module\Projects\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The Gantt chart of a project (legacy Project view_GanttChart page) for the SuiteCRM 8
 * widget: its tasks, resources and labels, and the task changes of the legacy chart, run with the
 * legacy controller (ProjectController: update_GanttChart, delete_task, update_order). Also the
 * Duplicate of a project with its tasks, as the legacy duplicate save (Project/Save.php).
 */
class ProjectGanttHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'project-gantt';

    protected const LABELS = [
        'LBL_TASK_ID', 'LBL_TASK_NAME', 'LBL_PREDECESSORS', 'LBL_START', 'LBL_FINISH', 'LBL_DURATION', 'LBL_ASSIGNED_USER_ID',
        'LBL_PERCENT_COMPLETE', 'LBL_MILESTONE_FLAG', 'LBL_ACTUAL_DURATION', 'LBL_TASK_TITLE', 'LBL_LAG', 'LBL_DAYS', 'LBL_HOURS',
        'LBL_SUBTASK', 'LBL_ADD_NEW_TASK', 'LBL_DELETE_TASK', 'LBL_EDIT_TASK_PROPERTIES', 'LBL_RELATIONSHIP_TYPE',
        'LBL_RESOURCE_TYPE_TITLE_USER', 'LBL_RESOURCE_TYPE_TITLE_CONTACT', 'LBL_DESCRIPTION', 'LBL_GANTT_NO_TASK',
        'LBL_GANTT_DELETE_TASK_CONFIRM', 'LBL_GANTT_NONE', 'LBL_GANTT_NOTES', 'LBL_GANTT_MOVE',
    ];


    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The chart of a project: its dates, tasks (in their order), resources (users and contacts of
     * the project), the relationship types and the labels.
     */
    public function load(string $projectId): array
    {
        return $this->inLegacy(fn () => $this->chart($this->project($projectId, 'view')));
    }

    /**
     * Creates (no task id) or saves a task, as the legacy chart task form (update_GanttChart: the
     * end date from the start, the duration and the business hours; the status from the percent).
     *
     * @param array<string, mixed> $task taskId, name, milestone (bool), predecessor, relationType,
     *                                   start (Y-m-d), duration, unit, resource, percent, note,
     *                                   actualDuration
     */
    public function save(string $projectId, array $task): array
    {
        return $this->inLegacy(function () use ($projectId, $task) {
            global $current_user;

            $project = $this->project($projectId, 'edit');
            $name = trim((string)($task['name'] ?? ''));
            $start = \DateTime::createFromFormat('Y-m-d', (string)($task['start'] ?? ''));
            if ($name === '' || !$start) {
                throw new \InvalidArgumentException('Name and start date are required');
            }

            $post = [
                'task_name' => $name,
                'project_id' => $project->id,
                'override_business_hours' => (string)(int)$project->override_business_hours,
                'task_id' => (string)($task['taskId'] ?? ''),
                'predecessor' => (string)($task['predecessor'] ?? ''),
                'rel_type' => (string)($task['relationType'] ?? 'FS'),
                'resource' => (string)($task['resource'] ?? ''),
                'percent' => (string)(int)($task['percent'] ?? 0),
                'note' => (string)($task['note'] ?? ''),
                'actual_duration' => (string)($task['actualDuration'] ?? ''),
                'milestone' => !empty($task['milestone']) ? 'Milestone' : 'Task',
                // The legacy form posts the start in the date format of the user.
                'start' => $start->format($current_user->getPreference('datef') ?: 'Y-m-d'),
                'duration' => (string)max(0, (int)($task['duration'] ?? 0)),
                'unit' => in_array($task['unit'] ?? '', ['Days', 'Hours'], true) ? $task['unit'] : 'Days',
            ];
            $this->withPost($post, fn () => $this->controller()->action_update_GanttChart());

            return $this->chart($project);
        });
    }

    /**
     * Deletes a task (legacy delete_task).
     */
    public function delete(string $projectId, string $taskId): array
    {
        return $this->inLegacy(function () use ($projectId, $taskId) {
            $project = $this->project($projectId, 'edit');
            $task = \BeanFactory::getBean('ProjectTask', $taskId);
            if (empty($task->id) || $task->project_id !== $project->id || !$task->ACLAccess('delete')) {
                throw new AccessDeniedHttpException('Task');
            }
            $this->withPost(['task_id' => $taskId], fn () => $this->controller()->action_delete_task());

            return $this->chart($project);
        });
    }

    /**
     * Saves the order of the tasks (legacy update_order, after a row is dragged).
     *
     * @param array<string, int> $order task id => order number
     */
    public function order(string $projectId, array $order): array
    {
        return $this->inLegacy(function () use ($projectId, $order) {
            $project = $this->project($projectId, 'edit');
            $ids = $this->taskIds($project->id);
            $order = array_filter(
                array_map('intval', $order),
                static fn ($id) => in_array($id, $ids, true),
                ARRAY_FILTER_USE_KEY
            );
            $this->withPost(['orderArray' => json_encode($order)], fn () => $this->controller()->action_update_order());

            return $this->chart($project);
        });
    }

    /**
     * Duplicates a project with its tasks, as the legacy Duplicate (Project/Save.php duplicateSave):
     * the new project, assigned to the user, and a copy of each task. Returns its id.
     */
    public function duplicate(string $projectId): array
    {
        return $this->inLegacy(function () use ($projectId) {
            global $current_user;

            $original = $this->project($projectId, 'view');
            $project = \BeanFactory::newBean('Project');
            if (!$project->ACLAccess('save')) {
                throw new AccessDeniedHttpException('Project');
            }
            foreach ($original->field_defs as $name => $def) {
                if (($def['source'] ?? 'db') === 'db' && !in_array($name, ['id', 'date_entered', 'date_modified', 'deleted'], true)) {
                    $project->$name = $original->$name;
                }
            }
            $project->assigned_user_id = $current_user->id;
            $project->is_template = 0;
            $project->save();

            foreach ($this->taskIds($original->id) as $taskId) {
                $task = \BeanFactory::getBean('ProjectTask', $taskId);
                $copy = \BeanFactory::newBean('ProjectTask');
                foreach ($task->field_defs as $name => $def) {
                    if (($def['source'] ?? 'db') === 'db' && !in_array($name, ['id', 'date_entered', 'date_modified', 'deleted'], true)) {
                        $copy->$name = $task->$name;
                    }
                }
                $copy->project_id = $project->id;
                $copy->save();
            }

            return ['id' => $project->id];
        });
    }

    protected function chart(\Project $project): array
    {
        global $app_list_strings, $current_language;

        $tasks = [];
        foreach (\BeanFactory::newBean('ProjectTask')->get_full_list('order_number', "project_task.project_id = '" . $project->db->quote($project->id) . "'") ?: [] as $task) {
            $tasks[] = [
                'id' => $task->id,
                'number' => (int)$task->project_task_id,
                'order' => (int)$task->order_number,
                'name' => $this->decode((string)$task->name),
                'predecessor' => (string)$task->predecessors,
                'relationType' => (string)($task->relationship_type ?: 'FS'),
                'start' => (string)$task->fetched_row['date_start'],
                'finish' => (string)$task->fetched_row['date_finish'],
                'duration' => (int)$task->duration,
                'unit' => (string)($task->duration_unit ?: 'Days'),
                'resource' => (string)$task->assigned_user_id,
                'percent' => (int)$task->percent_complete,
                'milestone' => !empty($task->milestone_flag),
                'actualDuration' => (string)$task->actual_duration,
                'note' => $this->decode((string)$task->description),
            ];
        }

        $resources = [];
        foreach (['project_users_1' => 'User', 'project_contacts_1' => 'Contact'] as $link => $type) {
            foreach ($project->get_linked_beans($link, $type) ?: [] as $bean) {
                $resources[] = ['id' => $bean->id, 'name' => $this->decode((string)$bean->name), 'type' => strtolower($type)];
            }
        }

        $strings = return_module_language($current_language, 'Project');
        $labels = [];
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }
        $types = [];
        foreach ($app_list_strings['relationship_type_list'] ?? ['FS' => 'FS', 'SS' => 'SS'] as $value => $label) {
            $types[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
        }

        return [
            'project' => [
                'id' => $project->id,
                'start' => (string)$project->fetched_row['estimated_start_date'],
                'end' => (string)$project->fetched_row['estimated_end_date'],
            ],
            'tasks' => $tasks,
            'resources' => $resources,
            'relationTypes' => $types,
            'canEdit' => (bool)$project->ACLAccess('edit'),
            'labels' => $labels,
        ];
    }

    /**
     * The project, with the access of the legacy page.
     */
    protected function project(string $projectId, string $access): \Project
    {
        $project = \BeanFactory::getBean('Project', $projectId);
        if (empty($project->id) || !$project->ACLAccess($access === 'edit' ? 'edit' : 'view')) {
            throw new AccessDeniedHttpException('Project');
        }

        return $project;
    }

    /**
     * @return string[]
     */
    protected function taskIds(string $projectId): array
    {
        $db = \DBManagerFactory::getInstance();
        $result = $db->query("SELECT id FROM project_task WHERE project_id = " . $db->quoted($projectId) . " AND deleted = 0");
        $ids = [];
        while ($row = $db->fetchByAssoc($result)) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    protected function controller(): \ProjectController
    {
        require_once 'modules/Project/controller.php';
        $controller = new \ProjectController();
        $controller->module = 'Project';

        return $controller;
    }

    /**
     * Runs a legacy action with posted values.
     *
     * @param array<string, string> $values
     */
    protected function withPost(array $values, callable $callback): mixed
    {
        $post = $_POST;
        $request = $_REQUEST;
        $_POST = $values;
        $_REQUEST = $values;
        try {
            return $callback();
        } finally {
            $_POST = $post;
            $_REQUEST = $request;
        }
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
