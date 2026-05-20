<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
 * along with this program.  If not, see http://www.gnu.org/licenses.
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


$module_name = 'ManualMigrationTasks';


$viewdefs[$module_name] = [
    'ListView' =>  [
        'sidebarWidgets' => [
            'messenger-setup-widget' => [
                'type' => 'statistics',
                'modes' => ['list'],
                'allowCollapse' => true,
                'labelKey' => 'LBL_MESSENGER_SETUP',
                'options' => [
                    'sidebarStatistic' => [
                        'rows' => [
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_MESSENGER_SETUP_DESC1',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_MESSENGER_SETUP_DESC2',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label pt-2',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_MESSENGER_SETUP_DESC3',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label pt-2',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'statistic' => 'messenger-setup-url',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label pt-1',
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
            ],
        ],
        'bulkActions' => [
            'actions' => [
            ],
            'exclude' => [
                'merge',
                'massupdate',
                'export',
                'delete'
            ],
        ]
    ]
];


$listViewDefs[$module_name] = [
    'NAME' => [
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true,
    ],
    'STATUS' => [
        'label' => 'LBL_STATUS',
        'default' => true,
    ],
    'PHASE' => [
        'label' => 'LBL_PHASE',
        'default' => true,
    ],
    'PROGRESS' => [
        'label' => 'LBL_PROGRESS',
        'default' => true,
    ],
    'LAST_RUN_DATETIME' => [
        'label' => 'LBL_LAST_RUN_DATETIME',
        'default' => true,
    ],
    'ASSIGNED_USER_NAME' => [
        'label' => 'LBL_ASSIGNED_TO_NAME',
        'default' => true,
    ],
];
