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

namespace App\Module\Users\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use TimeDate;
use User;

/**
 * User preferences, type and password of the user record view, as the legacy Users views
 * handle them (UserViewHelper to show them, User::saveFormPreferences() to save them from the
 * posted form).
 *
 * The profile fields (pref_*, non-db fields of the Users vardefs) are mapped to the legacy
 * preferences and to the names of the legacy form inputs.
 */
class UserProfileHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'user-profile';

    /**
     * Profile field => [preference, legacy form input, kind]. Kinds: value (as is),
     * on-off ('on' / 'off'), one-zero ('1' / '0'), checked ('on' / ''), group-tabs ('gm' / 'm').
     */
    public const PREFERENCES = [
        'pref_mailmerge_on' => ['mailmerge_on', 'mailmerge_on', 'on-off'],
        'pref_use_real_names' => ['use_real_names', 'use_real_names', 'on-off'],
        'pref_export_delimiter' => ['export_delimiter', 'export_delimiter', 'value'],
        'pref_default_export_charset' => ['default_export_charset', 'default_export_charset', 'value'],
        'pref_reminder_checked' => ['reminder_checked', 'reminder_checked', 'one-zero'],
        'pref_reminder_time' => ['reminder_time', 'reminder_time', 'value'],
        'pref_email_reminder_checked' => ['email_reminder_checked', 'email_reminder_checked', 'one-zero'],
        'pref_email_reminder_time' => ['email_reminder_time', 'email_reminder_time', 'value'],
        'pref_snooze_alert_timer' => ['snooze_alert_timer', 'snooze_alert_timer', 'value'],
        'pref_datef' => ['datef', 'dateformat', 'value'],
        'pref_timef' => ['timef', 'timeformat', 'value'],
        'pref_timezone' => ['timezone', 'timezone', 'value'],
        'pref_currency' => ['currency', 'currency', 'value'],
        'pref_default_currency_significant_digits' => ['default_currency_significant_digits', 'default_currency_significant_digits', 'value'],
        'pref_num_grp_sep' => ['num_grp_sep', 'num_grp_sep', 'value'],
        'pref_dec_sep' => ['dec_sep', 'dec_sep', 'value'],
        'pref_default_locale_name_format' => ['default_locale_name_format', 'default_locale_name_format', 'value'],
        'pref_language' => ['language', 'language', 'value'],
        'pref_calendar_publish_key' => ['calendar_publish_key', 'calendar_publish_key', 'value'],
        'pref_fdow' => ['fdow', 'fdow', 'value'],
        'pref_use_group_tabs' => ['navigation_paradigm', 'use_group_tabs', 'group-tabs'],
        'pref_sort_modules_by_name' => ['sort_modules_by_name', 'sort_modules_by_name', 'checked'],
        'pref_subpanel_tabs' => ['subpanel_tabs', 'user_subpanel_tabs', 'checked'],
        'pref_count_collapsed_subpanels' => ['count_collapsed_subpanels', 'user_count_collapsed_subpanels', 'checked'],
        'pref_listview_pagination_type' => ['listview_pagination_type', 'listview_pagination_type', 'value'],
        'pref_subpanel_pagination_type' => ['subpanel_pagination_type', 'subpanel_pagination_type', 'value'],
        'pref_record_modal_pagination_type' => ['record_modal_pagination_type', 'record_modal_pagination_type', 'value'],
        'pref_email_link_type' => ['email_link_type', 'email_link_type', 'value'],
        'pref_editor_type' => ['editor_type', 'editor_type', 'value'],
    ];

    public const PASSWORD_FIELDS = ['old_password', 'new_password', 'confirm_new_password'];

    public const USER_TYPE_FIELD = 'UserType';

    /**
     * Type of the administrators (list user_type_dom); the other users are 'RegularUser'.
     */
    public const ADMINISTRATOR_TYPE = 'Administrator';

    /**
     * Application label of the message shown when users change the type of their own profile.
     */
    public const OWN_USER_TYPE_LABEL = 'LBL_USER_TYPE_OWN_PROFILE';

    /**
     * 'group' for a group user created from the "Create Group User" menu (URL of the create view).
     */
    public const NEW_USER_TYPE_FIELD = 'usertype';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Profile fields of a user: its preferences (with the legacy fallbacks), its type, and
     * empty password fields.
     */
    public function getValues(string $userId): array
    {
        $this->init();
        $this->startLegacyApp();

        $values = [];
        /** @var User|false|null $user */
        $user = $userId !== '' ? BeanFactory::getBean('Users', $userId) : null;
        if ($user instanceof User && !empty($user->id)) {
            $values = $this->readPreferences($user);
            $values[self::USER_TYPE_FIELD] = $user->is_admin ? self::ADMINISTRATOR_TYPE : 'RegularUser';
        }
        foreach (self::PASSWORD_FIELDS as $field) {
            $values[$field] = '';
        }

        $this->close();

        return $values;
    }

    /**
     * Options of the lists built from the configuration, and the defaults of a new user, by
     * profile field. Called while the legacy application runs (field definitions).
     */
    public function getDefinitions(): array
    {
        $this->init();
        $this->startLegacyApp();

        global $sugar_config, $locale, $app_list_strings, $app_strings, $current_language;

        // The name formats are shown with the example names of the application strings.
        if (empty($app_strings['LBL_LOCALE_NAME_EXAMPLE_FIRST'])) {
            $app_strings = return_application_language($current_language ?: ($sugar_config['default_language'] ?? 'en_us'));
        }

        $options = [
            'pref_datef' => $sugar_config['date_formats'] ?? [],
            'pref_timef' => $sugar_config['time_formats'] ?? [],
            'pref_timezone' => TimeDate::getTimezoneList(),
            'pref_currency' => $this->getCurrencies(),
            'pref_default_currency_significant_digits' => array_combine(range(0, 6), range(0, 6)),
            'pref_default_locale_name_format' => $locale->getUsableLocaleNameOptions($sugar_config['name_formats'] ?? []),
            'pref_default_export_charset' => $locale->getCharsetSelect(),
            'pref_language' => get_languages(),
            'pref_fdow' => array_values(array_filter($app_list_strings['dom_cal_day_long'] ?? [], 'strlen')),
        ];

        $defaults = [
            'pref_datef' => $sugar_config['default_date_format'] ?? '',
            'pref_timef' => $sugar_config['default_time_format'] ?? '',
            'pref_timezone' => TimeDate::guessTimezone() ?: 'UTC',
            'pref_currency' => (string)($sugar_config['default_currency'] ?? '-99'),
            'pref_default_currency_significant_digits' => (string)($sugar_config['default_currency_significant_digits'] ?? 2),
            'pref_num_grp_sep' => $sugar_config['default_number_grouping_seperator'] ?? ',',
            'pref_dec_sep' => $sugar_config['default_decimal_seperator'] ?? '.',
            'pref_default_locale_name_format' => $sugar_config['default_locale_name_format'] ?? '',
            'pref_language' => $sugar_config['default_language'] ?? 'en_us',
            'pref_export_delimiter' => $sugar_config['export_delimiter'] ?? ',',
            'pref_default_export_charset' => $sugar_config['default_export_charset'] ?? 'UTF-8',
            'pref_reminder_time' => '60',
            'pref_email_reminder_time' => '60',
            'pref_snooze_alert_timer' => (string)($sugar_config['snooze_alert_timer'] ?? 600),
            'pref_fdow' => '0',
            'pref_use_group_tabs' => ($sugar_config['default_navigation_paradigm'] ?? 'gm') === 'gm',
            'pref_subpanel_tabs' => !empty($sugar_config['default_subpanel_tabs']),
            'pref_listview_pagination_type' => $sugar_config['listview_pagination_type'] ?? '',
            'pref_subpanel_pagination_type' => $sugar_config['subpanel_pagination_type'] ?? '',
            'pref_record_modal_pagination_type' => $sugar_config['record_modal_pagination_type'] ?? '',
            'pref_email_link_type' => 'sugar',
            'pref_editor_type' => 'tinymce',
        ];

        $this->close();

        return ['options' => $options, 'defaults' => $defaults];
    }

    /**
     * The legacy form inputs of the given profile fields, as User::saveFormPreferences()
     * reads them from $_POST.
     */
    public function toFormInputs(array $values): array
    {
        $inputs = [];
        foreach (self::PREFERENCES as $field => [, $input, $kind]) {
            if (!array_key_exists($field, $values)) {
                continue;
            }
            $value = $values[$field];
            $checked = $value === true || in_array(strtolower((string)$value), ['1', 'true', 'on', 'yes'], true);
            switch ($kind) {
                case 'on-off':
                    $inputs[$input] = $checked ? 'on' : 'off';
                    break;
                case 'one-zero':
                    $inputs[$input] = $checked ? '1' : '0';
                    break;
                case 'checked':
                    $inputs[$input] = $checked ? 'on' : '';
                    break;
                case 'group-tabs':
                    $inputs[$input] = $checked ? 'gm' : 'm';
                    break;
                default:
                    $inputs[$input] = is_scalar($value) ? (string)$value : '';
            }
        }
        if (array_key_exists(self::USER_TYPE_FIELD, $values) && (string)$values[self::USER_TYPE_FIELD] !== '') {
            $inputs[self::USER_TYPE_FIELD] = (string)$values[self::USER_TYPE_FIELD];
        }
        // Always posted by the legacy form ("prompt for the time zone" unchecked): read
        // without a check by User::saveFormPreferences().
        $inputs['ut'] = '';

        return $inputs;
    }

    /**
     * Checks a password change before the user is saved: confirmation, password rules and,
     * when required, the current password (as User::change_password()).
     */
    public function validatePassword(string $userId, string $userName, string $old, string $new, string $confirm): void
    {
        $this->init();
        $this->startLegacyApp();

        $error = '';
        $user = $userId !== '' ? BeanFactory::getBean('Users', $userId) : BeanFactory::newBean('Users');
        if ($new !== $confirm) {
            $error = translate('ERR_PASSWORD_MISMATCH', 'Users');
        } elseif ($user instanceof User) {
            $error = (string)$user->passwordValidationCheck($new);
            if ($error === '' && !empty($user->id) && $this->needsOldPassword($user)
                && empty(User::findUserPassword($user->user_name ?: $userName, md5($old)))) {
                $error = translate('ERR_PASSWORD_INCORRECT_OLD_1', 'Users') . $user->user_name
                    . translate('ERR_PASSWORD_INCORRECT_OLD_2', 'Users');
            }
        }

        $this->close();

        if ($error !== '') {
            throw new BadRequestHttpException(trim(strip_tags($error)));
        }
    }

    /**
     * Sets the new password of a saved user (User::change_password(): rules, current password).
     */
    public function changePassword(string $userId, string $old, string $new): void
    {
        $this->init();
        $this->startLegacyApp();

        $error = '';
        $user = BeanFactory::getBean('Users', $userId);
        if ($user instanceof User && !$user->change_password($old, $new)) {
            $error = sprintf(translate('ERR_PASSWORD_CHANGE', 'Users'), strip_tags((string)$user->error_string));
        }

        $this->close();

        if ($error !== '') {
            throw new BadRequestHttpException($error);
        }
    }

    /**
     * "Change Password" action: an administrator of users or the user itself changes the password.
     * Returns [whether the password changed, message to show].
     */
    public function changePasswordFromAction(string $userId, string $old, string $new, string $confirm): array
    {
        $this->init();
        $this->startLegacyApp();

        global $current_user;

        $user = BeanFactory::getBean('Users', $userId);
        if (!$user instanceof User || empty($user->id)) {
            $error = translate('LBL_ACTION_ERROR');
        } elseif (!$current_user->isAdminForModule('Users') && $current_user->id !== $user->id) {
            $error = translate('ERR_PASSWORD_NOT_ALLOWED', 'Users');
        } elseif ($new !== $confirm) {
            $error = translate('ERR_PASSWORD_MISMATCH', 'Users');
        } elseif (!$user->change_password($old, $new)) {
            $error = trim(strip_tags((string)$user->error_string));
        } else {
            $error = '';
        }
        $result = $error === '' ? [true, translate('LBL_PASSWORD_SENT', 'Users')] : [false, $error];

        $this->close();

        return $result;
    }

    /**
     * Whether the type would change the type of the current user. As in the legacy edit view,
     * users do not change the type of their own profile (an administrator would give up its
     * own access); only administrators change the type of the other users (User::save()).
     */
    public function isOwnTypeChange(string $userId, string $type): bool
    {
        if ($userId === '' || $type === '') {
            return false;
        }

        $this->init();
        $this->startLegacyApp();

        global $current_user;

        $changed = false;
        if (($current_user->id ?? '') === $userId) {
            $user = BeanFactory::getBean('Users', $userId);
            $changed = $user instanceof User && ($type === self::ADMINISTRATOR_TYPE) !== (bool)$user->is_admin;
        }

        $this->close();

        return $changed;
    }

    /**
     * Refuses a save that changes the type of the current user (see isOwnTypeChange(); the
     * edit view checks it first, with UserTypeValidator).
     */
    public function checkUserType(string $userId, string $type): void
    {
        if (!$this->isOwnTypeChange($userId, $type)) {
            return;
        }

        $this->init();
        $this->startLegacyApp();
        $error = translate(self::OWN_USER_TYPE_LABEL);
        $this->close();

        throw new BadRequestHttpException($error);
    }

    /**
     * A new user that does not log in with an external authentication needs a password.
     */
    public function checkNewUserPassword(array $attributes): void
    {
        $external = $attributes['external_auth_only'] ?? false;
        if ($external === true || in_array(strtolower((string)$external), ['1', 'true', 'on'], true)) {
            return;
        }

        $this->init();
        $this->startLegacyApp();
        $error = translate('ERR_PASSWORD_REQUIRED', 'Users');
        $this->close();

        throw new BadRequestHttpException($error);
    }

    protected function needsOldPassword(User $user): bool
    {
        global $current_user;

        return !$current_user->isAdminForModule('Users') || $current_user->id === $user->id;
    }

    protected function readPreferences(User $user): array
    {
        global $locale, $sugar_config;

        $preference = static fn (string $name) => $user->getPreference($name);
        $checked = static fn ($value): bool => in_array(strtolower((string)$value), ['1', 'true', 'on', 'yes'], true);

        $values = [
            'pref_mailmerge_on' => $preference('mailmerge_on') === 'on',
            'pref_use_real_names' => $preference('use_real_names') === 'on',
            'pref_export_delimiter' => (string)($preference('export_delimiter') ?? $sugar_config['export_delimiter'] ?? ','),
            'pref_default_export_charset' => (string)($preference('default_export_charset') ?: ($sugar_config['default_export_charset'] ?? 'UTF-8')),
            'pref_reminder_checked' => $checked($preference('reminder_checked')),
            'pref_reminder_time' => (string)($preference('reminder_time') ?? ''),
            'pref_email_reminder_checked' => $checked($preference('email_reminder_checked')),
            'pref_email_reminder_time' => (string)($preference('email_reminder_time') ?? ''),
            'pref_snooze_alert_timer' => (string)($preference('snooze_alert_timer') ?? $sugar_config['snooze_alert_timer'] ?? 600),
            'pref_datef' => (string)$locale->getPrecedentPreference('datef', $user),
            'pref_timef' => (string)$locale->getPrecedentPreference('timef', $user),
            'pref_timezone' => (string)($preference('timezone') ?? ''),
            'pref_currency' => (string)($locale->getPrecedentPreference('currency', $user) ?: '-99'),
            'pref_default_currency_significant_digits' => (string)$locale->getPrecedentPreference('default_currency_significant_digits', $user),
            'pref_num_grp_sep' => (string)($preference('num_grp_sep') ?: ($sugar_config['default_number_grouping_seperator'] ?? ',')),
            'pref_dec_sep' => (string)($preference('dec_sep') ?: ($sugar_config['default_decimal_seperator'] ?? '.')),
            'pref_default_locale_name_format' => (string)$locale->getPrecedentPreference('default_locale_name_format', $user),
            'pref_language' => (string)($preference('language') ?: ($sugar_config['default_language'] ?? 'en_us')),
            'pref_calendar_publish_key' => (string)($preference('calendar_publish_key') ?? ''),
            'pref_fdow' => (string)($user->get_first_day_of_week() ?? 0),
            'pref_use_group_tabs' => ($preference('navigation_paradigm') ?? $sugar_config['default_navigation_paradigm'] ?? 'gm') === 'gm',
            'pref_sort_modules_by_name' => $checked($preference('sort_modules_by_name')),
            'pref_subpanel_tabs' => $checked($preference('subpanel_tabs') ?? ($sugar_config['default_subpanel_tabs'] ?? '')),
            'pref_count_collapsed_subpanels' => $checked($preference('count_collapsed_subpanels')),
            'pref_listview_pagination_type' => (string)($preference('listview_pagination_type') ?: ($sugar_config['listview_pagination_type'] ?? '')),
            'pref_subpanel_pagination_type' => (string)($preference('subpanel_pagination_type') ?: ($sugar_config['subpanel_pagination_type'] ?? '')),
            'pref_record_modal_pagination_type' => (string)($preference('record_modal_pagination_type') ?: ($sugar_config['record_modal_pagination_type'] ?? '')),
            'pref_email_link_type' => (string)($preference('email_link_type') ?: 'sugar'),
            'pref_editor_type' => (string)($preference('editor_type') ?: 'tinymce'),
        ];

        // The front end sends booleans as strings.
        foreach ($values as $field => $value) {
            if (is_bool($value)) {
                $values[$field] = $value ? 'true' : 'false';
            }
        }

        return $values;
    }

    /**
     * Active currencies as the legacy list shows them ("name : symbol"), the system currency first.
     */
    protected function getCurrencies(): array
    {
        $currency = BeanFactory::newBean('Currencies');
        $currencies = ['-99' => $currency->getDefaultCurrencyName() . ' : ' . $currency->getDefaultCurrencySymbol()];
        foreach ($currency->get_full_list('name', "currencies.status = 'Active'") ?: [] as $row) {
            $currencies[$row->id] = $row->name . ' : ' . $row->symbol;
        }

        return $currencies;
    }
}
