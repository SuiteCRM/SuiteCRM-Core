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

$module_name = 'Emails';
$viewdefs[$module_name]['DetailView'] = [
    'templateMeta' => [
        'form' => [
            'buttons' => ['DELETE'],
        ],
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30'],
        ],
        'useTabs' => false,
        'tabDefs' => [
            'LBL_EMAIL_INFORMATION' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
        ],
    ],
    // Reply, Reply All and Forward open the compose modal with the answer prepared, as the
    // legacy mail client; a draft is edited in the compose modal.
    'recordActions' => [
        'actions' => [
            'email-reply' => [
                'key' => 'email-reply',
                'labelKey' => 'LBL_BUTTON_REPLY_TITLE',
                'priority' => 100,
                'asyncProcess' => true,
                'modes' => ['detail'],
                'acl' => ['view'],
                'displayLogic' => [
                    'hideForDraft' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => ['status' => [['operator' => 'is-equal', 'values' => ['draft']]]],
                        ],
                    ],
                ],
            ],
            'email-reply-all' => [
                'key' => 'email-reply-all',
                'labelKey' => 'LBL_BUTTON_REPLY_ALL',
                'priority' => 101,
                'asyncProcess' => true,
                'modes' => ['detail'],
                'acl' => ['view'],
                'displayLogic' => [
                    'hideForDraft' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => ['status' => [['operator' => 'is-equal', 'values' => ['draft']]]],
                        ],
                    ],
                ],
            ],
            'email-forward' => [
                'key' => 'email-forward',
                'labelKey' => 'LBL_BUTTON_FORWARD',
                'priority' => 102,
                'asyncProcess' => true,
                'modes' => ['detail'],
                'acl' => ['view'],
                'displayLogic' => [
                    'hideForDraft' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => ['status' => [['operator' => 'is-equal', 'values' => ['draft']]]],
                        ],
                    ],
                ],
            ],
            'email-edit-draft' => [
                'key' => 'email-edit-draft',
                'labelKey' => 'LBL_BUTTON_EDIT_EDIT_DRAFT',
                'priority' => 100,
                'asyncProcess' => true,
                'modes' => ['detail'],
                'acl' => ['edit'],
                'displayLogic' => [
                    'hideForSentEmail' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => ['status' => [['operator' => 'not-equal', 'values' => ['draft']]]],
                        ],
                    ],
                ],
            ],
        ],
        // A sent or archived email is not edited or copied; a draft is written in the compose modal.
        'exclude' => ['edit', 'duplicate'],
    ],
    'panels' => [
        'LBL_EMAIL_INFORMATION' => [
            ['email_from', 'date_sent_received'],
            ['email_to'],
            ['email_cc', 'email_bcc'],
            ['name'],
            ['parent_name', 'status'],
            ['assigned_user_name', 'email_attachments'],
            ['email_body'],
        ],
    ],
];
