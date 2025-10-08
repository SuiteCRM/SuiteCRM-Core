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

$viewdefs['EmailMan'] = [
    'ListView' => [
        'tableActions' => [
            'actions' => [
                'send-batched-campaign-emails' => [
                    'key' => 'send-batched-campaign-emails',
                    'labelKey' => 'LBL_CAMPAIGNS_SEND_NEXT_BATCH',
                    'modes' => ['list'],
                    'acl' => ['list'],
                    'asyncProcess' => true,
                    'params' => [
                        'expanded' => true,
                        'displayConfirmation' => true,
                        'confirmationLabel' => 'NTC_SEND_QUEUED_CAMPAIGN_EMAILS'
                    ]
                ],
            ]
        ]
    ]
];

$listViewDefs['EmailMan'] = [
    'CAMPAIGN_NAME' => [
        'width' => '10',
        'label' => 'LBL_LIST_CAMPAIGN',
        'link' => true,
        'id' => 'campaign_id',
        'module' => 'Campaigns',
        'ACLTag' => 'CAMPAIGNS',
        'default' => true,
        'related_fields' => array('campaign_id')
    ],
    'RECIPIENT_NAME' => [
        'sortable' => false,
        'width' => '10',
        'label' => 'LBL_LIST_RECIPIENT_NAME',
        'default' => true
    ],
    'RECIPIENT_EMAIL' => [
        'sortable' => false,
        'width' => '10',
        'label' => 'LBL_LIST_RECIPIENT_EMAIL',
        'default' => true
    ],
    'MESSAGE_NAME' => [
        'sortable' => false,
        'width' => '10',
        'label' => 'LBL_LIST_MESSAGE_NAME',
        'default' => true,
        'id' => 'marketing_id',
        'module' => 'EmailMarketing',
        'ACLTag' => 'EMAILMARKETING',
        'related_fields' => array('marketing_id')
    ],
    'SEND_DATE_TIME' => [
        'width' => '10',
        'label' => 'LBL_LIST_SEND_DATE_TIME',
        'default' => true
    ],
    'SEND_ATTEMPTS' => [
        'width' => '10',
        'label' => 'LBL_LIST_SEND_ATTEMPTS',
        'default' => true
    ],
    'IN_QUEUE' => [
        'width' => '10',
        'label' => 'LBL_LIST_IN_QUEUE',
        'default' => true
    ],
    'DATE_ENTERED' => [
        'width' => '10',
        'label' => 'LBL_DATE_ENTERED',
        'default' => false
    ],
    'DATE_MODIFIED' => [
        'width' => '10',
        'label' => 'LBL_DATE_MODIFIED',
        'default' => false
    ],
    'MODIFIED_USER_ID' => [
        'width' => '10',
        'label' => 'LBL_MODIFIED_USER',
        'default' => false
    ],
];
