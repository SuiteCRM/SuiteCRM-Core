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
 * Manage Connector Search (legacy Connectors&action=ModifySearch): the connector fields
 * used to search each module, for the connectors with a search (legacy view.searchproperties.php).
 * Read only: the legacy page posts to a SaveModifySearch action the Connectors module does not have.
 */
class ConnectorSearchPage extends ConnectorSettingsPage
{
    public function getPageKey(): string
    {
        return 'connector-search';
    }

    protected function getPage(): array
    {
        global $app_list_strings;

        require_once 'include/connectors/utils/ConnectorUtils.php';
        $searchDefs = \ConnectorUtils::getSearchDefs();

        $sections = [];
        foreach ($this->connectors(static fn (\source $source) => $source->isEnabledInAdminSearch() && $source->isEnabledInWizard(), false) as $id => $connector) {
            $fieldDefs = $connector['source']->getFieldDefs();
            $strings = \ConnectorUtils::getConnectorStrings($id) ?: [];
            $html = '<ul>';
            foreach ((array)($searchDefs[$id] ?? []) as $module => $fields) {
                $labels = array_map(
                    fn ($field) => htmlspecialchars($this->decode((string)($strings[$fieldDefs[$field]['vname'] ?? ''] ?? $field))),
                    (array)$fields
                );
                $html .= '<li><b>' . htmlspecialchars($this->decode((string)($app_list_strings['moduleList'][$module] ?? $module))) . '</b>: '
                    . implode(', ', $labels) . '</li>';
            }
            $section = $this->section('', [['name' => 'search_' . $id, 'label' => '', 'type' => 'html', 'help' => $html . '</ul>', 'wide' => true]], 'Connectors');
            $section['title'] = $this->connectorName($id, $connector['entry']);
            $sections[] = $section;
        }

        return [
            'title' => $this->text('LBL_MODIFY_SEARCH_TITLE', 'Connectors'),
            'sections' => $sections ?: [$this->section('LBL_MODIFY_SEARCH_TITLE', [], 'Connectors', 'LBL_MODIFY_SEARCH_DESC')],
            'values' => [],
            'readonly' => true,
            'back' => self::BACK,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        return null;
    }
}
