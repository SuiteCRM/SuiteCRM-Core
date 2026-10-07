<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2022 SalesAgility Ltd.
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
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}


class CreateUser
{

    /**
     * Create external user
     *
     * @param string $name
     * @param array $userInfo
     * @return SugarBean|null
     */
    public function createExternalAuthUser(string $name, array $userInfo): ?SugarBean
    {
        if (empty($userInfo)) {
            $userInfo = [];
        }

        $userInfo['employee_status'] = 'Active';
        $userInfo['status'] = 'Active';
        $userInfo['is_admin'] = 0;
        $userInfo['external_auth_only'] = 1;
        $userInfo['user_hash'] = '';

        if (empty($userInfo['first_name'])) {
            $userInfo['first_name'] = $name;
        }

        global $current_user;
        $previousUser = $current_user;
        $current_user = BeanFactory::newBean('Users')->getSystemUser();

        try {
            return $this->create($name, $userInfo);
        } finally {
            $current_user = $previousUser;
        }
    }

    /**
     * Create user
     *
     * @param string $name
     * @param array $fields
     * @return SugarBean|null
     */
    public function create(string $name, array $fields): ?SugarBean
    {
        $user = BeanFactory::newBean('Users');
        $user->user_name = $name;

        $fields = $fields ?? [];

        foreach ($fields as $key => $value) {
            $user->$key = $value;
        }

        $user->save();

        if (empty($user)) {
            return null;
        }

        return $user;
    }

    /**
     * User Exists
     * @param string $name
     * @return bool
     */
    public function userExists(string $name): bool
    {
        /** @var User $user */
        $user = BeanFactory::newBean('Users');
        $userId = $user->retrieve_user_id($name);

        if (empty($userId)) {
            return false;
        }

        return true;
    }

}
