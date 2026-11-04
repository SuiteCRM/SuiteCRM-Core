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

namespace App\Module\MailAccounts\FieldDefinitions;

/**
 * The fields of an external OAuth provider in the SuiteCRM 8 record view: not edited
 * inline (edited in the editor), and the key-value lists (legacy stringmap fields)
 * shown as text, one entry by line (ExternalOAuthProviderMappers).
 */
class ExternalOAuthProviderFieldDefinitionMapper extends MailAccountFieldDefinitionMapper
{
    public function getKey(): string
    {
        return 'oauth-provider-fields';
    }

    public function getModule(): string
    {
        return 'external-oauth-provider';
    }

    protected function hiddenOn(): array
    {
        return [];
    }

    protected function fieldTypes(): array
    {
        return array_fill_keys(
            ['scope', 'authorize_url_options', 'extra_provider_params', 'get_token_request_options', 'refresh_token_request_options'],
            'text'
        );
    }
}
