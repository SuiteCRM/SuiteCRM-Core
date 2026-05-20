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

/**
 * Shared vardef fields for async task modules (Processes, ManualMigrationTasks, etc.).
 *
 * Module-specific fields (type options, status options, progress layout) remain in each
 * module's own vardefs.php.  This template overrides the looser basic-template versions
 * of id, name, and description with the stricter readonly/non-exportable settings
 * required by all async task modules.
 */
$vardefs = [
    'fields' => [

        // --- Overrides of basic template fields ---

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

        'description' => [
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
            'unified_search' => false,
        ],

        'data' => [
            'name' => 'data',
            'vname' => 'LBL_DATA',
            'type' => 'longtext',
            'comment' => 'JSON-encoded task options and handler data',
            'display' => 'none',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        // --- Async-task-specific fields ---

        'progress' => [
            'name' => 'progress',
            'vname' => 'LBL_PROGRESS',
            'type' => 'composite',
            'dbType' => 'text',
            'layout' => ['percent'],
            'attributeFields' => [
                'percent' => [
                    'name' => 'percent',
                    'type' => 'texttemplate',
                    'metadata' => [
                        'templateLabelKey' => 'LBL_PROGRESS_PERCENT_TPL',
                        'conditionalTemplates' => [
                            [
                                'templateLabelKey' => 'LBL_PROGRESS_NOT_STARTED',
                                'displayModes' => ['detail', 'list'],
                                'fieldName' => 'status',
                                'activeOn' => [
                                    ['operator' => 'is-equal', 'values' => ['initial', 'pending']],
                                ],
                            ],
                            [
                                'templateLabelKey' => 'LBL_PROGRESS_QUEUING_TPL',
                                'displayModes' => ['detail', 'list'],
                                'fieldName' => 'phase',
                                'activeOn' => [
                                    ['operator' => 'is-equal', 'values' => ['queueing']],
                                ],
                            ],
                        ],
                    ],
                ],
                'completed' => [
                    'name' => 'completed',
                    'type' => 'int',
                ],
                'failed' => [
                    'name' => 'failed',
                    'type' => 'int',
                ],
                'total' => [
                    'name' => 'total',
                    'type' => 'int',
                ],
                'queued' => [
                    'name' => 'queued',
                    'type' => 'int',
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

        'allow_failure_retry_action' => [
            'name' => 'allow_failure_retry_action',
            'vname' => 'LBL_ALLOW_FAILURE_RETRY_ACTION',
            'type' => 'bool',
            'default' => false,
            'display' => 'none',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

        'allow_failure_rerun_action' => [
            'name' => 'allow_failure_rerun_action',
            'vname' => 'LBL_ALLOW_FAILURE_RERUN_ACTION',
            'type' => 'bool',
            'default' => false,
            'display' => 'none',
            'inline_edit' => false,
            'reportable' => false,
            'massupdate' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],

    ],
];
