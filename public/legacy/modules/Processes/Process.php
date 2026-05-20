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

require_once 'include/SugarObjects/templates/asynctask/AsyncTask.php';

#[\AllowDynamicProperties]
class Process extends AsyncTask
{
    public $disable_row_level_security = true;
    public $new_schema = true;
    public $module_dir = 'Processes';
    public $object_name = 'Process';
    public $table_name = 'processes';
    public $importable = false;

    public $id;
    public $name;
    public $type;
    public $estimated_run_time;
    public $status;
    public $service_key;
    public $last_run_datetime;
    public $assigned_user_id;
    public $assigned_user_name;
    public $assigned_user_link;
    public $SecurityGroups;

    /**
     * @inheritDoc
     */
    public function create_new_list_query(
        $order_by,
        $where,
        $filter = array(),
        $params = array(),
        $show_deleted = 0,
        $join_type = '',
        $return_array = false,
        $parentbean = null,
        $singleSelect = false,
        $ifListForExport = false
    ) {
        global $current_user, $db;

        $ret_array = parent::create_new_list_query(
            $order_by,
            $where,
            $filter,
            $params,
            $show_deleted,
            $join_type,
            true,
            $parentbean,
            $singleSelect,
            $ifListForExport
        );

        if (is_admin($current_user)) {
            if ($return_array) {
                return $ret_array;
            }

            return $ret_array['select'] . $ret_array['from'] . $ret_array['where'] . $ret_array['order_by'];
        }

        if (is_array($ret_array) && !empty($ret_array['where'])) {
            $tableName = $db->quote($this->table_name);
            $currentUserId = $db->quote($current_user->id);

            $ret_array['where'] = $ret_array['where'] . " AND ($tableName.assigned_user_id = '$currentUserId')";
        }

        if ($return_array) {
            return $ret_array;
        }

        return $ret_array['select'] . $ret_array['from'] . $ret_array['where'] . $ret_array['order_by'];
    }

    /**
     * Admins and the assigned user may access a process record.
     * Unassigned records are accessible to all authenticated users.
     */
    public function hasAccess(): bool
    {
        global $current_user;

        if (is_admin($current_user)) {
            return true;
        }

        if (empty($this->assigned_user_id)) {
            return true;
        }

        if ($this->assigned_user_id === $current_user->id) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function logAccessDenied(string $action): void
    {
        global $log, $current_user;

        $log->fatal("Processes | Access denied. Action: '" . $action . "' | Current user id: '" . $current_user->id . "' | record: '" . $this->id . "'");
    }
}
