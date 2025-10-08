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


$dictionary['EmailMarketing'] = [
    'table' => 'email_marketing',
    'fields' => [
        'id' => [
            'name' => 'id',
            'vname' => 'LBL_NAME',
            'type' => 'id',
            'required' => true,
        ],
        'deleted' => [
            'name' => 'deleted',
            'vname' => 'LBL_CREATED_BY',
            'type' => 'bool',
            'required' => false,
            'reportable' => false,
        ],
        'date_entered' => [
            'name' => 'date_entered',
            'vname' => 'LBL_DATE_ENTERED',
            'type' => 'datetime',
            'required' => true,
        ],
        'date_modified' => [
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
            'required' => true,
        ],
        'modified_user_id' => [
            'name' => 'modified_user_id',
            'rname' => 'user_name',
            'id_name' => 'modified_user_id',
            'vname' => 'LBL_MODIFIED_BY',
            'type' => 'assigned_user_name',
            'table' => 'users',
            'isnull' => 'false',
            'dbType' => 'id'
        ],
        'created_by' => [
            'name' => 'created_by',
            'rname' => 'user_name',
            'id_name' => 'modified_user_id',
            'vname' => 'LBL_CREATED_BY',
            'type' => 'assigned_user_name',
            'table' => 'users',
            'isnull' => 'false',
            'dbType' => 'id'
        ],
        'name' => [
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'varchar',
            'len' => '255',
            'importable' => 'required',
            'required' => true
        ],
        'from_name' =>  //starting from 4.0 from_name is obsolete..replaced with inbound_email_id
            [
                'name' => 'from_name',
                'vname' => 'LBL_FROM_NAME',
                'type' => 'varchar',
                'len' => '100',
                'importable' => 'required',
                'required' => true
            ],
        'from_addr' => [
            'name' => 'from_addr',
            'vname' => 'LBL_FROM_ADDR',
            'type' => 'varchar',
            'len' => '100',
            'importable' => 'required',
            'required' => true
        ],
        'reply_to_name' => [
            'name' => 'reply_to_name',
            'vname' => 'LBL_REPLY_NAME',
            'type' => 'varchar',
            'len' => '100',
        ],
        'reply_to_addr' => [
            'name' => 'reply_to_addr',
            'vname' => 'LBL_REPLY_ADDR',
            'type' => 'varchar',
            'len' => '100',
        ],
        'date_start' => [
            'name' => 'date_start',
            'vname' => 'LBL_SCHEDULED_START_DATE',
            'type' => 'datetime',
            'importable' => 'required',
            'required' => true,
            'footnotes' => [
                [
                    'labelKey' => 'LBL_SCHEDULED_START_DATE_HELP',
                    'displayModes' => ['edit', 'create', 'detail']
                ]
            ]
        ],

        'template_id' => [
            'name' => 'template_id',
            'vname' => 'LBL_TEMPLATE',
            'type' => 'id',
            'required' => true,
            'importable' => 'required',
        ],
        'status' => [
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'default' => 'draft',
            'len' => 100,
            'readonly' => 'true',
            'options' => 'email_marketing_status_dom',
            'importable' => 'required',
            'footnotes' => [
                [
                    'labelKey' => 'LBL_STATUS_DRAFT_NOT_SEND_HELP',
                    'displayModes' => ['edit', 'create', 'detail'],
                    'klass' => 'alert alert-warning pl-2 pb-2 pt-2 mb-1',
                    'icon' => 'exclamation-triangle',
                    'iconKlass' => 'mr-1 align-text-top svg-size-3',
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['draft']
                        ]
                    ]
                ]
            ]
        ],
        'duplicate' => [
            'name' => 'duplicate',
            'vname' => 'LBL_CHECK_DUPLICATE',
            'type' => 'enum',
            'default' => 'email',
            'options' => 'email_marketing_duplicate_dom',
        ],
        'queueing_status' => [
            'name' => 'queueing_status',
            'vname' => 'LBL_QUEUEING_STATUS',
            'type' => 'enum',
            'default' => 'not_started',
            'len' => 100,
            'readonly' => 'true',
            'options' => 'email_marketing_queueing_status_dom',
            'importable' => 'required',
        ],
        'type' => [
            'name' => 'type',
            'vname' => 'LBL_MARKETING_TYPE',
            'type' => 'enum',
            'len' => 100,
            'readonly' => 'true',
            'options' => 'email_marketing_type_dom',
            'importable' => 'required',
            'footnotes' => [
                [
                    'labelKey' => 'LBL_TYPE_LEGACY_HELP',
                    'displayModes' => ['edit', 'create', 'detail'],
                    'icon' => 'info_circled',
                    'iconKlass' => 'mr-1 align-text-bottom svg-size-3 stroke-info fill-info',
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['legacy']
                        ]
                    ]
                ],
                [
                    'labelKey' => 'LBL_TYPE_MARKETING_HELP',
                    'displayModes' => ['edit', 'create', 'detail'],
                    'icon' => 'info_circled',
                    'iconKlass' => 'mr-1 align-text-bottom svg-size-3 stroke-info fill-info',
                    'activeOn' => [
                        [
                            'operator' => 'not-equal',
                            'values' => ['transactional', 'legacy']
                        ]
                    ]
                ],
                [
                    'labelKey' => 'LBL_TYPE_TRANSACTIONAL_HELP',
                    'displayModes' => ['edit', 'create'],
                    'klass' => 'alert alert-warning pl-2 pb-2 pt-2 mb-1',
                    'icon' => 'exclamation-triangle',
                    'iconKlass' => 'mr-1 align-text-top svg-size-3',
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['transactional']
                        ]
                    ]
                ],
                [
                    'labelKey' => 'LBL_TYPE_TRANSACTIONAL_HELP',
                    'displayModes' => ['detail'],
                    'icon' => 'exclamation-triangle',
                    'iconKlass' => 'mr-1 align-text-top svg-size-3',
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['transactional']
                        ]
                    ]
                ],
            ]
        ],
        'has_test_data' => [
            'name' => 'has_test_data',
            'vname' => 'LBL_HAS_TEST_DATA',
            'type' => 'bool',
            'default' => '0',
            'required' => false,
            'reportable' => false,
        ],
        'trackers_enabled' => [
            'name' => 'trackers_enabled',
            'vname' => 'LBL_TRACKER_LINKS_ENABLED',
            'type' => 'bool',
            'displayType' => 'dropdown',
            'options' => 'dom_int_bool_string',
            'defaultValueModes' => [
                'create', 'edit', 'detail'
            ],
            'initDefaultProcess' => 'email-marketing-trackers-enabled-default',
            'required' => false,
            'reportable' => false,
            'metadata' => [
                'boolInternalType' => 'int'
            ],
            'footnotes' => [
                [
                    'labelKey' => 'LBL_TRACKERS_ENABLED_FOOTNOTE',
                    'displayModes' => ['edit', 'create', 'detail'],
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['1', 'true', true, 1]
                        ]
                    ]
                ],
                [
                    'labelKey' => 'LBL_TRACKERS_DISABLED_FOOTNOTE',
                    'displayModes' => ['edit', 'create', 'detail'],
                    'activeOn' => [
                        [
                            'operator' => 'is-equal',
                            'values' => ['0', 'false', false, 0]
                        ]
                    ]
                ]
            ]
        ],
        'email_marketing_config' => [
            'name' => 'email_marketing_config',
            'vname' => 'LBL_CONFIGS',
            'type' => 'varchar',
            'inline_edit' => false,
            'source' => 'non-db',
            'groupFields' => [
                'name',
                'outbound_email_name',
                'date_start',
                'status',
                'queueing_status',
                'type',
                'prospect_list_name',
                'trackers_enabled',
                'duplicate',
                'survey_name',
                'campaign_name',
            ],
            'layout' => [
                'name',
                'status',
                'queueing_status',
                'outbound_email_name',
                'prospect_list_name',
                'date_start',
                'type',
                'trackers_enabled',
                'duplicate',
                'survey_name',
                'campaign_name',
            ],
            'display' => 'vertical',
            'showLabel' => [
                'edit' => ['*'],
                'filter' => ['*'],
                'detail' => ['*'],
            ]
        ],
        'email_marketing_template' => [
            'name' => 'email_marketing_template',
            'vname' => 'LBL_EMAIL',
            'type' => 'varchar',
            'inline_edit' => false,
            'source' => 'non-db',
            'groupFields' => [
                'subject',
                'body'
            ],
            'layout' => [
                'subject',
                'body'
            ],
            'display' => 'vertical',
            'showLabel' => [
                'edit' => ['*'],
                'filter' => ['*'],
                'detail' => ['*'],
            ]
        ],
        'campaign_id' => [
            'name' => 'campaign_id',
            'vname' => 'LBL_CAMPAIGN_ID',
            'type' => 'id',
            'isnull' => true,
            'required' => false,
        ],
        'campaign_name' => [
            'name' => 'campaign_name',
            'rname' => 'name',
            'id_name' => 'campaign_id',
            'vname' => 'LBL_RELATED_CAMPAIGN',
            'type' => 'relate',
            'filterOnEmpty' => true,
            'link' => 'campaign_email_marketing',
            'table' => 'campaigns',
            'isnull' => 'true',
            'readonly' => 'true',
            'module' => 'Campaigns',
            'dbType' => 'varchar',
            'len' => '255',
            'source' => 'non-db',
            'reportable' => false,
            'required' => true,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'outbound_email_id' => [
            'name' => 'outbound_email_id',
            'vname' => 'LBL_OUTBOUND_EMAIL_ACOUNT_ID',
            'type' => 'id',
            'isnull' => true,
            'required' => false,
        ],
        'outbound_email_name' => [
            'name' => 'outbound_email_name',
            'rname' => 'from_addr',
            'defaultValueModes' => [
                'create'
            ],
            'initDefaultProcess' => 'outbound-email-default',
            'showFilter' => false,
            'filter' => [
                'preset' => [
                    'type' => 'outbound-email',
                    'params' => [
                        'module' => 'OutboundEmailAccounts'
                    ]
                ]
            ],
            'id_name' => 'outbound_email_id',
            'vname' => 'LBL_FROM',
            'join_name' => 'outbound_email',
            'type' => 'relate',
            'filterOnEmpty' => true,
            'link' => 'outbound_email',
            'table' => 'outbound_email',
            'isnull' => 'true',
            'module' => 'OutboundEmailAccounts',
            'dbType' => 'varchar',
            'len' => '255',
            'source' => 'non-db',
            'reportable' => false,
            'required' => true,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'log_entries' => [
            'name' => 'log_entries',
            'type' => 'link',
            'relationship' => 'email_marketing_campaignlog',
            'source' => 'non-db',
            'vname' => 'LBL_LOG_ENTRIES',
        ],
        'queueitems' => [
            'name' => 'queueitems',
            'vname' => 'LBL_QUEUE_ITEMS',
            'type' => 'link',
            'relationship' => 'email_marketing_emailman',
            'source' => 'non-db',
        ],
        'all_prospect_lists' => [
            'name' => 'all_prospect_lists',
            'vname' => 'LBL_ALL_PROSPECT_LISTS',
            'type' => 'bool',
            'default' => 0,
        ],
        'subject' => [
            'name' => 'subject',
            'vname' => 'LBL_SUBJECT',
            'type' => 'varchar',
            'len' => '255',
        ],
        'body' => [
            'name' => 'body',
            'type' => 'html',
            'displayType' => 'squire',
            'dbType' => 'longtext',
            'vname' => 'LBL_BODY',
            'inline_edit' => false,
            'rows' => 10,
            'asyncValidators' => [
                'unsubscribe-link-validation' => [
                    'key' => 'unsubscribe-link-validation'
                ]
            ],
            'cols' => 250,
            'metadata' => [
                'trustHTML' => true,
                'purifyHtml' => false,
                'errorPosition' => 'top',
                'squire' => [
                    'edit' => [
                        'dynamicHeight' => true,
                        'dynamicHeightAncestor' => '.field-layout',
                        'dynamicHeightAdjustment' => -140,
                        'buttonLayout' => [
                            [
                                'bold',
                                'italic',
                                'underline',
                                'strikethrough',
                            ],
                            [
                                'font',
                                'size',
                            ],
                            [
                                'textColour',
                                'highlight',
                            ],
                            [
                                'insertLink',
                            ],
                            [
                                'unorderedList',
                                'orderedList',
                                'indentMore',
                                'indentLess',
                            ],
                            [
                                'alignLeft',
                                'alignCenter',
                                'alignRight',
                                'justify',
                            ],
                            [
                                'quote',
                                'unquote',
                            ],
                            [
                                'clearFormatting',
                            ],
                            [
                                'injectUnsubscribe'
                            ],
                            [
                                'html'
                            ]
                        ]
                    ],
                    'detail' => [
                        'dynamicHeight' => true,
                        'dynamicHeightAncestor' => '.field-layout',
                        'dynamicHeightAdjustment' => -140,
                        'buttonLayout' => [
                            [
                                'bold',
                                'italic',
                                'underline',
                                'strikethrough',
                            ],
                            [
                                'font',
                                'size',
                            ],
                            [
                                'textColour',
                                'highlight',
                            ],
                            [
                                'insertLink',
                            ],
                            [
                                'unorderedList',
                                'orderedList',
                                'indentMore',
                                'indentLess',
                            ],
                            [
                                'alignLeft',
                                'alignCenter',
                                'alignRight',
                                'justify',
                            ],
                            [
                                'quote',
                                'unquote',
                            ],
                            [
                                'clearFormatting',
                            ],
                            [
                                'injectUnsubscribe'
                            ],
                            [
                                'html'
                            ]
                        ]
                    ]
                ]
            ],
        ],
        //non-db-fields.
        'template_name' => [
            'name' => 'template_name',
            'rname' => 'name',
            'id_name' => 'template_id',
            'vname' => 'LBL_TEMPLATE_SELECTED',
            'type' => 'relate',
            'table' => 'email_templates',
            'isnull' => 'true',
            'module' => 'EmailTemplates',
            'dbType' => 'varchar',
            'link' => 'emailtemplate',
            'filterOnEmpty' => true,
            'len' => '255',
            'source' => 'non-db',
            'metadata' => [
                'selectConfirmation' => true,
                'confirmationMessages' => ['LBL_TEMPLATE_CONFIRMATION'],
            ]
        ],
        'prospect_list_name' => [
            'required' => true,
            'metadata' => [
                'headerField' => [
                    'name' => 'name',
                ],
                'subHeaderField' => [
                    'name' => 'list_type',
                    'type' => 'enum',
                    'definition' => [
                        'options' => 'prospect_list_type_dom',
                    ]
                ],
            ],
            'name' => 'prospect_list_name',
            'vname' => 'LBL_TARGET_LISTS',
            'footnotes' => [
                [
                    'labelKey' => 'LBL_TARGET_LISTS_HELP',
                    'displayModes' => ['edit', 'create']
                ]
            ],
            'type' => 'multirelate',
            'link' => 'prospectlists',
            'source' => 'non-db',
            'module' => 'ProspectLists',
            'filterOnEmpty' => true,
            'rname' => 'name',
            'showFilter' => false,
            'filter' => [
                'attributes' => [
                    'id' => 'campaign_id'
                ],
                'preset' => [
                    'type' => 'prospectlists',
                    'params' => [
                        'parent_field' => 'propects_lists',
                        'parent_module' => 'Campaigns',
                    ],
                ],
                'static' => [
                    'list_type' => ['seed', 'default']
                ]
            ],
        ],
        //related fields.
        'prospectlists' => [
            'name' => 'prospectlists',
            'vname' => 'LBL_PROSPECT_LISTS',
            'type' => 'link',
            'relationship' => 'email_marketing_prospect_lists',
            'source' => 'non-db',
        ],
        'survey' => [
            'name' => 'survey',
            'type' => 'link',
            'relationship' => 'email_marketing_survey',
            'source' => 'non-db',
            'module' => 'Surveys',
            'bean_name' => 'Surveys',
            'id_name' => 'survey_id',
            'link_type' => 'one',
            'side' => 'left',
        ],
        'survey_name' => [
            'name' => 'survey_name',
            'type' => 'relate',
            'source' => 'non-db',
            'vname' => 'LBL_SURVEY',
            'save' => true,
            'id_name' => 'survey_id',
            'link' => 'survey',
            'table' => 'surveys',
            'filterOnEmpty' => true,
            'module' => 'Surveys',
            'rname' => 'name',
            'logic' => [
                'required' => [
                    'key' => 'required',
                    'modes' => ['edit', 'create'],
                    'params' => [
                        'fieldDependencies' => [
                            'type'
                        ],
                        'activeOnFields' => [
                            'type' => [
                                'survey'
                            ],
                        ],
                    ],
                ]
            ],
            'displayLogic' => [
                'show_for_survey_emails' => [
                    'key' => 'displayType',
                    'modes' => [
                        'detail',
                        'edit',
                        'create',
                    ],
                    'params' => [
                        'fieldDependencies' => [
                            'type',
                        ],
                        'activeOnFields' => [
                            'type' => [
                                [
                                    'operator' => 'not-equal',
                                    'values' => ['survey']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'survey_id' => [
            'name' => 'survey_id',
            'type' => 'id',
            'reportable' => false,
        ],
        'outbound_email' => [
            'name' => 'outbound_email',
            'type' => 'link',
            'relationship' => 'email_marketing_outbound_email_accounts',
            'link_type' => 'one',
            'source' => 'non-db',
            'vname' => 'LBL_OUTBOUND_EMAIL_ACCOUNT',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'emailtemplate' => [
            'name' => 'emailtemplate',
            'vname' => 'LBL_EMAIL_TEMPLATE',
            'type' => 'link',
            'relationship' => 'email_template_email_marketings',
            'source' => 'non-db',
        ],
        'surveylink' => [
            'name' => 'surveylink',
            'type' => 'link',
            'relationship' => 'email_marketing_survey',
            'source' => 'non-db',
            'bean_name' => 'Surveys',
            'id_name' => 'survey_id'
        ],
    ],
    'indices' => [
        [
            'name' => 'emmkpk',
            'type' => 'primary',
            'fields' => [
                'id'
            ]
        ],
        [
            'name' => 'idx_emmkt_name',
            'type' => 'index',
            'fields' => [
                'name'
            ]
        ],
        [
            'name' => 'idx_emmkit_del',
            'type' => 'index',
            'fields' => [
                'deleted'
            ]
        ],
        [
            'name' => 'idx_status',
            'type' => 'index',
            'fields' => [
                'status'
            ]
        ],
        [
            'name' => 'idx_date_start',
            'type' => 'index',
            'fields' => [
                'date_start'
            ]
        ],
        [
            'name' => 'idx_survey_id',
            'type' => 'index',
            'fields' => [
                'survey_id'
            ]
        ],
    ],
    'relationships' => [
        'email_template_email_marketings' => [
            'lhs_module' => 'EmailTemplates',
            'lhs_table' => 'email_templates',
            'lhs_key' => 'id',
            'rhs_module' => 'EmailMarketing',
            'rhs_table' => 'email_marketing',
            'rhs_key' => 'template_id',
            'relationship_type' => 'one-to-many'
        ],
        'email_marketing_survey' => [
            'lhs_module' => 'Surveys',
            'lhs_table' => 'surveys',
            'lhs_key' => 'id',
            'rhs_module' => 'EmailMarketing',
            'rhs_table' => 'email_marketing',
            'rhs_key' => 'survey_id',
            'relationship_type' => 'one-to-many'
        ],
        'email_marketing_outbound_email_accounts' => [
            'lhs_module' => 'OutboundEmailAccounts',
            'lhs_table' => 'outbound_email',
            'lhs_key' => 'id',
            'rhs_module' => 'EmailMarketing',
            'rhs_table' => 'email_marketing',
            'rhs_key' => 'outbound_email_id',
            'relationship_type' => 'one-to-many'
        ],
        'email_marketing_campaignlog' => [
            'lhs_module' => 'EmailMarketing',
            'lhs_table' => 'email_marketing',
            'lhs_key' => 'id',
            'rhs_module' => 'CampaignLog',
            'rhs_table' => 'campaign_log',
            'rhs_key' => 'marketing_id',
            'relationship_type' => 'one-to-many'
        ],
        'email_marketing_emailman' => [
            'lhs_module' => 'EmailMarketing',
            'lhs_table' => 'email_marketing',
            'lhs_key' => 'id',
            'rhs_module' => 'EmailMan',
            'rhs_table' => 'emailman',
            'rhs_key' => 'marketing_id',
            'relationship_type' => 'one-to-many'
        ],
    ],
];

if (!class_exists('VardefManager')) {
    require_once('include/SugarObjects/VardefManager.php');
}
VardefManager::createVardef('EmailMarketing', 'EmailMarketing', ['security_groups']);
