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

$viewdefs['Users']['DetailView'] = [
    'templateMeta' => [
        'form' => [
            /**
             * Actions for users are configured in modules/Users/views/view.detail.php
             * This is to control security access to the actions based on the user and system preferences.
             * To customise in an upgrade safe way, You need to create custom view instead.
             * Then override UsersViewDetail::preDisplay().
             */
            'buttons' => [],
        ],
        'maxColumns' => '2',
        'widths' => [
            0 => [
                'label' => '10',
                'field' => '30',
            ],
            1 => [
                'label' => '10',
                'field' => '30',
            ],
        ],
        'useTabs' => true,
        // The tabs of the legacy user profile: User Profile, Advanced (User Settings) and Password.
        'tabDefs' => [
            'LBL_USER_INFORMATION' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
            'LBL_EMPLOYEE_INFORMATION' => [
                'newTab' => false,
                'panelDefault' => 'collapsed',
            ],
            'LBL_USER_SETTINGS' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
            'LBL_USER_LOCALE' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_CALENDAR_OPTIONS' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_LAYOUT_OPTIONS' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_EMAIL_OPTIONS' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_CHANGE_PASSWORD_TITLE' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
                'displayLogic' => [
                    // The password of an existing user is changed with the "Change Password" action.
                    'hideForExistingUser' => [
                        'key' => 'displayType',
                        'modes' => ['detail', 'edit'],
                        'params' => [
                            'fieldDependencies' => ['new_password'],
                            'activeOnFields' => ['new_password' => ['']],
                        ],
                    ],
                    // Users logging in with an external authentication have no password here.
                    'hideForExternalAuthentication' => [
                        'key' => 'displayType',
                        'modes' => ['detail', 'edit', 'create'],
                        'params' => [
                            'fieldDependencies' => ['external_auth_only'],
                            'activeOnFields' => ['external_auth_only' => ['true']],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'recordActions' => [
        'actions' => [
            'user-change-password' => [
                'key' => 'user-change-password',
                'labelKey' => 'LBL_USER_CHANGE_PASSWORD',
                'priority' => 100,
                'asyncProcess' => true,
                'modes' => ['detail'],
                'acl' => ['edit'],
                'displayLogic' => [
                    'hideForExternalAuthentication' => [
                        'modes' => ['detail'],
                        'params' => [
                            'activeOnFields' => ['external_auth_only' => ['true']],
                        ],
                    ],
                ],
                'params' => [
                    'fieldModal' => [
                        // The modal shows application labels.
                        'titleKey' => 'LBL_USER_CHANGE_PASSWORD',
                        'actionLabelKey' => 'LBL_USER_CHANGE_PASSWORD',
                        'module' => 'users',
                        'centered' => true,
                        'fieldGridOptions' => ['maxColumns' => 1],
                        'fields' => [
                            'old_password' => [
                                'name' => 'old_password',
                                'type' => 'password',
                                'label' => 'LBL_OLD_PASSWORD',
                            ],
                            'new_password' => [
                                'name' => 'new_password',
                                'type' => 'password',
                                'label' => 'LBL_NEW_PASSWORD',
                                'fieldDefinition' => ['required' => true],
                            ],
                            'confirm_new_password' => [
                                'name' => 'confirm_new_password',
                                'type' => 'password',
                                'label' => 'LBL_CONFIRM_PASSWORD',
                                'fieldDefinition' => ['required' => true],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'panels' => [
        'LBL_USER_INFORMATION' => [
            0 => [
                0 => 'user_name',
                1 => 'first_name',
            ],
            1 => [
                0 => 'status',
                1 => 'last_name',
            ],
            2 => [
                0 => 'UserType',
                1 => 'photo',
            ],
            3 => [
                0 => 'factor_auth',
                1 => 'email1',
            ],
        ],
        'LBL_EMPLOYEE_INFORMATION' => [
            0 => [
                0 => 'employee_status',
                1 => 'show_on_employees',
            ],
            1 => [
                0 => 'title',
                1 => 'phone_work',
            ],
            2 => [
                0 => 'department',
                1 => 'phone_mobile',
            ],
            3 => [
                0 => 'reports_to_name',
                1 => 'phone_other',
            ],
            4 => [
                0 => 'phone_fax',
                1 => 'phone_home',
            ],
            5 => [
                0 => 'messenger_type',
                1 => 'messenger_id',
            ],
            6 => [
                0 => 'address_street',
                1 => 'address_city',
            ],
            7 => [
                0 => 'address_state',
                1 => 'address_postalcode',
            ],
            8 => [
                0 => 'address_country',
            ],
            9 => [
                0 => 'description',
            ],
        ],
        'LBL_USER_SETTINGS' => [
            0 => [
                0 => 'receive_notifications',
                1 => 'external_auth_only',
            ],
            1 => [
                0 => 'pref_reminder_checked',
                1 => 'pref_reminder_time',
            ],
            2 => [
                0 => 'pref_email_reminder_checked',
                1 => 'pref_email_reminder_time',
            ],
            3 => [
                0 => 'pref_snooze_alert_timer',
                1 => 'pref_mailmerge_on',
            ],
            4 => [
                0 => 'pref_export_delimiter',
                1 => 'pref_default_export_charset',
            ],
            5 => [
                0 => 'pref_use_real_names',
            ],
        ],
        'LBL_USER_LOCALE' => [
            0 => [
                0 => 'pref_datef',
                1 => 'pref_timef',
            ],
            1 => [
                0 => 'pref_timezone',
                1 => 'pref_language',
            ],
            2 => [
                0 => 'pref_currency',
                1 => 'pref_default_currency_significant_digits',
            ],
            3 => [
                0 => 'pref_num_grp_sep',
                1 => 'pref_dec_sep',
            ],
            4 => [
                0 => 'pref_default_locale_name_format',
            ],
        ],
        'LBL_CALENDAR_OPTIONS' => [
            0 => [
                0 => 'pref_calendar_publish_key',
                1 => 'pref_fdow',
            ],
        ],
        'LBL_LAYOUT_OPTIONS' => [
            0 => [
                0 => 'pref_use_group_tabs',
                1 => 'pref_sort_modules_by_name',
            ],
            1 => [
                0 => 'pref_subpanel_tabs',
                1 => 'pref_count_collapsed_subpanels',
            ],
            2 => [
                0 => 'pref_listview_pagination_type',
                1 => 'pref_subpanel_pagination_type',
            ],
            3 => [
                0 => 'pref_record_modal_pagination_type',
            ],
        ],
        'LBL_EMAIL_OPTIONS' => [
            0 => [
                0 => 'pref_email_link_type',
                1 => 'pref_editor_type',
            ],
        ],
        'LBL_CHANGE_PASSWORD_TITLE' => [
            0 => [
                0 => 'old_password',
            ],
            1 => [
                0 => 'new_password',
                1 => 'confirm_new_password',
            ],
        ],
    ],
];
