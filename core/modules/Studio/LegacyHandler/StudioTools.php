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

namespace App\Module\Studio\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The tools of Studio around the components of the modules:
 *  - Reset Module (legacy view.resetmodule): the customizations of a module it removes (the page
 *    posts the legacy action: its relationships and extensions repair the application);
 *  - Export Customizations (legacy view.exportcustomizations): the customized modules, and the
 *    package of the customizations of some of them, made as the legacy export makes it
 *    (MBPackage::exportCustom), for the browser to download.
 */
class StudioTools extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'studio-tools';

    /**
     * The customizations Reset Module removes, in the order it removes them, with their label.
     */
    protected const RESET_ACTIONS = [
        'relationships' => 'LBL_CLEAR_RELATIONSHIPS',
        'fields' => 'LBL_REMOVE_FIELDS',
        'layouts' => 'LBL_RESET_LAYOUTS',
        'labels' => 'LBL_RESET_LABELS',
        'extensions' => 'LBL_CLEAR_EXTENSIONS',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The customizations Reset Module can remove from a module (all checked in the legacy screen).
     */
    public function getResetActions(string $module): array
    {
        return $this->inLegacy(function () use ($module): array {
            $this->getStudioModule($module);
            $actions = [];
            foreach (self::RESET_ACTIONS as $name => $label) {
                $actions[] = ['name' => $name, 'label' => $this->decode((string)translate($label))];
            }

            return ['actions' => $actions];
        });
    }

    /**
     * The modules with customizations, as the legacy export lists them: their name and what is
     * customized (none for a module that cannot be exported).
     */
    public function getExportModules(): array
    {
        return $this->inLegacy(function (): array {
            global $app_list_strings;

            require_once 'modules/ModuleBuilder/MB/ModuleBuilder.php';
            $modules = [];
            foreach ((new \MBPackage('packageCustom'))->getCustomModules() as $module => $items) {
                $modules[] = [
                    'module' => (string)$module,
                    'name' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? '')),
                    'items' => array_values(array_map(fn ($item): string => $this->decode((string)$item), is_array($items) ? $items : [])),
                    'exportable' => $items !== '',
                ];
            }

            return ['modules' => $modules];
        });
    }

    /**
     * The package of the customizations of modules, made as the legacy export makes it (its zip
     * kept in the package folder too), for the browser to download: file name and content.
     *
     * @param string[] $modules
     */
    public function exportCustomizations(string $name, string $author, string $description, array $modules): array
    {
        return $this->inLegacy(function () use ($name, $author, $description, $modules): array {
            require_once 'modules/ModuleBuilder/MB/ModuleBuilder.php';
            // Read for the relationships of the modules (StudioModule::getRelationships()).
            require_once 'modules/ModuleBuilder/Module/StudioModule.php';
            $customized = (new \MBPackage('packageCustom'))->getCustomModules();
            foreach ($modules as $module) {
                if (!is_array($customized[$module] ?? null)) {
                    throw new \InvalidArgumentException('Not a customized module: ' . $module);
                }
            }

            $package = new \MBPackage($name);
            $package->author = $author;
            $package->description = $description;
            $zip = $this->withRequest([], static fn (): string => (string)$package->exportCustom($modules, false, true));
            if ($zip === '' || !is_file($zip)) {
                throw new \RuntimeException('The package was not made');
            }

            return ['fileName' => basename($zip), 'content' => base64_encode((string)file_get_contents($zip))];
        });
    }
}
