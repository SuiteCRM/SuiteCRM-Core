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
 * Columns of the campaign log list (the module had none): those of the log subpanels of the
 * campaigns and of the targets.
 */
$listViewDefs['CampaignLog'] = [
    'ACTIVITY_TYPE' => [
        'width' => '15%',
        'label' => 'LBL_ACTIVITY_TYPE',
        'link' => true,
        'default' => true,
    ],
    'ACTIVITY_DATE' => [
        'width' => '15%',
        'label' => 'LBL_ACTIVITY_DATE',
        'default' => true,
    ],
    'CAMPAIGN_NAME1' => [
        'width' => '20%',
        'label' => 'LBL_LIST_CAMPAIGN_NAME',
        'module' => 'Campaigns',
        'id' => 'CAMPAIGN_ID',
        'link' => true,
        'related_fields' => ['campaign_id'],
        'default' => true,
    ],
    'MARKETING_NAME' => [
        'width' => '15%',
        'label' => 'LBL_LIST_MARKETING_NAME',
        'default' => true,
    ],
    'RECIPIENT_NAME' => [
        'width' => '15%',
        'label' => 'LBL_LIST_RECIPIENT_NAME',
        'sortable' => false,
        'default' => true,
    ],
    'RECIPIENT_EMAIL' => [
        'width' => '15%',
        'label' => 'LBL_LIST_RECIPIENT_EMAIL',
        'sortable' => false,
        'default' => true,
    ],
    'HITS' => [
        'width' => '5%',
        'label' => 'LBL_HITS',
        'default' => true,
    ],
    'TARGET_TYPE' => [
        'width' => '10%',
        'label' => 'LBL_TARGET_TYPE',
        'default' => false,
    ],
];
