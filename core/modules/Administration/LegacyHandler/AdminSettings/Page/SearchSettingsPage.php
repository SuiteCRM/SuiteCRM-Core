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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;
use SuiteCRM\Search\SearchConfigurator;
use SuiteCRM\Search\SearchModules;
use SuiteCRM\Search\SearchWrapper;
use SuiteCRM\Utility\StringUtils;

/**
 * The Search Settings (legacy Administration&action=SearchSettings): the search engine
 * and the modules searched; saved as the legacy page
 * (SearchConfigurator, SearchModules::saveGlobalSearchSettings).
 */
class SearchSettingsPage extends AdminSettingsPage
{
    public function getPageKey(): string
    {
        return 'search';
    }

    protected function getPage(): array
    {
        $modules = SearchModules::getAllModules();
        $items = [];
        $enabled = [];
        foreach (['enabled', 'disabled'] as $state) {
            foreach ($modules[$state] ?? [] as $module) {
                $items[] = ['value' => (string)$module['module'], 'label' => $this->decode((string)$module['label'])];
                if ($state === 'enabled') {
                    $enabled[] = (string)$module['module'];
                }
            }
        }

        return [
            'title' => $this->text('LBL_SEARCH_WRAPPER'),
            'sections' => [
                $this->section('LBL_SEARCH_INTERFACE', [
                    $this->field('search_engine', 'LBL_SEARCH_ENGINE', 'select', [
                        'options' => $this->options($this->getEngines()),
                        'help' => 'LBL_SEARCH_ENGINE_HELP',
                    ]),
                ]),
                $this->section('LBL_SEARCH_MODULES', [
                    $this->field('enabled_modules', '', 'lists', [
                        'items' => $items,
                        'enabledLabel' => $this->text('LBL_ACTIVE_MODULES'),
                        'disabledLabel' => $this->text('LBL_DISABLED_MODULES'),
                        'wide' => true,
                    ]),
                ], 'Administration', 'LBL_SEARCH_MODULES_HELP'),
            ],
            'values' => [
                'search_engine' => SearchWrapper::getDefaultEngine(),
                'enabled_modules' => $enabled,
            ],
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $engine = (string)($values['search_engine'] ?? '');
        if (!array_key_exists($engine, $this->getEngines())) {
            return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
        }

        $enabled = is_array($values['enabled_modules'] ?? null) ? array_map('strval', $values['enabled_modules']) : [];

        SearchConfigurator::make()->setEngine($engine)->save();
        $this->withPost(['enabled_modules' => implode(',', $enabled)], static fn () => SearchModules::saveGlobalSearchSettings());

        return null;
    }

    /**
     * The engines of the legacy page: the basic one, with AOD when enabled, and the others.
     *
     * @return array<string, string>
     */
    protected function getEngines(): array
    {
        global $sugar_config;

        $engines = ['BasicSearchEngine' => $this->text('LBL_BASIC_SEARCH_ENGINE')];
        if (isTrue($sugar_config['aod']['enable_aod'] ?? false)) {
            $engines['BasicAndAodEngine'] = $this->text('LBL_BASIC_AND_AOD_ENGINE');
        }
        foreach (SearchWrapper::getEngines() as $engine) {
            $engines[$engine] = StringUtils::camelToTranslation($engine);
        }

        return $engines;
    }
}
