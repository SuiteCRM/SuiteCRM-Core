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
 * The list layouts of a module in Studio (legacy view.listview, view.searchview,
 * view.dashlet and view.popupview): the list view, a subpanel, the dashlet list and search, the
 * popup list and search, the quick and advanced filters.
 *  - their columns of fields (Default, Available, Hidden), as the legacy editor shows them, or
 *    as in a layout of their history;
 *  - saved as the legacy editor saves them: its controller action on its request (the fields
 *    of each column, their width), from a layout of the history restored as it restores them,
 *    with the labels changed (legacy property editor) and the subpanel title.
 */
class StudioListLayouts extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'studio-list-layouts';

    /**
     * The list layouts, by legacy view, with the legacy controller action saving them.
     */
    protected const VIEWS = [
        'listview' => 'action_listViewSave',
        'dashlet' => 'action_dashletSave',
        'dashletsearch' => 'action_dashletSave',
        'popuplist' => 'action_popupSave',
        'popupsearch' => 'action_popupSave',
        'basic_search' => 'action_searchViewSave',
        'advanced_search' => 'action_searchViewSave',
    ];

    /**
     * The layouts with a Restore Default (legacy view.listview and view.searchview).
     */
    protected const RESTORE_DEFAULT = ['listview', 'basic_search', 'advanced_search'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * A list layout: its columns of fields as the legacy editor shows them (or as in the layout
     * of its history sid), its subpanel title and its history.
     *
     * @return array<string, mixed>
     */
    public function getLayout(string $module, string $view, string $subpanel, string $sid = ''): array
    {
        return $this->inLegacy(function () use ($module, $view, $subpanel, $sid): array {
            $this->validate($module, $view, $subpanel);

            return $this->withRequest($this->request($module, $view, $subpanel), function () use ($module, $view, $subpanel, $sid): array {
                $parser = $this->getParser($module, $view, $subpanel);
                $history = $parser->getHistory();
                if ($sid !== '') {
                    if ($history->restoreByTimestamp($sid) === null) {
                        throw new \InvalidArgumentException('Not in the history of the layout: ' . $sid);
                    }
                    try {
                        $columns = $this->columns($this->getParser($module, $view, $subpanel), $subpanel);
                    } finally {
                        $history->undoRestore();
                    }
                } else {
                    $columns = $this->columns($parser, $subpanel);
                }
                $lowerView = strtolower($view);

                return [
                    'module' => $module,
                    'view' => $view,
                    'subpanel' => $subpanel,
                    'sid' => $sid,
                    'columns' => $columns,
                    'widths' => substr($lowerView, -6) !== 'search',
                    'minimum' => $lowerView === 'listview',
                    // The title of a subpanel of Studio (the subpanels of a package have their own).
                    'subpanelTitle' => $subpanel !== '' && $this->package === '' ? $this->subpanelTitle($module, $subpanel) : null,
                    'history' => $this->history($history),
                    'restoreDefault' => in_array($lowerView, self::RESTORE_DEFAULT, true) && $history->getCount() > 0
                        ? (string)$history->getLast() : '',
                ];
            });
        });
    }

    /**
     * Saves a list layout as the legacy editor (Save & Deploy): the fields of each column (in
     * the order of the parser columns) with their width, from the layout of its history sid if
     * restored, the labels changed (key => text) and the subpanel title.
     *
     * @param array<int, array<int, string>> $groups
     * @param array<string, string> $widths
     * @param array<string, string> $labels
     */
    public function saveLayout(
        string $module,
        string $view,
        string $subpanel,
        string $sid,
        array $groups,
        array $widths,
        array $labels,
        ?string $subpanelTitle
    ): void {
        $this->inLegacy(function () use ($module, $view, $subpanel, $sid, $groups, $widths, $labels, $subpanelTitle): void {
            global $current_language;

            $this->validate($module, $view, $subpanel);
            $params = $this->request($module, $view, $subpanel);
            foreach (array_values($groups) as $index => $names) {
                $params['group_' . $index] = [];
                foreach ($names as $name) {
                    $params['group_' . $index][] = $name;
                    // As the legacy editor (studiotabs.generateGroupForm): "width=" and the width.
                    $params[strtolower($name) . 'width'] = 'width=' . ($widths[$name] ?? '');
                }
            }
            if ($subpanel !== '' && $subpanelTitle !== null && $this->package === '') {
                $params['subpanel_title'] = securexss($subpanelTitle);
                $params['subpanel_title_key'] = $this->subpanelTitle($module, $subpanel)['key'];
                $params['selected_lang'] = $current_language;
            }

            $this->withRequest($params, function () use ($module, $view, $subpanel, $sid, $labels): void {
                global $current_language;

                if ($sid !== '' && $this->getParser($module, $view, $subpanel)->getHistory()->restoreByTimestamp($sid) === null) {
                    throw new \InvalidArgumentException('Not in the history of the layout: ' . $sid);
                }
                $this->getController()->{self::VIEWS[strtolower($view)]}();

                // The labels changed in the layout (legacy saveProperty), not for a subpanel.
                if ($subpanel === '' && $labels !== []) {
                    require_once 'modules/ModuleBuilder/parsers/parser.label.php';
                    $request = [];
                    foreach ($labels as $key => $value) {
                        $request['label_' . $key] = securexss($value);
                    }
                    (new \ParserLabel($module, $this->package))->handleSave($request, $current_language);
                }
            });
            $this->markCaches();
        });
    }

    /**
     * The columns of the layout (parser columns: Default, Available, Hidden), with their fields
     * as the legacy editor shows them (listView.tpl): label, label key, width.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function columns(\ListLayoutMetaDataParser $parser, string $subpanel): array
    {
        // A module of a package: its strings are in the global ones (set by its legacy parser).
        $language = $this->package !== '' ? '' : $parser->getLanguage();
        $columns = [];
        foreach ($parser->columns as $column => $function) {
            $fields = [];
            foreach ($parser->$function() as $name => $def) {
                $label = (string)($def['label'] ?? '') !== '' ? (string)$def['label'] : (string)($def['vname'] ?? '');
                $text = $label !== '' ? translate($label, $language) : (string)$name;
                // Without its colon nor spaces, as the legacy editor shows it (sugar_translate).
                $text = trim(preg_replace('/([:]|\xEF\xBC\x9A)[\s]*$/', '', $this->decode(is_string($text) ? $text : (string)$name)));
                $width = (string)($def['width'] ?? '');
                $fields[] = [
                    'name' => (string)$name,
                    'label' => $text,
                    // The label the legacy property editor saves (its label, vname or name).
                    'labelKey' => isset($def['label']) ? (string)$def['label'] : ($label !== '' ? $label : (string)$name),
                    // Not the labels of subpanels nor those with Smarty code (legacy view.property).
                    'labelEditable' => $subpanel === '' && !preg_match('/\{.*\}/', $text),
                    'width' => substr($width, -1) === '%' ? substr($width, 0, -1) : $width,
                ];
            }
            $columns[] = [
                'key' => (string)$column,
                'label' => $this->decode((string)($GLOBALS['mod_strings'][$column] ?? $column)),
                'fields' => $fields,
            ];
        }

        return $columns;
    }

    /**
     * The layouts of the history, the most recent first, the last one the default layout
     * (legacy view.history).
     *
     * @return array<int, array{sid: string, date: string}>
     */
    protected function history(\History $history): array
    {
        global $timedate;

        $count = $history->getCount();
        $entries = [];
        $timestamp = $history->getNth(0);
        for ($index = 0; $index < $count && $timestamp > 0; $index++) {
            $entries[] = [
                'sid' => (string)$timestamp,
                'date' => $index + 1 === $count
                    ? $this->decode((string)translate('LBL_MB_DEFAULT_LAYOUT'))
                    : $timedate->to_display_date_time($timedate->fromTimestamp($timestamp)->asDb()),
            ];
            $timestamp = $history->getNext();
        }

        return $entries;
    }

    /**
     * The title of a subpanel and its label (legacy view.listview).
     *
     * @return array{key: string, value: string}
     */
    protected function subpanelTitle(string $module, string $subpanel): array
    {
        require_once 'include/SubPanel/SubPanel.php';
        $subpanels = \SubPanel::getModuleSubpanels($module);
        $label = (string)($subpanels[strtolower($subpanel)] ?? '');
        $key = $label !== '' ? $label : 'LBL_' . strtoupper($subpanel) . '_SUBPANEL_TITLE';
        $title = $label !== '' ? translate($key, $module) : ucfirst($subpanel);

        return ['key' => $key, 'value' => $this->decode(is_string($title) ? $title : $subpanel)];
    }

    /**
     * Only the list layouts of the Studio modules, and their subpanels for a subpanel.
     */
    protected function validate(string $module, string $view, string $subpanel): void
    {
        $this->checkModule($module);
        if (!isset(self::VIEWS[strtolower($view)])) {
            throw new \InvalidArgumentException('Not a list layout: ' . $view);
        }
        if ($subpanel !== '' && $this->package !== '') {
            // A subpanel the module of the package provides (legacy Module Builder tree).
            if (strtolower($view) !== 'listview' || !isset($this->getMbModule($module)->getProvidedSubpanels()[$subpanel])) {
                throw new \InvalidArgumentException('Not a subpanel of the module: ' . $subpanel);
            }
        } elseif ($subpanel !== '') {
            require_once 'include/SubPanel/SubPanel.php';
            if (strtolower($view) !== 'listview' || !isset(\SubPanel::getModuleSubpanels($module)[strtolower($subpanel)])) {
                throw new \InvalidArgumentException('Not a subpanel of the module: ' . $subpanel);
            }
        }
    }

    /**
     * The legacy request of the layout (its parsers read the view from it).
     *
     * @return array<string, string>
     */
    protected function request(string $module, string $view, string $subpanel): array
    {
        $params = ['module' => 'ModuleBuilder', 'view' => $view, 'view_module' => $module] + $this->packageRequest();
        if ($subpanel !== '') {
            $params['subpanel'] = $subpanel;
        }

        return $params;
    }

    protected function getParser(string $module, string $view, string $subpanel): \ListLayoutMetaDataParser
    {
        require_once 'modules/ModuleBuilder/parsers/constants.php';
        require_once 'modules/ModuleBuilder/parsers/ParserFactory.php';

        return \ParserFactory::getParser($view, $module, $this->package !== '' ? $this->package : null, $subpanel !== '' ? $subpanel : null);
    }
}
