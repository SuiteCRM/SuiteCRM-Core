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

namespace App\Search\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Search\Service\GlobalSearchProviderInterface;
use SuiteCRM\Search\SearchModules;
use SuiteCRM\Search\SearchQuery;
use SuiteCRM\Search\SearchWrapper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Runs the global search through SearchWrapper, with the search engine set in the
 * system configuration (search.defaultEngine). Only returns record ids: the records
 * are loaded by the caller, with the same access checks as the record view.
 *
 * The searched modules are the ones enabled in the global search settings that the
 * user may list. The user's choice is kept in the globalSearch preference, the one
 * the legacy search page uses.
 */
class GlobalSearchHandler extends LegacyHandler implements GlobalSearchProviderInterface
{
    public const HANDLER_KEY = 'global-search';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function search(
        string $searchTerm,
        ?array $modules,
        string $module,
        int $offset,
        int $previewSize,
        int $pageSize
    ): array {
        $this->init();
        $this->startLegacyApp();

        try {
            return $this->runSearch($searchTerm, $modules, $module, $offset, $previewSize, $pageSize);
        } finally {
            $this->close();
        }
    }

    protected function runSearch(
        string $searchTerm,
        ?array $modules,
        string $module,
        int $offset,
        int $previewSize,
        int $pageSize
    ): array {
        global $app_list_strings, $current_user;

        // Searchable modules (global search settings) the user may list, by legacy name.
        $available = [];
        foreach (SearchModules::getEnabledModules() as $legacyModule) {
            if (\ACLController::checkAccess($legacyModule, 'list', true)) {
                $available[$legacyModule] = $app_list_strings['moduleList'][$legacyModule] ?? $legacyModule;
            }
        }

        // The requested modules are saved as the user's choice, else the saved choice is used.
        $selected = [];
        foreach ($modules ?? [] as $requested) {
            $legacyModule = $this->moduleNameMapper->toLegacy((string)$requested);
            if (isset($available[$legacyModule])) {
                $selected[$legacyModule] = $legacyModule;
            }
        }
        if ($selected) {
            SearchWrapper::getUserSelectedModules($selected);
            $current_user->savePreferencesToDB();
        } else {
            $selected = array_intersect_key(SearchWrapper::getUserSelectedModules(), $available);
        }

        // In the order of the global search settings; every module when none is selected.
        $scope = [];
        foreach (array_keys($available) as $legacyModule) {
            if (!$selected || isset($selected[$legacyModule])) {
                $scope[$legacyModule] = $legacyModule;
            }
        }

        $pageModule = $module !== '' ? $this->moduleNameMapper->toLegacy($module) : '';
        if (!isset($scope[$pageModule])) {
            $pageModule = '';
        }

        $counts = [];
        $ids = [];
        if ($searchTerm !== '' && $scope) {
            $engine = SearchWrapper::getDefaultEngine();

            // Result count of every module, with the first results when no module is paged.
            $hits = SearchWrapper::search(
                $engine,
                SearchQuery::fromString($searchTerm, $pageModule === '' ? $previewSize : 1, 0, null, [], $scope)
            )->getHits();

            foreach ($scope as $legacyModule) {
                $counts[$legacyModule] = (int)($hits[$legacyModule]['totalHits'] ?? 0);
            }

            if ($pageModule !== '') {
                $hits = SearchWrapper::search(
                    $engine,
                    SearchQuery::fromString($searchTerm, $pageSize, $offset, null, [], [$pageModule => $pageModule])
                )->getHits();
            }

            foreach ($pageModule === '' ? $scope : [$pageModule => $pageModule] as $legacyModule) {
                if (!empty($counts[$legacyModule])) {
                    $ids[$this->moduleNameMapper->toFrontEnd($legacyModule)] = array_values(
                        (array)($hits[$legacyModule]['results'] ?? [])
                    );
                }
            }
        }

        $moduleOptions = [];
        $moduleCounts = [];
        foreach ($available as $legacyModule => $label) {
            $frontEndModule = $this->moduleNameMapper->toFrontEnd($legacyModule);
            $moduleOptions[] = [
                'module' => $frontEndModule,
                'label' => $label,
                'selected' => isset($scope[$legacyModule]),
            ];
            if (isset($counts[$legacyModule])) {
                $moduleCounts[$frontEndModule] = $counts[$legacyModule];
            }
        }

        return [
            'modules' => $moduleOptions,
            'counts' => $moduleCounts,
            'module' => $pageModule !== '' ? $this->moduleNameMapper->toFrontEnd($pageModule) : '',
            'ids' => $ids,
        ];
    }
}
