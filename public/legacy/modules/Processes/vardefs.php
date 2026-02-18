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

$dictionary['Process'] = [
    'table' => 'processes',
    'comment' => 'Processes',
    'audited' => false,
    'inline_edit' => false,
    'massupdate' => false,
    'exportable' => false,
    'importable' => false,
    'fields' => [
        'id' => [
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
            'comment' => 'Unique identifier',
            'display' => 'readonly',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'name' => [
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'link' => true,
            'dbType' => 'varchar',
            'len' => 255,
            'required' => true,
            'display' => 'readonly',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'description' => array(
            'name' => 'description',
            'vname' => 'LBL_DESCRIPTION',
            'type' => 'text',
            'comment' => 'Full text of the note',
            'rows' => 6,
            'cols' => 80,
            'display' => 'readonly',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false
        ),
        'progress' => [
            'name' => 'progress',
            'vname' => 'LBL_PROGRESS',
            'type' => 'composite',
            'dbType' => 'text',
            'layout' => ['percent', 'completed', 'failed'],
            'attributeFields' => [
                'percent' => [
                    'name' => 'percent',
                    'type' => 'int',
                    'vname' => 'LBL_PROGRESS_PERCENT',
                ],
                'completed' => [
                    'name' => 'completed',
                    'type' => 'int',
                    'vname' => 'LBL_PROGRESS_COMPLETED',
                ],
                'failed' => [
                    'name' => 'failed',
                    'type' => 'int',
                    'vname' => 'LBL_PROGRESS_FAILED',
                ],
            ],
            'display' => 'readonly',
            'direction' => 'inline',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'type' => [
            'name' => 'type',
            'vname' => 'LBL_TYPE',
            'type' => 'enum',
            'options' => 'dom_processes_types',
            'display' => 'readonly',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'estimated_run_time' => [
            'name' => 'estimated_run_time',
            'vname' => 'LBL_ESTIMATED_RUN_TIME',
            'type' => 'varchar',
            'len' => 255,
            'source' => 'non-db',
            'required' => false,
            'display' => 'readonly',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'status' => [
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'dom_processes_statuses',
            'display' => 'readonly',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'phase' => [
            'name' => 'phase',
            'vname' => 'LBL_PHASE',
            'type' => 'enum',
            'options' => 'dom_async_task_phases',
            'len' => 50,
            'display' => 'readonly',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'service_key' => [
            'name' => 'service_key',
            'vname' => 'LBL_SERVICE_KEY',
            'type' => 'varchar',
            'len' => 255,
            'required' => false,
            'display' => 'readonly',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'last_run_datetime' => [
            'name' => 'last_run_datetime',
            'vname' => 'LBL_LAST_RUN_DATETIME',
            'type' => 'datetime',
            'required' => false,
            'display' => 'readonly',
            'duplicate_merge' => 'disabled',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'attachments' => [
            'name' => 'attachments',
            'vname' => 'LBL_ATTACHMENTS',
            'type' => 'attachment',
            'source' => 'non-db',
            'display' => 'readonly',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
            'metadata' => [
                'storage_type' => 'private-documents',
            ],
        ],
    ],
    'relationships' => [
    ],
];

VardefManager::createVardef('Processes', 'Process', ['basic', 'security_groups', 'assignable']);
