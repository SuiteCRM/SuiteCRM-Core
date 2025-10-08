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

namespace App\Engine\Service\ActionAvailabilityChecker\Checkers;

use App\Engine\Service\AclManagerInterface;
use App\Engine\Service\ActionAvailabilityChecker\ActionAvailabilityCheckerInterface;

class ACLActionChecker implements ActionAvailabilityCheckerInterface
{
    /**
     * @var AclManagerInterface
     */
    protected $aclManager;

    /**
     * ACLActionChecker constructor.
     * @param AclManagerInterface $aclManager
     */
    public function __construct(AclManagerInterface $aclManager)
    {
        $this->aclManager = $aclManager;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'acls';
    }

    /**
     * Check availability status of the action/function to the user
     *
     * @param string $module
     * @param array|null $entry
     * @param array|null $context
     * @return bool
     */
    public function checkAvailability(string $module, ?array $entry = [], ?array $context = []): bool
    {
        return $this->checkAccess($module, $entry['acl'] ?? [], $context);
    }

    /**
     * Check accessibility status of the action/function to the user
     *
     * @param string $module - module to be queried
     * @param array $aclList - list of the actions against which the accessibility to be checked e.g. delete, edit
     * @param array|null $context
     * @return bool
     */
    public function checkAccess(string $module, array $aclList, ?array $context = []): bool
    {
        if (empty($aclList)) {
            return true;
        }

        $record = $context['record'] ?? '';

        if ($record !== '') {
            foreach ($aclList as $acl) {
                if ($this->aclManager->checkRecordAccess($module, $acl, $record) === false) {
                    return false;
                }
            }

            return true;
        }

        foreach ($aclList as $acl) {

            if ($this->aclManager->checkAccess($module, $acl, true, 'module', true) === false) {
                return false;
            }
        }

        return true;
    }

}
