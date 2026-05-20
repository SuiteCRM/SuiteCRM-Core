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


namespace App\Engine\Service;

use SugarBean;

interface AclManagerInterface
{

    /**
     * Check if current user has access to give module and action
     *
     * @param string $module
     * @param string $action
     * @param bool $isOwner
     * @param string $type
     * @param bool $in_group
     * @return bool
     */
    public function checkAccess(
        string $module,
        string $action,
        bool $isOwner = false,
        $type = 'module',
        $in_group = false
    ): bool;

    /**
     * Check record access
     *
     * @param string $module
     * @param string $action
     * @param string $record
     * @return bool
     */
    public function checkRecordAccess(string $module, string $action, string $record): bool;

    /**
     * Get list of record acls
     *
     * @param SugarBean $bean
     * @return array
     */
    public function getRecordAcls(SugarBean $bean): array;
}
