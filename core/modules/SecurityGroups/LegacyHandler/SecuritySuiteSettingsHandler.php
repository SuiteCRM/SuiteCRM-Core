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

namespace App\Module\SecurityGroups\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The Security Suite settings of the administration (legacy SecurityGroups config action) for
 * the security suite settings view, as the legacy page and its SaveConfig.php: the rights and
 * group inheritance options (config) and the default groups of new records
 * (securitygroups_default).
 */
class SecuritySuiteSettingsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'security-suite-settings';

    /**
     * The options of the page, in its order: rights, then inheritance.
     */
    public const SETTINGS = [
        'securitysuite_additive' => 'LBL_ADDITIVE',
        'securitysuite_user_role_precedence' => 'LBL_USER_ROLE_PRECEDENCE',
        'securitysuite_strict_rights' => 'LBL_STRICT_RIGHTS',
        'securitysuite_filter_user_list' => 'LBL_FILTER_USER_LIST',
        'securitysuite_user_popup' => 'LBL_USER_POPUP',
        'securitysuite_popup_select' => 'LBL_POPUP_SELECT',
        'securitysuite_inherit_creator' => 'LBL_INHERIT_CREATOR',
        'securitysuite_inherit_parent' => 'LBL_INHERIT_PARENT',
        'securitysuite_inherit_assigned' => 'LBL_INHERIT_ASSIGNED',
        'securitysuite_inbound_email' => 'LBL_INBOUND_EMAIL',
    ];

    protected const LABELS = [
        'LBL_MODULE_NAME', 'LBL_CONFIGURE_SETTINGS', 'LBL_INHERIT_TITLE', 'LBL_DEFAULT_GROUP_TITLE', 'LBL_GROUP',
        'LBL_MODULE', 'LBL_ADD_BUTTON_LABEL', 'LBL_REMOVE_BUTTON_LABEL',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The options, the default groups, the groups and modules to choose from, and the labels of
     * the page (SecurityGroups strings).
     */
    public function load(): array
    {
        return $this->inLegacy(function () {
            global $sugar_config, $current_language;

            $strings = return_module_language($current_language, 'SecurityGroups');
            $settings = [];
            $labels = [];
            foreach (self::SETTINGS as $setting => $label) {
                $settings[$setting] = !empty($sugar_config[$setting]);
                $labels[$label] = $this->decode($strings[$label] ?? $label);
                $labels[$label . '_DESC'] = $this->decode($strings[$label . '_DESC'] ?? '');
            }
            foreach (self::LABELS as $label) {
                $labels[$label] = rtrim($this->decode($strings[$label] ?? $label), ': ');
            }

            $modules = $this->getModules();
            $defaults = [];
            foreach (\SecurityGroup::retrieveDefaultGroups() as $id => $default) {
                $module = (string)($default['module'] ?? '');
                $defaults[] = [
                    'id' => (string)$id,
                    'group' => $this->decode((string)($default['group'] ?? '')),
                    'module' => $module,
                    'moduleLabel' => $modules[$module] ?? $module,
                ];
            }

            $groups = [];
            $db = \DBManagerFactory::getInstance();
            $result = $db->query('SELECT id, name FROM securitygroups WHERE deleted = 0 ORDER BY name');
            while ($row = $db->fetchByAssoc($result, false)) {
                $groups[] = ['id' => $row['id'], 'name' => $this->decode((string)$row['name'])];
            }

            $moduleList = [];
            foreach ($modules as $module => $label) {
                $moduleList[] = ['value' => $module, 'label' => $label];
            }

            return [
                'settings' => $settings,
                'defaults' => $defaults,
                'groups' => $groups,
                'modules' => $moduleList,
                'labels' => $labels
            ];
        });
    }

    /**
     * Saves the options, as SaveConfig.php (config override, SecurityGroups banned from the
     * Ajax UI), and marks the system configs of the front to rebuild.
     *
     * @param array<string, mixed> $settings
     */
    public function save(array $settings): void
    {
        $this->inLegacy(static function () use ($settings) {
            require_once 'modules/Configurator/Configurator.php';
            require_once 'include/portability/Services/Cache/CacheManager.php';

            $configurator = new \Configurator();
            foreach (array_keys(self::SETTINGS) as $setting) {
                $configurator->config[$setting] = !empty($settings[$setting]);
            }
            $configurator->config['addAjaxBannedModules'] = $configurator->config['addAjaxBannedModules'] ?? [];
            if (!in_array('SecurityGroups', $configurator->config['addAjaxBannedModules'], true)) {
                $configurator->config['addAjaxBannedModules'][] = 'SecurityGroups';
            }
            $configurator->handleOverride();

            (new \CacheManager())->markAsNeedsUpdate('app-metadata-system-configs');
        });
    }

    /**
     * Adds a default group of the new records of a module (All: of every module).
     */
    public function addDefault(string $groupId, string $module): bool
    {
        return $this->inLegacy(function () use ($groupId, $module) {
            $group = $groupId !== '' ? \BeanFactory::getBean('SecurityGroups', $groupId) : null;
            if (!$group || empty($group->id) || !isset($this->getModules()[$module])) {
                return false;
            }

            \SecurityGroup::saveDefaultGroup($group->id, $module);

            return true;
        });
    }

    public function removeDefault(string $id): void
    {
        $this->inLegacy(static function () use ($id) {
            if ($id !== '') {
                require_once 'modules/SecurityGroups/SecurityGroup.php';
                \SecurityGroup::removeDefaultGroup($id);
            }
        });
    }

    /**
     * The modules of the default groups, as the legacy page: those related to the groups, and All.
     *
     * @return array<string, string>
     */
    protected function getModules(): array
    {
        global $current_language;

        require_once 'modules/SecurityGroups/SecurityGroup.php';
        $strings = return_module_language($current_language, 'SecurityGroups');
        $modules = \SecurityGroup::getSecurityModules();
        $modules['All'] = $strings['LBL_ALL_MODULES'] ?? 'All';
        asort($modules);

        return array_map(fn ($label): string => $this->decode((string)$label), $modules);
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
