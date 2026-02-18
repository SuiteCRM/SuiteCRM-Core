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

#[\AllowDynamicProperties]
class Process extends Basic
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
    public function retrieve($id = -1, $encode = true, $deleted = true)
    {
        $result = parent::retrieve($id, $encode, $deleted);

        if (!empty($result) && !$this->hasAccess()) {
            $this->logAccessDenied('retrieve');

            return null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save($check_notify = false)
    {
        if (!$this->hasAccess()) {
            $this->logAccessDenied('save');
            throw new RuntimeException('Access Denied');
        }

        $this->keepWriteOnlyFieldValues();


        return parent::save($check_notify);
    }

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
            $params ,
            $show_deleted,
            $join_type,
            true,
            $parentbean,
            $singleSelect,
            $ifListForExport
        );

        if(is_admin($current_user)) {
            if ($return_array) {
                return $ret_array;
            }

            return $ret_array['select'] . $ret_array['from'] . $ret_array['where'] . $ret_array['order_by'];
        }

        if (is_array($ret_array) && !empty($ret_array['where'])){
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
     * Check if user has access to personal account
     * @return bool
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
     * Log personal account access denied
     * @param string $action
     * @return void
     */
    public function logAccessDenied(string $action): void
    {
        global $log, $current_user;

        $log->fatal("Processes | Access denied. Action: '" . $action . "' | Current user id: '" . $current_user->id . "' | record: '" . $this->id . "'");
    }

    /**
     * @inheritDoc
     */
    public function bean_implements($interface)
    {
        if ($interface === 'ACL') {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function ACLAccess($view, $is_owner = 'not_set', $in_group = 'not_set')
    {
        global $current_user;

        $isNotAllowAction = $this->isNotAllowedAction($view);
        if ($isNotAllowAction === true) {
            return false;
        }

        if (!$this->hasAccess()) {
            $this->logAccessDenied("ACLAccess-$view");

            return false;
        }

        return parent::ACLAccess($view, $is_owner, $in_group);
    }


    /**
     * Do not clear write only fields
     * @return void
     */
    protected function keepWriteOnlyFieldValues(): void
    {
        if (empty($this->fetched_row)) {
            return;
        }

        foreach ($this->field_defs as $field => $field_def) {
            if (empty($field_def['display']) || $field_def['display'] !== 'writeonly') {
                continue;
            }

            if (empty($this->fetched_row[$field])) {
                continue;
            }

            if (!empty($this->$field)) {
                continue;
            }

            $this->$field = $this->fetched_row[$field];
        }
    }


    /**
     * Get failed async task items query for subpanel
     * @return array
     */
    public function getFailedAsyncTaskItems(): array
    {
        $idQuoted = $this->db->quoted($this->id);

        return [
            'select' => '',
            'from' => '',
            'where' => "async_task_items.async_task_id = $idQuoted AND async_task_items.status = 'failed' ",
            'join' => '',
            'join_tables' => [],
        ];
    }

    /**
     * Get not allowed action
     * @param string $view
     * @return bool
     */
    protected function isNotAllowedAction(string $view): bool
    {
        $notAllowed = [
            'export',
            'import',
            'massupdate',
            'duplicate',
            'edit',
            'editview',
            'delete',
            'create',
            'save'
        ];

        return in_array(strtolower($view), $notAllowed);
    }
}
