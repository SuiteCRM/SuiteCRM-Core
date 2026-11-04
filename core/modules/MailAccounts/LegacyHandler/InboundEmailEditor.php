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
use InboundEmail;
use SugarBean;
use User;

/**
 * The inbound email accounts in SuiteCRM 8, as the legacy InboundEmail edit view:
 * - the form of the account (type, server and folders, outbound settings, auto reply, group
 *   import, case creation), with the rules of its scripts (js/panel_toggle.js, fields_toggle.js,
 *   auth_type_fields_toggle.js, owner_toggle.js, case_create_toggle.js, distribution_toggle.js,
 *   ssl_port_set.js);
 * - the connection test (Popup.php) and the IMAP folders of the account (ShowInboundFoldersList.php);
 * - the save, by the legacy Save.php with the request of the legacy form (password, connection
 *   string, stored options, group folder and mail folders);
 * - Set as default of a personal account (InboundEmailController::action_SetDefault()).
 */
class InboundEmailEditor extends MailAccountEditor
{
    public const HANDLER_KEY = 'inbound-email-editor';

    protected const TYPES = ['personal', 'group', 'bounce'];

    /** The case distribution methods by users of a role or a security group (getAOPAssignField()). */
    protected const ASSIGN_METHODS = ['roundRobin', 'leastBusy', 'random'];

    protected const LABELS = [
        'LBL_TEST_CONNECTION_SETTINGS', 'LBL_TEST_WAIT_MESSAGE', 'LBL_SELECT_SUBSCRIBED_FOLDERS', 'LBL_SELECT_TRASH_FOLDERS',
        'LBL_SELECT_SENT_FOLDERS', 'LBL_MODULE_NAME', 'LNK_LIST_CREATE_NEW_PERSONAL', 'LNK_LIST_CREATE_NEW_GROUP',
        'LNK_LIST_CREATE_NEW_BOUNCE', 'LBL_SELECT_BUTTON_LABEL', 'LBL_ROLE', 'LBL_SECURITYGROUP',
    ];

    /** Labels of the editor (application strings). */
    protected const EDITOR_LABELS = ['LBL_ACCOUNT_EDITOR_ERROR', 'LBL_ACCOUNT_EDITOR_REQUIRED', 'LBL_ACCOUNT_EDITOR_NO_FOLDER', 'LBL_ACCOUNT_EDITOR_SAVE_ERROR'];

    /** The fields of the form saved by Save.php, with the field of their value. */
    protected const REQUEST_FIELDS = [
        'name', 'status', 'auth_type', 'external_oauth_connection_id', 'external_oauth_connection_name', 'server_url', 'email_user',
        'protocol', 'port', 'mailbox', 'primary_folder', 'connection_string', 'email_body_filtering', 'trashFolder', 'sentFolder',
        'outbound_email_id', 'account_signature_id', 'from_name', 'from_addr', 'reply_to_name', 'reply_to_addr', 'filter_domain',
        'template_id', 'email_num_autoreplies_24_hours', 'email_import_timeframe_start', 'email_import_per_run_threshold',
        'create_case_template_id', 'distrib_method', 'distribution_user_id', 'distribution_user_name',
    ];

    protected const BOOL_FIELDS = [
        'is_ssl', 'allow_outbound_group_usage', 'is_auto_import', 'move_messages_to_trash_after_import', 'email_import_unread_only',
        'is_create_case',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    protected function module(): string
    {
        return 'InboundEmail';
    }

    /**
     * The account to edit (a new account of the given type without id) with its form; null when
     * the user cannot edit it (a group or bounce account needs an admin, as the legacy page).
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
                'module' => 'inbound-email',
                'title' => $id !== '' ? $this->decode($account->name) : $this->label($this->newTitle((string)$account->type)),
                'values' => $this->values($account),
                'panels' => $this->panels($account),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * Tests the connection to the mail server with the settings of the form (legacy Popup.php):
     * success and the message of the legacy test.
     *
     * @param array<string, mixed> $values
     */
    public function test(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            $account = $this->connectionAccount($id, $values);
            if ($account === null) {
                return null;
            }

            global $mod_strings;
            $message = (string)$this->withRequest($this->sslRequest($values), fn() => $account->connectMailserver(true));
            $success = in_array(trim(strip_tags($message)), [$mod_strings['LBL_TEST_SUCCESSFUL'] ?? '', $mod_strings['LBL_POP3_SUCCESS'] ?? ''], true);

            return ['success' => $success, 'message' => $this->message($message)];
        });
    }

    /**
     * The folders of the mail server with the settings of the form (legacy ShowInboundFoldersList.php).
     *
     * @param array<string, mixed> $values
     */
    public function folders(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            $account = $this->connectionAccount($id, $values);
            if ($account === null) {
                return null;
            }

            $result = $this->withRequest($this->sslRequest($values), fn() => $account->getFoldersListForMailBox());
            $folders = !empty($result['status']) ? array_values(array_filter(array_map('trim', explode(',', (string)$result['foldersList'])))) : [];

            return [
                'success' => !empty($result['status']),
                'message' => $this->message((string)($result['statusMessage'] ?? '')),
                'folders' => array_map(fn($folder) => $this->decode($folder), $folders),
            ];
        });
    }

    /**
     * The values a related record sets in the form: the sender and reply-to of an outbound account
     * (field_to_name_array of outbound_email_name in the legacy edit view).
     */
    public function fill(string $field, string $id): ?array
    {
        return $this->inLegacy(function () use ($field, $id) {
            if ($field !== 'outbound_email_name') {
                return null;
            }
            $outbound = BeanFactory::getBean('OutboundEmailAccounts', $id);
            if (!$outbound instanceof SugarBean || empty($outbound->id) || !$outbound->ACLAccess('view')) {
                return null;
            }

            return [
                'from_name' => $this->decode($outbound->smtp_from_name ?? ''),
                'from_addr' => $this->decode($outbound->smtp_from_addr ?? ''),
                'reply_to_name' => $this->decode($outbound->reply_to_name ?? ''),
                'reply_to_addr' => $this->decode($outbound->reply_to_addr ?? ''),
            ];
        });
    }

    /**
     * Saves the account by the legacy Save.php with the request of the legacy form: the id of the
     * account, and a warning when the connection settings could not be found (the legacy page
     * saves the account with error=true); or the error that stops the save.
     *
     * @param array<string, mixed> $values
     */
    public function save(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            global $current_user, $mod_strings;

            $account = $id !== '' ? $this->account($id) : $this->newAccount((string)($values['type'] ?? ''));
            if ($account === null) {
                return null;
            }
            $type = (string)$account->type;

            $error = $this->validate($account, $values);
            if ($error !== '') {
                return ['error' => $error];
            }

            $request = ['module' => 'InboundEmail', 'action' => 'Save', 'record' => $id, 'type' => $type];
            foreach (self::REQUEST_FIELDS as $field) {
                $value = $values[$field] ?? '';
                $request[$field] = is_scalar($value) ? trim((string)$value) : '';
            }
            foreach (self::BOOL_FIELDS as $field) {
                $request[$field] = $this->bool($values[$field] ?? false) ? '1' : '0';
            }
            // Only set when given, as the legacy form keeps the saved password.
            if ((string)($values['email_password'] ?? '') !== '' && ($values['auth_type'] ?? '') === 'basic') {
                $request['email_password'] = (string)$values['email_password'];
            }
            if ($type === 'personal') {
                $request['is_personal'] = '1';
                if (is_admin($current_user) && !empty($values['created_by'])) {
                    $request['created_by'] = (string)$values['created_by'];
                }
            }
            $distribution = is_array($values['distribution_options'] ?? null) ? array_values($values['distribution_options']) : [];
            $request['distribution_options'] = [
                (string)($distribution[0] ?? ''), (string)($distribution[1] ?? ''), (string)($distribution[2] ?? ''),
            ];

            $saved = $this->withRequest($request, static function () {
                // Save.php uses its globals and sets the redirect of the legacy page, not used here.
                global $current_user, $mod_strings, $app_strings, $log;
                $error = null;
                include 'modules/InboundEmail/Save.php';
                header_remove('Location');

                return ['id' => (string)($focus->id ?? ''), 'warning' => !empty($error)];
            });

            if ($saved['id'] === '') {
                return ['error' => $this->label('LBL_ACCOUNT_EDITOR_SAVE_ERROR')];
            }

            return ['id' => $saved['id'], 'warning' => $saved['warning'] ? $this->decode($mod_strings['ERR_NO_OPTS_SAVED'] ?? '') : ''];
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
            if ($account === null || $account->type !== 'personal') {
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
            $account->setUsersDefaultOutboundServerId($owner, $account->id);

            return true;
        });
    }

    /** An account with the access of the legacy page to the action, filled in as its views. */
    protected function account(string $id, string $access = 'edit'): ?InboundEmail
    {
        // retrieve() returns null without access to a personal account.
        $account = BeanFactory::getBean('InboundEmail', $id);
        if (!$account instanceof InboundEmail || empty($account->id) || !$account->ACLAccess($access)) {
            return null;
        }
        $account->fill_in_additional_detail_fields();

        return $account;
    }

    /** A new account of the given type (a group or bounce account by an admin, as the legacy menu). */
    protected function newAccount(string $type): ?InboundEmail
    {
        global $current_user;

        if (!in_array($type, self::TYPES, true) || ($type !== 'personal' && !is_admin($current_user))) {
            return null;
        }

        /** @var InboundEmail $account */
        $account = BeanFactory::newBean('InboundEmail');
        $account->populateDefaultValues(true);
        $account->type = $type;
        $account->is_personal = $type === 'personal' ? 1 : 0;
        $account->mailbox_type = $type === 'bounce' ? 'bounce' : 'pick';
        $account->created_by = $current_user->id;
        if (!$account->ACLAccess('edit')) {
            return null;
        }
        // As the legacy edit view of a new group or bounce account (InboundEmailController::action_EditView()).
        if ($type !== 'personal') {
            $account->initializeEmailImportSettings($account);
        }

        return $account;
    }

    /**
     * An account with the connection settings of the form, to test them: the saved account (its
     * saved password when none is given) or a new one.
     *
     * @param array<string, mixed> $values
     */
    protected function connectionAccount(string $id, array $values): ?InboundEmail
    {
        $account = $id !== '' ? $this->account($id) : $this->newAccount((string)($values['type'] ?? ''));
        if ($account === null) {
            return null;
        }

        $account->email_user = trim((string)($values['email_user'] ?? ''));
        $account->server_url = trim((string)($values['server_url'] ?? ''));
        $account->port = trim((string)($values['port'] ?? ''));
        $account->protocol = (string)($values['protocol'] ?? '');
        $account->auth_type = (string)($values['auth_type'] ?? '') ?: $account->auth_type;
        if ((string)($values['email_password'] ?? '') !== '') {
            $account->email_password = (string)$values['email_password'];
        }
        $account->external_oauth_connection_id = (string)($values['external_oauth_connection_id'] ?? '');
        $account->external_oauth_connection_name = (string)($values['external_oauth_connection_name'] ?? '');
        $account->connection_string = trim((string)($values['connection_string'] ?? ''));
        $account->mailbox = 'INBOX';

        return $account;
    }

    /**
     * Why the account cannot be saved ('' when it can): the required fields of the legacy form,
     * the values of its lists, and the type of the OAuth connection (Save.php).
     *
     * @param array<string, mixed> $values
     */
    protected function validate(InboundEmail $account, array $values): string
    {
        global $app_list_strings, $mod_strings;

        $required = ['name', 'server_url', 'email_user', 'port', 'mailbox'];
        $authType = (string)($values['auth_type'] ?? '');
        // A password is needed unless one is saved (js/auth_type_fields_toggle.js).
        if ($authType === 'basic' && empty($account->email_password)) {
            $required[] = 'email_password';
        }
        if ($authType === 'oauth') {
            $required[] = 'external_oauth_connection_id';
        }
        foreach ($required as $field) {
            if (trim((string)($values[$field] ?? '')) === '') {
                return $this->label('LBL_ACCOUNT_EDITOR_REQUIRED');
            }
        }

        if (!$this->isOption($values['status'] ?? '', $app_list_strings['dom_inbound_email_account_status'] ?? [])
            || !$this->isOption($authType, $app_list_strings['dom_inbound_email_auth_types'] ?? [])
            || !$this->isOption($values['protocol'] ?? '', getInboundEmailProtocols())
            || !ctype_digit(trim((string)$values['port']))) {
            return $this->label('LBL_ACCOUNT_EDITOR_SAVE_ERROR');
        }

        if ($authType === 'oauth') {
            $connection = BeanFactory::getBean('ExternalOAuthConnection', (string)$values['external_oauth_connection_id']);
            $type = $account->type === 'bounce' ? 'group' : $account->type;
            if (!$connection instanceof SugarBean || empty($connection->id) || $connection->type !== $type) {
                return $this->decode($mod_strings['LBL_TYPE_DIFFERENT'] ?? 'LBL_TYPE_DIFFERENT');
            }
        }

        return '';
    }

    /** The values of the form (the password is never sent: isSet of its field says whether one is saved). */
    protected function values(InboundEmail $account): array
    {
        $ownerId = (string)$account->created_by;
        $distribution = is_array($account->distribution_options ?? null) ? array_values($account->distribution_options) : [];
        $values = [
            'type' => (string)$account->type,
            'created_by' => $ownerId,
            'owner_name' => $this->relatedName('Users', $ownerId),
            'auth_type' => (string)($account->auth_type ?: 'basic'),
            'external_oauth_connection_name' => $this->relatedName('ExternalOAuthConnection', (string)$account->external_oauth_connection_id),
            'email_password' => '',
            'protocol' => (string)($account->protocol ?: 'imap'),
            'outbound_email_name' => $this->relatedName('OutboundEmailAccounts', (string)($account->outbound_email_id ?? '')),
            'autoreply_email_template_name' => $this->relatedName('EmailTemplates', (string)$account->template_id),
            'create_case_email_template_name' => $this->relatedName('EmailTemplates', (string)($account->create_case_template_id ?? '')),
            'distribution_options' => [(string)($distribution[0] ?? ''), (string)($distribution[1] ?? ''), (string)($distribution[2] ?? '')],
            'distribution_user_name' => $this->relatedName('Users', (string)($account->distribution_user_id ?? '')),
        ];
        foreach (self::REQUEST_FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                $values[$field] = $this->decode($account->$field ?? '');
            }
        }
        foreach (self::BOOL_FIELDS as $field) {
            $values[$field] = isTrue($account->$field ?? false);
        }

        return $values;
    }

    /**
     * The panels of the legacy edit view, shown by the type of the account; each field with the
     * rule that shows it.
     */
    protected function panels(InboundEmail $account): array
    {
        global $app_list_strings, $current_user;

        $personal = ['type' => ['personal']];
        $group = ['type' => ['group']];
        $createCase = ['is_create_case' => ['true']];
        $field = fn(string $name, string $type, array $extra = []) => $this->field($account, $name, $type, $extra);
        $enum = fn(string $name, array $list, array $extra = []) => $field($name, 'enum', array_merge(['options' => $this->options($list)], $extra));

        $details = [
            $field('type', 'readonly', ['options' => $this->options($app_list_strings['dom_inbound_email_account_types'] ?? [])]),
            $field('name', 'text', ['required' => true]),
            $enum('status', $app_list_strings['dom_inbound_email_account_status'] ?? []),
        ];
        // The owner of a personal account, changed by an admin (js/owner_toggle.js).
        if (is_admin($current_user)) {
            $details[] = $field('owner_name', 'relate', ['module' => 'users', 'idField' => 'created_by', 'show' => $personal]);
        }

        return [
            ['key' => 'default', 'label' => '', 'fields' => $details],
            [
                'key' => 'connection',
                'label' => $this->label('LBL_CONNECTION_CONFIGURATION'),
                'test' => ['label' => $this->label('LBL_TEST_CONNECTION_SETTINGS'), 'wait' => $this->label('LBL_TEST_WAIT_MESSAGE')],
                'fields' => [
                    $enum('auth_type', $app_list_strings['dom_inbound_email_auth_types'] ?? []),
                    $field('external_oauth_connection_name', 'relate', [
                        'module' => 'external-oauth-connection', 'idField' => 'external_oauth_connection_id', 'required' => true,
                        'show' => ['auth_type' => ['oauth']], 'clear' => true,
                    ]),
                    $field('server_url', 'text', ['required' => true]),
                    $field('email_user', 'text', ['required' => true]),
                    $enum('protocol', getInboundEmailProtocols()),
                    $field('email_password', 'password', [
                        'show' => ['auth_type' => ['basic']], 'clear' => true, 'isSet' => !empty($account->email_password),
                    ]),
                    $field('port', 'int', ['required' => true]),
                    $field('mailbox', 'folders', ['required' => true, 'title' => $this->label('LBL_SELECT_SUBSCRIBED_FOLDERS')]),
                    // Port 993 with SSL, 143 without (js/ssl_port_set.js).
                    $field('is_ssl', 'bool', ['sets' => ['port' => ['true' => '993', 'false' => '143']]]),
                    $field('primary_folder', 'primary', ['of' => 'mailbox']),
                    $field('connection_string', 'text'),
                    $field('trashFolder', 'folder', ['title' => $this->label('LBL_SELECT_TRASH_FOLDERS')]),
                    $enum('email_body_filtering', $app_list_strings['dom_email_body_filtering_option'] ?? []),
                    $field('sentFolder', 'folder', ['title' => $this->label('LBL_SELECT_SENT_FOLDERS')]),
                ],
            ],
            [
                'key' => 'outbound',
                'label' => $this->label('LBL_OUTBOUND_CONFIGURATION'),
                'show' => ['type' => ['personal', 'group']],
                'fields' => [
                    $field('outbound_email_name', 'relate', [
                        'module' => 'outbound-email-accounts', 'idField' => 'outbound_email_id', 'fill' => true, 'show' => $personal,
                    ]),
                    $enum('account_signature_id', $this->signatures($account), ['show' => $personal]),
                    $field('allow_outbound_group_usage', 'bool', ['show' => $group]),
                    $field('from_name', 'text'),
                    $field('reply_to_name', 'text'),
                    $field('from_addr', 'text'),
                    $field('reply_to_addr', 'text'),
                ],
            ],
            [
                'key' => 'autoReply',
                'label' => $this->label('LBL_AUTO_REPLY_CONFIGURATION'),
                'show' => $group,
                'fields' => [
                    $field('filter_domain', 'text'),
                    $field('autoreply_email_template_name', 'relate', ['module' => 'email-templates', 'idField' => 'template_id']),
                    $field('email_num_autoreplies_24_hours', 'int'),
                ],
            ],
            [
                'key' => 'group',
                'label' => $this->label('LBL_GROUP_CONFIGURATION'),
                'show' => $group,
                'fields' => [
                    $field('is_auto_import', 'bool'),
                    $field('move_messages_to_trash_after_import', 'bool'),
                    $field('email_import_unread_only', 'bool'),
                    $enum('email_import_timeframe_start', $app_list_strings['email_import_timeframe_start_dom'] ?? []),
                    $field('email_import_per_run_threshold', 'int'),
                ],
            ],
            [
                'key' => 'case',
                'label' => $this->label('LBL_CASE_CONFIGURATION'),
                'show' => $group,
                'fields' => [
                    $field('is_create_case', 'bool'),
                    $field('create_case_email_template_name', 'relate', [
                        'module' => 'email-templates', 'idField' => 'create_case_template_id', 'show' => $createCase, 'clear' => true,
                    ]),
                    $enum('distrib_method', $app_list_strings['dom_email_distribution_for_auto_create'] ?? [], [
                        'show' => $createCase, 'clear' => true,
                    ]),
                    $field('distribution_options', 'assign', [
                        'show' => $createCase + ['distrib_method' => self::ASSIGN_METHODS],
                        'options' => $this->options($app_list_strings['aow_assign_options'] ?? []),
                        'groups' => $this->options(get_bean_select_array(true, 'SecurityGroup', 'name', '', 'name', true)),
                        'roles' => $this->options(get_bean_select_array(true, 'ACLRole', 'name', '', 'name', true)),
                    ]),
                    $field('distribution_user_name', 'relate', [
                        'module' => 'users', 'idField' => 'distribution_user_id', 'clear' => true,
                        'show' => $createCase + ['distrib_method' => ['singleUser']],
                    ]),
                ],
            ],
        ];
    }

    /**
     * The signatures of the owner of the account (getInboundEmailSignatures()): the default
     * signature first, then the others.
     */
    protected function signatures(InboundEmail $account): array
    {
        global $app_strings, $current_user;

        $owner = !empty($account->created_by) ? BeanFactory::getBean('Users', $account->created_by) : $current_user;
        if (!$owner instanceof User || empty($owner->id)) {
            return [];
        }

        $signatures = [(string)($owner->getPreference('signature_default') ?? '') => $app_strings['LBL_DEFAULT_EMAIL_SIGNATURES'] ?? ''];
        foreach ($owner->getSignaturesArray() as $signatureId => $signature) {
            if ($signatureId !== '' && is_array($signature)) {
                $signatures[$signatureId] = (string)($signature['name'] ?? '');
            }
        }

        return $signatures;
    }

    /** The title of the legacy menu entry that creates an account of this type. */
    protected function newTitle(string $type): string
    {
        return ['group' => 'LNK_LIST_CREATE_NEW_GROUP', 'bounce' => 'LNK_LIST_CREATE_NEW_BOUNCE'][$type] ?? 'LNK_LIST_CREATE_NEW_PERSONAL';
    }

    /** The SSL setting of the form, read from the request by the legacy connection test. */
    protected function sslRequest(array $values): array
    {
        return ['ssl' => $this->bool($values['is_ssl'] ?? false) ? 'true' : 'false'];
    }

    /** A legacy message as text. */
    protected function message(string $message): string
    {
        $text = strip_tags(preg_replace('/<(p|br)\s*\/?>/i', "\n", $message));

        return trim(preg_replace("/\n{2,}/", "\n", $this->decode($text)));
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
