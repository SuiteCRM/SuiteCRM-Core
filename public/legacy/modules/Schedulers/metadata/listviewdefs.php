<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

$viewdefs['Schedulers'] = [
    'ListView' =>  [
        'sidebarWidgets' => [
            'scheduler-info' => [
                'type' => 'statistics',
                'modes' => ['detail'],
                'allowCollapse' => true,
                'labelKey' => 'LBL_SCHEDULERS_INFO',
                'options' => [
                    'sidebarStatistic' => [
                        'rows' => [
                            [
                                'display' => 'none',
                                'cols' => [
                                    [
                                        'statistic' => 'scheduler-cron-last-run',
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_CRON_LAST_USER_TO_RUN',
                                        'hideIfLoading' => true,
                                        'class' => 'scheduler-sidebar-header',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'class' => 'scheduler-status-row-value',
                                        'dynamicLabel' => 'LBL_LAST_USER_RUN_VALUE',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_CRON_LAST_RUN',
                                        'hideIfLoading' => true,
                                        'class' => 'scheduler-sidebar-header',
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_RUN_USER_INVALID',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label',
                                        'activeOnFields' => [
                                            'validUser' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'false',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_LAST_RUN_VALUE',
                                        'class' => 'scheduler-status-row-value',
                                        'activeOnFields' => [
                                            'validUser' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'true',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'class' => 'cron-label pt-1',
                                        'dynamicLabel' => 'LBL_LAST_RUN_VALUE',
                                        'activeOnFields' => [
                                            'validUser' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'noUser',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
            ],
            'scheduler-widget' => [
                'type' => 'statistics',
                'modes' => ['detail'],
                'allowCollapse' => true,
                'labelKey' => 'LBL_CRON_SETUP',
                'options' => [
                    'sidebarStatistic' => [
                        'rows' => [
                            [
                                'display' => 'none',
                                'cols' => [
                                    [
                                        'statistic' => 'scheduler-cron-setup-widget',
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC1_DYNAMIC',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC2_DYNAMIC',
                                        'class' => 'pt-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC3_DYNAMIC',
                                        'class' => 'cron-code-label pt-2 pb-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC4_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC5_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-code-label pb-2 pt-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC6_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC7_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-code-label pt-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_LINUX_DESC8_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label pt-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Unix',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_WIN_DESC1_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Windows',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_WIN_DESC2_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-label pt-2',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Windows',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_WIN_DESC3_DYNAMIC',
                                        'hideIfLoading' => true,
                                        'class' => 'cron-code-label pt-1',
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Windows',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_WIN_DESC4_DYNAMIC',
                                        'class' => 'cron-code-label',
                                        'hideIfLoading' => true,
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Windows',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'justify' => 'start',
                                'cols' => [
                                    [
                                        'dynamicLabel' => 'LBL_CRON_WIN_DESC5_DYNAMIC',
                                        'class' => 'cron-label pt-1',
                                        'hideIfLoading' => true,
                                        'activeOnFields' => [
                                            'type' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'value' => 'Windows',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
            ],
        ],
        'bulkActions' => [
            'exclude' => [
                'merge',
            ]
        ]
    ]
];

$listViewDefs ['Schedulers'] = [
    'NAME' => [
        'width' => '35%',
        'label' => 'LBL_LIST_NAME',
        'link' => true,
        'sortable' => true,
        'default' => true,
    ],
    'JOB_INTERVAL' => [
        'width' => '20%',
        'label' => 'LBL_LIST_JOB_INTERVAL',
        'default' => true,
        'sortable' => false,
    ],
    'DATE_TIME_START' => [
        'width' => '25%',
        'label' => 'LBL_LIST_RANGE',
        'customCode' => '{$DATE_TIME_START} - {$DATE_TIME_END}',
        'type' => 'varchar',
        'default' => true,
        'related_fields' => ['date_time_end'],
    ],
    'STATUS' => [
        'width' => '15%',
        'label' => 'LBL_LIST_STATUS',
        'default' => true,
    ],
];
