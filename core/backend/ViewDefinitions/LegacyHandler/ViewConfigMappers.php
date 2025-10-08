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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */


namespace App\ViewDefinitions\LegacyHandler;

use Traversable;

class ViewConfigMappers
{
    /**
     * @var ViewConfigMapperInterface[][]
     */
    protected $registry = [];

    /**
     * ViewConfigMappers constructor.
     * @param Traversable $handlers
     */
    public function __construct(Traversable $handlers)
    {
        /**
         * @var $handlers ViewConfigMapperInterface[]
         */

        foreach ($handlers as $handler) {
            $type = $handler->getKey();
            $module = $handler->getModule();
            $view = $handler->getView();
            $viewLevelMappers = $this->registry[$view] ?? [];
            $moduleLevelMappers = $viewLevelMappers[$module] ?? [];
            $moduleLevelMappers[$type] = $handler;
            $viewLevelMappers[$module] = $moduleLevelMappers;
            $this->registry[$view] = $viewLevelMappers;
        }

    }

    /**
     * Get the mappers for the module key and view
     * @param string $module
     * @param string $view
     * @return ViewConfigMapperInterface[]
     */
    public function get(string $module, string $view): array
    {
        $viewLevelMappers = $this->registry[$view] ?? [];
        $defaultDefinitions = $viewLevelMappers['default'] ?? [];
        $moduleDefinitions = $viewLevelMappers[$module] ?? [];

        return array_merge($defaultDefinitions, $moduleDefinitions);
    }

    /**
     * @param string $module
     * @param string $view
     * @param array $viewDefs
     * @return array
     */
    public function run(string $module, string $view, array $viewDefs): array
    {
        $mappers = $this->get($module, $view) ?? [];

        foreach ($mappers as $mapper) {
            $viewDefs = $mapper->map($viewDefs);
        }

        return $viewDefs;
    }
}
