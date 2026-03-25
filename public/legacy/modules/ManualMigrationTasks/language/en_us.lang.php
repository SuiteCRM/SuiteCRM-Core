<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
 * along with this program.  If not, see http://www.gnu.org/licenses.
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

/**
 * Module-specific labels for ManualMigrationTasks.
 * Shared async task labels are inherited from the asynctask template language file.
 */
$mod_strings = [

    // Module naming
    'LBL_LIST_FORM_TITLE' => 'Migrations List',
    'LBL_MODULE_NAME' => 'Migrations',
    'LBL_MODULE_TITLE' => 'Migrations',
    'LBL_HOMEPAGE_TITLE' => 'My Migrations',
    'LNK_NEW_RECORD' => 'Create Migrations',
    'LNK_LIST' => 'View Migrations',
    'LBL_SEARCH_FORM_TITLE' => 'Search Migrations',
    'LBL_NEW_FORM_TITLE' => 'New Migrations',

    // Module-specific trigger action
    'LBL_RUN_MIGRATION' => 'Run Migration',
    'LBL_RUN_MIGRATION_CONFIRMATION' => 'Are you sure you want to run this migration?',

    // Rerun/dismiss confirmation messages (reference this module by name)
    'LBL_RERUN_CONFIRMATION' => 'Are you sure you want to re-run this migration from scratch? All existing items will be removed and the migration will restart.',
    'LBL_RERUN_SUCCESS' => 'Migration has been re-queued and will restart from the beginning.',
    'LBL_DISMISS_CONFIRMATION' => 'Are you sure you want to dismiss this migration task? This will remove it and all associated data.',
    'LBL_DISMISS_SUCCESS' => 'Migration task dismissed successfully.',

    // Type footnotes
    'LBL_TYPE_BACKGROUND_HELP' => 'This migration runs in the background via the message queue worker. It processes items in batches and does not block the UI.',
    'LBL_TYPE_IMMEDIATE_HELP' => 'This migration runs immediately when triggered. It processes all items in a single request and may take longer for large datasets.',
];
