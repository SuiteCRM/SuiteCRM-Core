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


$layout_defs['EmailMarketing'] = [
    'subpanel_setup' => [
        'track_queue' => [
            'order' => 100,
            'module' => 'EmailMan',
            'get_subpanel_data' => 'function:getQueueItems',
            'subpanel_name' => 'default',
            'title_key' => 'LBL_MESSAGE_QUEUE_TITLE',
            'sort_order' => 'desc',
        ],
        'targeted' => [
            'order' => 110,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'subpanel_name' => 'ForSentAttempt',
            'title_key' => 'LBL_LOG_ENTRIES_TARGETED_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'blocked' => [
            'order' => 120,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'function_parameters' => [
                0 => 'blocked',
            ],
            'subpanel_name' => 'default',
            'title_key' => 'LBL_LOG_ENTRIES_BLOCKED_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'send_error' => [
            'order' => 130,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'function_parameters' => [
                0 => ['send error', 'invalid email'],
            ],
            'subpanel_name' => 'default',
            'title_key' => 'LBL_LOG_ENTRIES_BOUNCED_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'viewed' => [
            'order' => 140,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'subpanel_name' => 'default',
            'function_parameters' => [
                0 => 'viewed',
            ],
            'title_key' => 'LBL_LOG_ENTRIES_VIEWED_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'lead' => [
            'order' => 150,
            'module' => 'CampaignLog',
            'hidden' => true,
            'get_subpanel_data' => "function:trackLogLeads",
            'subpanel_name' => 'default',
            'title_key' => 'LBL_LOG_ENTRIES_LEAD_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id',
            'top_buttons' => [
                ['widget_class' => 'SubPanelAddToProspectListButton', 'create' => true],
            ]
        ],
        'contact' => [
            'order' => 160,
            'module' => 'CampaignLog',
            'hidden' => true,
            'get_subpanel_data' => "function:trackLogEntries",
            'function_parameters' => [
                0 => 'contact',
            ],
            'subpanel_name' => 'default',
            'title_key' => 'LBL_LOG_ENTRIES_CONTACT_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'link' => [
            'order' => 170,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'function_parameters' => [
                0 => 'link',
                'params' => [
                    'selectFields' => [
                        'campaign_trkrs.tracker_url',
                    ],
                    'join' => 'INNER JOIN campaign_trkrs ON campaign_log.related_id = campaign_trkrs.id'
                ]
            ],
            'subpanel_name' => 'ForClickThru',
            'title_key' => 'LBL_LOG_ENTRIES_LINK_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'removed' => [
            'order' => 180,
            'module' => 'CampaignLog',
            'get_subpanel_data' => "function:trackLogEntries",
            'function_parameters' => [
                0 => 'removed',
            ],
            'subpanel_name' => 'default',
            'title_key' => 'LBL_LOG_ENTRIES_REMOVED_TITLE',
            'sort_order' => 'desc',
            'sort_by' => 'campaign_log.id'
        ],
        'prospectlists' => [
            'order' => 10,
            'sort_order' => 'asc',
            'sort_by' => 'name',
            'hidden' => true,
            'module' => 'ProspectLists',
            'get_subpanel_data' => 'prospectlists',
            'set_subpanel_data' => 'prospectlists',
            'subpanel_name' => 'default',
            'title_key' => 'LBL_PROSPECT_LIST_SUBPANEL_TITLE',
            'top_buttons' => [],
        ],
        'allprospectlists' => [
            'order' => 20,
            'hidden' => true,
            'module' => 'ProspectLists',
            'sort_order' => 'asc',
            'sort_by' => 'name',
            'get_subpanel_data' => 'function:get_all_prospect_lists',
            'set_subpanel_data' => 'prospectlists',
            'subpanel_name' => 'default',
            'title_key' => 'LBL_PROSPECT_LIST_SUBPANEL_TITLE',
            'top_buttons' => [],
        ],
    ]
];
