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

$module_name = 'Processes';
$viewdefs[$module_name]['DetailView'] = [
    'templateMeta' => [
        'form' => [
            'buttons' => [
            ]
        ],
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30']
        ],
        'tabDefs' => [
            'DEFAULT' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
        ],
    ],
    'header' => [
        'showFavoritesToggle' => false,
    ],
    'recordActions' => [
        'actions' => [
            'run-migration' => [
                'key' => 'run-migration',
                'labelKey' => 'LBL_RUN_MIGRATION',
                'asyncProcess' => true,
                'priority' => 100,
                'modes' => ['detail'],
                'display' => 'show',
                'params' => [
                    'asyncProcessKeyField' => 'service_key',
                    'asyncProcessKeyPrefix' => 'migration-task',
                    'expanded' => true,
                    'disableOnRun' => true,
                    'displayConfirmation' => true,
                    'confirmationMessages' => ['LBL_RUN_MIGRATION_CONFIRMATION'],
                ],
                'displayLogic' => [
                    'hide-on-running' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => [
                                'status' => [
                                    [
                                        'operator' => 'not-equal',
                                        'values' => ['pending']
                                    ],
                                ],
                            ]
                        ]
                    ],
                ],
            ],
            'retry-async-task' => [
                'key' => 'retry-async-task',
                'labelKey' => 'LBL_RETRY',
                'asyncProcess' => true,
                'priority' => 190,
                'modes' => ['detail'],
                'display' => 'hide',
                'params' => [
                    'expanded' => true,
                    'disableOnRun' => true,
                    'displayConfirmation' => true,
                    'confirmationMessages' => ['LBL_RETRY_CONFIRMATION'],
                ],
                'displayLogic' => [
                    'show-on-finished' => [
                        'modes' => ['detail'],
                        'params' => [
                            'fieldDependencies' => ['status'],
                            'activeOnFields' => [
                                'status' => [
                                    [
                                        'operator' => 'is-equal',
                                        'values' => ['completed', 'failed'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'dismiss-async-task' => [
                'key' => 'dismiss-async-task',
                'labelKey' => 'LBL_DISMISS',
                'asyncProcess' => true,
                'priority' => 200,
                'modes' => ['detail'],
                'display' => 'hide',
                'params' => [
                    'expanded' => true,
                    'displayConfirmation' => true,
                    'confirmationMessages' => ['LBL_DISMISS_CONFIRMATION'],
                ],
                'displayLogic' => [
                    'show-on-finished' => [
                        'modes' => ['detail'],
                        'params' => [
                            'fieldDependencies' => ['status'],
                            'activeOnFields' => [
                                'status' => [
                                    [
                                        'operator' => 'is-equal',
                                        'values' => ['completed', 'failed']
                                    ]
                                ],
                            ]
                        ]
                    ],
                ],
            ]
        ],
        'exclude' => [
            'duplicate',
            'delete',
            'duplicate-merge',
            'save',
            'saveNew',
            'saveContinue',
            'edit',
            'create',
        ]
    ],
    'panels' => [
        'default' => [
            [
                'name',
                'assigned_user_name',
            ],
            [
                'status',
                'type',
            ],
            [
                'phase',
                'service_key',
            ],
            [
                'progress',
                'last_run_datetime',
            ],
            [
                'attachments',
            ],
            [
                'description',
            ],
            [
                [
                    'name' => 'date_entered',
                    'label' => 'LBL_DATE_ENTERED',
                ],
                [
                    'name' => 'date_modified',
                    'label' => 'LBL_DATE_MODIFIED',
                ],
            ],
        ],
    ],
];
