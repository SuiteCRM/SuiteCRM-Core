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

require_once __DIR__ . '/../../ApiBeanModuleMappers.php';
require_once __DIR__ . '/../../FieldMappers/FieldMapperInterface.php';

/**
 * The values of an external OAuth provider in the SuiteCRM 8 record view that the legacy
 * detail view computes: the redirect URI (getExternalOAuthProviderRedirectURI()) and the key-value
 * lists of the stringmap fields (JSON), one entry by line as the legacy field shows them.
 */
class ExternalOAuthProviderMappers extends ApiBeanModuleMappers
{
    public const MODULE = 'ExternalOAuthProvider';

    public function __construct()
    {
        $this->fieldMappers['redirect_uri'] = new ExternalOAuthProviderDetailMapper('redirect_uri');
        foreach (['scope', 'authorize_url_options', 'extra_provider_params', 'get_token_request_options', 'refresh_token_request_options'] as $field) {
            $this->fieldMappers[$field] = new ExternalOAuthProviderDetailMapper($field);
        }
    }

    public static function getModule(): string
    {
        return self::MODULE;
    }
}

/**
 * A field of an external OAuth provider shown as the legacy detail view shows it (read only: the
 * provider is saved in the account editor of SuiteCRM 8).
 */
class ExternalOAuthProviderDetailMapper implements FieldMapperInterface
{
    public function __construct(protected string $field)
    {
    }

    public static function getField(): string
    {
        return '';
    }

    public function toApi(SugarBean $bean, array &$container, string $alternativeName = ''): void
    {
        $name = $alternativeName !== '' ? $alternativeName : $this->field;
        if ($this->field === 'redirect_uri') {
            $container[$name] = method_exists($bean, 'getRedirectURI') ? (string)$bean->getRedirectURI() : '';

            return;
        }

        $map = json_decode(html_entity_decode((string)($bean->{$this->field} ?? ''), ENT_QUOTES), true);
        $showKeys = !empty($bean->field_defs[$this->field]['show_keys']);
        $lines = [];
        foreach (is_array($map) ? $map : [] as $key => $value) {
            $value = is_scalar($value) ? (string)$value : '';
            $lines[] = $showKeys && !is_int($key) ? $key . ': ' . $value : $value;
        }
        $container[$name] = implode("\n", $lines);
    }

    public function toBean(SugarBean $bean, array &$container, string $alternativeName = ''): void
    {
    }
}
