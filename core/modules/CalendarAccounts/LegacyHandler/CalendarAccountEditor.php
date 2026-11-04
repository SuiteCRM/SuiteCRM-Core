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

namespace App\Module\CalendarAccounts\LegacyHandler;

use App\Module\MailAccounts\LegacyHandler\MailAccountEditor;
use BeanFactory;
use CalendarAccount;
use CalendarSync;
use Throwable;

/**
 * The calendar accounts (calendar synchronization) in SuiteCRM 8, as the legacy
 * CalendarAccount edit view, in the mail account editor:
 * - the form: name, source, type, calendar user (changed by an admin), and the authentication
 *   fields of the source (OAuth connection, user name and password, or API key: js/
 *   auth_fields_visibility.js), with the synchronization status;
 * - Test Connection (CalendarAccountController::action_testConnection()): the connection status
 *   and the calendar found are saved with the account, as the legacy form;
 * - the save by CalendarAccount::save() (its validation: one personal account by user, group
 *   accounts by an admin, one account by calendar);
 * - Sync Now of the record view (action_syncCalendarAccount()).
 */
class CalendarAccountEditor extends MailAccountEditor
{
    public const HANDLER_KEY = 'calendar-account-editor';

    /** The types of a new account (legacy menu): personal, group by an admin. */
    protected const TYPES = ['personal', 'group'];

    /** The authentication fields of each method (CalendarSync::getFieldsToHide()). */
    protected const AUTH_FIELDS = [
        'oauth2' => ['oauth_connection_name'],
        'basic' => ['username', 'password', 'server_url'],
        'api_key' => ['api_key', 'api_endpoint'],
    ];

    /** The fields of the form saved on the account (write-only ones only when given). */
    protected const FIELDS = ['name', 'source', 'oauth_connection_id', 'username', 'server_url', 'api_endpoint'];

    protected const SECRET_FIELDS = ['password', 'api_key'];

    /** The status of the connection test, posted with the form as the legacy page. */
    protected const TEST_FIELDS = ['last_connection_status', 'last_connection_test', 'external_calendar_id'];

    protected const LABELS = [
        'LBL_MODULE_NAME', 'LNK_LIST_CREATE_NEW_PERSONAL', 'LNK_LIST_CREATE_NEW_GROUP', 'LBL_TEST_CONNECTION', 'LBL_SELECT_BUTTON_LABEL',
        'LBL_SYNC_STATUS',
    ];

    /** Labels of the editor (application strings). */
    protected const EDITOR_LABELS = ['LBL_ACCOUNT_EDITOR_ERROR', 'LBL_ACCOUNT_EDITOR_REQUIRED', 'LBL_ACCOUNT_EDITOR_SAVE_ERROR', 'LBL_ACCOUNT_EDITOR_TESTING'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    protected function module(): string
    {
        return 'CalendarAccount';
    }

    /**
     * The account to edit (a new account of the given type without id) with its form; null when
     * the user cannot edit it.
     */
    public function load(string $id, string $type): ?array
    {
        return $this->inLegacy(function () use ($id, $type) {
            $account = $id !== '' ? $this->account($id, 'edit') : $this->newAccount($type);
            if ($account === null) {
                return null;
            }

            $values = [
                'type' => (string)$account->type,
                'calendar_user_id' => (string)$account->calendar_user_id,
                'calendar_user_name' => $this->relatedName('Users', (string)$account->calendar_user_id),
                'oauth_connection_name' => $this->relatedName('ExternalOAuthConnection', (string)$account->oauth_connection_id),
                'password' => '',
                'api_key' => '',
                'last_connection_status' => !empty($account->last_connection_status) ? '1' : '0',
            ];
            foreach (array_merge(self::FIELDS, self::TEST_FIELDS) as $field) {
                $values[$field] ??= $this->decode($account->$field ?? '');
            }
            foreach (['last_sync_attempt_status', 'last_sync_attempt_message', 'last_sync_attempt_date', 'last_sync_date'] as $field) {
                $values[$field] = $this->decode($account->$field ?? '');
            }

            return [
                'id' => (string)$account->id,
                'module' => 'calendar-account',
                'title' => $id !== '' ? $this->decode($account->name)
                    : $this->label($account->type === 'group' ? 'LNK_LIST_CREATE_NEW_GROUP' : 'LNK_LIST_CREATE_NEW_PERSONAL'),
                'values' => $values,
                'panels' => $this->panels($account),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * Tests the connection with the settings of the form (CalendarAccountController::
     * action_testConnection()): success, the message, and the status of the test saved with the
     * account (connection status, date, calendar found).
     *
     * @param array<string, mixed> $values
     */
    public function test(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            global $timedate;

            $account = $id !== '' ? $this->account($id, 'edit') : $this->newAccount((string)($values['type'] ?? ''));
            if ($account === null) {
                return null;
            }

            $source = trim((string)($values['source'] ?? ''));
            $error = $source === '' ? $this->label('LBL_SELECT_CALENDAR_SOURCE_FIRST') : $this->missingAuthFields($account, $source, $values);
            if ($error !== '') {
                return ['success' => false, 'message' => $error];
            }

            $failed = ['last_connection_status' => '0', 'last_connection_test' => '', 'external_calendar_id' => ''];
            try {
                $calendarSync = $this->calendarSync();
                $tested = $this->testedAccount($account, $source, $values);
                $result = $calendarSync->testProviderConnectionWithValidation($tested);
                if (!$result->isSuccessful()) {
                    return ['success' => false, 'message' => 'Connection test failed. ' . $result->getErrorMessage(), 'values' => $failed];
                }

                $externalCalendarId = (string)($result->getExternalCalendarId() ?? '');
                $duplicate = $externalCalendarId !== '' ? $calendarSync->findDuplicateCalendarAccount($externalCalendarId, $tested->id) : null;
                if ($duplicate) {
                    return [
                        'success' => false,
                        'message' => $this->label('LBL_DUPLICATE_CALENDAR_ERROR') . ': "' . $this->decode($duplicate->name ?? '') . '"',
                        'values' => $failed,
                    ];
                }
            } catch (Throwable $throwable) {
                return ['success' => false, 'message' => $throwable->getMessage(), 'values' => $failed];
            }

            return [
                'success' => true,
                'message' => $this->label('LBL_CONNECTION_TEST_SUCCESS_MESSAGE'),
                'values' => ['last_connection_status' => '1', 'last_connection_test' => $timedate->nowDb(), 'external_calendar_id' => $externalCalendarId],
            ];
        });
    }

    /**
     * Saves the account as the legacy form: the id of the account, or the error that stops the
     * save (the validation of CalendarAccount::save()).
     *
     * @param array<string, mixed> $values
     */
    public function save(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            global $current_user;

            $account = $id !== '' ? $this->account($id, 'edit') : $this->newAccount((string)($values['type'] ?? ''));
            if ($account === null) {
                return null;
            }

            $source = trim((string)($values['source'] ?? ''));
            if (trim((string)($values['name'] ?? '')) === '' || !array_key_exists($source, $this->sources())) {
                return ['error' => $this->label('LBL_ACCOUNT_EDITOR_REQUIRED')];
            }

            foreach (self::FIELDS as $field) {
                $value = $values[$field] ?? '';
                $account->$field = is_scalar($value) ? trim((string)$value) : '';
            }
            // Kept by CalendarAccount::save() when none is given (write-only fields).
            foreach (self::SECRET_FIELDS as $field) {
                $account->$field = trim((string)($values[$field] ?? ''));
            }
            foreach (self::TEST_FIELDS as $field) {
                $account->$field = trim((string)($values[$field] ?? ''));
            }
            // The calendar user: changed by an admin only (js/auth_fields_visibility.js).
            $userId = trim((string)($values['calendar_user_id'] ?? ''));
            if (is_admin($current_user) && $userId !== '') {
                $account->calendar_user_id = $userId;
            }

            try {
                $account->save();
            } catch (Throwable $throwable) {
                return ['error' => $throwable->getMessage()];
            }

            return ['id' => (string)$account->id];
        });
    }

    /**
     * Synchronizes the meetings of the account now (Sync Now of the legacy detail view): success
     * and the message of the legacy page.
     */
    public function sync(string $id): ?array
    {
        return $this->inLegacy(function () use ($id) {
            if ($this->account($id, 'view') === null) {
                return null;
            }

            try {
                $this->calendarSync()->syncAllMeetingsOfCalendarAccount($id);
            } catch (Throwable $throwable) {
                return ['success' => false, 'message' => $this->label('LBL_SYNC_FAILED') . ': ' . $throwable->getMessage()];
            }

            return ['success' => true, 'message' => $this->label('LBL_SYNC_SUCCESS')];
        });
    }

    protected function account(string $id, string $access): ?CalendarAccount
    {
        // retrieve() returns null without access to the account.
        $account = BeanFactory::getBean('CalendarAccount', $id);

        return $account instanceof CalendarAccount && !empty($account->id) && $account->ACLAccess($access) ? $account : null;
    }

    /** A new account of the given type for the user, as the legacy edit view. */
    protected function newAccount(string $type): ?CalendarAccount
    {
        global $current_user;

        if (!in_array($type, self::TYPES, true) || ($type === 'group' && !is_admin($current_user))) {
            return null;
        }

        /** @var CalendarAccount $account */
        $account = BeanFactory::newBean('CalendarAccount');
        $account->populateDefaultValues(true);
        $account->type = $type;
        $account->calendar_user_id = $current_user->id;

        return $account->ACLAccess('edit') ? $account : null;
    }

    /**
     * The account tested: the saved one (its saved secrets when none is given) or a new one, with
     * the settings of the form (CalendarAccountController::setupCalendarAccountFromRequest()).
     *
     * @param array<string, mixed> $values
     */
    protected function testedAccount(CalendarAccount $account, string $source, array $values): CalendarAccount
    {
        $tested = clone $account;
        $tested->source = $source;
        $tested->id = $tested->id ?: 'uncreated_calendar_account';
        $tested->calendar_user_id = $tested->calendar_user_id ?: 'unlinked_calendar_account';

        foreach (['oauth_connection_id', 'username', 'password', 'api_key'] as $field) {
            $value = trim((string)($values[$field] ?? ''));
            if ($value !== '') {
                $tested->$field = $value;
            }
        }
        foreach (['server_url', 'api_endpoint'] as $field) {
            $value = trim((string)($values[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (!validate_external_host($value)) {
                throw new \InvalidArgumentException($field === 'server_url'
                    ? 'The provided server URL is not allowed.' : 'The provided API endpoint URL is not allowed.');
            }
            $tested->$field = $value;
        }

        return $tested;
    }

    /**
     * The message when the fields the test needs are missing ('' when they are given), as
     * js/sync_actions.js: the OAuth connection; the user name and server; the API key (unless saved).
     *
     * @param array<string, mixed> $values
     */
    protected function missingAuthFields(CalendarAccount $account, string $source, array $values): string
    {
        $filled = fn (string $field) => trim((string)($values[$field] ?? '')) !== '';

        return match ($this->authMethods()[$source] ?? '') {
            'oauth2' => $filled('oauth_connection_id') ? '' : $this->label('LBL_OAUTH_CONNECTION_REQUIRED'),
            'basic' => $filled('username') && $filled('server_url') ? '' : $this->label('LBL_BASIC_AUTH_FIELDS_REQUIRED'),
            'api_key' => $filled('api_key') || !empty($account->api_key) ? '' : $this->label('LBL_API_KEY_REQUIRED'),
            default => $this->label('LBL_UNKNOWN_AUTH_METHOD') . ': ' . $source,
        };
    }

    protected function panels(CalendarAccount $account): array
    {
        global $app_list_strings, $app_strings, $current_user;

        $field = fn (string $name, string $type, array $extra = []) => $this->field($account, $name, $type, $extra);
        // The sources of each authentication method, to show its fields.
        $sourcesOf = [];
        foreach ($this->authMethods() as $source => $method) {
            $sourcesOf[$method][] = (string)$source;
        }
        $auth = fn (string $method) => ['source' => $sourcesOf[$method] ?? ['-']];
        $readonly = fn (string $name, array $extra = []) => $field($name, 'readonly', $extra);

        return [
            [
                'key' => 'default',
                'label' => '',
                'test' => ['label' => $this->label('LBL_TEST_CONNECTION'), 'wait' => $this->label('LBL_ACCOUNT_EDITOR_TESTING')],
                'fields' => [
                    $field('name', 'text', ['required' => true]),
                    $field('source', 'enum', ['required' => true, 'options' => $this->options($this->sources())]),
                    $readonly('type', ['options' => $this->options($app_list_strings['calendar_account_types'] ?? [])]),
                    // Changed by an admin only (js/auth_fields_visibility.js handleCalendarUserField()).
                    is_admin($current_user)
                        ? $field('calendar_user_name', 'relate', ['module' => 'users', 'idField' => 'calendar_user_id', 'required' => true])
                        : $readonly('calendar_user_name'),
                    $field('oauth_connection_name', 'relate', [
                        'module' => 'external-oauth-connection', 'idField' => 'oauth_connection_id', 'show' => $auth('oauth2'), 'clear' => true,
                    ]),
                    $field('username', 'text', ['show' => $auth('basic'), 'clear' => true]),
                    $field('server_url', 'text', ['show' => $auth('basic'), 'clear' => true]),
                    $field('password', 'password', ['show' => $auth('basic'), 'clear' => true, 'isSet' => !empty($account->password)]),
                    $field('api_key', 'password', ['show' => $auth('api_key'), 'clear' => true, 'isSet' => !empty($account->api_key)]),
                    $field('api_endpoint', 'text', ['show' => $auth('api_key'), 'clear' => true]),
                ],
            ],
            [
                'key' => 'status',
                'label' => $this->label('LBL_SYNC_STATUS'),
                'fields' => [
                    $readonly('last_connection_status', ['options' => [
                        ['value' => '1', 'label' => $this->decode($app_strings['LBL_YES'] ?? 'Yes')],
                        ['value' => '0', 'label' => $this->decode($app_strings['LBL_NO'] ?? 'No')],
                    ]]),
                    $readonly('last_connection_test', ['format' => 'datetime']),
                    $readonly('last_sync_attempt_status', ['options' => $this->options($app_list_strings['sync_attempt_status_list'] ?? [])]),
                    $readonly('last_sync_attempt_date', ['format' => 'datetime']),
                    $readonly('last_sync_attempt_message', ['options' => $this->options($app_list_strings['sync_attempt_message_list'] ?? [])]),
                    $readonly('last_sync_date', ['format' => 'datetime']),
                ],
            ],
        ];
    }

    /** The calendar sources (CalendarSync::getCalendarSourceTypes()). */
    protected function sources(): array
    {
        return $this->calendarSync()->getCalendarSourceTypes();
    }

    /** The authentication method of each source. */
    protected function authMethods(): array
    {
        $methods = [];
        foreach (array_keys($this->sources()) as $source) {
            try {
                $methods[(string)$source] = $this->calendarSync()->getProviderAuthMethodWithValidation((string)$source);
            } catch (Throwable) {
                continue;
            }
        }

        return $methods;
    }

    protected function calendarSync(): CalendarSync
    {
        require_once 'include/CalendarSync/CalendarSync.php';

        return CalendarSync::getInstance();
    }

    protected function labels(): array
    {
        $labels = [];
        foreach (array_merge(self::EDITOR_LABELS, self::LABELS) as $key) {
            $labels[$key] = $this->label($key);
        }

        return $labels;
    }

}
