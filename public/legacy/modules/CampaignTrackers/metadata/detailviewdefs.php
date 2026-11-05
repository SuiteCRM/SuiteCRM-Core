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

/*
 * Layout of the campaign trackers in the record, create and edit views: the fields of the legacy
 * detail and edit pages (DetailView.html, EditView.html, which do not use this file). The tracker
 * key and the URL for the campaign message are set by the save and the bean.
 */
$viewdefs['CampaignTrackers']['DetailView'] = [
    // Title of the record: the tracker name (the module has no name field).
    'summaryTemplates' => [
        'edit' => 'LBL_SUMMARY_CAMPAIGN_TRACKER',
        'detail' => 'LBL_SUMMARY_CAMPAIGN_TRACKER',
    ],
    'templateMeta' => [
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30'],
        ],
    ],
    'panels' => [
        'default' => [
            ['tracker_name', 'campaign_name'],
            ['tracker_url', 'is_optout'],
            [['name' => 'tracker_key', 'readonly' => true], ['name' => 'message_url', 'readonly' => true]],
            [['name' => 'date_entered', 'readonly' => true], ['name' => 'date_modified', 'readonly' => true]],
        ],
    ],
];
