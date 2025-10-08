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


namespace App\Process\Service;

use App\Engine\Service\ActionAvailabilityChecker\ActionAvailabilityChecker;

class SubpanelLineActionDefinitionProvider extends ActionDefinitionProvider implements SubpanelLineActionDefinitionProviderInterface
{
    /**
     * @var array
     */
    private $subpanelLineActions;

    /**
     * SubpanelLineActionDefinitionProvider constructor.
     * @param array $subpanelLineActions
     * @param ActionAvailabilityChecker $actionChecker
     */
    public function __construct(array $subpanelLineActions, ActionAvailabilityChecker $actionChecker)
    {
        parent::__construct($actionChecker);
        $this->subpanelLineActions = $subpanelLineActions;
    }

    /**
     * @inheritDoc
     */
    public function getActions(string $module): array
    {
        return $this->filterActions($module, $this->subpanelLineActions);
    }

    /**
     * Checks if the action is defined on base actions
     * @param string $action
     * @return bool
     */
    public function isActionDefined(string $action): bool
    {
        $config = $this->subpanelLineActions;
        $defaults = $config['default'] ?? [];
        $defaultActions = $defaults['actions'] ?? [];

        return array_key_exists($action, $defaultActions);
    }

    /**
     * Get list of modules the user has access to
     * @param string $module
     * @param string $actionKey
     * @return bool
     */
    public function isActionAvailable(string $module, string $actionKey): bool
    {
        $actions = $this->getActions($module);

        return !(empty($actions) || !array_key_exists($actionKey, $actions));
    }

    /**
     * Get list of modules the user has access to
     * @param string $module
     * @param string $actionKey
     * @return bool
     */
    public function isActionAccessible(string $module, string $actionKey): bool
    {
        return $this->isActionDefined($actionKey)
            && $this->isActionAvailable($module, $actionKey);
    }

    /**
     * Get list of modules the user has access to
     * @param string $module
     * @param string $actionKey
     * @return array
     */
    public function getLineAction(string $module, string $actionKey): array
    {
        if (!$this->isActionAccessible($module, $actionKey)) {
            return [];
        }

        $actions = $this->getActions($module);

        return array_merge(
            $actions[$actionKey],
            [
                'module' => $module
            ]
        );
    }
}

