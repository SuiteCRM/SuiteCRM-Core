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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;
use CalendarSync;

/**
 * The calendar synchronization settings (legacy Administration&action=CalendarSyncSettings):
 * status of the scheduler and of the runs, batch control, sync window and conflict resolution,
 * logic hooks, saved by CalendarSync::saveConfig(); and the manual synchronization of all the
 * calendar accounts (CalendarSync::syncAllCalendarAccounts()), after a confirmation, as the legacy
 * page.
 */
class CalendarSyncSettingsPage extends AdminSettingsPage
{
    /** The number fields, with their range (min and max of the legacy inputs). */
    protected const NUMBERS = [
        'max_accounts_per_sync' => [1, 200],
        'max_operations_per_account' => [1, 1000],
        'sync_window_past_days' => [0, 365],
        'sync_window_future_days' => [1, 730],
    ];

    public function getPageKey(): string
    {
        return 'calendar-sync';
    }

    protected function getPage(): array
    {
        $calendarSync = $this->calendarSync();
        $config = $calendarSync->getConfig();

        $conflictResolutions = [];
        foreach ($calendarSync->getConflictResolutionCases() as $case) {
            $conflictResolutions[$case->value] = $this->text('LBL_CALENDAR_SYNC_CONFLICT_' . strtoupper($case->value), 'Administration', false);
        }

        $number = fn (string $name, string $label) => $this->field($name, $label, 'number', [
            'max' => self::NUMBERS[$name][1],
            'help' => $label . '_DESC',
        ]);

        return [
            'title' => $this->text('LBL_CALENDAR_SYNC_SETTINGS'),
            'sections' => array_values(array_filter([
                $this->section('LBL_CALENDAR_SYNC_SETTINGS', [
                    ['name' => 'status', 'label' => '', 'type' => 'html', 'help' => $this->statusHtml($calendarSync, $config), 'wide' => true],
                ], 'Administration', 'LBL_CALENDAR_SYNC_DESCRIPTION'),
                $this->section('LBL_CALENDAR_SYNC_BATCH_CONTROL', [
                    $number('max_accounts_per_sync', 'LBL_CALENDAR_SYNC_MAX_ACCOUNTS_PER_SYNC'),
                    $number('max_operations_per_account', 'LBL_CALENDAR_SYNC_MAX_OPERATIONS_PER_ACCOUNT'),
                ]),
                $this->section('LBL_CALENDAR_SYNC_ADVANCED_SETTINGS', [
                    $number('sync_window_past_days', 'LBL_CALENDAR_SYNC_WINDOW_PAST_DAYS'),
                    $number('sync_window_future_days', 'LBL_CALENDAR_SYNC_WINDOW_FUTURE_DAYS'),
                    $this->field('conflict_resolution', 'LBL_CALENDAR_SYNC_CONFLICT_RESOLUTION', 'select', [
                        'options' => $this->options($conflictResolutions),
                        'help' => 'LBL_CALENDAR_SYNC_CONFLICT_RESOLUTION_DESC',
                    ]),
                ]),
                $this->section('LBL_CALENDAR_SYNC_LOGIC_HOOKS_CONTROL', [
                    $this->field('enable_calendar_sync_logic_hooks', 'LBL_CALENDAR_SYNC_ENABLE_LOGIC_HOOKS', 'bool', [
                        'help' => 'LBL_CALENDAR_SYNC_ENABLE_LOGIC_HOOKS_DESC',
                    ]),
                ]),
                $this->section('LBL_CALENDAR_SYNC_MANUAL_TRIGGER_TITLE', [], 'Administration', 'LBL_CALENDAR_SYNC_MANUAL_TRIGGER_DESC', [
                    'trigger' => ['LBL_CALENDAR_SYNC_MANUAL_TRIGGER_BUTTON', 'LBL_CALENDAR_SYNC_MANUAL_TRIGGER_CONFIRM'],
                ]),
            ])),
            'values' => [
                'max_accounts_per_sync' => (string)($config['max_accounts_per_sync'] ?? ''),
                'max_operations_per_account' => (string)($config['max_operations_per_account'] ?? ''),
                'sync_window_past_days' => (string)($config['sync_window_past_days'] ?? ''),
                'sync_window_future_days' => (string)($config['sync_window_future_days'] ?? ''),
                'conflict_resolution' => (string)($config['conflict_resolution'] ?? ''),
                'enable_calendar_sync_logic_hooks' => filter_var($config['enable_calendar_sync_logic_hooks'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $calendarSync = $this->calendarSync();

        $post = [];
        foreach (self::NUMBERS as $name => [$min, $max]) {
            $value = trim((string)($values[$name] ?? ''));
            if (!ctype_digit($value) || (int)$value < $min || (int)$value > $max) {
                return $this->text('LBL_CALENDAR_SYNC_SETTINGS_SAVED_FAILED', 'Administration', false);
            }
            $post[$name] = $value;
        }
        $conflictResolutions = array_map(static fn ($case) => $case->value, $calendarSync->getConflictResolutionCases());
        if (!in_array((string)($values['conflict_resolution'] ?? ''), $conflictResolutions, true)) {
            return $this->text('LBL_CALENDAR_SYNC_SETTINGS_SAVED_FAILED', 'Administration', false);
        }
        $post['conflict_resolution'] = (string)$values['conflict_resolution'];
        $post['enable_calendar_sync_logic_hooks'] = !empty($values['enable_calendar_sync_logic_hooks']) ? '1' : '0';

        return $calendarSync->saveConfig($post) ? null : $this->text('LBL_CALENDAR_SYNC_SETTINGS_SAVED_FAILED', 'Administration', false);
    }

    protected function runAction(string $action, array $values): array
    {
        if ($action !== 'trigger') {
            return parent::runAction($action, $values);
        }

        $success = $this->calendarSync()->syncAllCalendarAccounts(true);

        return [
            'status' => $success ? 'success' : 'error',
            'message' => $this->text($success ? 'LBL_CALENDAR_SYNC_MANUAL_TRIGGER_SUCCESS' : 'LBL_CALENDAR_SYNC_MANUAL_TRIGGER_FAILED', 'Administration', false),
            // The time of the last manual run changes.
            'reload' => true,
        ];
    }

    /**
     * The status of the legacy page: the Google sync migration warning, the scheduler (status and
     * last run, with a link to it) and the last manual run, in the format of the user.
     *
     * @param array<string, mixed> $config
     */
    protected function statusHtml(CalendarSync $calendarSync, array $config): string
    {
        global $timedate;

        $html = '';
        $migration = (string)($GLOBALS['db']->getOne(
            "SELECT value FROM config WHERE category = 'calendar_sync' AND name = 'google_sync_migration_status'"
        ) ?: '');
        if (in_array($migration, ['pending', 'failed'], true)) {
            $prefix = $migration === 'failed' ? 'LBL_GOOGLE_SYNC_MIGRATION_FAILED' : 'LBL_GOOGLE_SYNC_MIGRATION_PENDING';
            $html .= '<p><b>' . htmlspecialchars($this->text($prefix . '_TITLE', 'Administration', false)) . '</b> '
                . htmlspecialchars($this->text($prefix . '_DESC', 'Administration', false)) . '</p>';
        }

        $scheduler = $calendarSync->getScheduler();
        if ($scheduler === null) {
            return $html;
        }

        $never = $this->text('LBL_CALENDAR_SYNC_NEVER', 'Administration', false);
        $lastScheduled = !empty($scheduler->last_run) ? $timedate->to_display_date_time($scheduler->last_run) : $never;
        $lastManual = !empty($config['last_manual_run_time']) ? $timedate->to_display_date_time($config['last_manual_run_time']) : $never;
        $status = $scheduler->status === 'Active' ? 'LBL_CALENDAR_SYNC_ACTIVE' : 'LBL_CALENDAR_SYNC_INACTIVE';
        $rows = [
            'LBL_CALENDAR_SYNC_SCHEDULER_STATUS' => '<a href="#/schedulers/record/' . rawurlencode((string)$scheduler->id) . '">'
                . htmlspecialchars($this->text($status, 'Administration', false)) . '</a>',
            'LBL_CALENDAR_SYNC_LAST_SCHEDULED_RUN' => htmlspecialchars((string)$lastScheduled),
            'LBL_CALENDAR_SYNC_LAST_MANUAL_RUN' => htmlspecialchars((string)$lastManual),
        ];

        $html .= '<h4>' . htmlspecialchars($this->text('LBL_CALENDAR_SYNC_STATUS_INFO', 'Administration', false)) . '</h4><ul>';
        foreach ($rows as $label => $value) {
            $html .= '<li>' . htmlspecialchars($this->text($label)) . ': ' . $value . '</li>';
        }

        return $html . '</ul>';
    }

    protected function calendarSync(): CalendarSync
    {
        require_once 'include/CalendarSync/CalendarSync.php';

        return CalendarSync::getInstance();
    }
}
