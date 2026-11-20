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
 * The Connector Settings of the administration (legacy Connectors&action=ConnectorSettings):
 * the links to the settings pages of the connectors, with their description, as the legacy page.
 */
class ConnectorsPage extends ConnectorSettingsPage
{
    /** The settings pages (route => title, description), in the order of the legacy page. */
    protected const PAGES = [
        'connectors/ModifyProperties' => ['LBL_MODIFY_PROPERTIES_TITLE', 'LBL_MODIFY_PROPERTIES_DESC'],
        'connectors/ModifyDisplay' => ['LBL_MODIFY_DISPLAY_TITLE', 'LBL_MODIFY_DISPLAY_DESC'],
        'connectors/ModifyMapping' => ['LBL_MODIFY_MAPPING_TITLE', 'LBL_MODIFY_MAPPING_DESC'],
        'connectors/ModifySearch' => ['LBL_MODIFY_SEARCH_TITLE', 'LBL_MODIFY_SEARCH_DESC'],
    ];

    public function getPageKey(): string
    {
        return 'connectors';
    }

    protected function getPage(): array
    {
        $html = '<ul>';
        foreach (self::PAGES as $route => [$title, $description]) {
            $html .= '<li><a href="#/' . $route . '"><b>' . htmlspecialchars($this->text($title, 'Connectors')) . '</b></a><br>'
                . htmlspecialchars($this->text($description, 'Connectors', false)) . '</li>';
        }

        return [
            'title' => $this->text('LBL_ADMINISTRATION_MAIN', 'Connectors'),
            'sections' => [
                $this->section('LBL_ADMINISTRATION_MAIN', [
                    ['name' => 'pages', 'label' => '', 'type' => 'html', 'help' => $html . '</ul>', 'wide' => true],
                ], 'Connectors'),
            ],
            'values' => [],
            'readonly' => true,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        return null;
    }
}
