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

namespace App\Module\Calendar\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The activities calendar (legacy Calendar module) for the calendar view:
 *  - the period of a view (day, week, month, shared week, shared month) around a
 *    date, with its days, title and neighbour dates, in the user's date formats;
 *  - the activities of the period (legacy CalendarActivity::get_activities():
 *    meetings, calls, tasks and events the user is assigned to or invited to,
 *    without the declined ones), of the user or of the users of the shared
 *    calendar, as the legacy calendar shows them (tasks at their due date,
 *    all-day activities, colours of the modules);
 *  - the calendar preferences of the legacy settings (time slots, day start and
 *    end, calls, tasks and completed activities shown, shared calendars
 *    separate, colours) and the users of the shared calendar;
 *  - the changes made on the calendar, as the legacy Calendar controller: move
 *    and resize a meeting or a call (Reschedule, Resize), delete an activity
 *    (Remove), save the settings (SaveSettings) and the shared users.
 *
 * Dates and times are given and read in the user's time zone ('Y-m-d H:i:s').
 */
class CalendarHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'calendar-activities';

    public const VIEWS = ['day', 'week', 'month', 'sharedWeek', 'sharedMonth'];

    /**
     * Views of the legacy calendar (index.php?module=Calendar&view=..., default_view
     * config) and the page views showing them.
     */
    public const LEGACY_VIEWS = [
        'agendaDay' => 'day',
        'basicDay' => 'day',
        'agendaWeek' => 'week',
        'basicWeek' => 'week',
        'month' => 'month',
        'sharedWeek' => 'sharedWeek',
        'sharedMonth' => 'sharedMonth',
    ];

    /**
     * Activities moved and resized on the calendar, as the legacy calendar (the tasks
     * and events are not).
     */
    public const MOVABLE = ['Meetings', 'Calls'];

    /**
     * Activities created from the calendar.
     */
    protected const CREATE = ['Meetings', 'Calls', 'Tasks'];

    protected const DATETIME = 'Y-m-d H:i:s';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The calendar of $view (default view of the legacy calendar when empty) around
     * $date (Y-m-d, today when empty). Null when the user has no access to the calendar.
     */
    public function load(string $view, string $date): ?array
    {
        return $this->inLegacy(function () use ($view, $date) {
            global $current_user, $timedate;

            if (!\ACLController::checkAccess('Calendar', 'list', true)) {
                return null;
            }

            $calendar = $this->newCalendar();
            $settings = $this->getSettings($calendar);
            $users = $this->getUsers();

            $view = in_array($view, self::VIEWS, true) ? $view : $this->getDefaultView();
            $today = $timedate->getNow(true)->format('Y-m-d');
            $day = $this->parseDate($date) ?? new \DateTimeImmutable($today);
            [$start, $end] = $this->getRange($view, $day, $settings['firstDay']);

            $shared = str_starts_with($view, 'shared');
            $sharedIds = $this->getSharedIds($users);
            $calendarUsers = $shared ? $sharedIds : [$current_user->id];

            $calendars = [];
            foreach ($calendarUsers as $userId) {
                $events = $this->loadEvents($calendar, $userId, $start, $end);
                if ($shared && !$settings['sharedSeparate']) {
                    // As the legacy calendar: one calendar with the activities of all the users.
                    $calendars[0] ??= ['userId' => '', 'userName' => '', 'events' => []];
                    $calendars[0]['events'] = array_merge($calendars[0]['events'], $events);
                    continue;
                }
                $calendars[] = [
                    'userId' => $userId,
                    'userName' => $users[$userId] ?? '',
                    'events' => $events,
                ];
            }
            foreach ($calendars as &$userCalendar) {
                $userCalendar['events'] = $this->sortEvents($userCalendar['events']);
            }
            unset($userCalendar);

            return [
                'view' => $view,
                'date' => $day->format('Y-m-d'),
                'today' => $today,
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'title' => $this->getTitle($view, $day, $start, $end),
                'previous' => $this->getNeighbour($view, $day, -1),
                'next' => $this->getNeighbour($view, $day, 1),
                'days' => $this->getDays($view, $day, $start, $end, $today),
                'weekdays' => $this->getWeekdays($settings['firstDay']),
                'hours' => $this->getHours(),
                'calendars' => $calendars,
                'settings' => $settings,
                'create' => $this->getCreateModules(),
                'users' => array_map(
                    static fn (string $id, string $name): array => ['value' => $id, 'label' => $name],
                    array_keys($users),
                    array_values($users)
                ),
                'sharedIds' => $sharedIds,
                'currentUser' => ['id' => $current_user->id, 'name' => $users[$current_user->id] ?? $current_user->user_name],
            ];
        });
    }

    /**
     * Labels of the page: the Calendar module strings (the front end only has the
     * strings of the modules shown in the navigation).
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        return $this->inLegacy(static function () {
            global $current_language;

            $strings = return_module_language($current_language, 'Calendar');

            return array_filter(is_array($strings) ? $strings : [], 'is_string');
        });
    }

    /**
     * Moves a meeting or a call to $start (Y-m-d H:i:s), as the legacy Reschedule
     * action. Its duration is kept.
     */
    public function move(string $module, string $id, string $start): bool
    {
        return $this->inLegacy(function () use ($module, $id, $start) {
            global $timedate;

            $bean = $this->getMovableBean($module, $id);
            $date = $this->parseDateTime($start);
            if (!$bean || !$date) {
                return false;
            }

            $bean->date_start = $timedate->asUser($timedate->fromString($date->format(self::DATETIME)));
            $bean->save();

            return true;
        });
    }

    /**
     * Changes the duration of a meeting or a call, as the legacy Resize action.
     */
    public function resize(string $module, string $id, int $minutes): bool
    {
        return $this->inLegacy(function () use ($module, $id, $minutes) {
            $bean = $this->getMovableBean($module, $id);
            if (!$bean || $minutes <= 0) {
                return false;
            }

            $bean->duration_hours = intdiv($minutes, 60);
            $bean->duration_minutes = $minutes % 60;
            $bean->save();

            return true;
        });
    }

    /**
     * Deletes an activity, as the legacy Remove action.
     */
    public function remove(string $module, string $id): bool
    {
        return $this->inLegacy(function () use ($module, $id) {
            $calendar = $this->newCalendar();
            if (!isset($calendar->activityList[$module])) {
                return false;
            }

            $bean = \BeanFactory::getBean($module, $id);
            if (!$bean || empty($bean->id) || !$bean->ACLAccess('Delete')) {
                return false;
            }

            $bean->mark_deleted($bean->id);

            return true;
        });
    }

    /**
     * Saves the calendar settings, as the legacy SaveSettings view.
     *
     * @param array{dayStart?: string, dayEnd?: string, timeslots?: bool, sharedSeparate?: bool,
     *     showCalls?: bool, showTasks?: bool, showCompleted?: bool, colors?: array} $settings
     */
    public function saveSettings(array $settings): bool
    {
        return $this->inLegacy(function () use ($settings) {
            global $current_user;

            $dayStart = $this->parseTime((string)($settings['dayStart'] ?? ''));
            $dayEnd = $this->parseTime((string)($settings['dayEnd'] ?? ''));
            if ($dayStart === null || $dayEnd === null || $dayStart >= $dayEnd) {
                return false;
            }

            $calendar = $this->newCalendar();
            $colors = [];
            foreach (is_array($settings['colors'] ?? null) ? $settings['colors'] : [] as $color) {
                $module = (string)($color['module'] ?? '');
                if (!isset($calendar->activityList[$module])) {
                    continue;
                }
                foreach (['body', 'border', 'text'] as $part) {
                    $value = ltrim((string)($color[$part] ?? ''), '#');
                    if (preg_match('/^[0-9A-Fa-f]{6}$/', $value)) {
                        $colors[$module][$part] = strtoupper($value);
                    }
                }
            }

            $current_user->setPreference('day_start_time', $dayStart, 0, 'global', $current_user);
            $current_user->setPreference('day_end_time', $dayEnd, 0, 'global', $current_user);
            $current_user->setPreference('CalendarActivities', base64_encode(serialize($colors)));
            $current_user->setPreference('calendar_display_timeslots', !empty($settings['timeslots']) ? '1' : '', 0, 'global', $current_user);
            $current_user->setPreference('show_tasks', !empty($settings['showTasks']) ? 'true' : '', 0, 'global', $current_user);
            $current_user->setPreference('show_calls', !empty($settings['showCalls']) ? 'true' : '', 0, 'global', $current_user);
            $current_user->setPreference('show_completed', !empty($settings['showCompleted']) ? 'true' : '', 0, 'global', $current_user);
            $current_user->setPreference('calendar_display_shared_separate', !empty($settings['sharedSeparate']) ? '1' : '', 0, 'global', $current_user);
            $this->savePreferences();

            return true;
        });
    }

    /**
     * Saves the users of the shared calendar (legacy `shared_ids` preference).
     */
    public function saveSharedUsers(array $ids): bool
    {
        return $this->inLegacy(function () use ($ids) {
            global $current_user;

            $users = $this->getUsers();
            $ids = array_values(array_unique(array_filter(
                array_map('strval', $ids),
                static fn (string $id): bool => isset($users[$id])
            )));

            $current_user->setPreference('shared_ids', $ids ?: [$current_user->id]);
            $this->savePreferences();

            return true;
        });
    }

    /**
     * Invitees of a meeting or a call created in SuiteCRM 8 (record view, calendar), as the
     * legacy forms add them to a new activity (MeetingFormBase / CallFormBase::handleSave()):
     * its assigned user and the user creating it, the assigned user accepting when they are
     * the same. The calendars show the activities of their invitees only.
     */
    public function addCreatedActivityInvitees(string $module, string $id): void
    {
        $this->inLegacy(function () use ($module, $id) {
            global $current_user;

            $bean = in_array($module, self::MOVABLE, true) ? \BeanFactory::getBean($module, $id) : null;
            if (!$bean || empty($bean->id) || !$bean->load_relationship('users')) {
                return;
            }

            $invitees = $bean->users->get();
            foreach (array_unique(array_filter([$bean->assigned_user_id, $current_user->id])) as $userId) {
                if (!in_array($userId, $invitees, true)) {
                    $bean->users->add($userId);
                }
            }
            if ($bean->assigned_user_id === $current_user->id) {
                $bean->set_accept_status($current_user, 'accept');
            }
        });
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            require_once 'modules/Calendar/Calendar.php';
            require_once 'modules/Calendar/CalendarDisplay.php';

            return $callback();
        } finally {
            $this->close();
        }
    }

    /**
     * Writes the changed preferences to the database. setPreference() only changes the
     * session: the legacy application writes them at the end of its requests
     * (sugar_cleanup()), which the SuiteCRM 8 requests do not run.
     */
    protected function savePreferences(): void
    {
        global $current_user;

        $current_user->savePreferencesToDB();
    }

    /**
     * The legacy calendar: its activity modules and the user's calendar preferences, with
     * the config defaults.
     */
    protected function newCalendar(): \Calendar
    {
        return new \Calendar('agendaWeek');
    }

    protected function getSettings(\Calendar $calendar): array
    {
        global $current_user, $sugar_config;

        $config = \SugarConfig::getInstance();

        $timeslots = $current_user->getPreference('calendar_display_timeslots');
        $sharedSeparate = $current_user->getPreference('calendar_display_shared_separate');

        $colors = [];
        foreach ((new \CalendarDisplay($calendar))->checkActivity() as $module => $color) {
            if (!isset($calendar->activityList[$module]) || !is_array($color)) {
                continue;
            }
            $defaults = $sugar_config['CalendarColors'][$module] ?? [];
            $colors[] = [
                'module' => $module,
                'frontModule' => $this->moduleNameMapper->toFrontEnd($module),
                'label' => (string)($color['label'] ?? $module),
                'body' => $this->hexColor($color['body'] ?? $defaults['body'] ?? '', '707C9C'),
                'border' => $this->hexColor($color['border'] ?? $defaults['border'] ?? '', '515A71'),
                'text' => $this->hexColor($color['text'] ?? $defaults['text'] ?? '', 'E5E5E5'),
            ];
        }

        return [
            'firstDay' => (int)$current_user->get_first_day_of_week(),
            'dayStart' => $this->parseTime((string)$calendar->day_start_time) ?? '08:00',
            'dayEnd' => $this->parseTime((string)$calendar->day_end_time) ?? '19:00',
            'timeslots' => (bool)($timeslots ?? $config->get('calendar.display_timeslots', true)),
            'sharedSeparate' => (bool)($sharedSeparate ?? $config->get('calendar.calendar_display_shared_separate', true)),
            'showCalls' => (bool)$calendar->show_calls,
            'showTasks' => (bool)$calendar->show_tasks,
            'showCompleted' => (bool)$calendar->show_completed,
            'draggable' => (bool)$config->get('calendar.items_draggable', true),
            'resizable' => (bool)$config->get('calendar.items_resizable', true),
            'colors' => $colors,
        ];
    }

    protected function getDefaultView(): string
    {
        $view = (string)\SugarConfig::getInstance()->get('calendar.default_view', 'agendaWeek');

        return self::LEGACY_VIEWS[$view] ?? 'week';
    }

    /**
     * Active users, by id, with their names as the legacy user lists show them.
     *
     * @return array<string, string>
     */
    protected function getUsers(): array
    {
        $users = array_map(fn ($name): string => $this->decode((string)$name), get_user_array(false));
        natcasesort($users);

        return $users;
    }

    /**
     * Users of the shared calendar: the `shared_ids` preference, or the user (legacy
     * Calendar::init_shared()). The current user comes first, as the legacy calendar shows
     * them.
     */
    protected function getSharedIds(array $users): array
    {
        global $current_user;

        $ids = $current_user->getPreference('shared_ids');
        $ids = array_values(array_filter(
            array_map('strval', is_array($ids) ? $ids : []),
            static fn (string $id): bool => isset($users[$id])
        ));
        if (!$ids) {
            return [$current_user->id];
        }

        usort($ids, static fn (string $a, string $b): int => ($b === $current_user->id) <=> ($a === $current_user->id));

        return array_values(array_unique($ids));
    }

    /**
     * Activities of a user overlapping the period, as the legacy calendar fetches them
     * (5 days before the period for the activities lasting several days).
     */
    protected function loadEvents(\Calendar $calendar, string $userId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        global $timedate;

        $from = $timedate->fromString($start->modify('-5 days')->format('Y-m-d') . ' 00:00:00');
        $to = $timedate->fromString($end->format('Y-m-d') . ' 00:00:00');

        $activities = \CalendarActivity::get_activities(
            $calendar->activityList,
            $userId,
            $calendar->show_tasks,
            $from,
            $to,
            'agendaWeek',
            $calendar->show_calls,
            $calendar->show_completed
        );

        $periodStart = $start->format(self::DATETIME);
        $periodEnd = $end->format(self::DATETIME);

        $events = [];
        foreach ($activities as $activity) {
            if (!$activity->sugar_bean instanceof \SugarBean) {
                continue;
            }
            $event = $this->describeEvent($activity->sugar_bean);
            if ($event === null) {
                continue;
            }
            $overlaps = $event['start'] === $event['end']
                ? $event['start'] >= $periodStart && $event['start'] < $periodEnd
                : $event['start'] < $periodEnd && $event['end'] > $periodStart;
            if ($overlaps) {
                $events[$event['id']] = $event;
            }
        }

        return array_values($events);
    }

    /**
     * An activity on the calendar: its start and end in the user's time zone (tasks at
     * their due date), whether it lasts whole days, its details and what the user can do
     * with it.
     */
    protected function describeEvent(\SugarBean $bean): ?array
    {
        global $current_user, $timedate;

        $module = $bean->module_dir;
        if ($module === 'Tasks') {
            $start = $this->userDateTime($bean->date_due ?? '');
            $end = $start;
        } else {
            $start = $this->userDateTime($bean->date_start ?? '');
            $end = $this->userDateTime($bean->date_end ?? '');
            if ($start && isset($bean->duration_hours) && ($module !== 'FP_events' || !$end || $end < $start)) {
                $minutes = (int)$bean->duration_hours * 60 + (int)($bean->duration_minutes ?? 0);
                $end = $start->modify("+$minutes minutes");
            }
        }
        if (!$start) {
            return null;
        }
        $end = $end && $end >= $start ? $end : $start;

        $minutes = intdiv($end->getTimestamp() - $start->getTimestamp(), 60);
        $view = $bean->ACLAccess('DetailView');
        $edit = $bean->ACLAccess('Save');
        $timeFormat = $timedate->get_time_format($current_user);

        $event = [
            'id' => $bean->id,
            'module' => $this->moduleNameMapper->toFrontEnd($module),
            'legacyModule' => $module,
            'name' => $this->decode($bean->name ?? ''),
            'assignedUserId' => (string)($bean->assigned_user_id ?? ''),
            'assignedUserName' => $this->decode($bean->assigned_user_name ?? ''),
            'start' => $start->format(self::DATETIME),
            'end' => $end->format(self::DATETIME),
            'time' => $start->format($timeFormat),
            'endTime' => $end->format($timeFormat),
            'allDay' => $start->format('H:i') === '00:00' && $minutes % 1440 === 0,
            'view' => $view,
            'edit' => $edit,
            'delete' => $bean->ACLAccess('Delete'),
            'movable' => $edit && in_array($module, self::MOVABLE, true),
            'parent' => null,
            'details' => [],
        ];

        // As the legacy calendar: no details of the activities the user cannot see.
        if (!$view) {
            return $event;
        }

        $event['parent'] = $this->describeParent($bean);
        $fields = ['status'];
        if ($module === 'Tasks') {
            array_push($fields, 'date_start', 'date_due', 'priority');
        } else {
            $fields[] = 'date_start';
            if ($module === 'FP_events') {
                $fields[] = 'date_end';
            }
            $fields[] = 'duration';
            if ($module === 'Meetings') {
                $fields[] = 'location';
            }
        }
        $fields[] = 'assigned_user_name';
        $fields[] = 'description';

        foreach ($fields as $field) {
            $detail = $this->describeField($bean, $field);
            if ($detail !== null) {
                $event['details'][] = $detail;
            }
        }

        return $event;
    }

    protected function describeParent(\SugarBean $bean): ?array
    {
        if (empty($bean->parent_type) || empty($bean->parent_id)) {
            return null;
        }

        $parent = \BeanFactory::getBean($bean->parent_type, $bean->parent_id);
        if (!$parent || empty($parent->id)) {
            return null;
        }

        return [
            'module' => $this->moduleNameMapper->toFrontEnd($bean->parent_type),
            'id' => $parent->id,
            'name' => $parent->ACLAccess('DetailView') ? $this->decode($parent->name ?? '') : '',
            'label' => $this->fieldLabel($bean, 'parent_name'),
            'view' => $parent->ACLAccess('DetailView'),
        ];
    }

    /**
     * A detail of the activity: label and value in the user's formats, or null when empty.
     */
    protected function describeField(\SugarBean $bean, string $field): ?array
    {
        global $app_list_strings, $current_language;

        if ($field === 'duration') {
            $hours = (int)($bean->duration_hours ?? 0);
            $minutes = (int)($bean->duration_minutes ?? 0);
            if (!isset($bean->duration_hours) || ($hours === 0 && $minutes === 0)) {
                return null;
            }
            $strings = return_module_language($current_language, 'Calendar');

            return [
                'label' => $this->trimLabel($strings['LBL_DURATION'] ?? 'Duration'),
                'value' => trim(
                    ($hours ? $hours . ($strings['LBL_HOURS_ABBREV'] ?? 'h') . ' ' : '')
                    . ($minutes ? $minutes . ($strings['LBL_MINS_ABBREV'] ?? 'm') : '')
                ),
            ];
        }

        $definition = $bean->field_defs[$field] ?? null;
        $value = $bean->$field ?? '';
        if (!$definition || $value === '' || $value === null) {
            return null;
        }

        if (in_array($definition['type'] ?? '', ['enum', 'dynamicenum'], true)) {
            $value = $app_list_strings[$definition['options'] ?? ''][$value] ?? $value;
        }

        $value = $this->decode((string)$value);
        if ($field === 'description' && mb_strlen($value) > 500) {
            $value = mb_substr($value, 0, 500) . '…';
        }

        return ['label' => $this->fieldLabel($bean, $field), 'value' => $value, 'multiline' => $field === 'description'];
    }

    protected function fieldLabel(\SugarBean $bean, string $field): string
    {
        $label = translate($bean->field_defs[$field]['vname'] ?? '', $bean->module_dir);

        return $this->trimLabel(is_string($label) && $label !== '' ? $label : $field);
    }

    protected function sortEvents(array $events): array
    {
        $unique = [];
        foreach ($events as $event) {
            $unique[$event['id']] ??= $event;
        }
        $events = array_values($unique);
        usort($events, static fn (array $a, array $b): int => [$a['start'], $b['end'], $a['name']] <=> [$b['start'], $a['end'], $b['name']]);

        return $events;
    }

    protected function getMovableBean(string $module, string $id): ?\SugarBean
    {
        if (!in_array($module, self::MOVABLE, true)) {
            return null;
        }

        $bean = \BeanFactory::getBean($module, $id);
        if (!$bean || empty($bean->id) || !$bean->ACLAccess('Save')) {
            return null;
        }

        return $bean;
    }

    /**
     * @return array<string, bool> front end module => whether the user can create it
     */
    protected function getCreateModules(): array
    {
        $create = [];
        foreach (self::CREATE as $module) {
            $create[$this->moduleNameMapper->toFrontEnd($module)] = (bool)\ACLController::checkAccess($module, 'edit', true);
        }

        return $create;
    }

    /**
     * First day and day after the last day of the view around $day: the day, its week
     * (from the user's first day of the week), or the weeks of its month.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    protected function getRange(string $view, \DateTimeImmutable $day, int $firstDay): array
    {
        $weekStart = static fn (\DateTimeImmutable $date): \DateTimeImmutable => $date->modify(
            '-' . (((int)$date->format('w') - $firstDay + 7) % 7) . ' days'
        );

        if ($view === 'day') {
            return [$day, $day->modify('+1 day')];
        }

        if ($view === 'week' || $view === 'sharedWeek') {
            $start = $weekStart($day);

            return [$start, $start->modify('+7 days')];
        }

        $first = $day->modify('first day of this month');
        $next = $first->modify('first day of next month');
        $end = $weekStart($next);
        if ($end < $next) {
            $end = $end->modify('+7 days');
        }

        return [$weekStart($first), $end];
    }

    protected function getNeighbour(string $view, \DateTimeImmutable $day, int $direction): string
    {
        $sign = $direction < 0 ? '-' : '+';

        $neighbour = match ($view) {
            'day' => $day->modify($sign . '1 day'),
            'week', 'sharedWeek' => $day->modify($sign . '7 days'),
            default => $day->modify('first day of this month')->modify($sign . '1 month'),
        };

        return $neighbour->format('Y-m-d');
    }

    protected function getDays(string $view, \DateTimeImmutable $day, \DateTimeImmutable $start, \DateTimeImmutable $end, string $today): array
    {
        global $app_list_strings;

        $weekdays = $app_list_strings['dom_cal_day_short'] ?? [];
        $month = $day->format('Y-m');

        $days = [];
        for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
            $weekday = (int)$date->format('w');
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'number' => $date->format('j'),
                'label' => trim(($weekdays[$weekday + 1] ?? '') . ' ' . $date->format('j')),
                'today' => $date->format('Y-m-d') === $today,
                'weekend' => $weekday === 0 || $weekday === 6,
                'outside' => in_array($view, ['month', 'sharedMonth'], true) && $date->format('Y-m') !== $month,
            ];
        }

        return $days;
    }

    /**
     * Names of the days of the week, from the user's first day of the week.
     */
    protected function getWeekdays(int $firstDay): array
    {
        global $app_list_strings;

        $weekdays = $app_list_strings['dom_cal_day_short'] ?? [];

        $names = [];
        for ($index = $firstDay; $index < $firstDay + 7; $index++) {
            $names[] = (string)($weekdays[$index % 7 + 1] ?? '');
        }

        return $names;
    }

    /**
     * Hours of the day in the user's time format, for the time slots.
     */
    protected function getHours(): array
    {
        global $current_user, $timedate;

        $format = $timedate->get_time_format($current_user);

        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = (new \DateTimeImmutable(sprintf('2000-01-01 %02d:00:00', $hour)))->format($format);
        }

        return $hours;
    }

    /**
     * Title of the period, with the month names in the order of the user's date format.
     */
    protected function getTitle(string $view, \DateTimeImmutable $day, \DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        global $app_list_strings;

        if ($view === 'day') {
            $weekdays = $app_list_strings['dom_cal_day_long'] ?? [];

            return trim(($weekdays[(int)$day->format('w') + 1] ?? '') . ' ' . $this->longDate($day, 'dmY'));
        }

        if ($view === 'week' || $view === 'sharedWeek') {
            return $this->longDate($start, 'dmY') . ' – ' . $this->longDate($end->modify('-1 day'), 'dmY');
        }

        return $this->longDate($day, 'mY');
    }

    /**
     * A date with the month name, its parts ($parts: d, m, Y) in the order of the user's date
     * format, as the legacy calendar titles (CalendarDisplay::get_date_info()).
     */
    protected function longDate(\DateTimeImmutable $date, string $parts): string
    {
        global $app_list_strings, $current_user, $timedate;

        $months = $app_list_strings['dom_cal_month_long'] ?? [];
        $format = (string)$timedate->get_date_format($current_user);

        $values = [];
        foreach (str_split($format) as $char) {
            if (!str_contains($parts, $char)) {
                continue;
            }
            $values[] = match ($char) {
                'd' => $date->format('j'),
                'm' => (string)($months[(int)$date->format('n')] ?? $date->format('F')),
                'Y' => $date->format('Y'),
            };
        }

        return implode(' ', $values);
    }

    /**
     * A date and time of an activity (user format, as the beans give them) in the user's
     * time zone.
     */
    protected function userDateTime(?string $value): ?\DateTimeImmutable
    {
        global $current_user, $timedate;

        if (empty($value)) {
            return null;
        }

        $date = $timedate->fromUser($value, $current_user);
        if (!$date) {
            $date = $timedate->fromDb($value);
            $date = $date ? $timedate->tzUser($date, $current_user) : null;
        }
        if (!$date) {
            return null;
        }

        return new \DateTimeImmutable($date->format(self::DATETIME));
    }

    protected function parseDate(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
    }

    protected function parseDateTime(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!' . self::DATETIME, $date);

        return $parsed && $parsed->format(self::DATETIME) === $date ? $parsed : null;
    }

    /**
     * A time of the day (H:i, 24:00 for the end of the day), or null.
     */
    protected function parseTime(string $time): ?string
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return null;
        }

        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];
        if ($hours > 24 || $minutes > 59 || ($hours === 24 && $minutes > 0)) {
            return null;
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    protected function hexColor($value, string $default): string
    {
        $value = ltrim((string)$value, '#');

        return preg_match('/^[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : $default;
    }

    protected function trimLabel(string $label): string
    {
        return trim(preg_replace('/:\s*$/', '', $label));
    }

    protected function decode(?string $value): string
    {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
