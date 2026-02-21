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

$mod_strings = [

    'LBL_ASSIGNED_TO_ID' => 'Assigned User Id',
    'LBL_ASSIGNED_TO_NAME' => 'Assigned to',
    'LBL_ID' => 'ID',
    'LBL_DATE_ENTERED' => 'Date Created',
    'LBL_DATE_MODIFIED' => 'Date Modified',
    'LBL_MODIFIED' => 'Modified By',
    'LBL_MODIFIED_NAME' => 'Modified By Name',
    'LBL_CREATED' => 'Created By',
    'LBL_DESCRIPTION' => 'Description',
    'LBL_DELETED' => 'Deleted',
    'LBL_NAME' => 'Name',
    'LBL_CREATED_USER' => 'Created by User',
    'LBL_MODIFIED_USER' => 'Modified by User',
    'LBL_LIST_NAME' => 'Name',
    'LBL_EDIT_BUTTON' => 'Edit',
    'LBL_REMOVE' => 'Remove',
    'LBL_LIST_FORM_TITLE' => 'Migrations List',
    'LBL_MODULE_NAME' => 'Migrations',
    'LBL_MODULE_TITLE' => 'Migrations',
    'LBL_HOMEPAGE_TITLE' => 'My Migrations',
    'LNK_NEW_RECORD' => 'Create Migrations',

    'LNK_LIST' => 'View Migrations',
    'LBL_SEARCH_FORM_TITLE' => 'Search Migrations',
    'LBL_HISTORY_SUBPANEL_TITLE' => 'View History',
    'LBL_ACTIVITIES_SUBPANEL_TITLE' => 'Activities',
    'LBL_NEW_FORM_TITLE' => 'New Migrations',

    'LBL_LIST_DELETE' => 'Delete',
    'LBL_TYPE' => 'Type',
    'LBL_ESTIMATED_RUN_TIME' => 'Run time estimate',
    'LBL_STATUS' => 'Status',
    'LBL_SERVICE_KEY' => 'Service Key',
    'LBL_LAST_RUN_DATETIME' => 'Last Run',
    'LBL_ATTACHMENTS' => 'Attachments',

    'LBL_PHASE' => 'Phase',
    'LBL_OWNER' => 'Owner',

    'LBL_RUN_MIGRATION' => 'Run Migration',
    'LBL_RUN_MIGRATION_CONFIRMATION' => 'Are you sure you want to run this migration?',

    'LBL_RETRY' => 'Retry',
    'LBL_RETRY_CONFIRMATION' => 'Are you sure you want to retry? This will re-queue all failed items for processing.',
    'LBL_RETRY_SUCCESS' => 'Failed items have been re-queued for processing.',
    'LBL_RETRY_FAILED' => 'Retry Failed',
    'LBL_RETRY_FAILED_CONFIRMATION' => 'Are you sure you want to retry? This will re-queue all failed items for processing.',
    'LBL_RETRY_FAILED_SUCCESS' => 'Failed items have been re-queued for processing.',
    'LBL_RERUN' => 'Re-run',
    'LBL_RERUN_CONFIRMATION' => 'Are you sure you want to re-run this migration from scratch? All existing items will be removed and the migration will restart.',
    'LBL_RERUN_SUCCESS' => 'Migration has been re-queued and will restart from the beginning.',
    'LBL_ALLOW_FAILURE_RETRY_ACTION' => 'Allow Failure Retry Action',
    'LBL_ALLOW_FAILURE_RERUN_ACTION' => 'Allow Failure Rerun Action',
    'LBL_COMPLETED_WITH_FAILURES' => 'Completed With Failures',
    'LBL_DISMISS' => 'Dismiss',
    'LBL_DISMISS_CONFIRMATION' => 'Are you sure you want to dismiss this migration task? This will remove it and all associated data.',
    'LBL_DISMISS_SUCCESS' => 'Migration task dismissed successfully.',

    'LBL_PROGRESS' => 'Progress',
    'LBL_PROGRESS_PERCENT' => 'Percent',
    'LBL_PROGRESS_COMPLETED' => 'Completed',
    'LBL_PROGRESS_FAILED' => 'Failed',
    'LBL_PROGRESS_PERCENT_TPL' => '{{fields.progress.attributes.percent|default:-}}% ({{fields.progress.attributes.completed|default:-}} completed, {{fields.progress.attributes.failed|default:-}} failed)',

    'LBL_FAILED_ITEMS' => 'Failed Items',
];
