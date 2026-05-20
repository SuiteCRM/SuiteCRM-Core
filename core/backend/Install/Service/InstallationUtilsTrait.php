<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Install\Service;

trait InstallationUtilsTrait
{
    /**
     * Check if is app is installed
     * @param $legacyDir
     * @return bool is locked
     */
    public function isAppInstalled($legacyDir): bool
    {
        $sugarConfigFile = $legacyDir . '/config.php';
        if (!file_exists($sugarConfigFile)) {
            return false;
        }
        return true;
    }

    /**
     * Check if is installer locked
     * @param $legacyDir
     * @return bool is locked
     */
    public function isAppInstallerLocked($legacyDir): bool
    {
        $installerLocked = false;
        $sugarConfigFile = $legacyDir . '/config.php';

        if (is_file($sugarConfigFile)) {
            $sugar_config = [];
            include $sugarConfigFile;
            $installerLocked = $sugar_config['installer_locked'];
        }

        return $installerLocked;
    }

    /**
     * Get Legacy Config
     * @param $legacyDir
     * @return array|null is locked
     */
    public function getLegacyConfig($legacyDir): ?array
    {
        $sugarConfigFile = $legacyDir . '/config.php';
        $sugarOverrideConfigFile = $legacyDir . '/config_override.php';

        if (is_file($sugarConfigFile)) {
            $sugar_config = [];
            include $sugarConfigFile;

            if (is_file($sugarOverrideConfigFile)) {
                include $sugarOverrideConfigFile;
            }

            return $sugar_config;
        }

        return null;
    }
}
