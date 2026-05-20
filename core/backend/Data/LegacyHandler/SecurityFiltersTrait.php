<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Data\LegacyHandler;

use ACLController;
use DBManagerFactory;
use SecurityGroup;
use SugarBean;

trait SecurityFiltersTrait
{


    /**
     * @param SugarBean $bean
     * @param string $where
     * @param string|null $table
     * @return string
     */
    public function addSecurityWhereClause(SugarBean $bean, string $where, string $table = null): string
    {
        global $current_user, $sugar_config;

        $filterUserList = $sugar_config['securitysuite_filter_user_list'] ?? false;

        $tableName = $table ?? $bean->table_name;

        if ($filterUserList && $bean->module_dir === 'Users' && !is_admin($current_user)) {
            /* @noinspection PhpIncludeInspection */
            require_once 'modules/SecurityGroups/SecurityGroup.php';

            $group_where = SecurityGroup::getGroupUsersWhere($current_user->id);
            if (empty($where)) {
                $where = " (" . $group_where . ") ";
            } else {
                $where .= " AND (" . $group_where . ") ";
            }

            return $where;
        }

        if ($bean->bean_implements('ACL') && ACLController::requireSecurityGroup($bean->module_dir, 'list')) {
            /* @noinspection PhpIncludeInspection */
            require_once 'modules/SecurityGroups/SecurityGroup.php';

            $owner_where = $this->getOwnerWhere($tableName, $current_user->id);
            $group_where = SecurityGroup::getGroupWhere($tableName, $bean->module_dir, $current_user->id);

            if (!empty($owner_where)) {
                $condition = " (" . $owner_where . " or " . $group_where . ") ";
            } else {
                $condition = $group_where;
            }

            if (empty($where)) {
                $where = $condition;
            } else {
                $where .= ' AND ' . $condition;
            }
        }

        return $where;
    }

    /**
     * Gets there where statement for checking if a user is an owner
     *
     * @param string $table
     * @param string $user_id GUID
     * @return string
     */
    public function getOwnerWhere(string $table, string $user_id): string
    {
        $db = DBManagerFactory::getInstance();
        if (isset($this->field_defs['assigned_user_id'])) {
            return " $table.assigned_user_id ='" . $db->quote($user_id) . "' ";
        }
        if (isset($this->field_defs['created_by'])) {
            return " $table.created_by ='" . $db->quote($user_id) . "' ";
        }

        return '';
    }
}
