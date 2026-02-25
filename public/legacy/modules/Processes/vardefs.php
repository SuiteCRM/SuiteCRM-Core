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

        // Processes shows completed and failed counts alongside the percentage bar.
        'progress' => [
            'name' => 'progress',
            'vname' => 'LBL_PROGRESS',
            'type' => 'composite',
            'dbType' => 'text',
            'layout' => ['percent', 'completed', 'failed'],
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
    ],
    'relationships' => [
    ],
];

VardefManager::createVardef('Processes', 'Process', ['basic', 'security_groups', 'assignable', 'asynctask']);
