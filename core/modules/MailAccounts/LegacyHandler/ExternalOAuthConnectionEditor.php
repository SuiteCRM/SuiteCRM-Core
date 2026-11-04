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
use ExternalOAuthConnection;
use SugarBean;

/**
 * The external OAuth connections (of the OAuth mail accounts) in SuiteCRM 8, as the
 * legacy ExternalOAuthConnection edit view: name, provider, and the token of the connection, got
 * by Authenticate (legacy entry points redirectToExternalOAuth, then setExternalOAuthToken, which
 * sends the token to the page: js/authenticate.js, js/broadcast.js). Saved by
 * ExternalOAuthConnection::save(), which keeps the saved tokens when none is given.
 */
class ExternalOAuthConnectionEditor extends MailAccountEditor
{
    public const HANDLER_KEY = 'oauth-connection-editor';

    /** The types of a new connection: personal by its owner, group by an admin (legacy menu). */
    protected const TYPES = ['personal', 'group'];

    protected const FIELDS = ['name', 'external_oauth_provider_id', 'token_type'];

    /** The token of the connection (write-only: kept when left empty). */
    protected const TOKEN_FIELDS = ['expires_in', 'access_token', 'refresh_token'];

    protected const LABELS = ['LBL_MODULE_NAME', 'LNK_LIST_CREATE_NEW_PERSONAL', 'LNK_LIST_CREATE_NEW_GROUP', 'LBL_AUTHENTICATE', 'LBL_SELECT_BUTTON_LABEL'];

    /** Labels of the editor (application strings). */
    protected const EDITOR_LABELS = ['LBL_ACCOUNT_EDITOR_ERROR', 'LBL_ACCOUNT_EDITOR_REQUIRED', 'LBL_ACCOUNT_EDITOR_SAVE_ERROR', 'LBL_ACCOUNT_EDITOR_SELECT_PROVIDER'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    protected function module(): string
    {
        return 'ExternalOAuthConnection';
    }

    /**
     * The connection to edit (a new connection of the given type without id) with its form; null
     * when the user cannot edit it.
     */
    public function load(string $id, string $type): ?array
    {
        return $this->inLegacy(function () use ($id, $type) {
            $connection = $id !== '' ? $this->connection($id) : $this->newConnection($type);
            if ($connection === null) {
                return null;
            }

            $values = [
                'type' => (string)$connection->type,
                'external_oauth_provider_name' => $this->relatedName('ExternalOAuthProvider', (string)$connection->external_oauth_provider_id),
            ];
            foreach (self::FIELDS as $field) {
                $values[$field] = $this->decode($connection->$field ?? '');
            }
            foreach (self::TOKEN_FIELDS as $field) {
                $values[$field] = '';
            }

            return [
                'id' => (string)$connection->id,
                'module' => 'external-oauth-connection',
                'title' => $id !== '' ? $this->decode($connection->name)
                    : $this->label($connection->type === 'group' ? 'LNK_LIST_CREATE_NEW_GROUP' : 'LNK_LIST_CREATE_NEW_PERSONAL'),
                'values' => $values,
                'panels' => $this->panels($connection),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * Saves the connection as the legacy form: the id of the connection, or the error that stops
     * the save.
     *
     * @param array<string, mixed> $values
     */
    public function save(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            $connection = $id !== '' ? $this->connection($id) : $this->newConnection((string)($values['type'] ?? ''));
            if ($connection === null) {
                return null;
            }

            if (trim((string)($values['name'] ?? '')) === '') {
                return ['error' => $this->label('LBL_ACCOUNT_EDITOR_REQUIRED')];
            }
            $providerId = trim((string)($values['external_oauth_provider_id'] ?? ''));
            $provider = $providerId !== '' ? BeanFactory::getBean('ExternalOAuthProvider', $providerId) : null;
            if ($providerId !== '' && (!$provider instanceof SugarBean || empty($provider->id))) {
                return ['error' => $this->label('LBL_ACCOUNT_EDITOR_SAVE_ERROR')];
            }

            foreach (self::FIELDS as $field) {
                $value = $values[$field] ?? '';
                $connection->$field = is_scalar($value) ? trim((string)$value) : '';
            }
            // Kept by ExternalOAuthConnection::save() when none is given (write-only fields).
            foreach (self::TOKEN_FIELDS as $field) {
                $value = $values[$field] ?? '';
                $connection->$field = is_scalar($value) ? trim((string)$value) : '';
            }
            $connection->save();

            return ['id' => (string)$connection->id];
        });
    }

    protected function connection(string $id): ?ExternalOAuthConnection
    {
        // retrieve() returns null without access to a personal connection.
        $connection = BeanFactory::getBean('ExternalOAuthConnection', $id);

        return $connection instanceof ExternalOAuthConnection && !empty($connection->id) && $connection->ACLAccess('edit') ? $connection : null;
    }

    protected function newConnection(string $type): ?ExternalOAuthConnection
    {
        global $current_user;

        if (!in_array($type, self::TYPES, true) || ($type !== 'personal' && !is_admin($current_user))) {
            return null;
        }

        /** @var ExternalOAuthConnection $connection */
        $connection = BeanFactory::newBean('ExternalOAuthConnection');
        $connection->populateDefaultValues(true);
        $connection->type = $type;

        return $connection->ACLAccess('edit') ? $connection : null;
    }

    protected function panels(ExternalOAuthConnection $connection): array
    {
        global $app_list_strings;

        $field = fn(string $name, string $type, array $extra = []) => $this->field($connection, $name, $type, $extra);
        $token = fn(string $name) => $field($name, 'password', ['isSet' => !empty($connection->$name)]);

        return [
            [
                'key' => 'default',
                'label' => '',
                // Authenticate of the legacy edit view: the provider authorizes the connection in a
                // window, which sends the token back (setExternalOAuthToken, BroadcastChannel "auth").
                'authorize' => [
                    'label' => $this->label('LBL_AUTHENTICATE'),
                    'url' => 'legacy/index.php?entryPoint=redirectToExternalOAuth',
                    'provider' => 'external_oauth_provider_id',
                    'missing' => $this->label('LBL_ACCOUNT_EDITOR_SELECT_PROVIDER'),
                ],
                'fields' => [
                    $field('type', 'readonly', ['options' => $this->options($app_list_strings['dom_external_oauth_connection_types'] ?? [])]),
                    $field('name', 'text', ['required' => true]),
                    $field('external_oauth_provider_name', 'relate', [
                        'module' => 'external-oauth-provider', 'idField' => 'external_oauth_provider_id',
                    ]),
                    $field('token_type', 'text'),
                    $token('expires_in'),
                    $token('access_token'),
                    $token('refresh_token'),
                ],
            ],
        ];
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
