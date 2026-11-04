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
use ExternalOAuthProvider;
use OAuthAuthorizationService;

/**
 * The external OAuth providers (of the OAuth mail accounts) in SuiteCRM 8, as the legacy
 * ExternalOAuthProvider edit view: connector, client, URLs, scope and options (key-value lists of
 * the legacy stringmap fields), redirect URI by its type (js/redirect_uri_toggle.js), token
 * requests and mapping. Saved by ExternalOAuthProvider::save(), which keeps the saved client secret
 * when none is given.
 */
class ExternalOAuthProviderEditor extends MailAccountEditor
{
    public const HANDLER_KEY = 'oauth-provider-editor';

    /** The types of a new provider: personal by its owner, group by an admin (legacy menu). */
    protected const TYPES = ['personal', 'group'];

    protected const TEXT_FIELDS = [
        'name', 'connector', 'client_id', 'url_authorize', 'url_access_token', 'redirect_uri_type', 'get_token_request_grant',
        'refresh_token_request_grant', 'access_token_mapping', 'expires_in_mapping', 'refresh_token_mapping', 'token_type_mapping',
    ];

    protected const MAP_FIELDS = [
        'scope', 'authorize_url_options', 'extra_provider_params', 'get_token_request_options', 'refresh_token_request_options',
    ];

    protected const LABELS = ['LBL_MODULE_NAME', 'LNK_LIST_CREATE_NEW_PERSONAL', 'LNK_LIST_CREATE_NEW_GROUP', 'LBL_KEY', 'LBL_VALUE'];

    /** Labels of the editor (application strings). */
    protected const EDITOR_LABELS = ['LBL_ACCOUNT_EDITOR_ERROR', 'LBL_ACCOUNT_EDITOR_REQUIRED', 'LBL_ACCOUNT_EDITOR_SAVE_ERROR', 'LBL_ACCOUNT_EDITOR_ADD'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    protected function module(): string
    {
        return 'ExternalOAuthProvider';
    }

    /**
     * The provider to edit (a new provider of the given type without id) with its form; null when
     * the user cannot edit it.
     */
    public function load(string $id, string $type): ?array
    {
        return $this->inLegacy(function () use ($id, $type) {
            $provider = $id !== '' ? $this->provider($id) : $this->newProvider($type);
            if ($provider === null) {
                return null;
            }

            $values = ['type' => (string)$provider->type, 'client_secret' => '', 'redirect_uri' => $provider->getRedirectURI()];
            foreach (self::TEXT_FIELDS as $field) {
                $values[$field] = $this->decode($provider->$field ?? '');
            }
            foreach (self::MAP_FIELDS as $field) {
                $values[$field] = $this->stringMapRows($provider->$field ?? '');
            }

            return [
                'id' => (string)$provider->id,
                'module' => 'external-oauth-provider',
                'title' => $id !== '' ? $this->decode($provider->name)
                    : $this->label($provider->type === 'group' ? 'LNK_LIST_CREATE_NEW_GROUP' : 'LNK_LIST_CREATE_NEW_PERSONAL'),
                'values' => $values,
                'panels' => $this->panels($provider),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * Saves the provider as the legacy form: the id of the provider, or the error that stops the save.
     *
     * @param array<string, mixed> $values
     */
    public function save(string $id, array $values): ?array
    {
        return $this->inLegacy(function () use ($id, $values) {
            global $app_list_strings;

            $provider = $id !== '' ? $this->provider($id) : $this->newProvider((string)($values['type'] ?? ''));
            if ($provider === null) {
                return null;
            }

            foreach (['name', 'connector', 'client_id'] as $field) {
                if (trim((string)($values[$field] ?? '')) === '') {
                    return ['error' => $this->label('LBL_ACCOUNT_EDITOR_REQUIRED')];
                }
            }
            if (!array_key_exists((string)$values['connector'], $this->connectors())
                || !$this->isOption($values['redirect_uri_type'] ?? '', $app_list_strings['redirect_uri_type_dom'] ?? [])) {
                return ['error' => $this->label('LBL_ACCOUNT_EDITOR_SAVE_ERROR')];
            }

            foreach (self::TEXT_FIELDS as $field) {
                $value = $values[$field] ?? '';
                $provider->$field = is_scalar($value) ? trim((string)$value) : '';
            }
            foreach (self::MAP_FIELDS as $field) {
                $provider->$field = $this->stringMapValue($values[$field] ?? []);
            }
            // Kept by ExternalOAuthProvider::save() when none is given (write-only field).
            $provider->client_secret = trim((string)($values['client_secret'] ?? ''));
            $provider->save();

            return ['id' => (string)$provider->id];
        });
    }

    protected function provider(string $id): ?ExternalOAuthProvider
    {
        // retrieve() returns null without access to a personal provider.
        $provider = BeanFactory::getBean('ExternalOAuthProvider', $id);

        return $provider instanceof ExternalOAuthProvider && !empty($provider->id) && $provider->ACLAccess('edit') ? $provider : null;
    }

    protected function newProvider(string $type): ?ExternalOAuthProvider
    {
        global $current_user;

        if (!in_array($type, self::TYPES, true) || ($type !== 'personal' && !is_admin($current_user))) {
            return null;
        }

        /** @var ExternalOAuthProvider $provider */
        $provider = BeanFactory::newBean('ExternalOAuthProvider');
        $provider->populateDefaultValues(true);
        $provider->type = $type;

        return $provider->ACLAccess('edit') ? $provider : null;
    }

    /** The connectors of the providers (getExternalOAuthProviderConnectors()). */
    protected function connectors(): array
    {
        require_once 'modules/ExternalOAuthConnection/services/OAuthAuthorizationService.php';
        $connectors = array_keys((new OAuthAuthorizationService())->getExternalOauthProvidersConnectors() ?? []);

        return array_combine($connectors, $connectors) ?: [];
    }

    protected function panels(ExternalOAuthProvider $provider): array
    {
        global $app_list_strings;

        $field = fn(string $name, string $type, array $extra = []) => $this->field($provider, $name, $type, $extra);
        $map = fn(string $name) => $field($name, 'stringmap', ['showKeys' => !empty($provider->field_defs[$name]['show_keys'])]);

        // The redirect URI of each type (ExternalOAuthProvider::getRedirectURI(), js/redirect_uri_toggle.js).
        $redirectUris = [];
        foreach (array_keys($app_list_strings['redirect_uri_type_dom'] ?? []) as $type) {
            $copy = clone $provider;
            $copy->redirect_uri_type = $type;
            $redirectUris[$type] = $copy->getRedirectURI();
        }

        return [
            [
                'key' => 'default',
                'label' => '',
                'fields' => [
                    $field('type', 'readonly', ['options' => $this->options($app_list_strings['dom_external_oauth_provider_types'] ?? [])]),
                    $field('connector', 'enum', ['required' => true, 'options' => $this->options($this->connectors())]),
                    $field('name', 'text', ['required' => true]),
                    $map('scope'),
                    $field('client_id', 'text', ['required' => true]),
                    $field('client_secret', 'password', ['isSet' => !empty($provider->client_secret)]),
                    $field('url_authorize', 'text'),
                    $field('url_access_token', 'text'),
                    $map('authorize_url_options'),
                    $field('redirect_uri_type', 'enum', [
                        'options' => $this->options($app_list_strings['redirect_uri_type_dom'] ?? []),
                        'sets' => ['redirect_uri' => $redirectUris],
                    ]),
                    $field('redirect_uri', 'readonly'),
                ],
            ],
            [
                'key' => 'extra',
                'label' => $this->label('LBL_EXTRA'),
                'fields' => [
                    $map('extra_provider_params'),
                    $field('get_token_request_grant', 'text'),
                    $map('get_token_request_options'),
                    $field('refresh_token_request_grant', 'text'),
                    $map('refresh_token_request_options'),
                ],
            ],
            [
                'key' => 'mapping',
                'label' => $this->label('LBL_MAPPING'),
                'fields' => [
                    $field('access_token_mapping', 'text'),
                    $field('expires_in_mapping', 'text'),
                    $field('refresh_token_mapping', 'text'),
                    $field('token_type_mapping', 'text'),
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
