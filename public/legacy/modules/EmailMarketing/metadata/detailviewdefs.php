<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2024 SalesAgility Ltd.
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

$viewdefs ['EmailMarketing'] = [
    'DetailView' => [
        'header' => [
            'backButton' => [
                'display' => true,
                'navigate' => [
                    'parentId' => 'campaign_id',
                    'parentModule' => 'Campaigns',
                ],
            ]
        ],
        'headerWidgets' => [
            'test-data-banner' => [
                'type' => 'banner-grid',
                'modes' => ['detail'],
                'options' => [
                    'mainRowClass' => 'd-flex h-100 row justify-content-center align-items-center w-100 mb-0 mr-0 ml-0 alert alert-warning',
                    'bannerGrid' => [
                        'rows' => [
                            [
                                'justify' => 'center',
                                'cols' => [
                                    [
                                        'bold' => true,
                                        'class' => 'd-flex align-items-center',
                                        'icon' => 'exclamation-triangle',
                                        'labelKey' => 'LBL_WARNING',
                                        'labelClass' => 'd-inline-block ml-1',
                                    ],
                                    [
                                        'class' => 'd-flex align-items-center',
                                        'labelKey' => 'LBL_DASH_SYMBOL',
                                        'labelClass' => 'd-inline-block ml-1',
                                    ],
                                    [
                                        'class' => 'd-flex align-items-center',
                                        'labelKey' => 'LBL_DISPLAYING_TEST_EMAIL_MARKETING_DATA',
                                        'labelClass' => 'd-inline-block ml-1 text-wrap',
                                    ],
                                ]
                            ],
                        ]
                    ]
                ],
                'acls' => [
                ],
                'activeOnFields' => [
                    'has_test_data' => [
                        [
                            'operator' => 'is-equal',
                            'values' => [true, 'true', 1, '1']
                        ],
                    ],
                ]
            ],
        ],
        'sidebarWidgets' => [
            'scheduler-widget' => [
                'type' => 'statistics',
                'modes' => ['detail'],
                'allowCollapse' => true,
                'labelKey' => 'LBL_SCHEDULER_WIDGET',
                'options' => [
                    'sidebarStatistic' => [
                        'rows' => [
                            [
                                'display' => 'none',
                                'cols' => [
                                    [
                                        'statistic' => 'email-marketing-diagnostics',
                                        'params' => [
                                            'jobs' => [
                                                'scheduler::send-from-queue',
                                                'scheduler::email-to-queue',
                                                'function::pollMonitoredInboxesForBouncedCampaignEmails',
                                            ],
                                            'settings' => [
                                                [
                                                    'key' => 'campaign_marketing_items_per_run',
                                                    'defaultKey' => 'campaign_marketing_items_per_run_default',
                                                    'type' => 'int'
                                                ],
                                                [
                                                    'key' => 'campaign_emails_per_run',
                                                    'defaultKey' => 'campaign_emails_per_run_default',
                                                    'type' => 'int'
                                                ],
                                                [
                                                    'key' => 'trackers_enabled',
                                                    'default' => false,
                                                    'hasConfig' => false,
                                                    'type' => 'bool'
                                                ],
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-header-row',
                                'cols' => [
                                    [
                                        'icon' => 'Schedulers',
                                        'class' => 'campaign-sidebar-header-icon'
                                    ],
                                    [
                                        'labelKey' => 'LBL_SCHEDULERS',
                                        'class' => 'campaign-sidebar-header',
                                    ],
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_OOTB_SEND_EMAIL_FROM_QUEUE',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true,
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_SEND_FROM_QUEUE_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                        'activeOnFields' => [
                                            'send-from-queue' => [
                                                [
                                                    'operator' => 'not-empty',
                                                ],
                                            ],
                                        ]
                                    ],
                                    [
                                        'labelKey' => 'LBL_INACTIVE',
                                        'hideIfLoading' => true,
                                        'class' => 'campaign-status-check-row-value w-fit-content alert alert-danger d-flex align-items-center m-0 pb-1 pl-2 pr-2 pt-1',
                                        'icon' => 'exclamation-circle',
                                        'labelClass' => 'd-inline-block ml-1',
                                        'activeOnFields' => [
                                            'send-from-queue' => [
                                                [
                                                    'operator' => 'is-empty',
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_OOTB_SEND_EMAIL_TO_QUEUE',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_EMAIL_TO_QUEUE_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                        'activeOnFields' => [
                                            'email-to-queue' => [
                                                [
                                                    'operator' => 'not-empty',
                                                ],
                                            ],
                                        ]
                                    ],
                                    [
                                        'labelKey' => 'LBL_INACTIVE',
                                        'hideIfLoading' => true,
                                        'class' => 'campaign-status-check-row-value w-fit-content alert alert-danger d-flex align-items-center m-0 pb-1 pl-2 pr-2 pt-1',
                                        'icon' => 'exclamation-circle',
                                        'labelClass' => 'd-inline-block ml-1',
                                        'activeOnFields' => [
                                            'email-to-queue' => [
                                                [
                                                    'operator' => 'is-empty',
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row border-bottom pb-2 mb-1',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_OOTB_BOUNCE',
                                        'titleKey' => 'LBL_OOTB_BOUNCE',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_POLL_BOUNCED_CAMPAIGN_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                        'activeOnFields' => [
                                            'pollMonitoredInboxesForBouncedCampaignEmails' => [
                                                [
                                                    'operator' => 'not-empty',
                                                ],
                                            ],
                                        ]
                                    ],
                                    [
                                        'labelKey' => 'LBL_INACTIVE',
                                        'hideIfLoading' => true,
                                        'class' => 'campaign-status-check-row-value w-fit-content alert alert-danger d-flex align-items-center m-0 pb-1 pl-2 pr-2 pt-1',
                                        'icon' => 'exclamation-circle',
                                        'labelClass' => 'd-inline-block ml-1',
                                        'activeOnFields' => [
                                            'pollMonitoredInboxesForBouncedCampaignEmails' => [
                                                [
                                                    'operator' => 'is-empty',
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-header-row',
                                'cols' => [
                                    [
                                        'icon' => 'Emails',
                                        'class' => 'campaign-sidebar-header-icon'
                                    ],
                                    [
                                        'labelKey' => 'LBL_INBOUND_EMAIL',
                                        'class' => 'campaign-sidebar-header',
                                    ],
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row border-bottom pb-2 mb-1',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_DOES_BOUNCE_EXIST',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'labelKey' => 'LBL_YES',
                                        'hideIfLoading' => true,
                                        'class' => 'campaign-status-check-row-value',
                                    ]
                                ],
                                'activeOnFields' => [
                                    'bounce_exists' => [
                                        [
                                            'operator' => 'is-equal',
                                            'values' => [true]
                                        ],
                                    ],
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row border-bottom pb-2 mb-1',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_DOES_BOUNCE_EXIST',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'labelKey' => 'LBL_NO',
                                        'hideIfLoading' => true,
                                        'class' => 'campaign-status-check-row-value w-fit-content alert alert-warning d-flex align-items-center m-0 pb-1 pl-2 pr-2 pt-1',
                                        'icon' => 'exclamation-triangle',
                                        'labelClass' => 'd-inline-block ml-1',
                                    ]
                                ],
                                'activeOnFields' => [
                                    'bounce_exists' => [
                                        [
                                            'operator' => 'is-equal',
                                            'values' => [false, 'false', 0, '0']
                                        ],
                                    ],
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-header-row',
                                'cols' => [
                                    [
                                        'icon' => 'Campaigns',
                                        'class' => 'campaign-sidebar-header-icon',
                                    ],
                                    [
                                        'labelKey' => 'LBL_CAMPAIGN_SETTINGS',
                                        'class' => 'campaign-sidebar-header',
                                    ],
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_MARKETING_ITEMS_PER_RUN',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_MARKETING_ITEMS_PER_RUN_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_EMAILS_PER_RUN',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_EMAILS_PER_RUN_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                    ]
                                ]
                            ],
                            [
                                'justify' => 'start',
                                'class' => 'campaign-status-check-row  mb-0',
                                'cols' => [
                                    [
                                        'labelKey' => 'LBL_TRACKER_LINKS_ENABLED',
                                        'class' => 'campaign-status-check-row-label text-uppercase',
                                        'bold' => true
                                    ],
                                    [
                                        'dynamicLabel' => 'LBL_TRACKERS_ENABLED_DYNAMIC_LABEL',
                                        'class' => 'campaign-status-check-row-value',
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'email-marketing-charts' => [
                'type' => 'chart',
                'modes' => ['detail'],
                'labelKey' => 'LBL_EMAIL_MARKETING_CHARTS',
                'options' => [
                    'toggle' => true,
                    'headerTitle' => false,
                    'charts' => [
                        [
                            'chartKey' => 'campaign-response-by-recipient-activity',
                            'chartType' => 'vertical-bar',
                            'statisticsType' => 'campaign-response-by-recipient-activity',
                            'labelKey' => 'LBL_EMAIL_MARKETING_RESPONSE_BY_RECIPIENT_ACTIVITY',
                            'chartOptions' => [
                                'noBarWhenZero' => false,
                                'showDataLabel' => true,
                                'showYAxisLabel' => false,
                                'showXAxisLabel' => false,
                                'yAxis' => false,
                                'xAxis' => true,
                                'rotateXAxisTicks' => false,
                                'trimXAxisTicks' => false,
                            ],
                        ],
                        [
                            'chartKey' => 'campaign-send-status',
                            'chartType' => 'vertical-bar',
                            'statisticsType' => 'campaign-send-status',
                            'labelKey' => 'LBL_EMAIL_MARKETING_SEND_STATUS',
                            'chartOptions' => [
                                'noBarWhenZero' => false,
                                'showDataLabel' => true,
                                'showYAxisLabel' => false,
                                'showXAxisLabel' => false,
                                'yAxis' => false,
                                'xAxis' => true,
                                'rotateXAxisTicks' => false,
                                'trimXAxisTicks' => true,
                                'maxXAxisTickLength' => 8,
                            ],
                        ],
                    ],
                ],
                'acls' => [
                    'EmailMarketing' => ['view']
                ]
            ],
        ],
        'templateMeta' => [
            'maxColumns' => '2',
            'colClasses' => [
                'col-xs-12 col-md-3 col-lg-3 col-xl-3 border-right',
                'col-xs-12 col-md-9 col-lg-9 col-xl-9',
            ],
            'widths' => [
                [
                    'label' => '10',
                    'field' => '30',
                ],
                [
                    'label' => '10',
                    'field' => '80',
                ],
            ],
            'useTabs' => true,
            'tabDefs' => [
                'LBL_OVERVIEW' => [
                    'newTab' => true,
                    'panelDefault' => 'expanded',
                ],
            ],
        ],
        'recordActions' => [
            'actions' => [
                'edit' => [
                    'key' => 'edit',
                    'labelKey' => 'LBL_EDIT',
                    'modes' => ['detail'],
                    'priority' => 100,
                    'params' => [
                        'expanded' => true,
                    ],
                    'acl' => ['edit'],
                    'displayLogic' => [
                        'hide-on-scheduled' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['draft']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'save' => [
                    'key' => 'save',
                    'labelKey' => 'LBL_SAVE_BUTTON_LABEL',
                    'modes' => ['edit'],
                    'acl' => ['edit'],
                    'priority' => 100,
                    'params' => [
                        'expanded' => true,
                        'disableOnRun' => true,
                    ],
                ],
                'saveNew' => [
                    'key' => 'saveNew',
                    'labelKey' => 'LBL_SAVE_BUTTON_LABEL',
                    'modes' => ['create'],
                    'priority' => 100,
                    'acl' => ['edit'],
                    'params' => [
                        'expanded' => true,
                        'disableOnRun' => true,
                    ],
                ],
                'saveContinue' => [
                    'key' => 'saveContinue',
                    'labelKey' => 'LBL_SAVE_CONTINUE_LABEL',
                    'modes' => ['create'],
                    'priority' => 150,
                    'acl' => ['edit'],
                    'params' => [
                        'expanded' => true,
                        'disableOnRun' => true,
                        'collapsedMobile' => true,
                    ],
                ],
                'cancel' => [
                    'key' => 'cancel',
                    'priority' => 200,
                    'labelKey' => 'LBL_CANCEL',
                    'modes' => ['edit'],
                    'params' => [
                        'expanded' => true,
                    ],
                ],
                'cancelCreate' => [
                    'key' => 'cancelCreate',
                    'labelKey' => 'LBL_CANCEL',
                    'modes' => ['create'],
                    'priority' => 200,
                    'params' => [
                        'expanded' => true,
                    ],
                ],
                'insert-email-template' => [
                    'key' => 'insert-email-template',
                    'labelKey' => 'LBL_INSERT_TEMPLATE',
                    'modes' => ['edit', 'create'],
                    'asyncProcess' => true,
                    'aclModule' => 'EmailTemplates',
                    'params' => [
                        'expanded' => true,
                        'selectModal' => [
                            'module' => 'EmailTemplates'
                        ],
                        'displayConfirmation' => true,
                        'confirmationMessages' => ['LBL_TEMPLATE_CONFIRMATION'],
                        'setFieldSubject' => 'subject',
                        'setFieldBody' => 'body',
                    ],
                ],
                'schedule-email-marketing' => [
                    'key' => 'schedule-email-marketing',
                    'labelKey' => 'LBL_SCHEDULE',
                    'asyncProcess' => true,
                    'modes' => ['detail'],
                    'params' => [
                        'expanded' => true,
                        'displayConfirmation' => true,
                        'confirmationMessages' => ['NTC_SCHEDULE_CONFIRMATION', 'NTC_DELETE_TEST_ENTRIES', 'NTC_PROCEED'],
                    ],
                    'acl' => ['view'],
                    'displayLogic' => [
                        'hide-on-scheduled' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['draft']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'unschedule-email-marketing' => [
                    'key' => 'unschedule-email-marketing',
                    'labelKey' => 'LBL_UNSCHEDULE',
                    'asyncProcess' => true,
                    'modes' => ['detail'],
                    'params' => [
                        'expanded' => true,
                        'displayConfirmation' => true,
                        'confirmationMessages' => ['NTC_UNSCHEDULE_CONFIRMATION', 'NTC_UNSCHEDULE_CONFIRMATION_OTHER', 'NTC_PROCEED'],
                    ],
                    'acl' => ['view'],
                    'displayLogic' => [
                        'hide-on-unscheduled-or-sending' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['scheduled']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'abort-email-marketing' => [
                    'key' => 'abort-email-marketing',
                    'labelKey' => 'LBL_ABORT',
                    'asyncProcess' => true,
                    'modes' => ['detail'],
                    'icon' => 'exclamation-triangle',
                    'klass' => ['btn-danger'],
                    'params' => [
                        'expanded' => true,
                        'displayConfirmation' => true,
                        'confirmationMessages' => ['NTC_ABORT_CONFIRMATION'],
                    ],
                    'acl' => ['view'],
                    'display' => 'hide',
                    'displayLogic' => [
                        'show-on-send' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'is-equal',
                                            'values' => ['pending_send', 'sending']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'send-test-email' => [
                    'key' => 'send-test-email',
                    'labelKey' => 'LBL_SEND_TEST_EMAIL',
                    'modes' => ['detail'],
                    'asyncProcess' => true,
                    'params' => [
                        'expanded' => true,
                        'fieldModal' => [
                            'validationProcess' => 'send-test-email-validation',
                            'fieldGridOptions' => [
                                'maxColumns' => 1,
                            ],
                            'limit' => [
                                'showLimit' => true,
                                'limit_key' => 'test_email_limit',
                                'limitEndLabel' => 'LBL_EMAIL_ADDRESSES'
                            ],
                            'actionLabelKey' => 'LBL_SEND',
                            'titleKey' => 'LBL_SEND_TEST_EMAIL',
                            'descriptionKey' => 'LBL_SEND_TEST_EMAIL_DESC',
                            'centered' => true,
                            'fields' => [
                                'email_address' => [
                                    'name' => 'email_address',
                                    'module' => 'EmailAddress',
                                    'type' => 'line-items',
                                    'label' => 'LBL_EMAIL',
                                    'fieldDefinition' => [
                                        'lineItems' => [
                                            'labelOnFirstLine' => true,
                                            'definition' => [
                                                'name' => 'email-fields',
                                                'type' => 'composite',
                                                'layout' => ['email_address'],
                                                'display' => 'inline',
                                                'attributeFields' => [
                                                    'email_address' => [
                                                        'name' => 'email_address',
                                                        'type' => 'email',
                                                        'showLabel' => ['*'],
                                                    ],
                                                ],
                                            ]
                                        ]
                                    ]
                                ],
                                'email_marketing_users' => [
                                    'name' => 'email_marketing_users',
                                    'label' => 'LBL_USERS',
                                    'type' => 'multirelate',
                                    'fieldDefinition' => [
                                        'source' => 'non-db',
                                        'filterOnEmpty' => true,
                                        'module' => 'Users',
                                        'link' => 'emailmarketing_users',
                                        'rname' => 'name',
                                    ],
                                ],
                                'prospect_list_name' => [
                                    'name' => 'prospect_list_name',
                                    'label' => 'LBL_PROSPECT_LIST_NAME',
                                    'type' => 'multirelate',
                                    'fieldDefinition' => [
                                        'showFilter' => false,
                                        'link' => 'prospectlists',
                                        'source' => 'non-db',
                                        'filterOnEmpty' => true,
                                        'module' => 'ProspectLists',
                                        'rname' => 'name',
                                        'filter' => [
                                            'static' => [
                                                'list_type' => 'test'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ],
                    ],
                    'acl' => ['view'],
                    'displayLogic' => [
                        'hide-on-sending' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['draft']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'delete-test-mail-marketing-entries' => [
                    'key' => 'delete-test-mail-marketing-entries',
                    'labelKey' => 'LBL_DELETE_TEST_ENTRIES',
                    'asyncProcess' => true,
                    'modes' => ['detail'],
                    'params' => [
                        'expanded' => true,
                        'displayConfirmation' => true,
                        'confirmationLabel' => 'NTC_DELETE_TEST_ENTRIES_CONFIRMATION',
                    ],
                    'acl' => ['view'],
                    'displayLogic' => [
                        'hide-on-scheduled' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['draft']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'toggle-widgets' => [
                    'key' => 'toggle-widgets',
                    'labelKey' => 'LBL_INSIGHTS',
                    'priority' => 5000,
                    'modes' => ['detail', 'edit'],
                    'params' => [
                        'expanded' => true,
                    ],
                ],
                'delete' => [
                    'key' => 'delete',
                    'labelKey' => 'LBL_DELETE',
                    'modes' => ['detail'],
                    'asyncProcess' => true,
                    'priority' => 2000,
                    'params' => [
                        'displayConfirmation' => true,
                        'redirectModule' => 'campaigns',
                        'confirmationLabel' => 'NTC_DELETE_CONFIRMATION',
                    ],
                    'acl' => ['delete'],
                    'displayLogic' => [
                        'hide-on-send' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'status' => [
                                        [
                                            'operator' => 'not-equal',
                                            'values' => ['draft']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
                'duplicate' => [
                    'key' => 'duplicate',
                    'labelKey' => 'LBL_DUPLICATE_BUTTON',
                    'modes' => ['detail'],
                    'priority' => 1900,
                    'asyncProcess' => true,
                    'params' => [
                        'queryParams' => [
                            'returnModule' => 'campaigns',
                            'status' => 'draft',
                            'queueing_status' => 'not_started',
                            'has_test_data' => '0'
                        ]
                    ],
                    'acl' => ['delete'],
                    'displayLogic' => [
                        'hide-on-legacy' => [
                            'modes' => ['detail'],
                            'params' => [
                                'activeOnFields' => [
                                    'type' => [
                                        [
                                            'operator' => 'is-equal',
                                            'values' => ['legacy']
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ],
        'metadata' => [
            'validateOnlyOnSubmit' => true,
        ],
        'panels' => [
            'LBL_OVERVIEW' => [
                [
                    [
                        'name' => 'email_marketing_config',
                        'useFullColumn' => ['xs', 'sm', 'md', 'lg', 'xl'],
                    ],
                    [
                        'name' => 'email_marketing_template',
                        'useFullColumn' => ['sm', 'md', 'lg', 'xl'],
                    ]
                ],
            ],
        ],
    ],
];
