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
 * Columns of the campaign trackers list: those of the Tracker URLs subpanel of the campaigns and
 * the campaign.
 */
$listViewDefs['CampaignTrackers'] = [
    'TRACKER_NAME' => [
        'width' => '20%',
        'label' => 'LBL_TRACKER_NAME',
        'link' => true,
        'default' => true,
    ],
    'TRACKER_URL' => [
        'width' => '40%',
        'label' => 'LBL_TRACKER_URL',
        'default' => true,
    ],
    'TRACKER_KEY' => [
        'width' => '10%',
        'label' => 'LBL_TRACKER_KEY',
        'default' => true,
    ],
    'CAMPAIGN_NAME' => [
        'width' => '20%',
        'label' => 'LBL_CAMPAIGN',
        'module' => 'Campaigns',
        'id' => 'CAMPAIGN_ID',
        'link' => true,
        'related_fields' => ['campaign_id'],
        'default' => true,
    ],
    'IS_OPTOUT' => [
        'width' => '10%',
        'label' => 'LBL_OPTOUT',
        'default' => true,
    ],
];
