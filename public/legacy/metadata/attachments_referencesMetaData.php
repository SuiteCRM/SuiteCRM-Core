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

$dictionary['attachments_references'] = [
    'table' => 'attachments_references',
    'fields' => [
        ['name' => 'parent_id', 'type' => 'id', 'len' => '36'],
        ['name' => 'parent_field', 'type' => 'varchar', 'len' => '255'],
        ['name' => 'parent_type', 'type' => 'varchar', 'len' => '25'],
        ['name' => 'type', 'type' => 'varchar', 'len' => '100'],
        ['name' => 'source_record_id', 'type' => 'id', 'len' => '36'],
        ['name' => 'deleted', 'type' => 'bool', 'default'=>'0'],
    ],
    'indices' => [
        [
            'name' => 'idx_attachments_ref_parent_id',
            'type' => 'index',
            'fields' => ['parent_id']
        ],
        [
            'name' => 'idx_attachments_ref_source_record_id',
            'type' => 'index',
            'fields' => ['source_record_id']
        ],
        [
            'name' => 'idx_attachments_ref_parent',
            'type' => 'index',
            'fields' => ['parent_id', 'parent_type', 'parent_field']
        ],
        [
            'name' => 'idx_attachments_ref_source_type',
            'type' => 'index',
            'fields' => ['type', 'source_record_id']
        ]
    ]
];
