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

/**
 * Enable Connectors (legacy Connectors&action=ModifyDisplay): the modules each connector
 * is enabled for (enabled and disabled lists, as the legacy page), and the external accounts of the
 * connectors that have them, saved by ConnectorsController::action_SaveModifyDisplay() (which also
 * adds the fields of the social connectors to the layouts of the modules, as the legacy page).
 */
class ConnectorDisplayPage extends ConnectorSettingsPage
{
    public function getPageKey(): string
    {
        return 'connector-display';
    }

    protected function getPage(): array
    {
        require_once 'include/connectors/utils/ConnectorUtils.php';

        $modulesSources = \ConnectorUtils::getDisplayConfig();
        $sections = [];
        $values = [];
        foreach ($this->connectors(static fn (\source $source) => $source->isEnabledInAdminDisplay()) as $id => $connector) {
            $items = [];
            foreach ($this->allowedModules($connector['source']) as $module => $label) {
                $items[] = ['value' => (string)$module, 'label' => $this->decode($label)];
            }
            $enabled = [];
            foreach (is_array($modulesSources) ? $modulesSources : [] as $module => $sources) {
                if (in_array($id, (array)$sources, true)) {
                    $enabled[] = (string)$module;
                }
            }

            $fields = [[
                'name' => 'display_' . $id, 'label' => '', 'type' => 'lists', 'items' => $items, 'wide' => true,
                'enabledLabel' => $this->text('LBL_ENABLED', 'Connectors'), 'disabledLabel' => $this->text('LBL_DISABLED', 'Connectors'),
            ]];
            $values['display_' . $id] = $enabled;
            if (!empty($connector['entry']['eapm'])) {
                $fields[] = $this->field($id . '_external', 'LBL_EXTERNAL', 'bool', ['wide' => true], 'Connectors');
                $values[$id . '_external'] = !empty($connector['entry']['eapm']['enabled']);
            }

            $section = $this->section('', $fields, 'Connectors');
            $section['title'] = $this->connectorName($id, $connector['entry']);
            $sections[] = $section;
        }

        return [
            'title' => $this->text('LBL_MODIFY_DISPLAY_TITLE', 'Connectors'),
            'sections' => $sections ?: [$this->section('LBL_MODIFY_DISPLAY_TITLE', [], 'Connectors', 'LBL_MODIFY_DISPLAY_DESC')],
            'values' => $values,
            'back' => self::BACK,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $connectors = $this->connectors(static fn (\source $source) => $source->isEnabledInAdminDisplay());
        if (!$connectors) {
            return null;
        }

        // The request of the legacy form: the connectors changed, their enabled modules
        // (<connector>:<module>) and the external accounts.
        $request = ['display_sources' => implode(',', array_keys($connectors))];
        $display = [];
        foreach ($connectors as $id => $connector) {
            $allowed = $this->allowedModules($connector['source']);
            foreach ((array)($values['display_' . $id] ?? []) as $module) {
                if (array_key_exists((string)$module, $allowed)) {
                    $display[] = $id . ':' . $module;
                }
            }
            if (!empty($connector['entry']['eapm']) && !empty($values[$id . '_external'])) {
                $request[$id . '_external'] = '1';
            }
        }
        $request['display_values'] = implode(',', $display);

        $this->runController('SaveModifyDisplay', $request);

        return null;
    }
}
