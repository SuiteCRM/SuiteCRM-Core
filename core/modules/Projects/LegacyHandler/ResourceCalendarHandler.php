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

/**
 * The resource calendar of Projects, as the legacy Project ResourceList page
 * (controller action_update_chart() and chart.php) builds it:
 *  - the period of the chart type from the week of the start date: weekly (a
 *    column per day, 10 weeks), monthly (a column per week, 52 weeks) or
 *    quarterly (a column per month, 48 months), with its header rows;
 *  - the resources of the projects (users and contacts of the project
 *    resources relationships), filtered by project, user and contact; "none"
 *    leaves the users or the contacts out, as the legacy filters;
 *  - the project tasks of each resource (assigned to it) in the period;
 *  - the projects, users and contacts the filters offer.
 */
class ResourceCalendarHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'project-resource-calendar';

    public const TYPES = ['weekly', 'monthly', 'quarterly'];

    public const NONE = 'none';

    protected const COLUMNS = ['weekly' => 70, 'monthly' => 52, 'quarterly' => 48];

    protected const RESOURCES = [
        'users' => [
            'module' => 'Users',
            'table' => 'project_users_1_c',
            'project' => 'project_users_1project_ida',
            'resource' => 'project_users_1users_idb',
            'beans' => 'users',
        ],
        'contacts' => [
            'module' => 'Contacts',
            'table' => 'project_contacts_1_c',
            'project' => 'project_contacts_1project_ida',
            'resource' => 'project_contacts_1contacts_idb',
            'beans' => 'contacts',
        ],
    ];

    /**
     * Labels of the page, by front end module: the front end only has the labels of
     * the modules shown in the navigation.
     */
    protected const LABELS = [
        'Project' => [
            'LBL_RESOURCE_CHART', 'LBL_PROJECTS_SEARCH', 'LBL_USERS_SEARCH', 'LBL_CONTACTS_SEARCH',
            'LBL_ALL_PROJECTS', 'LBL_ALL_USERS', 'LBL_ALL_CONTACTS', 'LBL_RESOURCE_CHART_WARNING',
            'LBL_CHART_TYPE', 'LBL_CHART_WEEKLY', 'LBL_CHART_MONTHLY', 'LBL_CHART_QUARTERLY', 'LBL_DATE_START',
            'LBL_RESOURCE_CHART_SEARCH_BUTTON', 'LBL_RESOURCE_CHART_PREVIOUS_MONTH', 'LBL_RESOURCE_CHART_NEXT_MONTH',
            'LBL_RESOURCE_CHART_WEEK', 'LBL_RESOURCE_CHART_DAY', 'LBL_RESOURCE_CHART_MONTH', 'LBL_RESOURCE_CHART_QUARTER',
            'LBL_RESOURCE_TYPE_TITLE_USER', 'LBL_RESOURCE_TYPE_TITLE_CONTACT', 'LBL_TOOLTIP_TITLE',
            'LBL_TOOLTIP_PROJECT_NAME', 'LBL_TOOLTIP_TASK_NAME', 'LBL_TOOLTIP_TASK_DURATION',
        ],
        'ProjectTask' => ['LBL_DATE_START', 'LBL_DATE_FINISH'],
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @return array<string, array<string, string>> front end module => label key => label
     */
    public function labels(): array
    {
        $this->init();
        $this->startLegacyApp();

        try {
            global $current_language;

            $labels = [];
            foreach (self::LABELS as $module => $keys) {
                $strings = return_module_language($current_language, $module);
                $frontModule = $module === 'ProjectTask' ? 'project-task' : 'project';
                foreach ($keys as $key) {
                    if (isset($strings[$key]) && is_string($strings[$key])) {
                        $labels[$frontModule][$key] = $strings[$key];
                    }
                }
            }

            return $labels;
        } finally {
            $this->close();
        }
    }

    /**
     * The calendar of $type from the week of $start (Y-m-d, today when empty),
     * for the projects, users and contacts given (all when empty). Null when the
     * user has no access to Projects.
     *
     * @param array{type?: string, start?: string, projects?: array, users?: array, contacts?: array} $filters
     */
    public function build(array $filters): ?array
    {
        $this->init();
        $this->startLegacyApp();

        try {
            if (!\ACLController::checkAccess('Project', 'list', true) || !\ACLController::checkAccess('Project', 'view', true)) {
                return null;
            }

            $type = in_array($filters['type'] ?? '', self::TYPES, true) ? $filters['type'] : self::TYPES[0];
            $selected = [
                'projects' => $this->cleanIds($filters['projects'] ?? []),
                'users' => $this->cleanIds($filters['users'] ?? [], true),
                'contacts' => $this->cleanIds($filters['contacts'] ?? [], true),
            ];

            $today = $this->today();
            $start = $this->parseDate((string)($filters['start'] ?? '')) ?? $today;
            $first = $type === 'quarterly' ? $start->modify('first day of this month') : $start->modify('monday this week');
            $columns = $this->buildColumns($type, $first, $today);
            $end = end($columns)['end'];

            $resources = $this->loadResources($selected);
            $this->loadTasks($resources, $selected['projects'], $first->format('Y-m-d'), $end);

            return [
                'type' => $type,
                'start' => $first->format('Y-m-d'),
                'end' => $end,
                'previous' => $start->modify('-1 month')->format('Y-m-d'),
                'next' => $start->modify('+1 month')->format('Y-m-d'),
                'headers' => $this->buildHeaders($type, $columns),
                'columns' => $columns,
                'resources' => array_values($resources),
                'filters' => $selected,
                'options' => $this->loadOptions(),
            ];
        } finally {
            $this->close();
        }
    }

    /**
     * Columns of the chart: a day, a week or a month, with its dates.
     */
    protected function buildColumns(string $type, \DateTimeImmutable $first, \DateTimeImmutable $today): array
    {
        global $app_list_strings;

        $months = $app_list_strings['dom_cal_month_short'] ?? [];
        $days = $app_list_strings['dom_cal_day_short'] ?? [];
        $todayDate = $today->format('Y-m-d');

        $columns = [];
        for ($index = 0; $index < self::COLUMNS[$type]; $index++) {
            if ($type === 'weekly') {
                $day = $first->modify("+$index days");
                $date = $day->format('Y-m-d');
                $columns[] = [
                    'start' => $date,
                    'end' => $date,
                    'label' => $day->format('j'),
                    'sub' => mb_substr((string)($days[(int)$day->format('w') + 1] ?? ''), 0, 1),
                    'weekend' => (int)$day->format('N') >= 6,
                    'today' => $date === $todayDate,
                ];
                continue;
            }

            if ($type === 'monthly') {
                $week = $first->modify('+' . ($index * 7) . ' days');
                $weekEnd = $week->modify('+6 days');
                $columns[] = [
                    'start' => $week->format('Y-m-d'),
                    'end' => $weekEnd->format('Y-m-d'),
                    'label' => $week->format('W'),
                    'sub' => '',
                    'weekend' => false,
                    'today' => $week->format('Y-m-d') <= $todayDate && $todayDate <= $weekEnd->format('Y-m-d'),
                ];
                continue;
            }

            $month = $first->modify("+$index months");
            $monthEnd = $month->modify('last day of this month');
            $columns[] = [
                'start' => $month->format('Y-m-d'),
                'end' => $monthEnd->format('Y-m-d'),
                'label' => mb_substr((string)($months[(int)$month->format('n')] ?? $month->format('M')), 0, 3),
                'sub' => '',
                'weekend' => false,
                'today' => $month->format('Y-m') === $today->format('Y-m'),
            ];
        }

        return $columns;
    }

    /**
     * Header rows over the columns, as the legacy chart: weekly — weeks, then
     * months; monthly — months, then years; quarterly — quarters, then years.
     *
     * @return array<int, array<int, array{label: string, span: int}>>
     */
    protected function buildHeaders(string $type, array $columns): array
    {
        global $app_list_strings;

        $months = $app_list_strings['dom_cal_month_short'] ?? [];
        $monthLabel = static fn (\DateTimeImmutable $date): string => mb_substr((string)($months[(int)$date->format('n')] ?? $date->format('M')), 0, 3);

        $keys = [];
        foreach ($columns as $column) {
            $date = new \DateTimeImmutable($column['start']);
            if ($type === 'weekly') {
                $keys[0][] = $date->format('W');
                $keys[1][] = $monthLabel($date) . ', ' . $date->format('Y');
            } elseif ($type === 'monthly') {
                $keys[0][] = $monthLabel($date);
                $keys[1][] = $date->format('Y');
            } else {
                $keys[0][] = 'Q' . (int)ceil((int)$date->format('n') / 3);
                $keys[1][] = $date->format('Y');
            }
        }

        $headers = [];
        foreach ($keys as $row => $labels) {
            foreach ($labels as $label) {
                $last = count($headers[$row] ?? []) - 1;
                if ($last >= 0 && $headers[$row][$last]['label'] === $label) {
                    $headers[$row][$last]['span']++;
                    continue;
                }
                $headers[$row][] = ['label' => $label, 'span' => 1];
            }
        }

        return $headers;
    }

    /**
     * Users and contacts of the project resources, by id.
     *
     * @return array<string, array{id: string, module: string, name: string, tasks: array}>
     */
    protected function loadResources(array $selected): array
    {
        $db = \DBManagerFactory::getInstance();

        $resources = [];
        foreach (self::RESOURCES as $key => $relation) {
            if ($selected[$key] === [self::NONE]) {
                continue;
            }

            $where = $relation['table'] . '.deleted = 0';
            if ($selected['projects']) {
                $where .= ' AND ' . $relation['project'] . ' IN (' . $this->quoteIds($selected['projects']) . ')';
            }
            if ($selected[$key]) {
                $where .= ' AND ' . $relation['resource'] . ' IN (' . $this->quoteIds($selected[$key]) . ')';
            }

            $result = $db->query(
                'SELECT DISTINCT ' . $relation['resource'] . ' AS id FROM ' . $relation['table']
                . ' JOIN ' . $relation['beans'] . ' ON ' . $relation['beans'] . '.id = ' . $relation['resource']
                . ' AND ' . $relation['beans'] . '.deleted = 0 WHERE ' . $where
            );
            while ($row = $db->fetchByAssoc($result, false)) {
                $bean = \BeanFactory::getBean($relation['module'], $row['id']);
                if (!$bean || empty($bean->id)) {
                    continue;
                }
                $resources[$bean->id] = [
                    'id' => $bean->id,
                    'module' => $key,
                    'name' => $this->decode($bean->full_name ?? $bean->name ?? ''),
                    'tasks' => [],
                ];
            }
        }

        // Users, then contacts, as the legacy chart, by name.
        $order = array_flip(array_keys(self::RESOURCES));
        uasort($resources, static fn (array $a, array $b): int => [$order[$a['module']], $a['name']] <=> [$order[$b['module']], $b['name']]);

        return $resources;
    }

    /**
     * The project tasks assigned to the resources (the legacy chart uses the
     * assigned user of the task for the contacts too) in the period.
     */
    protected function loadTasks(array &$resources, array $projects, string $start, string $end): void
    {
        if (!$resources) {
            return;
        }

        $db = \DBManagerFactory::getInstance();
        $where = 'project_task.deleted = 0 AND project_task.assigned_user_id IN (' . $this->quoteIds(array_keys($resources)) . ')'
            . ' AND project_task.date_start <= ' . $db->quoted($end)
            . ' AND COALESCE(project_task.date_finish, project_task.date_start) >= ' . $db->quoted($start);
        if ($projects) {
            $where .= ' AND project_task.project_id IN (' . $this->quoteIds($projects) . ')';
        }

        $result = $db->query(
            'SELECT project_task.id, project_task.name, project_task.status, project_task.percent_complete,'
            . ' project_task.date_start, project_task.date_finish, project_task.duration, project_task.duration_unit,'
            . ' project_task.assigned_user_id, project.id AS project_id, project.name AS project_name'
            . ' FROM project_task JOIN project ON project.id = project_task.project_id AND project.deleted = 0'
            . ' WHERE ' . $where . ' ORDER BY project_task.date_start, project_task.name'
        );
        while ($row = $db->fetchByAssoc($result, false)) {
            $resources[$row['assigned_user_id']]['tasks'][] = [
                'id' => $row['id'],
                'name' => $this->decode($row['name']),
                'status' => (string)$row['status'],
                'percentComplete' => (int)$row['percent_complete'],
                'start' => (string)$row['date_start'],
                'end' => (string)($row['date_finish'] ?: $row['date_start']),
                'duration' => trim($row['duration'] . ' ' . $row['duration_unit']),
                'projectId' => $row['project_id'],
                'projectName' => $this->decode($row['project_name']),
            ];
        }
    }

    /**
     * The projects, and the users and contacts that are resources of a project.
     */
    protected function loadOptions(): array
    {
        $db = \DBManagerFactory::getInstance();

        $options = ['projects' => []];
        $result = $db->query('SELECT id, name FROM project WHERE deleted = 0 ORDER BY name');
        while ($row = $db->fetchByAssoc($result, false)) {
            $options['projects'][] = ['value' => $row['id'], 'label' => $this->decode($row['name'])];
        }

        $options += array_fill_keys(array_keys(self::RESOURCES), []);
        foreach ($this->loadResources(['projects' => [], 'users' => [], 'contacts' => []]) as $resource) {
            $options[$resource['module']][] = ['value' => $resource['id'], 'label' => $resource['name']];
        }

        return $options;
    }

    protected function today(): \DateTimeImmutable
    {
        global $current_user;

        $timezone = new \DateTimeZone(\TimeDate::userTimezone($current_user) ?: 'UTC');

        return new \DateTimeImmutable((new \DateTimeImmutable('now', $timezone))->format('Y-m-d'));
    }

    protected function parseDate(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
    }

    /**
     * @return string[] ids (and "none" when allowed and asked alone)
     */
    protected function cleanIds($ids, bool $allowNone = false): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', is_array($ids) ? $ids : []))));
        if ($allowNone && in_array(self::NONE, $ids, true)) {
            return [self::NONE];
        }

        return array_values(array_filter($ids, static fn (string $id): bool => (bool)preg_match('/^[A-Za-z0-9-]{1,36}$/', $id)));
    }

    protected function quoteIds(array $ids): string
    {
        $db = \DBManagerFactory::getInstance();

        return implode(', ', array_map(static fn (string $id): string => $db->quoted($id), $ids));
    }

    protected function decode(?string $value): string
    {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
