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
 * The packages and modules of Module Builder (legacy ModuleBuilder&type=mb) for the
 * SuiteCRM 8 Module Builder page, by the actions of the legacy ModuleBuilder controller with the
 * request of the legacy forms:
 *  - the packages and their modules, the properties of a package (name, key, author, description,
 *    readme) saved, duplicated or deleted (SavePackage, DeletePackage);
 *  - Publish (the installable package) and Export (the project, to import in another Module
 *    Builder); Deploy is posted by the page to the legacy action (its install clears the Symfony
 *    cache, see postStudioAction());
 *  - the properties of a module (name, label, type of a new one, importing, navigation tab),
 *    saved, duplicated or deleted (SaveModule, DeleteModule).
 * The components of a module (fields, labels, relationships, layouts) are those of Studio for the
 * package (StudioLegacyTrait::forPackage()).
 */
class ModuleBuilderPackages extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'module-builder';

    /** A package, module or key name (legacy DBName check of the forms). */
    protected const NAME = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    protected const LABELS = [
        'LBL_MODULEBUILDER', 'LBL_NEW_PACKAGE', 'LBL_QUESTION_PACKAGE', 'LBL_PACKAGE_NAME', 'LBL_KEY', 'LBL_AUTHOR', 'LBL_DESCRIPTION',
        'LBL_ADD_README', 'LBL_LAST_MODIFIED', 'LBL_MODULES', 'LBL_NEW_MODULE', 'LBL_BTN_SAVE', 'LBL_BTN_DEPLOY', 'LBL_BTN_PUBLISH',
        'LBL_BTN_EXP', 'LBL_BTN_DUPLICATE', 'LBL_BTN_DELETE', 'LBL_PACKAGE', 'LBL_MODULE_NAME', 'LBL_LABEL', 'LBL_TYPE',
        'LBL_MB_IMPORTABLE', 'LBL_NAV_TAB', 'LBL_BTN_VIEW_FIELDS', 'LBL_BTN_VIEW_LAYOUTS', 'LBL_BTN_VIEW_RELATIONSHIPS',
        'LBL_JS_REMOVE_PACKAGE', 'LBL_JS_REMOVE_MODULE', 'LBL_JS_DEPLOY_PACKAGE', 'LBL_JS_VALIDATE_NAME', 'LBL_JS_VALIDATE_KEY',
        'LBL_JS_VALIDATE_LABEL', 'LBL_JS_VALIDATE_TYPE', 'LBL_JS_VALIDATE_PACKAGE_NAME', 'LBL_MODULE_DEPLOYED',
        'LBL_AJAX_DEPLOYPROGRESS', 'LBL_AJAX_BUILDPROGRESS', 'LBL_FIELDS', 'LBL_LABELS', 'LBL_RELATIONSHIPS', 'LBL_LAYOUTS',
        'LBL_SECTION_PACKAGES', 'LBL_SECTION_PACKAGE', 'LBL_SECTION_MODULE', 'LBL_HOME', 'LBL_SECTION_HELP',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The packages with their modules, the types of a new module, the labels and help of the page.
     */
    public function getPackages(): array
    {
        return $this->inLegacy(function (): array {
            $builder = $this->getBuilder();
            $packages = [];
            foreach ($builder->getPackageList() as $name) {
                $packages[] = ['name' => (string)$name, 'modules' => $this->moduleNames($builder->getPackage($name))];
            }

            $types = [];
            foreach (array_keys(\MBModule::getTypes()) as $type) {
                if ($type !== 'asynctask') {
                    $types[(string)$type] = $this->decode((string)translate('LBL_TYPE_' . strtoupper((string)$type), 'ModuleBuilder'));
                }
            }
            natcasesort($types);

            return [
                'packages' => $packages,
                'types' => array_map(static fn ($value, $label): array => ['value' => (string)$value, 'label' => $label], array_keys($types), $types),
                'labels' => $this->getLabels(),
                'help' => $this->getHelp(),
            ];
        });
    }

    /**
     * A package: its properties and modules (a new one: empty).
     */
    public function getPackage(string $name): array
    {
        return $this->inLegacy(function () use ($name): array {
            if ($name === '') {
                return ['name' => '', 'key' => '', 'author' => '', 'description' => '', 'readme' => '', 'dateModified' => '', 'modules' => []];
            }
            $package = $this->package($name);

            return [
                'name' => (string)$package->name,
                'key' => $this->decode((string)$package->key),
                'author' => $this->decode((string)$package->author),
                'description' => $this->decode((string)$package->description),
                'readme' => $this->decode((string)$package->readme),
                'dateModified' => (string)$package->date_modified,
                'modules' => $this->moduleNames($package),
            ];
        });
    }

    /**
     * Saves a package as the legacy form (a new one without original name; a copy with duplicate):
     * its name, or the error of the legacy checks.
     *
     * @param array<string, mixed> $values name, key, author, description, readme
     */
    public function savePackage(string $original, array $values, bool $duplicate): array
    {
        return $this->inLegacy(function () use ($original, $values, $duplicate): array {
            global $mod_strings;

            $name = trim((string)($values['name'] ?? ''));
            $key = trim((string)($values['key'] ?? ''));
            if (preg_match(self::NAME, $name) !== 1) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_NAME'])];
            }
            if (preg_match(self::NAME, $key) !== 1) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_KEY'])];
            }
            $builder = $this->getBuilder();
            if ($original !== '') {
                $this->package($original);
            }
            // Another package of this name (legacy addToValidateIsInArray of the form).
            if (($original === '' || $duplicate || strcasecmp($original, $name) !== 0)
                && in_array(strtolower($name), array_map('strtolower', $builder->getPackageList()), true)) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_PACKAGE_NAME'])];
            }

            $this->withRequest([
                'module' => 'ModuleBuilder',
                'action' => 'SavePackage',
                'name' => $name,
                'original_name' => $original,
                'duplicate' => $duplicate ? '1' : '',
                'key' => $key,
                'author' => securexss(trim((string)($values['author'] ?? ''))),
                'description' => securexss(trim((string)($values['description'] ?? ''))),
                'readme' => securexss(trim((string)($values['readme'] ?? ''))),
            ], function (): void {
                $this->getController()->action_SavePackage();
            });
            $this->markCaches();

            return ['name' => $name];
        });
    }

    public function deletePackage(string $name): array
    {
        return $this->inLegacy(function () use ($name): array {
            $this->package($name);
            $this->withRequest(['module' => 'ModuleBuilder', 'action' => 'DeletePackage', 'package' => $name], function (): void {
                $this->getController()->action_DeletePackage();
            });
            $this->markCaches();

            return ['name' => $name];
        });
    }

    /**
     * The installable package (legacy Publish: its build) or the project of the package (legacy
     * Export, with its author, description and readme), for the browser to download: file name
     * and content in base64.
     *
     * @param array<string, mixed> $values author, description, readme (Export)
     */
    public function downloadPackage(string $name, bool $export, array $values = []): array
    {
        return $this->inLegacy(function () use ($name, $export, $values): array {
            $package = $this->package($name);
            $zip = $this->withRequest([], static function () use ($package, $export, $values): string {
                if (!$export) {
                    return (string)($package->build(false)['zip'] ?? '');
                }
                $package->author = trim((string)($values['author'] ?? $package->author));
                $package->description = trim((string)($values['description'] ?? $package->description));
                $package->readme = trim((string)($values['readme'] ?? $package->readme));

                return (string)$package->exportProject('', false);
            });
            if ($zip === '' || !is_file($zip)) {
                throw new \RuntimeException('The package was not made');
            }

            return ['fileName' => basename($zip), 'content' => base64_encode((string)file_get_contents($zip))];
        });
    }

    /**
     * A module of a package: its properties (a new one: empty) and the types of a new module.
     */
    public function getModule(string $packageName, string $name): array
    {
        return $this->inLegacy(function () use ($packageName, $name): array {
            $package = $this->package($packageName);
            if ($name === '') {
                return ['name' => '', 'label' => '', 'importable' => false, 'hasTab' => true, 'types' => [], 'package' => $package->name];
            }
            $module = $this->forPackage($package->name)->getMbModule($name);

            return [
                'name' => (string)$module->name,
                'package' => (string)$package->name,
                'label' => $this->decode((string)($module->config['label'] ?? '')),
                'importable' => !empty($module->config['importable']),
                'hasTab' => !empty($module->config['has_tab']),
                // The templates of the module (legacy module.tpl: its types, not changed).
                'types' => array_values(array_map('strval', array_keys((array)$module->mbvardefs->templates))),
            ];
        });
    }

    /**
     * Saves a module of a package as the legacy form (a new one without original name, of a type;
     * a copy with duplicate): its name, or the error of the legacy checks.
     *
     * @param array<string, mixed> $values name, label, type (new module), importable, hasTab
     */
    public function saveModule(string $packageName, string $original, array $values, bool $duplicate): array
    {
        return $this->inLegacy(function () use ($packageName, $original, $values, $duplicate): array {
            global $mod_strings;

            $package = $this->package($packageName);
            $name = trim((string)($values['name'] ?? ''));
            $label = trim((string)($values['label'] ?? ''));
            $type = (string)($values['type'] ?? '');
            if (preg_match(self::NAME, $name) !== 1 || !isAllowedModuleName($name)) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_NAME'])];
            }
            if ($label === '') {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_LABEL'])];
            }
            if ($original === '' && !array_key_exists($type, \MBModule::getTypes())) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_TYPE'])];
            }
            if ($original !== '') {
                $this->forPackage($package->name)->getMbModule($original);
            }
            if (($original === '' || $duplicate || $original !== $name) && in_array($name, $this->moduleNames($package), true)) {
                return ['error' => $this->decode((string)$mod_strings['LBL_JS_VALIDATE_NAME'])];
            }

            $request = [
                'module' => 'ModuleBuilder',
                'action' => 'SaveModule',
                'package' => $package->name,
                'name' => $name,
                'original_name' => $original,
                'duplicate' => $duplicate ? '1' : '',
                'label' => securexss($label),
            ];
            // The checkboxes posted when checked only, as the legacy form.
            if (!empty($values['importable'])) {
                $request['importable'] = '1';
            }
            if (!empty($values['hasTab'])) {
                $request['has_tab'] = '1';
            }
            if ($original === '') {
                $request['type'] = $type;
            }
            $this->withRequest($request, function (): void {
                $this->getController()->action_SaveModule();
            });
            $this->markCaches();

            return ['name' => $name];
        });
    }

    public function deleteModule(string $packageName, string $name): array
    {
        return $this->inLegacy(function () use ($packageName, $name): array {
            $package = $this->package($packageName);
            $this->forPackage($package->name)->getMbModule($name);
            $this->withRequest(['module' => 'ModuleBuilder', 'action' => 'DeleteModule', 'package' => $package->name, 'view_module' => $name], function (): void {
                $this->getController()->action_DeleteModule();
            });
            $this->markCaches();

            return ['name' => $name];
        });
    }

    protected function getBuilder(): \ModuleBuilder
    {
        require_once 'modules/ModuleBuilder/MB/ModuleBuilder.php';

        return new \ModuleBuilder();
    }

    /**
     * A package of Module Builder (only those: their files are read).
     */
    protected function package(string $name): \MBPackage
    {
        $builder = $this->getBuilder();
        if (!in_array($name, $builder->getPackageList(), true)) {
            throw new \InvalidArgumentException('Not a package: ' . $name);
        }

        return $builder->getPackage($name);
    }

    /**
     * The modules of a package (their folders), by name.
     *
     * @return array<int, string>
     */
    protected function moduleNames(\MBPackage $package): array
    {
        $names = [];
        $dir = $package->getPackageDir() . '/modules';
        foreach (is_dir($dir) ? (scandir($dir) ?: []) : [] as $entry) {
            if ($entry[0] !== '.' && is_dir($dir . '/' . $entry)) {
                $names[] = $entry;
            }
        }
        sort($names);

        return $names;
    }

    /**
     * @return array<string, string>
     */
    protected function getLabels(): array
    {
        global $current_language;

        $strings = return_module_language($current_language, 'ModuleBuilder');
        $labels = [];
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }

        return $labels;
    }

    /**
     * The help of the legacy Module Builder screens (main, package and module), as HTML.
     *
     * @return array<string, array<string, string>>
     */
    protected function getHelp(): array
    {
        $help = $GLOBALS['mod_strings']['help'] ?? [];
        $texts = [];
        foreach (['main', 'package', 'module', 'exportcustom'] as $group) {
            foreach (is_array($help[$group] ?? null) ? $help[$group] : [] as $key => $text) {
                if (is_string($text)) {
                    $texts[$group][$key] = $text;
                }
            }
        }

        return $texts;
    }
}
