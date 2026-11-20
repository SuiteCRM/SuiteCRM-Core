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
 * Base of the connector settings pages (legacy Connectors module: ConnectorSettings and
 * its Modify* pages): the connectors shown by a page, and the save by the action of the legacy
 * ConnectorsController with the request of the legacy form.
 */
abstract class ConnectorSettingsPage extends AdminSettingsPage
{
    /** The Connector Settings page, where Cancel and the save of the other pages go back to. */
    protected const BACK = 'connectors/ConnectorSettings';

    /**
     * The connectors (id => entry), with their source, kept when the source accepts them.
     *
     * @param callable(\source): bool $accept
     * @return array<string, array{entry: array, source: \source}>
     */
    protected function connectors(callable $accept, bool $all = true): array
    {
        require_once 'include/connectors/utils/ConnectorUtils.php';
        require_once 'include/connectors/sources/SourceFactory.php';

        $connectors = [];
        foreach (\ConnectorUtils::getConnectors($all) as $id => $entry) {
            $source = \SourceFactory::getSource($id);
            if ($source !== null && $accept($source)) {
                $connectors[(string)$id] = ['entry' => $entry, 'source' => $source];
            }
        }

        return $connectors;
    }

    /** The name of a connector, as the legacy tabs show it. */
    protected function connectorName(string $id, array $entry): string
    {
        return $this->decode((string)($entry['name'] ?? $id));
    }

    /**
     * Runs an action of the legacy ConnectorsController with the request of the legacy form and the
     * strings of the module (the redirect it sets is not used).
     *
     * @param array<string, mixed> $post
     */
    protected function runController(string $action, array $post): mixed
    {
        return $this->withPost($post + ['module' => 'Connectors', 'action' => $action], static function () use ($action) {
            global $current_language, $mod_strings;

            require_once 'include/MVC/Controller/SugarController.php';
            require_once 'modules/Connectors/controller.php';
            $strings = $mod_strings;
            $mod_strings = return_module_language($current_language, 'Connectors');
            ob_start();
            try {
                (new \ConnectorsController())->{'action_' . $action}();

                return ob_get_contents();
            } finally {
                ob_end_clean();
                header_remove('Location');
                $mod_strings = $strings;
            }
        });
    }

    /** The modules a connector can be enabled for (legacy view.displayproperties.php). */
    protected function allowedModules(\source $source): array
    {
        global $app_list_strings, $beanList, $current_user;

        $access = $current_user->getDeveloperModules();
        $modules = [];
        foreach (scandir('modules') ?: [] as $module) {
            if ($module[0] === '.' || !is_dir('modules/' . $module)) {
                continue;
            }
            if (file_exists('modules/' . $module . '/metadata/studio.php') && file_exists('modules/' . $module . '/metadata/detailviewdefs.php')
                && isset($beanList[$module]) && (in_array($module, $access, true) || is_admin($current_user))) {
                $modules[$module] = (string)($app_list_strings['moduleList'][$module] ?? $module);
            }
        }
        $modules = $source->filterAllowedModules($modules);
        asort($modules);

        return $modules;
    }
}
