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

namespace App\Module\Administration\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The Module Loader of the administration (legacy Administration&action=UpgradeWizard&view=module)
 * for the module loader view: the packages ready to install and the installed ones,
 * as the legacy page lists them (PackageManager, their files hashed in the legacy session for
 * its prepare and commit actions), and the deletion of a package ready to install (its legacy
 * HandleAjaxCall remove). The upload, the prepare and the commit stay the legacy actions, called
 * by the view.
 */
class ModuleLoaderHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'module-loader';

    /**
     * The upload size below which the legacy page warns (UpgradeWizard.php).
     */
    protected const MIN_UPLOAD_MAX_FILESIZE = 6 * 1024 * 1024;

    protected const LABELS = [
        'LBL_MODULE_LOADER_TITLE', 'LBL_MODULE_LOADER', 'LBL_UW_UPLOAD_MODULE', 'LBL_UW_BTN_UPLOAD',
        'LBL_UW_DESC_MODULES_QUEUED', 'LBL_UW_DESC_MODULES_INSTALLED', 'LBL_ML_NAME', 'LBL_ML_TYPE',
        'LBL_ML_VERSION', 'LBL_ML_PUBLISHED', 'LBL_ML_DESCRIPTION', 'LBL_ML_ACTION', 'LBL_UW_BTN_INSTALL',
        'LBL_UW_BTN_DELETE_PACKAGE', 'LBL_UW_UNINSTALL', 'LBL_UW_DISABLE', 'LBL_UW_ENABLE', 'LBL_ML_COMMIT',
        'LBL_ML_CANCEL', 'REMOVE_QUESTION', 'LBL_ML_ENABLE_OR_DISABLE', 'LBL_ML_UNINSTALLABLE',
        'ERR_UW_ACCEPT_LICENSE', 'ERR_UW_NO_UPLOAD_FILE', 'MSG_INCREASE_UPLOAD_MAX_FILESIZE', 'LBL_LICENSE',
        'LBL_MODULE_LICENSE', 'LBL_README', 'LBL_UW_BTN_BACK_TO_MOD_LOADER', 'LBL_ACCEPT', 'LBL_UW_UPLOAD_SUCCESS',
        'ERR_UW_UPLOAD_ERROR',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The packages ready to install and the installed ones, the labels of the page and the upload
     * warning of the legacy page.
     */
    public function load(): array
    {
        return $this->inLegacy(function (): array {
            global $current_language;

            $manager = new \PackageManager();

            $staged = [];
            foreach ($manager->getPackagesInStaging('module') as $package) {
                $staged[] = $this->package($package) + ['file' => (string)($package['file'] ?? '')];
            }

            $installed = [];
            foreach ($manager->getinstalledPackages() as $package) {
                $installed[] = $this->package($package) + [
                    'file' => (string)($package['file'] ?? ''),
                    'uninstallable' => ($package['file_install'] ?? '') !== 'UNINSTALLABLE',
                    'enabled' => ($package['enabled'] ?? '') === 'ENABLED',
                    'canDisable' => ($package['enabled'] ?? '') !== 'UNINSTALLABLE',
                ];
            }

            $strings = return_module_language($current_language, 'Administration');
            $labels = [];
            foreach (self::LABELS as $label) {
                $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
            }

            $warning = '';
            if (return_bytes(ini_get('upload_max_filesize')) < self::MIN_UPLOAD_MAX_FILESIZE) {
                $warning = $labels['MSG_INCREASE_UPLOAD_MAX_FILESIZE'] . ' ' . get_cfg_var('cfg_file_path');
            }

            return ['staged' => $staged, 'installed' => $installed, 'labels' => $labels, 'uploadWarning' => $warning];
        });
    }

    /**
     * Deletes a package ready to install (its zip, manifest and icon), as the legacy remove.
     */
    public function remove(string $hash): bool
    {
        return $this->inLegacy(function () use ($hash): bool {
            $file = urldecode((string)hashToFile($hash));
            // Only a package of the uploads (the legacy staging directory).
            if ($file === '' || !str_starts_with($file, 'upload://upgrades/') || str_contains($file, '..')
                || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'zip' || !is_file($file)) {
                return false;
            }

            unlink($file);
            foreach (['manifest', 'icon'] as $meta) {
                $metaFile = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME) . "-$meta.php";
                if (file_exists($metaFile)) {
                    unlink($metaFile);
                }
            }

            return true;
        });
    }

    /**
     * @param array<string, mixed> $package
     * @return array<string, string>
     */
    protected function package(array $package): array
    {
        $values = [];
        foreach (['name', 'version', 'type', 'published_date', 'description'] as $key) {
            $values[$key === 'published_date' ? 'published' : $key] = $this->decode(strip_tags((string)($package[$key] ?? '')));
        }

        return $values;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback): mixed
    {
        $this->init();
        $this->startLegacyApp();

        try {
            require_once 'ModuleInstall/PackageManager/PackageManager.php';

            return $callback();
        } finally {
            $this->close();
        }
    }
}
