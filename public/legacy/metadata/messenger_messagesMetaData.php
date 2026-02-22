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

// Symfony Messenger doctrine transport queue table
$dictionary['messenger_messages'] = [
    'table' => 'messenger_messages',
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'long',
            'auto_increment' => true,
            'required' => true,
        ],
        'body' => [
            'name' => 'body',
            'type' => 'longtext',
            'required' => true,
            'isnull' => 'false'
        ],
        'headers' => [
            'name' => 'headers',
            'type' => 'longtext',
            'required' => true,
            'isnull' => 'false'
        ],
        'queue_name' => [
            'name' => 'queue_name',
            'type' => 'varchar',
            'len' => 190,
            'required' => true,
            'isnull' => 'false'
        ],
        'created_at' => [
            'name' => 'created_at',
            'type' => 'datetime',
            'required' => true,
            'isnull' => 'false'
        ],
        'available_at' => [
            'name' => 'available_at',
            'type' => 'datetime',
            'required' => true,
            'isnull' => 'false'
        ],
        'delivered_at' => [
            'name' => 'delivered_at',
            'type' => 'datetime',
            'required' => false,
        ],
    ],
    'indices' => [
        [
            'name' => 'messenger_messages_pk',
            'type' => 'primary',
            'fields' => ['id'],
        ],
        [
            'name' => 'idx_msg_queue_available_delivered',
            'type' => 'index',
            'fields' => ['queue_name', 'available_at', 'delivered_at', 'id'],
        ],
    ],
];
