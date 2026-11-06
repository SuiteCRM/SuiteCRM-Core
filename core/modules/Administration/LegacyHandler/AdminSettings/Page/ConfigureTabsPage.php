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

/**
 * The Display Modules and Subpanels (legacy Administration&action=ConfigureTabs): the
 * modules shown in the navigation (in order) or hidden, whether the users can choose theirs, and
 * the subpanels shown or hidden; saved as the legacy action (TabController, SubPanelDefinitions).
 */
class ConfigureTabsPage extends AdminSettingsPage
{
    public function getPageKey(): string
    {
        return 'tabs';
    }

    protected function getPage(): array
    {
        [$shown, $hidden] = $this->getTabs();
        $tabs = [];
        foreach (array_merge(array_keys($shown), array_keys($hidden)) as $module) {
            $tabs[] = ['value' => (string)$module, 'label' => $this->getModuleLabel((string)$module)];
        }

        [$shownPanels, $hiddenPanels] = $this->getSubpanels();
        $panels = [];
        foreach (array_merge($shownPanels, $hiddenPanels) as $panel => $label) {
            $panels[] = ['value' => (string)$panel, 'label' => $label];
        }

        return [
            'title' => $this->text('LBL_CONFIG_TABS'),
            'leave' => false,
            'sections' => [
                $this->section('LBL_CONFIG_TABS', [
                    $this->field('user_edit_tabs', 'LBL_ALLOW_USER_TABS', 'bool', ['help' => 'LBL_CONFIG_TABS_ALLOW_USERS_HIDE_TABS_HELP', 'wide' => true]),
                    $this->field('tabs', '', 'lists', [
                        'items' => $tabs,
                        'ordered' => true,
                        'enabledLabel' => $this->text('LBL_VISIBLE_TABS'),
                        'disabledLabel' => $this->text('LBL_HIDDEN_TABS'),
                    ]),
                ]) + ['intro' => $this->getIntro()],
                $this->section('LBL_SUBPANELS', [
                    $this->field('subpanels', '', 'lists', [
                        'items' => $panels,
                        'enabledLabel' => $this->text('LBL_VISIBLE_PANELS'),
                        'disabledLabel' => $this->text('LBL_HIDDEN_PANELS'),
                    ]),
                ], 'ModuleBuilder'),
            ],
            'values' => [
                'user_edit_tabs' => (new \TabController())->get_users_can_edit(),
                'tabs' => array_map('strval', array_keys($shown)),
                'subpanels' => array_map('strval', array_keys($shownPanels)),
            ],
        ];
    }

    /**
     * Saves as the legacy action: the shown modules in their order, whether the users can choose
     * theirs, the hidden subpanels (those not shown).
     */
    protected function saveValues(array $values): ?string
    {
        [$shown, $hidden] = $this->getTabs();
        $modules = $shown + $hidden;
        $tabs = [];
        foreach (is_array($values['tabs'] ?? null) ? $values['tabs'] : [] as $module) {
            if (is_string($module) && isset($modules[$module]) && !in_array($module, $tabs, true)) {
                $tabs[] = $module;
            }
        }

        [$shownPanels, $hiddenPanels] = $this->getSubpanels();
        $panels = is_array($values['subpanels'] ?? null) ? $values['subpanels'] : [];
        $hide = [];
        // The hidden subpanels first (their order), then the subpanels being hidden.
        foreach (array_merge(array_keys($hiddenPanels), array_keys($shownPanels)) as $panel) {
            if (!in_array($panel, $panels, true)) {
                $hide[] = (string)$panel;
            }
        }

        $controller = new \TabController();
        $controller->set_system_tabs($tabs);
        $controller->set_users_can_edit(!empty($values['user_edit_tabs']));
        (new \SubPanelDefinitions(\BeanFactory::newBean('Administration')))->set_hidden_subpanels(\TabController::get_key_array($hide));

        return null;
    }

    /**
     * The shown and hidden modules, by module (TabController::get_tabs_system).
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    protected function getTabs(): array
    {
        require_once 'modules/MySettings/TabController.php';

        $tabs = (new \TabController())->get_tabs_system();

        return [is_array($tabs[0] ?? null) ? $tabs[0] : [], is_array($tabs[1] ?? null) ? $tabs[1] : []];
    }

    /**
     * The shown and hidden subpanels, subpanel (lowercase module) => label, as the legacy page.
     * The hidden ones read from the settings: SubPanelDefinitions keeps the ones of the start of
     * the request (as before a save).
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    protected function getSubpanels(): array
    {
        global $app_list_strings;

        require_once 'include/SubPanel/SubPanelDefinitions.php';
        require_once 'modules/MySettings/TabController.php';

        $labels = array_change_key_case($app_list_strings['moduleList'] ?? []);
        $definitions = new \SubPanelDefinitions(\BeanFactory::newBean('Administration'));
        $known = $definitions->get_hidden_subpanels();
        // All the subpanels: the shown ones, then the hidden ones.
        $all = array_merge($definitions->get_all_subpanels(), is_array($known) ? array_values($known) : []);

        $administration = \BeanFactory::newBean('Administration');
        $administration->retrieveSettings('MySettings', true);
        $setting = trim((string)($administration->settings['MySettings_hide_subpanels'] ?? ''));
        $hidden = $setting !== '' ? unserialize(base64_decode($setting), ['allowed_classes' => false]) : [];
        $hidden = array_map(static fn (mixed $panel): string => strtolower((string)$panel), is_array($hidden) ? array_values($hidden) : []);

        $lists = [[], []];
        foreach ([array_diff(array_map(static fn (mixed $panel): string => strtolower((string)$panel), $all), $hidden), $hidden] as $index => $panels) {
            foreach ($panels as $panel) {
                if ($panel !== '') {
                    $lists[$index][$panel] = $this->decode((string)($labels[$panel] ?? $panel));
                }
            }
        }

        return $lists;
    }

    /**
     * The description of the legacy page, its link to the roles to the SuiteCRM 8 page.
     */
    protected function getIntro(): string
    {
        return str_replace('?module=ACLRoles&action=index', '#/acl-roles/index', $this->text('LBL_CONFIG_TABS_DESC', 'Administration', false));
    }

    protected function getModuleLabel(string $module): string
    {
        global $app_list_strings;

        return $this->decode((string)($app_list_strings['moduleList'][$module] ?? translate($module)));
    }
}
