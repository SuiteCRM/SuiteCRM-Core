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

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/*
 * Layout of the OAuth2 clients in the record, create and edit views: the fields of the legacy
 * layouts of the grant types (detail<type>viewdefs.php, edit<type>viewdefs.php) in one layout.
 * The fields of a grant type are shown for it only, the grant type is chosen when the client is
 * created and the secret is changed in the edit view (OAuth2ClientFieldDefinitionMapper).
 * The legacy views keep their layout of each grant type, which they pick from the grant type of
 * the client; the record view takes this one (OAuth2ClientViewConfigMapper).
 */
$module_name = 'OAuth2Clients';
$viewdefs[$module_name]['DetailView'] = [
    'templateMeta' => [
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30'],
        ],
        'useTabs' => false,
        'tabDefs' => [
            'DEFAULT' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_PANEL_ASSIGNMENT' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
        ],
    ],
    'panels' => [
        'default' => [
            ['name'],
            ['allowed_grant_type'],
            ['new_secret'],
            ['redirect_url'],
            ['is_confidential'],
            ['assigned_user_name'],
            // The client ID of the API clients: the record id.
            [['name' => 'id', 'readonly' => true]],
        ],
        // Set by the save, as the legacy detail views show them.
        'LBL_PANEL_ASSIGNMENT' => [
            [['name' => 'date_entered', 'readonly' => true], ['name' => 'created_by_name', 'readonly' => true]],
            [['name' => 'date_modified', 'readonly' => true], ['name' => 'modified_by_name', 'readonly' => true]],
        ],
    ],
];
