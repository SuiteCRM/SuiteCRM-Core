<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
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

$subpanel_layout = array(
    'top_buttons' => array(
        array('widget_class' => 'SubPanelAddToProspectListButton','create'=>'true'),
    ),

    'where' => '',


    'list_fields' => array(
        'related_name' => array(
            'vname' => 'LBL_LIST_RECIPIENT_NAME',
            'width' => '20%',
            'sortable'=>false,
        ),
        'recipient_email'=>array(
            'vname' => 'LBL_LIST_RECIPIENT_EMAIL',
            'width' => '14%',
            'sortable'=>false,
        ),
        'activity_type' => array(
            'vname' => 'LBL_ACTIVITY_TYPE',
            'width' => '14%',
        ),
        'activity_date' => array(
            'vname' => 'LBL_ACTIVITY_DATE',
            'width' => '14%',
        ),
        'target_id'=>array(
            'usage' =>'query_only',
        ),
        'target_type'=>array(
            'usage' =>'query_only',
        ),
        'related_id'=>array(
            'usage' =>'query_only',
        ),
        'related_type'=>array(
            'usage' =>'query_only',
        ),
    ),
);
