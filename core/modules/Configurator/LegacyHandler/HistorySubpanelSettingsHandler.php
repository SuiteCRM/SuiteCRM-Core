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

namespace App\Module\Configurator\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The History Subpanel settings of the administration (legacy
 * Configurator&action=historyContactsEmails) for its SuiteCRM 8 view, as the legacy page and its
 * historyContactsEmailsSave action: the modules whose History subpanel shows the emails of their
 * related contacts (config hide_history_contacts_emails).
 */
class HistorySubpanelSettingsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'history-subpanel-settings';

    protected const LABELS = ['LBL_HISTORY_SUBPANEL', 'LBL_ENABLE_HISTORY_CONTACTS_EMAILS'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The modules (by name), with whether the emails of their contacts are shown, and the labels.
     */
    public function load(): array
    {
        return $this->inLegacy(function () {
            global $sugar_config, $app_list_strings, $current_language;

            $hidden = is_array($sugar_config['hide_history_contacts_emails'] ?? null) ? $sugar_config['hide_history_contacts_emails'] : [];
            $modules = [];
            foreach ($this->getModules() as $module => $hiddenByDefault) {
                $modules[] = [
                    'module' => $module,
                    'label' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? translate($module))),
                    'enabled' => array_key_exists($module, $hidden) ? !$hidden[$module] : !$hiddenByDefault,
                ];
            }

            $strings = return_module_language($current_language, 'Configurator');
            $labels = [];
            foreach (self::LABELS as $label) {
                $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
            }

            return ['modules' => $modules, 'labels' => $labels];
        });
    }

    /**
     * Saves which modules show the emails of their contacts, as the legacy action.
     *
     * @param array<string, mixed> $enabled module => bool
     */
    public function save(array $enabled): void
    {
        $this->inLegacy(function () use ($enabled) {
            require_once 'modules/Configurator/Configurator.php';

            $hidden = [];
            foreach (array_keys($this->getModules()) as $module) {
                if (array_key_exists($module, $enabled)) {
                    $hidden[$module] = empty($enabled[$module]);
                }
            }

            $configurator = new \Configurator();
            $configurator->config['hide_history_contacts_emails'] = $hidden;
            $configurator->handleOverride();
        });
    }

    /**
     * The modules with a History subpanel listing the emails of their contacts
     * (get_emails_by_assign_or_link) and related to the contacts, as the legacy page, with
     * whether their vardefs hide these emails.
     *
     * @return array<string, bool> module => hidden by the vardefs, by module name
     */
    protected function getModules(): array
    {
        require_once 'include/SubPanel/SubPanelDefinitions.php';

        $modules = [];
        foreach (array_keys($GLOBALS['beanList']) as $module) {
            $bean = \BeanFactory::getBean($module);
            if (!($bean instanceof \SugarBean) || empty($bean->field_defs) || !$this->hasContactsEmailsHistory($bean)) {
                continue;
            }

            $bean->load_relationships();
            foreach ($bean->get_linked_fields() as $field => $definition) {
                if (is_object($bean->$field ?? null) && $bean->$field->getRelatedModuleName() === 'Contacts') {
                    $modules[$module] = !empty($definition['hide_history_contacts_emails']);
                    break;
                }
            }
        }
        ksort($modules);

        return $modules;
    }

    protected function hasContactsEmailsHistory(\SugarBean $bean): bool
    {
        $subpanels = new \SubPanelDefinitions($bean);
        foreach ($subpanels->layout_defs['subpanel_setup'] ?? [] as $subpanel) {
            if (($subpanel['module'] ?? '') !== 'History') {
                continue;
            }
            foreach ($subpanel['collection_list'] ?? [] as $collection) {
                if (($collection['get_subpanel_data'] ?? '') === 'function:get_emails_by_assign_or_link') {
                    return true;
                }
            }
        }

        return false;
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
