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

namespace App\Module\OAuth2Clients\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * Fields of an OAuth2 client in the record view (one layout for the grant types), as the legacy
 * edit views of the grant types:
 *  - the grant type is chosen when the client is created (the New ... Client links of the menu
 *    set it), from the grant types of those links, and read-only afterwards;
 *  - the redirect URL is for authorization code clients, the associated user for client
 *    credentials clients: shown and required for their grant type only;
 *  - the secret is set from new_secret by the bean, not typed.
 * The conditions use an operator: a list of values in activeOnFields is not "one of them".
 */
class OAuth2ClientFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    protected const GRANT_TYPE_FIELDS = [
        'redirect_url' => 'authorization_code',
        'assigned_user_name' => 'client_credentials',
    ];

    public function getKey(): string
    {
        return 'oauth2-client-fields';
    }

    public function getModule(): string
    {
        return 'oauth2-clients';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (!empty($vardefs['allowed_grant_type'])) {
            $vardefs['allowed_grant_type']['options'] = 'oauth2_client_grant_type_dom';
            $vardefs['allowed_grant_type']['displayType'] = 'create-only';
        }

        foreach (self::GRANT_TYPE_FIELDS as $field => $grantType) {
            if (empty($vardefs[$field])) {
                continue;
            }

            $vardefs[$field]['required'] = false;
            $vardefs[$field]['logic'] = [
                'required' => [
                    'key' => 'required',
                    'modes' => ['edit', 'create'],
                    'params' => [
                        'fieldDependencies' => ['allowed_grant_type'],
                        'activeOnFields' => [
                            'allowed_grant_type' => [['operator' => 'is-equal', 'values' => [$grantType]]],
                        ],
                    ],
                ],
            ];
            $vardefs[$field]['displayLogic'] = [
                'hide_for_other_grant_types' => [
                    'key' => 'displayType',
                    'modes' => ['detail', 'edit', 'create'],
                    'params' => [
                        'fieldDependencies' => ['allowed_grant_type'],
                        'activeOnFields' => [
                            'allowed_grant_type' => [['operator' => 'not-equal', 'values' => [$grantType]]],
                        ],
                    ],
                ],
            ];
        }

        if (!empty($vardefs['secret'])) {
            $vardefs['secret']['required'] = false;
        }

        $definition->setVardef($vardefs);
    }
}
