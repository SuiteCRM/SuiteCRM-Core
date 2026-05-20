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

$dictionary['AsyncTaskItem'] = [
    'table' => 'async_task_items',
    'comment' => 'Async Task Items - individual work units for batch async tasks',
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
        ],
        'name' => [
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'dbType' => 'varchar',
            'len' => 255,
            'required' => false,
            'display' => 'readonly',
            'reportable' => false,
        ],
        'async_task_id' => [
            'name' => 'async_task_id',
            'vname' => 'LBL_ASYNC_TASK_ID',
            'type' => 'varchar',
            'len' => 36,
            'required' => true,
            'comment' => 'FK to the parent async task record',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'item_key' => [
            'name' => 'item_key',
            'vname' => 'LBL_ITEM_KEY',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
            'comment' => 'Flexible identifier: record ID, line number, filename, etc.',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'item_name' => [
            'name' => 'item_name',
            'vname' => 'LBL_ITEM_NAME',
            'type' => 'varchar',
            'len' => 255,
            'required' => false,
            'comment' => 'Display name for this item (record name, filename, etc.); falls back to item_key.',
            'display' => 'readonly',
            'reportable' => false,
            'linkActions' => [
                [
                    'key' => 'record-link',
                    'params' => [
                        'moduleField' => 'item_module',
                        'recordField' => 'item_key',
                    ],
                    'activeOnFields' => [
                        'item_module' => [
                            ['operator' => 'not-empty'],
                        ],
                    ],
                ],
            ],
        ],
        'item_module' => [
            'name' => 'item_module',
            'vname' => 'LBL_ITEM_MODULE',
            'type' => 'varchar',
            'len' => 100,
            'required' => false,
            'comment' => 'Legacy module name when item_key is a record ID; empty otherwise.',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'status' => [
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'varchar',
            'len' => 36,
            'required' => true,
            'default' => 'queued',
            'comment' => 'Item status: queued, processing, completed, failed, skipped',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'error_message' => [
            'name' => 'error_message',
            'vname' => 'LBL_ERROR_MESSAGE',
            'type' => 'text',
            'required' => false,
            'comment' => 'Error details if status is failed',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'data' => [
            'name' => 'data',
            'vname' => 'LBL_DATA',
            'type' => 'text',
            'required' => false,
            'comment' => 'JSON-encoded handler-specific input payload',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'result_data' => [
            'name' => 'result_data',
            'vname' => 'LBL_RESULT_DATA',
            'type' => 'text',
            'required' => false,
            'comment' => 'JSON-encoded result data from processing',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'sort_order' => [
            'name' => 'sort_order',
            'vname' => 'LBL_SORT_ORDER',
            'type' => 'int',
            'len' => 11,
            'required' => false,
            'default' => 0,
            'comment' => 'Ordering of items within the task',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'retry_count' => [
            'name' => 'retry_count',
            'vname' => 'LBL_RETRY_COUNT',
            'type' => 'int',
            'len' => 11,
            'required' => false,
            'default' => 0,
            'comment' => 'Number of times this item has been retried after failure',
            'display' => 'readonly',
            'reportable' => false,
        ],
        'attachments' => [
            'name' => 'attachments',
            'vname' => 'LBL_ATTACHMENTS',
            'type' => 'attachment',
            'source' => 'non-db',
            'inline_edit' => false,
            'comment' => 'File attachments for this task item',
            'metadata' => [
                'storage_type' => 'private-documents',
            ],
        ],
    ],
    'indices' => [
        [
            'name' => 'idx_ati_async_task_id',
            'type' => 'index',
            'fields' => ['async_task_id'],
        ],
        [
            'name' => 'idx_ati_async_task_status',
            'type' => 'index',
            'fields' => ['async_task_id', 'status'],
        ],
        [
            'name' => 'idx_ati_item_key',
            'type' => 'index',
            'fields' => ['async_task_id', 'item_key'],
        ],
    ],
    'relationships' => [],
];

VardefManager::createVardef('AsyncTaskItems', 'AsyncTaskItem', ['basic']);
