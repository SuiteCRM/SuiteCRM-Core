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

namespace App\Module\ProjectTemplates\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The Gantt chart of a project template (legacy AM_ProjectTemplates view_GanttChart
 * page) for the SuiteCRM 8 widget: its task templates, resources and labels, and the task changes
 * of the legacy chart, run with the legacy controller (AM_ProjectTemplatesController:
 * update_GanttChart, delete_task, update_order). Also Create Project of the legacy template page.
 */
class ProjectTemplateGanttHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'project-template-gantt';

    protected const LABELS = [
        'LBL_TASK_ID', 'LBL_TASK_NAME', 'LBL_PREDECESSORS', 'LBL_START', 'LBL_FINISH', 'LBL_DURATION', 'LBL_ASSIGNED_USER_ID',
        'LBL_PERCENT_COMPLETE', 'LBL_MILESTONE_FLAG', 'LBL_TASK_TITLE', 'LBL_DAYS', 'LBL_SUBTASK', 'LBL_ADD_NEW_TASK',
        'LBL_DELETE_TASK', 'LBL_EDIT_TASK_PROPERTIES', 'LBL_RELATIONSHIP_TYPE', 'LBL_UNASSIGNED', 'LBL_DESCRIPTION',
        'LBL_CREATE_PROJECT_TITLE', 'LBL_PROJECT_NAME', 'LBL_START_DATE', 'LBL_COPY_ALL_TASKS', 'LBL_COPY_SEL_TASKS',
        'LBL_TOOLTIP_TEXT', 'LBL_NEW_PROJECT_CREATED',
    ];

    /** Labels of the Gantt chart, in the Project strings (shared with the chart of the projects). */
    protected const GANTT_LABELS = [
        'LBL_GANTT_NO_TASK', 'LBL_GANTT_DELETE_TASK_CONFIRM', 'LBL_GANTT_NONE', 'LBL_GANTT_NOTES', 'LBL_GANTT_MOVE',
        'LBL_GANTT_TEMPLATE_DAYS',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The chart of a template: its tasks (in their order), resources (users and contacts of the
     * template), the relationship types and the labels.
     */
    public function load(string $templateId): array
    {
        return $this->inLegacy(fn () => $this->chart($this->template($templateId, 'view')));
    }

    /**
     * Creates (no task id) or saves a task template, as the legacy chart task form
     * (update_GanttChart: the status from the percent; a template task has no dates).
     *
     * @param array<string, mixed> $task taskId, name, milestone (bool), predecessor, relationType,
     *                                   duration, resource, percent, note
     */
    public function save(string $templateId, array $task): array
    {
        return $this->inLegacy(function () use ($templateId, $task) {
            $template = $this->template($templateId, 'edit');
            $name = trim((string)($task['name'] ?? ''));
            if ($name === '') {
                throw new \InvalidArgumentException('Name is required');
            }

            $post = [
                'task_name' => $name,
                'project_id' => $template->id,
                'override_business_hours' => (string)(int)$template->override_business_hours,
                'task_id' => (string)($task['taskId'] ?? ''),
                'predecessor' => (string)($task['predecessor'] ?? ''),
                'rel_type' => (string)($task['relationType'] ?? 'FS'),
                'resource' => (string)($task['resource'] ?? ''),
                'percent' => (string)(int)($task['percent'] ?? 0),
                'note' => (string)($task['note'] ?? ''),
                'milestone' => !empty($task['milestone']) ? 'Milestone' : 'Task',
                'start' => '',
                'duration' => (string)max(0, (int)($task['duration'] ?? 0)),
                'unit' => 'Days',
            ];
            $this->withPost($post, fn () => $this->controller()->action_update_GanttChart());

            return $this->chart($template);
        });
    }

    /**
     * Deletes a task template (legacy delete_task).
     */
    public function delete(string $templateId, string $taskId): array
    {
        return $this->inLegacy(function () use ($templateId, $taskId) {
            $template = $this->template($templateId, 'edit');
            if (!in_array($taskId, $this->taskIds($template->id), true)) {
                throw new AccessDeniedHttpException('Task');
            }
            $task = \BeanFactory::getBean('AM_TaskTemplates', $taskId);
            if (empty($task->id) || !$task->ACLAccess('delete')) {
                throw new AccessDeniedHttpException('Task');
            }
            $this->withPost(['task_id' => $taskId], fn () => $this->controller()->action_delete_task());

            return $this->chart($template);
        });
    }

    /**
     * Saves the order of the tasks (legacy update_order, after a row is dragged).
     *
     * @param array<string, int> $order task id => order number
     */
    public function order(string $templateId, array $order): array
    {
        return $this->inLegacy(function () use ($templateId, $order) {
            $template = $this->template($templateId, 'edit');
            $ids = $this->taskIds($template->id);
            $order = array_filter(
                array_map('intval', $order),
                static fn ($id) => in_array($id, $ids, true),
                ARRAY_FILTER_USE_KEY
            );
            $this->withPost(['orderArray' => json_encode($order)], fn () => $this->controller()->action_update_order());

            return $this->chart($template);
        });
    }

    /**
     * Create Project of the legacy template page (AM_ProjectTemplatesController::create_project,
     * which redirects and exits, hence run here the same way): the project with the status,
     * priority, description and user of the template, its users and contacts, and a project task
     * by task template, one after the other from the start date, by business days (the business
     * hours when the template overrides them, else Monday to Friday); the user of a task template
     * is kept for all the tasks (copy all) or for the chosen ones. Returns the id of the project.
     *
     * @param string[] $taskIds the task templates whose user is kept (without copy all)
     */
    public function createProject(string $templateId, string $name, string $start, bool $copyAll, array $taskIds): array
    {
        return $this->inLegacy(function () use ($templateId, $name, $start, $copyAll, $taskIds) {
            $template = $this->template($templateId, 'edit');
            if (!\ACLController::checkAccess('Project', 'edit')) {
                throw new AccessDeniedHttpException('Project');
            }
            $name = trim($name);
            if ($name === '') {
                throw new \InvalidArgumentException('The project name is required');
            }
            $startDate = $start !== '' ? \DateTime::createFromFormat('!Y-m-d', $start) : null;
            if ($start !== '' && !$startDate) {
                throw new \InvalidArgumentException('Start date');
            }
            $hours = $this->businessHours((int)$template->override_business_hours === 1);

            $project = \BeanFactory::newBean('Project');
            $project->name = $name;
            $project->estimated_start_date = $startDate ? $startDate->format('Y-m-d') : null;
            $project->status = $template->status;
            $project->priority = strtolower((string)$template->priority);
            $project->description = $template->description;
            $project->assigned_user_id = $template->assigned_user_id;
            $project->save();

            $project->load_relationship('project_users_1');
            $project->load_relationship('project_contacts_1');
            foreach ($template->get_linked_beans('am_projecttemplates_users_1', 'User') ?: [] as $user) {
                $project->project_users_1->add($user->id);
            }
            foreach ($template->get_linked_beans('am_projecttemplates_contacts_1', 'Contact') ?: [] as $contact) {
                $project->project_contacts_1->add($contact->id);
            }
            $template->load_relationship('am_projecttemplates_project_1');
            $template->am_projecttemplates_project_1->add($project->id);

            $end = '';
            $count = 1;
            foreach ($this->tasks($template) as $row) {
                $task = \BeanFactory::newBean('ProjectTask');
                foreach (['name', 'status', 'percent_complete', 'predecessors', 'milestone_flag', 'relationship_type', 'task_number',
                             'order_number', 'estimated_effort', 'utilization', 'description', 'duration'] as $field) {
                    $task->$field = $row->$field;
                }
                $task->priority = strtolower((string)$row->priority);
                $task->assigned_user_id = $copyAll || in_array($row->id, $taskIds, true) ? $row->assigned_user_id : null;
                $task->duration_unit = 'Days';
                $task->project_task_id = $count;
                // As the legacy action: the project end date is set below, not by the task hook.
                $task->set_project_end_date = 0;

                if ($startDate) {
                    $finish = clone $startDate;
                    for ($days = 0; (int)$task->duration > $days; $finish->modify('+1 Days')) {
                        if (($hours[$finish->format('l')] ?? 0) != 0) {
                            $days++;
                        }
                    }
                    $finish->modify('-1 Days');
                    $task->date_start = $startDate->format('Y-m-d');
                    $task->date_finish = $end = $finish->format('Y-m-d');
                    $startDate = (clone $finish)->modify('+1 Days');
                }

                $task->save();
                $task->load_relationship('projects');
                $task->projects->add($project->id);
                if (!empty($row->assigned_user_id)) {
                    $project->project_users_1->add($row->assigned_user_id);
                }
                $count++;
            }

            $project->estimated_end_date = $end;
            $project->save();

            return ['id' => $project->id];
        });
    }

    protected function chart(\AM_ProjectTemplates $template): array
    {
        global $app_list_strings, $current_language;

        $tasks = [];
        foreach ($this->tasks($template) as $task) {
            $tasks[] = [
                'id' => $task->id,
                'number' => (int)$task->task_number,
                'order' => (int)$task->order_number,
                'name' => $this->decode((string)$task->name),
                'predecessor' => (string)($task->predecessors ?: ''),
                'relationType' => (string)($task->relationship_type ?: 'FS'),
                'start' => '',
                'finish' => '',
                'duration' => (int)$task->duration,
                'unit' => 'Days',
                'resource' => (string)$task->assigned_user_id,
                'percent' => (int)$task->percent_complete,
                'milestone' => !empty($task->milestone_flag),
                'actualDuration' => '',
                'note' => $this->decode((string)$task->description),
            ];
        }

        $resources = [];
        foreach (['am_projecttemplates_users_1' => 'User', 'am_projecttemplates_contacts_1' => 'Contact'] as $link => $type) {
            foreach ($template->get_linked_beans($link, $type) ?: [] as $bean) {
                $resources[] = ['id' => $bean->id, 'name' => $this->decode((string)$bean->name), 'type' => strtolower($type)];
            }
        }

        $strings = return_module_language($current_language, 'AM_ProjectTemplates');
        $labels = [];
        $projectStrings = return_module_language($current_language, 'Project');
        foreach (self::GANTT_LABELS as $label) {
            $labels[$label] = $this->decode((string)($projectStrings[$label] ?? $label));
        }
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }
        $types = [];
        foreach ($app_list_strings['relationship_type_list'] ?? ['FS' => 'FS', 'SS' => 'SS'] as $value => $label) {
            $types[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
        }

        return [
            'project' => ['id' => $template->id, 'start' => '', 'end' => ''],
            'template' => true,
            'tasks' => $tasks,
            'resources' => $resources,
            'relationTypes' => $types,
            'canEdit' => (bool)$template->ACLAccess('edit'),
            'canCreateProject' => (bool)$template->ACLAccess('edit') && \ACLController::checkAccess('Project', 'edit'),
            'labels' => $labels,
        ];
    }

    /**
     * The task templates of a template, in their order.
     *
     * @return \AM_TaskTemplates[]
     */
    protected function tasks(\AM_ProjectTemplates $template): array
    {
        // The legacy controller loads this link on the same bean (BeanFactory cache) before it adds
        // or deletes a task: load it again.
        unset($template->am_tasktemplates_am_projecttemplates);
        $tasks = $template->get_linked_beans('am_tasktemplates_am_projecttemplates', 'AM_TaskTemplates') ?: [];
        usort($tasks, static fn ($a, $b) => (int)$a->order_number <=> (int)$b->order_number);

        return $tasks;
    }

    /**
     * The hours by day of the legacy action: the business hours of the instance when the template
     * overrides them, else 8 hours Monday to Friday.
     *
     * @return array<string, float|int>
     */
    protected function businessHours(bool $override): array
    {
        $default = ['Monday' => 8, 'Tuesday' => 8, 'Wednesday' => 8, 'Thursday' => 8, 'Friday' => 8, 'Saturday' => 0, 'Sunday' => 0];
        if (!$override) {
            return $default;
        }

        $hours = [];
        $businessHours = \BeanFactory::newBean('AOBH_BusinessHours');
        foreach (array_keys($default) as $day) {
            $hour = $businessHours->getBusinessHoursForDay($day)[0] ?? null;
            if ($hour) {
                $hours[$day] = $hour->open_status ? abs((float)$hour->closing_hours - (float)$hour->opening_hours) : 0;
            }
        }

        return $hours ?: $default;
    }

    /**
     * The template, with the access of the legacy page.
     */
    protected function template(string $templateId, string $access): \AM_ProjectTemplates
    {
        $template = \BeanFactory::getBean('AM_ProjectTemplates', $templateId);
        if (empty($template->id) || !$template->ACLAccess($access === 'edit' ? 'edit' : 'view')) {
            throw new AccessDeniedHttpException('Project template');
        }

        return $template;
    }

    /**
     * @return string[]
     */
    protected function taskIds(string $templateId): array
    {
        $db = \DBManagerFactory::getInstance();
        $result = $db->query(
            'SELECT am_tasktemplates_am_projecttemplatesam_tasktemplates_idb AS id FROM am_tasktemplates_am_projecttemplates_c'
            . ' WHERE am_tasktemplates_am_projecttemplatesam_projecttemplates_ida = ' . $db->quoted($templateId) . ' AND deleted = 0'
        );
        $ids = [];
        while ($row = $db->fetchByAssoc($result)) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    protected function controller(): \AM_ProjectTemplatesController
    {
        require_once 'modules/AM_ProjectTemplates/controller.php';
        $controller = new \AM_ProjectTemplatesController();
        $controller->module = 'AM_ProjectTemplates';

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
