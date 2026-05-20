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

namespace App\Engine\Service\ActionAvailabilityChecker;

use ApiPlatform\Core\Exception\ItemNotFoundException;

class ActionAvailabilityChecker
{
    protected const MSG_HANDLER_NOT_FOUND = 'Action availability checker is not defined';

    /**
     * @var ActionAvailabilityCheckerInterface[]
     */
    protected $registry = [];

    /**
     * ActionAvailabilityChecker constructor.
     * @param iterable $handlers
     */
    public function __construct(iterable $handlers)
    {
        /**
         * @var ActionAvailabilityCheckerInterface[]
         */
        foreach ($handlers as $handler) {
            $type = $handler->getType();
            $this->registry[$type] = $handler;
        }
    }

    /**
     * Get the checker for action key
     * @param string $type
     * @return ActionAvailabilityCheckerInterface
     * @throws ItemNotFoundException
     */
    public function get(string $type): ActionAvailabilityCheckerInterface
    {

        if (empty($this->registry[$type])) {
            throw new ItemNotFoundException(self::MSG_HANDLER_NOT_FOUND);
        }

        return $this->registry[$type];
    }

    /**
     * check if the action key exists in the registry
     * @param string $type
     * @return bool
     */
    public function hasChecker(string $type): bool
    {
        return !(empty($this->registry[$type]));
    }

    /**
     * check the availability status of the action key, if found in registry
     * @param string $module
     * @param array $entry
     * @param string $type
     * action key/type to check the availability for defined inside a configuration file e.g. actions.yaml in the format
     * - availability
     *  - audited
     *  - acls
     * @param array|null $context
     * @return bool
     */
    public function checkAvailability(string $module, array $entry, string $type, ?array $context = []): bool
    {
        if ($this->hasChecker($type)) {
            return $this->get($type)->checkAvailability($module, $entry, $context);
        }

        return true;
    }

}
