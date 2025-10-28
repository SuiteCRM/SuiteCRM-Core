<?php
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

$viewdefs ['Campaigns'] = [
    'DetailView' => [
        'sidebarWidgets' => [
            'campaign-charts' => [
                'type' => 'chart',
                'labelKey' => 'LBL_CAMPAIGN_CHARTS',
                'modes' => ['detail'],
                'options' => [
                    'toggle' => true,
                    'headerTitle' => false,
                    'charts' => [
                        [
                            'chartKey' => 'campaign-response-by-recipient-activity',
                            'chartType' => 'vertical-bar',
                            'statisticsType' => 'campaign-response-by-recipient-activity',
                            'labelKey' => 'LBL_CAMPAIGN_RESPONSE_BY_RECIPIENT_ACTIVITY',
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
                            'labelKey' => 'LBL_CAMPAIGN_SEND_STATUS',
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
                    'Campaigns' => ['view']
                ]
            ],
        ],
        'bottomWidgets' => [
            [
                'type' => 'record-table',
                'allowCollapse' => true,
                'modes' => ['detail'],
                'acl' => ['list'],
                'aclModule' => 'EmailMarketing',
                'options' => [
                    'recordTable' => [
                        'name' => 'campaign_interactions',
                        'type' => 'collection',
                        'sort_order' => 'desc',
                        'sort_by' => 'date_start',
                        'labelKey' => 'LBL_CAMPAIGN_ACTIONS',
                        'title_key' => 'LBL_CAMPAIGN_ACTIONS',
                        'headerModule' => 'Campaigns',
                        'module' => 'EmailMarketing',
                        'top_buttons' => [
                            [
                                'modes' => ['list'],
                                'acl' => ['edit'],
                                'action' => 'create',
                                'key' => 'create',
                                'module' => 'email-marketing',
                                'additionalFields' => [
                                    'campaign_email_marketing_name' => 'name',
                                    'campaign_id' => 'id',
                                    'campaign_name' => 'name',
                                    'created_by' => 'user_name',
                                    'modified_by' => 'id',
                                    'parent_id' => 'id',
                                    'parent_name' => 'name',
                                ],
                                'params' => [
                                    'expanded' => true,
                                    'collapsedMobile' => true,
                                    'redirect' => false,
                                ],
                                'extraParams' => [
                                    'type' => 'marketing',
                                    'parent_type' => 'Campaigns',
                                    'return_relationship' => 'campaign_email_marketing',
                                    'target_module' => 'email-marketing',
                                ],
                                'labelKey' => 'LBL_NEW_EM_MARKETING',
                                'widget_class' => 'SubPanelTopButtonQuickCreate',
                            ],
                            [
                                'modes' => ['list'],
                                'acl' => ['edit'],
                                'action' => 'create',
                                'key' => 'create',
                                'params' => [
                                    'collapsedMobile' => true,
                                ],
                                'module' => 'surveys',
                                'labelKey' => 'LBL_NEW_SURVEY',
                                'widget_class' => 'SubPanelTopButtonQuickCreate',
                            ],
                            [
                                'modes' => ['list'],
                                'acl' => ['edit'],
                                'action' => 'create',
                                'key' => 'create',
                                'module' => 'email-marketing',
                                'params' => [
                                    'redirect' => false,
                                ],
                                'additionalFields' => [
                                    'campaign_email_marketing_name' => 'name',
                                    'campaign_id' => 'id',
                                    'campaign_name' => 'name',
                                    'created_by' => 'user_name',
                                    'modified_by' => 'id',
                                    'parent_id' => 'id',
                                    'parent_name' => 'name',
                                ],
                                'extraParams' => [
                                    'type' => 'survey',
                                    'parent_type' => 'Campaigns',
                                    'return_relationship' => 'campaign_email_marketing',
                                    'target_module' => 'email-marketing',
                                ],
                                'labelKey' => 'LBL_NEW_EM_SURVEY',
                                'widget_class' => 'SubPanelTopButtonQuickCreate',
                            ],
                            [
                                'modes' => ['list'],
                                'acl' => ['edit'],
                                'action' => 'create',
                                'key' => 'create',
                                'module' => 'email-marketing',
                                'params' => [
                                    'expanded' => true,
                                    'redirect' => false,
                                ],
                                'additionalFields' => [
                                    'campaign_email_marketing_name' => 'name',
                                    'campaign_id' => 'id',
                                    'campaign_name' => 'name',
                                    'created_by' => 'user_name',
                                    'modified_by' => 'id',
                                    'parent_id' => 'id',
                                    'parent_name' => 'name',
                                ],
                                'extraParams' => [
                                    'type' => 'transactional',
                                    'parent_type' => 'Campaigns',
                                    'return_relationship' => 'campaign_email_marketing',
                                    'target_module' => 'email-marketing',
                                ],
                                'labelKey' => 'LBL_NEW_EM_TRANSACTIONAL',
                                'widget_class' => 'SubPanelTopButtonQuickCreate',
                            ],
                        ],
                        'columns' => [
                            [
                                'name' => 'name',
                                'label' => 'LBL_NAME',
                                'link' => true,
                            ],
                            [
                                'name' => 'status',
                                'label' => 'LBL_STATUS',
                                'type' => 'enum',
                                'fieldDefinition' => [
                                    'options' => 'email_marketing_status_dom'
                                ]
                            ],
                            [
                                'name' => 'type',
                                'label' => 'LBL_TYPE',
                                'type' => 'enum',
                                'fieldDefinition' => [
                                    'options' => 'email_marketing_type_dom',
                                ]
                            ],
                            [
                                'name' => 'date_start',
                                'label' => 'LBL_SEND_DATE',
                                'sortable' => true,
                                'sortReadOnly' => true,
                                'type' => 'datetime',
                            ],
                            [
                                'name' => 'created_by_name',
                                'label' => 'LBL_CREATED_BY',
                                'type' => 'relate',
                                'fieldDefinition' => [
                                    'rname' => 'user_name',
                                    'source' => 'non-db'
                                ]
                            ]
                        ],
                    ]
                ],
            ]
        ],
        'templateMeta' => [
            'maxColumns' => '2',
            'widths' => [
                [
                    'label' => '10',
                    'field' => '30',
                ],
                [
                    'label' => '10',
                    'field' => '30',
                ],
            ],
            'useTabs' => true,
            'tabDefs' => [
                'LBL_CAMPAIGN_INFORMATION' => [
                    'newTab' => true,
                    'panelDefault' => 'expanded',
                ],
                'LBL_NAVIGATION_MENU_GEN2' => [
                    'newTab' => true,
                    'panelDefault' => 'expanded',
                ],
                'LBL_PANEL_ASSIGNMENT' => [
                    'newTab' => true,
                    'panelDefault' => 'expanded',
                ],
            ],
        ],
        'metadata' => [
            'validateOnlyOnSubmit' => true,
        ],
        'panels' => [
            'lbl_campaign_information' => [
                [
                    'name',
                    [
                        'name' => 'status',
                        'label' => 'LBL_CAMPAIGN_STATUS',
                    ],
                ],
                [
                    [
                        'name' => 'start_date',
                        'label' => 'LBL_CAMPAIGN_START_DATE',
                    ],
                    [
                        'name' => 'end_date',
                        'label' => 'LBL_CAMPAIGN_END_DATE',
                    ],
                ],
                [
                    [
                        'name' => 'propects_lists',
                        'label' => 'LBL_PROSPECT_LISTS'
                    ],
                    [
                        'name' => 'content',
                        'label' => 'LBL_CAMPAIGN_CONTENT',
                    ]
                ],
                [
                    [
                        'name' => 'suppression_lists',
                        'label' => 'LBL_SUPPRESSION_LISTS'
                    ],
                    [
                        'name' => 'assigned_user_name',
                        'label' => 'LBL_ASSIGNED_TO',
                    ],
                ],
            ],
            'LBL_NAVIGATION_MENU_GEN2' => [
                [
                    [
                        'name' => 'budget',
                    ],
                    [
                        'name' => 'expected_cost',
                    ],
                ],
                [
                    [
                        'name' => 'actual_cost',
                    ],
                    [
                        'name' => 'expected_revenue',
                    ],
                ],
                [
                    [
                        'name' => 'objective',
                        'label' => 'LBL_CAMPAIGN_OBJECTIVE',
                    ],
                    [
                        'name' => 'impressions',
                        'label' => 'LBL_CAMPAIGN_IMPRESSIONS',
                    ],
                ],
            ],
            'LBL_PANEL_ASSIGNMENT' => [
                [
                    [
                        'name' => 'date_entered',
                        'customCode' => '{$fields.date_entered.value} {$APP.LBL_BY} {$fields.created_by_name.value}',
                    ],
                    [
                        'name' => 'date_modified',
                        'label' => 'LBL_DATE_MODIFIED',
                        'customCode' => '{$fields.date_modified.value} {$APP.LBL_BY} {$fields.modified_by_name.value}',
                    ],
                ],
            ],
        ],
    ],
];
