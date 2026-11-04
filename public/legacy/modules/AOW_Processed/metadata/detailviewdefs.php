<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2011 - 2025 SuiteCRM Ltd.
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

/*
 * A processed record of a workflow (process audit): the workflow, the record it ran on, the status
 * and the dates (the module had only the name, which the workflows do not set). Read-only in the
 * SuiteCRM 8 record view: the workflows write it; deleting it lets a workflow without repeated runs
 * run again on the record.
 */
$viewdefs['AOW_Processed']['DetailView'] = [
    // Title of the record: the workflow and the record it ran on (the module has no name).
    'summaryTemplates' => [
        'edit' => 'LBL_SUMMARY_WORKFLOW_PROCESSED',
        'detail' => 'LBL_SUMMARY_WORKFLOW_PROCESSED',
    ],
    'templateMeta' => [
        'form' => [
            'buttons' => ['DELETE'],
        ],
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30'],
        ],
    ],
    'recordActions' => [
        'exclude' => ['edit', 'duplicate'],
    ],
    'panels' => [
        'default' => [
            [['name' => 'aow_workflow', 'readonly' => true], ['name' => 'status', 'readonly' => true]],
            // The record it ran on (its label is its module).
            [['name' => 'parent_name', 'readonly' => true]],
            [['name' => 'date_entered', 'readonly' => true], ['name' => 'date_modified', 'readonly' => true]],
        ],
    ],
];
