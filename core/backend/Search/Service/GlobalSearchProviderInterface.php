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

namespace App\Search\Service;

interface GlobalSearchProviderInterface
{
    /**
     * Runs the global search with the configured search engine.
     *
     * Returns:
     * - modules: the searchable modules the user may list, as
     *   ['module' => front-end name, 'label' => label, 'selected' => searched or not];
     * - counts: front-end module name => number of results;
     * - module: the module of the results page, '' for the first results of every module;
     * - ids: front-end module name => ids of the records found, in the engine's order.
     *
     * @param string $searchTerm searched text
     * @param string[]|null $modules front-end names of the modules to search in, saved as the user's
     * choice; null for the saved choice
     * @param string $module front-end name of the module to page through, '' for every module
     * @param int $offset first result of the module page
     * @param int $previewSize results per module when no module is given
     * @param int $pageSize results per page of a module
     * @return array
     */
    public function search(
        string $searchTerm,
        ?array $modules,
        string $module,
        int $offset,
        int $previewSize,
        int $pageSize
    ): array;
}
