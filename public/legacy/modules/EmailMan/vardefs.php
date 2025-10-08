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

$dictionary['EmailMan'] = [
    'table' => 'emailman',
    'comment' => 'Email campaign queue',
    'fields' => [
        'date_entered' => [
            'name' => 'date_entered',
            'vname' => 'LBL_DATE_ENTERED',
            'type' => 'datetime',
            'comment' => 'Date record created',
            'enable_range_search' => true,
            'options' => 'date_range_search_dom',
        ],
        'date_modified' => [
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
            'comment' => 'Date record last modified',
            'enable_range_search' => true,
            'options' => 'date_range_search_dom',
        ],
        'user_id' => [
            'name' => 'user_id',
            'vname' => 'LBL_USER_ID',
            'type' => 'id', 'len' => '36',
            'reportable' => false,
            'comment' => 'User ID representing assigned-to user',
        ],
        'id' => [
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'int',
            'len' => '11',
            'auto_increment' => true,
            'comment' => 'Unique identifier',
        ],
        'list_id' => [
            'name' => 'list_id',
            'vname' => 'LBL_LIST_ID',
            'type' => 'id',
            'reportable' => false,
            'len' => '36',
            'comment' => 'Associated list',
        ],
        'send_date_time' => [
            'name' => 'send_date_time',
            'vname' => 'LBL_SEND_DATE_TIME',
            'type' => 'datetime',
        ],
        'modified_user_id' => [
            'name' => 'modified_user_id',
            'vname' => 'LBL_MODIFIED_USER_ID',
            'type' => 'id',
            'reportable' => false,
            'len' => '36',
            'comment' => 'User ID who last modified record',
        ],
        'more_information' => [
            'name' => 'more_information',
            'vname' => 'LBL_MORE_INFO',
            'type' => 'varchar',
            'len' => '100',
        ],
        'in_queue' => [
            'name' => 'in_queue',
            'vname' => 'LBL_IN_QUEUE',
            'type' => 'bool',
            'default' => '0',
            'displayType' => 'checkbox',
            'comment' => 'Flag indicating if item still in queue',
        ],
        'in_queue_date' => [
            'name' => 'in_queue_date',
            'vname' => 'LBL_IN_QUEUE_DATE',
            'type' => 'datetime',
            'comment' => 'Datetime in which item entered queue',
        ],
        'send_attempts' => [
            'name' => 'send_attempts',
            'vname' => 'LBL_SEND_ATTEMPTS',
            'type' => 'int',
            'default' => '0',
            'comment' => 'Number of attempts made to send this item',
        ],
        'deleted' => [
            'name' => 'deleted',
            'vname' => 'LBL_DELETED',
            'type' => 'bool',
            'reportable' => false,
            'comment' => 'Record deletion indicator',
            'default' => '0',
        ],
        'related_type' => [
            'name' => 'related_type',
            'vname' => 'LBL_RELATED_TYPE',
            'type' => 'varchar',
            'len' => '100',
        ],
        'related_id' => [
            'name' => 'related_id',
            'vname' => 'LBL_RELATED_ID',
            'type' => 'id',
            'reportable' => false,
        ],
        'related_confirm_opt_in' => [
            'name' => 'related_confirm_opt_in',
            'vname' => 'LBL_RELATED_CONFIRM_OPT_IN',
            'type' => 'bool',
            'default' => 0,
            'reportable' => false,
            'comment' => '',
        ],
        'recipient_name' => [
            'name' => 'recipient_name',
            'type' => 'varchar',
            'len' => '255',
            'source' => 'non-db',
        ],
        'recipient_email' => [
            'name' => 'recipient_email',
            'type' => 'varchar',
            'len' => '255',
            'source' => 'non-db',
        ],
        'message_name' => [
            'name' => 'message_name',
            'id_name' => 'marketing_id',
            'group' => 'message_name',
            'len' => '255',
            'source' => 'non-db',
            'rname' => 'name',
            'type' => 'relate',
            'module' => 'EmailMarketing',
            'link' => 'email_marketing',
            'table' => 'email_marketing',
        ],
        'marketing_id' => [
            'name' => 'marketing_id',
            'vname' => 'LBL_MARKETING_ID',
            'group' => 'message_name',
            'type' => 'id',
            'reportable' => false,
            'comment' => '',
        ],
        'campaign_name' => [
            'name' => 'campaign_name',
            'rname' => 'name',
            'source' => 'non-db',
            'id_name' => 'campaign_id',
            'vname' => 'LBL_LIST_CAMPAIGN',
            'group' => 'campaign_name',
            'type' => 'relate',
            'len' => '50',
            'module' => 'Campaigns',
            'link' => 'campaigns',
            'table' => 'campaigns',
        ],
        'campaign_id' => [
            'name' => 'campaign_id',
            'vname' => 'LBL_CAMPAIGN_ID',
            'group' => 'campaign_name',
            'type' => 'id',
            'reportable' => false,
            'comment' => 'ID of related campaign',
        ],

        // Subpanel Fields
        'name' => [
            'name' => 'name',
            'vname' => 'LBL_SUBJECT',
            'type' => 'varchar',
            'metadata' => [
                'linkRoute' => '../../../email-marketing/record/{{attributes.marketing_id}}'
            ],
            'source' => 'non-db',
            'len' => '255',
        ],
        'status' => [
            'name' => 'status',
            'type' => 'enum',
            'source' => 'non-db',
            'len' => 100,
            'options' => 'email_marketing_status_dom',
        ],
        'assigned_user_id' => [
            'name' => 'assigned_user_id',
            'rname' => 'user_name',
            'id_name' => 'assigned_user_id',
            'vname' => 'LBL_ASSIGNED_TO_ID',
            'group' => 'assigned_user_name',
            'type' => 'relate',
            'table' => 'users',
            'module' => 'Users',
            'source' => 'non-db',
            'isnull' => 'false',
            'dbType' => 'id',
            'comment' => 'User ID assigned to record',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'assigned_user_name' => [
            'name' => 'assigned_user_name',
            'link' => 'assigned_user_link',
            'vname' => 'LBL_ASSIGNED_TO_NAME',
            'rname' => 'user_name',
            'type' => 'relate',
            'source' => 'non-db',
            'table' => 'users',
            'id_name' => 'assigned_user_id',
            'module' => 'Users',
            'duplicate_merge' => 'disabled',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
        'assigned_user_link' => [
            'name' => 'assigned_user_link',
            'type' => 'link',
            'relationship' => 'emailman_assigned_user',
            'vname' => 'LBL_ASSIGNED_TO_USER',
            'link_type' => 'one',
            'module' => 'Users',
            'bean_name' => 'User',
            'source' => 'non-db',
            'duplicate_merge' => 'enabled',
            'rname' => 'user_name',
            'id_name' => 'assigned_user_id',
            'table' => 'users',
            'reportable' => false,
            'massupdate' => false,
            'inline_edit' => false,
            'importable' => false,
            'exportable' => false,
            'unified_search' => false,
        ],
    ],
    'relationships' => [
        'emailman_assigned_user' => [
            'lhs_module' => 'Users',
            'lhs_table' => 'users',
            'lhs_key' => 'id',
            'rhs_module' => 'Emailman',
            'rhs_table' => 'emailman',
            'rhs_key' => 'assigned_user_id',
            'relationship_type' => 'one-to-many',
        ],
    ],
    'indices' => [
        ['name' => 'emailmanpk', 'type' => 'primary', 'fields' => ['id']],
        ['name' => 'idx_eman_list', 'type' => 'index', 'fields' => ['list_id', 'user_id', 'deleted']],
        ['name' => 'idx_eman_campaign_id', 'type' => 'index', 'fields' => ['campaign_id']],
        ['name' => 'idx_eman_relid_reltype_id', 'type' => 'index', 'fields' => ['related_id', 'related_type', 'campaign_id']],
        ['name' => 'idx_eman_related', 'type' => 'index', 'fields' => ['related_id', 'related_type', 'marketing_id', 'deleted']],
    ]
];
