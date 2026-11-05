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

namespace App\Module\Home\LegacyHandler;

use App\Engine\LegacyHandler\LegacyClassNamesTrait;

/**
 * The user's Home dashboard in the legacy user preferences (`Home` category): `pages` (tabs,
 * their columns and dashlet ids) and `dashlets` (class, file and options of each dashlet).
 * Shared by the dashboard (DashboardHandler) and its editing (DashboardEditHandler), so the
 * legacy dashboard and the SuiteCRM 8 one always show the same dashboard.
 */
trait DashboardPreferencesTrait
{
    use LegacyClassNamesTrait;

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

    protected function getDashletDefinitions(): array
    {
        global $current_user;

        $definitions = $current_user->getPreference('dashlets', 'Home');

        return is_array($definitions) ? $definitions : [];
    }

    protected function getPages(): array
    {
        global $current_user;

        $pages = $current_user->getPreference('pages', 'Home');

        return is_array($pages) ? array_values($pages) : [];
    }

    /**
     * Writes the changed preferences to the database. setPreference() only changes the
     * session: the legacy application writes them at the end of its requests
     * (sugar_cleanup()), which the SuiteCRM 8 requests do not run.
     */
    protected function savePreferences(): void
    {
        global $current_user;

        $current_user->savePreferencesToDB();
    }

    protected function getDashletsFiles(): array
    {
        $dashletsFiles = [];

        $cacheFile = sugar_cached('dashlets/dashlets.php');
        if (!is_file($cacheFile)) {
            require_once 'include/Dashlets/DashletCacheBuilder.php';
            (new \DashletCacheBuilder())->buildCache();
        }
        require $cacheFile;

        return $dashletsFiles;
    }

    /**
     * The dashlet object of a definition, or null when the legacy dashboard does not show it
     * (module not available to the user, dashlet file removed...).
     */
    protected function createDashlet(string $id, array $definition): ?object
    {
        if (empty($definition['fileLocation']) || !is_file($definition['fileLocation'])) {
            return null;
        }

        $className = $definition['className'] ?? '';
        $module = $this->getDashletsFiles()[$className]['module'] ?? ($definition['module'] ?? '') ?: 'Home';

        require_once 'include/MySugar/MySugar.php';
        if (!(new \MySugar($module))->checkDashletDisplay()) {
            return null;
        }

        require_once $definition['fileLocation'];
        if (!class_exists($className)) {
            return null;
        }

        $dashlet = new $className($id, $definition['options'] ?? []);
        if (method_exists($dashlet, 'shouldDisplay') && !$dashlet->shouldDisplay()) {
            return null;
        }

        return $dashlet;
    }

    /**
     * Same test as the legacy list dashlets: a DashletGeneric shown with the generic list
     * display (SugarFeed, for example, has its own).
     */
    protected function isListDashlet($dashlet): bool
    {
        if (!$dashlet instanceof \DashletGeneric || !$dashlet->seedBean instanceof \SugarBean) {
            return false;
        }

        $display = new \ReflectionMethod($dashlet, 'display');

        return $display->getDeclaringClass()->getName() === \DashletGeneric::class
            && $dashlet->displayTpl === 'include/Dashlets/DashletGenericDisplay.tpl';
    }
}
