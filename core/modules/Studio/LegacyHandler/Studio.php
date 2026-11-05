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
 * Studio of the administration (legacy ModuleBuilder&type=studio) for the SuiteCRM 8
 * Studio page:
 *  - the modules of Studio (StudioBrowser) and the components of a module (StudioModule nodes:
 *    labels, fields, relationships, layouts, subpanels), with their legacy actions, help and, for
 *    a subpanel, its module (its icon);
 *  - the help of the legacy Studio screens (ModuleBuilder help strings);
 *  - the labels of a module in a language, as the legacy label editor lists them (ViewLabels),
 *    saved as it saves them (ParserLabel::handleSave);
 *  - the fields of a module, as the legacy fields list (ViewModulefields).
 * Reset Module and Export Customizations: StudioTools; the relationships: StudioRelationships;
 * the field editor: the page shows the legacy one (studio-field-form.ts).
 * The legacy controller marks the SuiteCRM 8 caches to rebuild on each action: so do the saves.
 */
class Studio extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'studio';

    protected const LABELS = [
        'LBL_STUDIO', 'LBL_BTN_EXPORT', 'LBL_LABELS', 'LBL_FIELDS', 'LBL_RELATIONSHIPS', 'LBL_LAYOUTS',
        'LBL_SUBPANELS', 'LBL_RESET_MODULE', 'LBL_BTN_SAVEPUBLISH', 'LBL_BTN_RENAME_MODULE', 'LBL_LANGUAGE',
        'LBL_BTN_ADDFIELD', 'LBL_BTN_EDLABELS', 'LBL_NAME', 'LBL_DROPDOWN_ITEM_LABEL', 'LBL_DATA_TYPE',
        'LBL_HCUSTOM', 'LBL_HDEFAULT', 'LBL_EDIT_FIELDS', 'LBL_SECTION_EDLABELS', 'LBL_QUESTION_EDIT',
        'LBL_QUESTION_MODULE', 'LBL_SEARCH', 'LBL_SECTION_MODULES', 'LBL_BTN_CANCEL', 'LBL_EDIT',
        'LBL_SECTION_HELP', 'LBL_HOME', 'LBL_FILTER', 'LBL_POPUP', 'LBL_DASHLET', 'LBL_QUESTION_LAYOUT',
        'LBL_QUESTION_SUBPANEL', 'LBL_QUESTION_SEARCH', 'LBL_QUESTION_DASHLET', 'LBL_QUESTION_POPUP',
        'LBL_DEFAULT', 'LBL_AVAILABLE', 'LBL_HIDDEN', 'LBL_HISTORY', 'LBL_RESTORE_DEFAULT', 'LBL_MB_RESTORE',
        'LBL_MB_DEFAULT_LAYOUT', 'LBL_WIDTH', 'LBL_LABEL_TITLE', 'LBL_SUBPANEL_TITLE', 'ERROR_MINIMUM_FIELDS',
        'LBL_BTN_CLOSE', 'LBL_SECTION_PROPERTIES', 'LBL_TOOLBOX', 'LBL_NEW_PANEL', 'LBL_NEW_ROW', 'LBL_FILLER',
        'LBL_TAB_ORDER', 'LBL_TABDEF_TYPE', 'LBL_TABDEF_TYPE_OPTION_PANEL', 'LBL_TABDEF_TYPE_OPTION_TAB',
        'LBL_TABDEF_COLLAPSE', 'LBL_SYNC_TO_DETAILVIEW', 'LBL_SYNC_TO_DETAILVIEW_NOTICE', 'LBL_COPY_FROM_EDITVIEW',
        'LBL_BTN_SAVE', 'ERROR_REQUIRED_FIELDS', 'LBL_CURRENT_LAYOUT', 'LBL_DELETE', 'LBL_SYNC_TO_DETAILVIEW_HELP',
        'LBL_TABDEF_TYPE_OPTION_HELP', 'LBL_RESET', 'LBL_REMOVE_CUSTOM', 'LBL_EC_TITLE', 'LBL_EC_NAME', 'LBL_EC_AUTHOR',
        'LBL_EC_DESCRIPTION', 'LBL_EC_CHECKERROR', 'LBL_EC_EXPORTBTN', 'LBL_BTN_ADD_RELATIONSHIP', 'LBL_CUSTOM_RELATIONSHIPS',
        'LBL_REL_NAME', 'LBL_LHS_MODULE', 'LBL_REL_TYPE', 'LBL_RHS_MODULE', 'LBL_NO_RELS', 'LBL_RELATIONSHIP_EDIT',
        'LBL_DROPDOWN_LANGUAGE', 'LBL_MODULE', 'LBL_REL_LABEL', 'LBL_SUBPANEL_FROM', 'LBL_RELATIONSHIP_ONLY', 'LBL_BTN_DELETE',
        'LBL_CONFIRM_RELATIONSHIP_DELETE', 'LBL_BTN_CLONE', 'LBL_CONFIRM_FIELD_DELETE',
        // The texts of the SuiteCRM 8 page that the legacy Studio does not have.
        'LBL_STUDIO_VIEW_MOVE_UP', 'LBL_STUDIO_VIEW_MOVE_DOWN', 'LBL_STUDIO_VIEW_MOVE_TO', 'LBL_STUDIO_VIEW_RESTORED', 'LBL_STUDIO_VIEW_COPIED', 'LBL_STUDIO_VIEW_EXPAND', 'LBL_STUDIO_VIEW_REDUCE', 'LBL_STUDIO_VIEW_REMOVE',
    ];

    /**
     * The texts of the page from the strings of the administration (legacy Studio too).
     */
    protected const ADMINISTRATION_LABELS = ['ML_LBL_DO_NOT_REMOVE_TABLES'];


    /**
     * The help groups of the legacy Studio screens: the wizard (home, module, layouts, subpanels
     * and their buttons), the labels, the fields and the layout editors.
     */
    protected const HELP = [
        'studioWizard', 'labelsHelp', 'fieldsEditor', 'listViewEditor', 'subPanelEditor', 'searchViewEditor',
        'popupListViewEditor', 'layoutEditor', 'exportcustom',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The modules of Studio (by name) and the labels of the page.
     */
    public function getModules(): array
    {
        return $this->inLegacy(function (): array {
            $modules = [];
            foreach ($this->getStudioModules() as $module => $studioModule) {
                $modules[] = ['module' => (string)$module, 'name' => $this->decode((string)$studioModule->name)];
            }
            usort($modules, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

            return ['modules' => $modules, 'labels' => $this->getLabelsOfPage(), 'help' => $this->getHelpOfPage()];
        });
    }

    /**
     * The components of a module, as the legacy Studio tree (name, legacy action, children).
     */
    public function getModule(string $module): array
    {
        return $this->inLegacy(function () use ($module): array {
            if ($this->package !== '') {
                // A module of a Module Builder package: its components as the legacy Module Builder tree.
                $nodes = $this->getMbModule($module)->getNodes();

                return [
                    'module' => $module,
                    'name' => $module,
                    'nodes' => $this->nodes($this->mbFolders(is_array($nodes['children'] ?? null) ? $nodes['children'] : [], $module), []),
                    'labels' => $this->getLabelsOfPage(),
                ];
            }
            $studioModule = $this->getStudioModule($module);
            $nodes = $studioModule->getNodes();

            return [
                'module' => $module,
                'name' => $this->decode((string)$studioModule->name),
                'nodes' => $this->nodes(is_array($nodes['children'] ?? null) ? $nodes['children'] : [], $this->getSubpanelSetup($module)),
                'labels' => $this->getLabelsOfPage(),
            ];
        });
    }

    /**
     * The labels of a module in a language, as the legacy label editor: the labels of its fields,
     * layouts and subpanels (all of them with $all), and those of its custom language files.
     */
    public function getLabels(string $module, string $language, bool $all): array
    {
        return $this->inLegacy(function () use ($module, $language, $all): array {
            global $dictionary;

            $languages = get_languages();
            if (!array_key_exists($language, $languages)) {
                $language = (string)($_SESSION['authenticated_user_language'] ?? '') ?: (string)($GLOBALS['sugar_config']['default_language'] ?? 'en_us');
            }
            if ($this->package !== '') {
                // A module of a Module Builder package: all its strings (legacy view.modulelabels).
                $labels = array_map('strval', $this->getMbModule($module)->getModStrings($language) ?: []);
                ksort($labels);

                return $this->labelsData($language, $languages, $labels);
            }
            $this->getStudioModule($module);

            require_once 'modules/ModuleBuilder/parsers/views/ListLayoutMetaDataParser.php';
            require_once 'modules/ModuleBuilder/parsers/views/GridLayoutMetaDataParser.php';
            require_once 'include/SubPanel/SubPanel.php';

            $objectName = \BeanFactory::getObjectName($module);
            \VardefManager::loadVardef($module, $objectName);

            $names = [];
            foreach ((new \ListLayoutMetaDataParser(MB_LISTVIEW, $module))->getLayout() as $def) {
                if (isset($def['label'])) {
                    $names[$def['label']] = true;
                }
            }
            foreach ($this->getLabelsView()->getVariableMap($module) as $view => $name) {
                foreach ((new \GridLayoutMetaDataParser($view, $module))->getLayout() as $panel) {
                    foreach ($panel as $row) {
                        foreach ($row as $field) {
                            if (isset($field['label'])) {
                                $names[$field['label']] = true;
                            }
                        }
                    }
                }
            }
            foreach (\SubPanel::getModuleSubpanels($module) as $title) {
                $names[$title] = true;
            }
            foreach ($dictionary[$objectName]['fields'] ?? [] as $def) {
                if (isset($def['vname'])) {
                    $names[$def['vname']] = true;
                }
            }

            $labels = [];
            foreach (return_module_language($language, $module, false) as $key => $label) {
                if ($all || isset($names[$key]) || preg_match('/lbl_city|lbl_country|lbl_billing_address|lbl_alt_address|lbl_shipping_address|lbl_postal_code|lbl_state$/si', (string)$key)) {
                    $labels[$key] = (string)$label;
                }
            }
            // The labels of the custom language files of the module, all of them.
            foreach (["custom/modules/$module/language/$language.lang.php", "custom/modules/$module/Ext/Language/$language.lang.ext.php"] as $file) {
                foreach ($this->readModStrings($file) as $key => $label) {
                    $labels[$key] = (string)$label;
                }
            }
            ksort($labels);

            return $this->labelsData($language, $languages, $labels);
        });
    }

    /**
     * The labels of the label editor in a language, with the languages and the label types.
     *
     * @param array<string, string> $languages
     * @param array<string, string> $labels
     */
    protected function labelsData(string $language, array $languages, array $labels): array
    {
        $types = [];
        foreach (return_module_language($GLOBALS['current_language'], 'ModuleBuilder')['labelTypes'] ?? [] as $value => $label) {
            $types[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
        }
        $options = [];
        foreach ($languages as $value => $label) {
            $options[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
        }

        return [
            'language' => $language,
            'languages' => $options,
            'types' => $types,
            'labels' => array_map(static fn (string $key, string $value): array => ['key' => $key, 'value' => $value], array_keys($labels), $labels),
        ];
    }

    /**
     * Saves labels of a module in a language, as the legacy label editor (the changed ones, their
     * texts posted encoded by the legacy entry point).
     *
     * @param array<string, mixed> $labels key => text
     */
    public function saveLabels(string $module, string $language, array $labels): void
    {
        $this->inLegacy(function () use ($module, $language, $labels): void {
            $this->checkModule($module);
            if (!array_key_exists($language, get_languages())) {
                throw new \InvalidArgumentException('Unknown language ' . $language);
            }

            $params = [];
            foreach ($labels as $key => $value) {
                if (is_string($key) && preg_match('/^\w+$/', $key) && is_scalar($value)) {
                    $params['label_' . $key] = securexss((string)$value);
                }
            }
            if (empty($params)) {
                return;
            }

            require_once 'modules/ModuleBuilder/parsers/parser.label.php';
            $this->withRequest($this->packageRequest(), fn () => (new \ParserLabel($module, $this->package))->handleSave($params, $language));
            $this->markCaches();
        });
    }

    /**
     * The fields of a module, as the legacy fields list of Studio: name, label, type and whether
     * it is a custom field.
     */
    public function getFields(string $module): array
    {
        return $this->inLegacy(function () use ($module): array {
            global $dictionary, $current_language;

            $types = return_module_language($current_language, 'ModuleBuilder')['fieldTypes'] ?? [];
            $types['datetime'] = $types['datetimecombo'] ?? 'Datetime';
            if ($this->package !== '') {
                return ['fields' => $this->getMbFields($this->getMbModule($module), $types)];
            }
            $this->getStudioModule($module);

            $objectName = \BeanFactory::getObjectName($module);
            \VardefManager::loadVardef($module, $objectName, true);
            $view = $this->getLabelsView();

            $fields = [];
            foreach ($dictionary[$objectName]['fields'] ?? [] as $def) {
                if (!is_array($def) || !isset($def['name']) || !$view->isValidStudioField($def)) {
                    continue;
                }
                $type = (string)($def['type'] ?? '');
                $fields[] = [
                    'name' => (string)$def['name'],
                    'label' => rtrim($this->decode((string)translate((string)($def['vname'] ?? ''), $module)), ': '),
                    'type' => $this->decode((string)($types[$type] ?? ucfirst($type))),
                    // Custom relate fields are not in the custom table but have their module.
                    'custom' => ($def['source'] ?? '') === 'custom_fields' || isset($def['custom_module']),
                ];
            }

            return ['fields' => $fields];
        });
    }

    /**
     * The fields of a module of a package, as the legacy fields list of Module Builder
     * (view.modulefields): those of its templates, then its own (custom) fields.
     *
     * @param array<string, string> $types the labels of the field types
     * @return array<int, array{name: string, label: string, type: string, custom: bool}>
     */
    protected function getMbFields(\MBModule $mbModule, array $types): array
    {
        global $current_language;

        $mbModule->getVardefs(true);
        $groups = array_reverse($mbModule->mbvardefs->vardefs['fields'] ?? [], true);
        $own = $groups[$mbModule->name] ?? [];
        // A field of a template redefined by the module is shown with the template (as the legacy list).
        foreach ($groups as $group => $fields) {
            if ($group === $mbModule->name) {
                continue;
            }
            foreach (array_keys($fields) as $name) {
                if (array_key_exists($name, $own)) {
                    $groups[$group][$name] = $own[$name];
                    unset($groups[$mbModule->name][$name]);
                }
            }
        }

        $strings = $mbModule->getModStrings($current_language) ?: $mbModule->getModStrings('en_us') ?: [];
        $view = $this->getLabelsView();
        $loaded = [];
        $list = [];
        foreach ($groups as $group => $fields) {
            foreach ($fields as $name => $def) {
                if (!is_array($def) || isset($loaded[$name]) || !$view->isValidStudioField($def)) {
                    continue;
                }
                $loaded[$name] = true;
                $type = (string)($def['type'] ?? '');
                $list[] = [
                    'name' => (string)$name,
                    'label' => rtrim($this->decode((string)($strings[$def['vname'] ?? ''] ?? $name)), ': '),
                    'type' => $this->decode((string)($types[$type] ?? ucfirst($type))),
                    'custom' => $group === $mbModule->name,
                ];
            }
        }

        return $list;
    }

    /**
     * @param array<string, array> $children
     * @param array<string, mixed> $subpanels the subpanel layout defs of the module, by name
     * @return array<int, array{name: string, action: string, help?: string, module?: string, children?: array}>
     */
    protected function nodes(array $children, array $subpanels): array
    {
        $nodes = [];
        foreach ($children as $key => $child) {
            if (!is_array($child)) {
                continue;
            }
            $node = [
                'name' => $this->decode((string)($child['label'] ?? $child['name'] ?? $key)),
                'action' => (string)($child['action'] ?? ''),
            ];
            if (is_string($child['help'] ?? null) && $child['help'] !== '') {
                $node['help'] = $child['help'];
            }
            parse_str($node['action'], $params);
            if (is_string($params['subpanel'] ?? null) && is_string($subpanels[$params['subpanel']]['module'] ?? null)) {
                $node['module'] = $subpanels[$params['subpanel']]['module'];
            }
            if (is_array($child['children'] ?? null)) {
                $node['children'] = $this->nodes($child['children'], $subpanels);
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * The folders of a Module Builder module with the wizard view of their screen, as those of
     * Studio: the legacy tree opens Layouts without a view, and Available Subpanels has no action.
     *
     * @param array<string, array> $children
     * @return array<string, array>
     */
    protected function mbFolders(array $children, string $module): array
    {
        foreach ($children as &$child) {
            if (!is_array($child) || !is_array($child['children'] ?? null)) {
                continue;
            }
            parse_str((string)($child['action'] ?? ''), $params);
            if (($params['view'] ?? '') !== '') {
                continue;
            }
            $view = ($params['action'] ?? '') === 'wizard' ? 'layouts' : 'subpanels';
            $child['action'] = 'module=ModuleBuilder&MB=true&action=wizard&view=' . $view
                . '&view_module=' . $module . '&view_package=' . $this->package;
        }
        unset($child);

        return $children;
    }

    /**
     * The subpanels of a module (layout defs, by name), for their module.
     *
     * @return array<string, mixed>
     */
    protected function getSubpanelSetup(string $module): array
    {
        require_once 'include/SubPanel/SubPanelDefinitions.php';
        $bean = \BeanFactory::newBean($module);
        if (!$bean) {
            return [];
        }
        $definitions = new \SubPanelDefinitions($bean);

        return is_array($definitions->layout_defs['subpanel_setup'] ?? null) ? $definitions->layout_defs['subpanel_setup'] : [];
    }

    /**
     * The legacy label editor view, for its rules (fields of Studio, layouts of a module): its
     * constructor only logs a deprecation.
     */
    protected function getLabelsView(): \ViewLabels
    {
        require_once 'modules/ModuleBuilder/views/view.labels.php';

        return (new \ReflectionClass(\ViewLabels::class))->newInstanceWithoutConstructor();
    }

    /**
     * @return array<string, mixed>
     */
    protected function readModStrings(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $mod_strings = [];
        include $file;

        return is_array($mod_strings) ? $mod_strings : [];
    }

    /**
     * @return array<string, string>
     */
    protected function getLabelsOfPage(): array
    {
        global $current_language;

        $strings = return_module_language($current_language, 'ModuleBuilder');
        $labels = [];
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }
        $strings = return_module_language($current_language, 'Administration');
        foreach (self::ADMINISTRATION_LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }

        return $labels;
    }

    /**
     * The help of the legacy Studio screens, by group (their HTML, from the language files).
     *
     * @return array<string, array<string, string>>
     */
    protected function getHelpOfPage(): array
    {
        $help = $GLOBALS['mod_strings']['help'] ?? [];
        $texts = [];
        foreach (self::HELP as $group) {
            foreach (is_array($help[$group] ?? null) ? $help[$group] : [] as $key => $text) {
                if (is_string($text)) {
                    $texts[$group][$key] = $text;
                }
            }
        }

        return $texts;
    }
}
