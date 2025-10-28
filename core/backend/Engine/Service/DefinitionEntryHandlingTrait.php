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

use App\Engine\Service\ActionAvailabilityChecker\ActionAvailabilityChecker;

trait DefinitionEntryHandlingTrait
{
    /**
     * @param string $module
     * @param string $entryName
     * @param array $config
     * @param ActionAvailabilityChecker|null $actionAvailabilityChecker
     * @param array|null $context
     * @return array
     */
    public function filterDefinitionEntries(
        string $module,
        string $entryName,
        array &$config,
        ?ActionAvailabilityChecker $actionAvailabilityChecker,
        ?array $context = []
    ): array {
        $defaults = $config['default'] ?? [];
        $defaultEntries = $defaults[$entryName] ?? [];
        $modulesConfig = $config['modules'] ?? [];
        $moduleEntryConfig = $modulesConfig[$module] ?? [];
        $exclude = $moduleEntryConfig['exclude'] ?? [];
        $moduleEntries = $moduleEntryConfig[$entryName] ?? [];

        $entries = $this->applyActionOverrides($defaultEntries, $moduleEntries);
        $filteredEntries = [];
        foreach ($entries as $entryKey => $entry) {
            if (in_array($entryKey, $exclude, true)) {
                continue;
            }

            $aclModule = $entry['aclModule'] ?? $module;

            if ($actionAvailabilityChecker && $this->checkAvailability($aclModule, $entry, $actionAvailabilityChecker, $context) === false) {
                continue;
            }

            $filteredEntries[$entryKey] = $entry;
        }
        return $filteredEntries;
    }

    /**
     * Check availability/accessibility status of the action/function to the user
     *
     * @param string $module - module to be queried
     * @param array $entry
     * @param ActionAvailabilityChecker $actionAvailabilityChecker
     * @param array|null $context
     * @return bool
     */
    public function checkAvailability(
        string $module,
        array $entry,
        ActionAvailabilityChecker $actionAvailabilityChecker,
        ?array $context = []
    ): bool {

        $checker = $entry['availability'] ?? null;

        if (empty($checker)) {
            $checker = ['acls'];
        }

        $availabilityCheckerKeys = $checker;

        if (empty($availabilityCheckerKeys)) {
            return true;
        }

        foreach ($availabilityCheckerKeys as $availabilityCheckerKey) {
            if ($actionAvailabilityChecker->checkAvailability($module, $entry, $availabilityCheckerKey,
                    $context) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $defaultEntries
     * @param array $moduleEntries
     * @return array
     */
    protected function applyActionOverrides(array $defaultEntries, array $moduleEntries): array
    {
        $newDefaultEntries = $defaultEntries;

        $entries = [];

        foreach ($moduleEntries as $key => $entry) {
            if (!empty($newDefaultEntries[$key])) {
                $newDefaultEntries[$key] = $entry;

                if (!isset($entry['priority'])) {
                    $newDefaultEntries[$key]['priority'] = $defaultEntries[$key]['priority'] ?? 1000;
                }

            } else {
                $entries[$key] = $entry;
            }
        }


        $unionEntries = $entries + $newDefaultEntries;


        uasort($unionEntries, function ($a, $b) {
            $priorityA = $a['priority'] ?? 1000;
            $priorityB = $b['priority'] ?? 1000;
            return $priorityA <=> $priorityB;
        });

        return $unionEntries;
    }
}
