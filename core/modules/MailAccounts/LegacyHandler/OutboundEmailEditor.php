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

namespace App\Module\MailAccounts\LegacyHandler;

use BeanFactory;
use OutboundEmail;
use OutboundEmailAccounts;
use SugarBean;
use User;

/**
 * The outbound email accounts in SuiteCRM 8, as the legacy OutboundEmailAccounts edit
 * view:
 * - the form of the account (type, SMTP server, sender and signature), with the rules of its
 *   scripts (js/panel_toggle.js, owner_toggle.js, auth_type_fields_toggle.js, ssl_port_set.js);
 * - the test email (EmailMan/testOutboundEmail.php: Email::sendEmailTest() with the settings of
 *   the form);
 * - the save, with the checks of OutboundEmailAccountsController::action_save() (one system
 *   account, the type of the OAuth connection), by OutboundEmailAccounts::save() (password kept
 *   or encrypted, authentication, sender of the system notifications);
 * - Set as default of a personal account (OutboundEmailAccountsController::action_SetDefault()).
 */
class OutboundEmailEditor extends MailAccountEditor
{
    public const HANDLER_KEY = 'outbound-email-editor';

    /** The types of a new account: personal by its owner, group and system by an admin. */
    protected const TYPES = ['user', 'group', 'system'];

    protected const LABELS = [
        'LBL_MODULE_NAME', 'LNK_LIST_CREATE_NEW_PERSONAL', 'LNK_LIST_CREATE_NEW_GROUP', 'LBL_SELECT_BUTTON_LABEL',
        'LBL_EMAIL_TEST_OUTBOUND_SETTINGS', 'LBL_EMAIL_SETTINGS_FROM_TO_EMAIL_ADDR', 'LBL_EMAIL_SEND',
        'LBL_EMAIL_TEST_SEE_FULL_SMTP_LOG', 'LBL_EMAIL_ONE_MOMENT',
    ];

    /** Labels of the editor (application strings). */
    protected const EDITOR_LABELS = ['LBL_ACCOUNT_EDITOR_ERROR', 'LBL_ACCOUNT_EDITOR_REQUIRED', 'LBL_ACCOUNT_EDITOR_SAVE_ERROR', 'LBL_ACCOUNT_EDITOR_NEW_SYSTEM'];

    /** The fields of the form, set on the account as the legacy form posts them. */
    protected const FIELDS = [
        'name', 'auth_type', 'mail_smtpuser', 'mail_smtpserver', 'mail_smtpssl', 'external_oauth_connection_id', 'mail_smtpport',
        'smtp_from_name', 'reply_to_name', 'smtp_from_addr', 'reply_to_addr', 'signature',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    protected function module(): string
    {
        return 'OutboundEmailAccounts';
    }

    /**
     * The account to edit (a new account of the given type without id) with its form; null when
     * the user cannot edit it.
     */
    public function load(string $id, string $type): ?array
    {
        return $this->inLegacy(function () use ($id, $type) {
            $account = $id !== '' ? $this->account($id) : $this->newAccount($type);
            if ($account === null) {
                return null;
            }

            return [
                'id' => (string)$account->id,
                'module' => 'outbound-email-accounts',
                'title' => $id !== '' ? $this->decode($account->name) : $this->label($this->newTitle((string)$account->type)),
                'values' => $this->values($account),
                'panels' => $this->panels($account),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * Sends the test email of the legacy page with the settings of the form (the saved password
     * when none is given) to the given address: success, the message and the SMTP log of a failure.
     *
     * @param array<string, mixed> $values
     */
    public function test(string $id, array $values, string $address): ?array
    {
        return $this->inLegacy(function () use ($id, $values, $address) {
            global $app_strings, $sugar_config;

            $account = $id !== '' ? $this->account($id) : $this->newAccount((string)($values['type'] ?? ''));
            if ($account === null) {
                return null;
            }

            $fromAddress = trim((string)($values['smtp_from_addr'] ?? ''));
            $fromName = trim((string)($values['smtp_from_name'] ?? ''));
            if ($fromAddress === '' || $fromName === '') {
                return ['success' => false, 'message' => $this->decode($app_strings['LBL_EMAIL_SETTINGS_FROM_ADDR_NOT_SET'] ?? '')];
            }
            $address = trim($address);
            if ($address === '' || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => $this->decode($app_strings['LBL_EMAIL_SETTINGS_FROM_TO_EMAIL_ADDR'] ?? '')];
            }

            $authType = (string)($values['auth_type'] ?? 'no_auth');
            $password = (string)($values['mail_smtppass'] ?? '');
            if ($password === '') {
                $password = (string)($account->mail_smtppass ?? '');
            }

            $timeout = (int)($sugar_config['outbound_email_test_max_timeout'] ?? 30);
            ini_set('max_execution_time', (string)max($timeout, 1));

            $result = BeanFactory::newBean('Emails')->sendEmailTest(
                trim((string)($values['mail_smtpserver'] ?? '')),
                trim((string)($values['mail_smtpport'] ?? '')),
                (string)($values['mail_smtpssl'] ?? ''),
                $authType === 'basic' ? 1 : 0,
                trim((string)($values['mail_smtpuser'] ?? '')),
                $password,
                $fromAddress,
                $address,
                'SMTP',
                $fromName,
                $authType,
                (string)($values['external_oauth_connection_id'] ?? '')
            );

            if (!empty($result['status'])) {
                return ['success' => true, 'message' => $this->decode($app_strings['LBL_EMAIL_TEST_NOTIFICATION_SENT'] ?? '')];
            }

            return [
                'success' => false,
                'message' => $this->decode($result['errorMessage'] ?? ''),
                'log' => (string)($result['fullSmtpLog'] ?? ''),
            ];
        });
    }

    /**
     * Saves the account as the legacy form: the id of the account, or the error that stops the save.
     *
     * @param array<string, mixed> $values
     */
    public function save(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            global $current_user;

            $account = $id !== '' ? $this->account($id) : $this->newAccount((string)($values['type'] ?? ''));
            if ($account === null) {
                return null;
            }

            $error = $this->validate($account, $values);
            if ($error !== '') {
                return ['error' => $error];
            }

            foreach (self::FIELDS as $field) {
                $value = $values[$field] ?? '';
                $account->$field = is_scalar($value) ? trim((string)$value) : '';
            }
            // Kept by OutboundEmailAccounts::save() when none is given.
            $account->mail_smtppass = (string)($values['mail_smtppass'] ?? '') !== '' && $account->auth_type === 'basic'
                ? (string)$values['mail_smtppass'] : '';
            $account->mail_sendtype = 'SMTP';

            // The owner of a personal account: changed by an admin, else the user who creates it.
            $ownerId = (string)($values['user_id'] ?? '');
            if ($account->type === 'user' && $ownerId !== '' && is_admin($current_user)) {
                $account->user_id = $ownerId;
                $account->assigned_user_id = $ownerId;
            }
            if (empty($account->id) && empty($account->user_id)) {
                $account->user_id = $current_user->id;
                $account->assigned_user_id = $current_user->id;
            }
            if (!empty($account->id) && !empty($account->user_id) && empty($account->assigned_user_id)) {
                $account->assigned_user_id = $account->user_id;
            }

            $account->save();

            return ['id' => (string)$account->id];
        });
    }

    /**
     * Sets a personal account as the default of its owner (by the owner, or an admin).
     */
    public function setDefault(string $id): bool
    {
        return (bool)$this->inLegacy(function () use ($id) {
            global $current_user;

            $account = $this->account($id, 'view');
            if ($account === null || $account->type !== 'user') {
                return false;
            }
            $ownerId = (string)($account->created_by ?: $current_user->id);
            if ($ownerId !== $current_user->id && !is_admin($current_user)) {
                return false;
            }
            $owner = BeanFactory::getBean('Users', $ownerId);
            if (!$owner instanceof User || empty($owner->id)) {
                return false;
            }
            $account->setUsersDefaultOutboundAccount($owner, $account->id);

            return true;
        });
    }

    /** An account with the access of the legacy page to the action, filled in as its views. */
    protected function account(string $id, string $access = 'edit'): ?OutboundEmailAccounts
    {
        // retrieve() returns null without access to a personal account.
        $account = BeanFactory::getBean('OutboundEmailAccounts', $id);
        if (!$account instanceof OutboundEmailAccounts || empty($account->id) || !$account->ACLAccess($access)) {
            return null;
        }
        $account->fill_in_additional_detail_fields();

        return $account;
    }

    /**
     * A new account of the given type, as the legacy menu and edit view: a group account by an
     * admin, a system account by an admin when there is none (OutboundEmailAccountsController).
     */
    protected function newAccount(string $type): ?OutboundEmailAccounts
    {
        global $current_user;

        if (!in_array($type, self::TYPES, true) || ($type !== 'user' && !is_admin($current_user))
            || ($type === 'system' && $this->systemAccountId() !== '')) {
            return null;
        }

        /** @var OutboundEmailAccounts $account */
        $account = BeanFactory::newBean('OutboundEmailAccounts');
        $account->populateDefaultValues(true);
        $account->type = $type;
        $account->is_personal = $type === 'user' ? 1 : 0;

        return $account->ACLAccess('edit') ? $account : null;
    }

    /**
     * Why the account cannot be saved ('' when it can): the required fields of the legacy form,
     * one system account, and the type of the OAuth connection (OutboundEmailAccountsController).
     *
     * @param array<string, mixed> $values
     */
    protected function validate(OutboundEmailAccounts $account, array $values): string
    {
        global $app_list_strings, $mod_strings;

        $required = ['name', 'smtp_from_name', 'smtp_from_addr'];
        $authType = (string)($values['auth_type'] ?? '');
        // A password is needed unless one is saved (js/auth_type_fields_toggle.js).
        if ($authType === 'basic' && empty($account->mail_smtppass)) {
            $required[] = 'mail_smtppass';
        }
        if ($authType === 'oauth') {
            $required[] = 'external_oauth_connection_id';
        }
        foreach ($required as $field) {
            if (trim((string)($values[$field] ?? '')) === '') {
                return $this->label('LBL_ACCOUNT_EDITOR_REQUIRED');
            }
        }

        if (!$this->isOption($authType, $app_list_strings['dom_outbound_email_auth_types'] ?? [])
            || !$this->isOption($values['mail_smtpssl'] ?? '', $app_list_strings['email_settings_for_ssl'] ?? [])
            || (trim((string)($values['mail_smtpport'] ?? '')) !== '' && !ctype_digit(trim((string)$values['mail_smtpport'])))) {
            return $this->label('LBL_ACCOUNT_EDITOR_SAVE_ERROR');
        }

        $type = (string)$account->type;
        $systemId = $this->systemAccountId();
        if ($type === 'system' && $systemId !== '' && $systemId !== (string)$account->id) {
            return $this->decode($mod_strings['LBL_ERROR_OUTBOUND_EMAIL_SYSTEM_EXISTS'] ?? '');
        }

        if ($authType === 'oauth') {
            $connection = BeanFactory::getBean('ExternalOAuthConnection', (string)$values['external_oauth_connection_id']);
            $connectionType = $connection instanceof SugarBean ? (string)$connection->type : '';
            if ($type === 'system' && $connectionType !== 'group') {
                return $this->decode($mod_strings['LBL_ERROR_OUTBOUND_EMAIL_SYSTEM_IS_NOT_GROUP'] ?? '');
            }
            if ($type !== 'system' && $connectionType !== ($type === 'user' ? 'personal' : $type)) {
                return $this->decode($mod_strings['LBL_ERROR_OUTBOUND_EMAIL_CONNECTION_TYPE_MISMATCH'] ?? '');
            }
        }

        return '';
    }

    /** The values of the form (the password is never sent: isSet of its field says whether one is saved). */
    protected function values(OutboundEmailAccounts $account): array
    {
        $ownerId = (string)$account->user_id;
        $values = [
            'type' => (string)$account->type,
            'user_id' => $ownerId,
            'owner_name' => $this->relatedName('Users', $ownerId),
            'auth_type' => (string)($account->auth_type ?: 'no_auth'),
            'external_oauth_connection_name' => $this->relatedName('ExternalOAuthConnection', (string)$account->external_oauth_connection_id),
            'mail_smtppass' => '',
            'mail_smtpssl' => (string)($account->mail_smtpssl ?? '0'),
            'mail_smtpport' => (string)($account->mail_smtpport ?? ''),
            'signature' => html_entity_decode((string)($account->signature ?? ''), ENT_QUOTES),
            'testAddress' => $this->decode($GLOBALS['current_user']->email1 ?? ''),
        ];
        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                $values[$field] = $this->decode($account->$field ?? '');
            }
        }

        return $values;
    }

    /**
     * The panels of the legacy edit view; the server of a system override account is the one of
     * the system account (js/panel_toggle.js).
     */
    protected function panels(OutboundEmailAccounts $account): array
    {
        global $app_list_strings, $current_user;

        $field = fn(string $name, string $type, array $extra = []) => $this->field($account, $name, $type, $extra);
        $enum = fn(string $name, array $list, array $extra = []) => $field($name, 'enum', array_merge(['options' => $this->options($list)], $extra));

        $details = [
            $field('name', 'text', ['required' => true]),
            $field('type', 'readonly', ['options' => $this->options($app_list_strings['dom_outbound_email_account_types'] ?? [])]),
        ];
        // The owner of a personal account, changed by an admin (js/owner_toggle.js).
        if (is_admin($current_user)) {
            $details[] = $field('owner_name', 'relate', ['module' => 'users', 'idField' => 'user_id', 'show' => ['type' => ['user']]]);
        }

        return [
            ['key' => 'default', 'label' => '', 'fields' => $details],
            [
                'key' => 'connection',
                'label' => $this->label('LBL_CONNECTION_CONFIGURATION'),
                'show' => ['type' => ['user', 'group', 'system']],
                // The test email of the legacy page, to an address (the email of the user first).
                'test' => [
                    'label' => $this->label('LBL_EMAIL_TEST_OUTBOUND_SETTINGS'),
                    'wait' => $this->label('LBL_EMAIL_ONE_MOMENT'),
                    'address' => $this->label('LBL_EMAIL_SETTINGS_FROM_TO_EMAIL_ADDR'),
                    'send' => $this->label('LBL_EMAIL_SEND'),
                ],
                'fields' => [
                    $enum('auth_type', $app_list_strings['dom_outbound_email_auth_types'] ?? []),
                    $field('mail_smtpuser', 'text'),
                    $field('mail_smtpserver', 'text'),
                    $field('mail_smtppass', 'password', [
                        'show' => ['auth_type' => ['basic']], 'clear' => true, 'isSet' => !empty($account->mail_smtppass),
                    ]),
                    // Port 25, 465 with SSL, 587 with TLS (js/ssl_port_set.js).
                    $enum('mail_smtpssl', $app_list_strings['email_settings_for_ssl'] ?? [], [
                        'sets' => ['mail_smtpport' => ['0' => '25', '1' => '465', '2' => '587']],
                    ]),
                    $field('external_oauth_connection_name', 'relate', [
                        'module' => 'external-oauth-connection', 'idField' => 'external_oauth_connection_id', 'required' => true,
                        'show' => ['auth_type' => ['oauth']], 'clear' => true,
                    ]),
                    $field('mail_smtpport', 'int'),
                ],
            ],
            [
                'key' => 'outbound',
                'label' => $this->label('LBL_OUTBOUND_CONFIGURATION'),
                'fields' => [
                    $field('smtp_from_name', 'text', ['required' => true]),
                    $field('reply_to_name', 'text'),
                    $field('smtp_from_addr', 'text', ['required' => true]),
                    $field('reply_to_addr', 'text'),
                    $field('signature', 'textarea'),
                ],
            ],
        ];
    }

    /** The id of the system account ('' when there is none). */
    protected function systemAccountId(): string
    {
        require_once 'include/OutboundEmail/OutboundEmail.php';
        $system = (new OutboundEmail())->getSystemEmail();

        return $system !== null ? (string)$system->id : '';
    }

    /** The title of the legacy menu entry that creates an account of this type. */
    protected function newTitle(string $type): string
    {
        return ['group' => 'LNK_LIST_CREATE_NEW_GROUP', 'system' => 'LBL_ACCOUNT_EDITOR_NEW_SYSTEM'][$type] ?? 'LNK_LIST_CREATE_NEW_PERSONAL';
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
