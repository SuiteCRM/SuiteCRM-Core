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
 * The grid layouts of a module in Studio (legacy view.layoutview): the edit view,
 * the detail view and the quick create.
 *  - their panels (label, panel or tab, collapsed), rows and fields as the legacy editor shows
 *    them, the fields not on the layout (toolbox), or as in a layout of their history, or as the
 *    edit view (Copy from EditView);
 *  - saved as the legacy editor saves them (Save: a draft, Save & Deploy): its controller action
 *    on its request (panels, slots of the fields with their label and tab order, Sync to Detail
 *    View), from the layout of the history restored as it restores them, with the labels
 *    changed (legacy property editor of the fields and panels).
 */
class StudioGridLayouts extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'studio-grid-layouts';

    /**
     * The grid layouts (legacy views).
     */
    protected const VIEWS = ['editview', 'detailview', 'quickcreate'];

    /**
     * The slots without a field: a blank cell, the cell taken by the field before it.
     */
    protected const FILLER = '(filler)';
    protected const EMPTY = '(empty)';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * A grid layout: its panels, rows and fields as the legacy editor shows them (or as in the
     * layout of its history sid, or as the edit view), the fields to add, its history.
     *
     * @return array<string, mixed>
     */
    public function getLayout(string $module, string $view, string $sid = '', bool $copyFromEditView = false): array
    {
        return $this->inLegacy(function () use ($module, $view, $sid, $copyFromEditView): array {
            $this->validate($module, $view);

            return $this->withRequest($this->request($module, $view), function () use ($module, $view, $sid, $copyFromEditView): array {
                global $current_language;

                $parser = $this->getParser($module, $view);
                $history = $parser->getHistory();
                if ($sid !== '') {
                    if ($history->restoreByTimestamp($sid) === null) {
                        throw new \InvalidArgumentException('Not in the history of the layout: ' . $sid);
                    }
                    try {
                        $parser = $this->getParser($module, $view);
                    } finally {
                        $history->undoRestore();
                    }
                }

                $type = $this->getType($parser);
                $disabled = false;
                if ($type === MB_DETAILVIEW || $type === MB_QUICKCREATE) {
                    $editView = $this->getParser($module, MB_EDITVIEW);
                    $disabled = $type === MB_DETAILVIEW && $editView->getSyncDetailEditViews();
                    if ($copyFromEditView) {
                        $parser->_viewdefs['panels'] = $editView->convertFromCanonicalForm($editView->_viewdefs['panels'], $editView->_fielddefs);
                        $parser->_fielddefs = $editView->_fielddefs;
                        $parser->setUseTabs($editView->getUseTabs());
                        $parser->setTabDefs($editView->getTabDefs());
                    }
                }

                // The strings of the legacy editor: for the fields those of the module, else those of
                // ModuleBuilder; for the default panel those of ModuleBuilder (its $mod).
                $strings = $GLOBALS['mod_strings'];
                $moduleStrings = return_module_language($current_language, $module);
                $GLOBALS['mod_strings'] = sugarArrayMerge($strings, $moduleStrings);
                try {
                    $data = [
                        'panels' => $this->panels($parser, (string)(sugarArrayMerge($moduleStrings, $strings)['LBL_DEFAULT'] ?? 'Default')),
                        // Called once in a request: it declares a function (cmpLabel).
                        'available' => $this->available($parser),
                    ];
                } finally {
                    $GLOBALS['mod_strings'] = $strings;
                }

                return $data + [
                    'module' => $module,
                    'view' => $view,
                    'type' => $type,
                    'sid' => $sid,
                    'copied' => $copyFromEditView,
                    'maxColumns' => (int)$parser->getMaxColumns(),
                    'nextPanelId' => (int)$parser->getFirstNewPanelId(),
                    'sync' => $type === MB_EDITVIEW ? (bool)$parser->getSyncDetailEditViews() : null,
                    'disabled' => $disabled,
                    'copyFromEditView' => $type === MB_DETAILVIEW || $type === MB_QUICKCREATE,
                    'required' => array_map(static fn ($name): string => trim((string)$name, '"'), $parser->getRequiredFields()),
                    'history' => $this->history($history),
                    'restoreDefault' => $history->getCount() > 0 ? (string)$history->getLast() : '',
                ];
            });
        });
    }

    /**
     * Saves a grid layout as the legacy editor: a draft (Save) or deployed (Save & Deploy), its
     * panels in order (id, label, newTab, panelDefault, rows of maxColumns slots: name, labelKey,
     * tabindex), Sync to Detail View for the edit view, from the layout of its history sid if
     * restored, the labels changed (key => text).
     *
     * @param array<int, array<string, mixed>> $panels
     * @param array<string, string> $labels
     */
    public function saveLayout(string $module, string $view, string $sid, bool $deploy, array $panels, ?bool $sync, array $labels): void
    {
        $this->inLegacy(function () use ($module, $view, $sid, $deploy, $panels, $sync, $labels): void {
            $this->validate($module, $view);
            $params = $this->request($module, $view);
            foreach (array_values($panels) as $index => $panel) {
                $number = $index + 1;
                $id = (string)$panel['id'];
                // As the legacy editor (Studio2.prepareForSave): panel-#-label is the panel id.
                $params['panel-' . $number . '-name'] = (string)($panel['label'] ?? '');
                $params['panel-' . $number . '-label'] = $id;
                $params['tabDefs_' . $id . '_newTab'] = !empty($panel['newTab']) ? '1' : '0';
                $params['tabDefs_' . $id . '_panelDefault'] = ($panel['panelDefault'] ?? '') === 'collapsed' ? 'collapsed' : 'expanded';
                $slot = -1;
                foreach ($panel['rows'] ?? [] as $row) {
                    foreach ($row as $field) {
                        $slot++;
                        $name = (string)$field['name'];
                        $params['slot-' . $number . '-' . $slot . '-name'] = $name;
                        if ($name !== self::FILLER && $name !== self::EMPTY) {
                            if ((string)($field['labelKey'] ?? '') !== '') {
                                $params['slot-' . $number . '-' . $slot . '-label'] = (string)$field['labelKey'];
                            }
                            if ((string)($field['tabindex'] ?? '') !== '') {
                                $params['slot-' . $number . '-' . $slot . '-tabindex'] = (string)$field['tabindex'];
                            }
                        }
                    }
                }
            }

            $this->withRequest($params, function () use ($module, $view, $sid, $deploy, $sync, $labels): void {
                global $current_language;

                $parser = $this->getParser($module, $view);
                if ($this->getType($parser) === MB_DETAILVIEW && $this->getParser($module, MB_EDITVIEW)->getSyncDetailEditViews()) {
                    throw new \InvalidArgumentException('The detail view is synced with the edit view');
                }
                if ($this->getType($parser) === MB_EDITVIEW && $sync !== null) {
                    $_REQUEST['sync_detail_and_edit'] = $_POST['sync_detail_and_edit'] = $sync ? 'true' : 'false';
                }
                if ($sid !== '' && $parser->getHistory()->restoreByTimestamp($sid) === null) {
                    throw new \InvalidArgumentException('Not in the history of the layout: ' . $sid);
                }
                $controller = $this->getController();
                $deploy ? $controller->action_saveAndPublishLayout() : $controller->action_saveLayout();

                // The labels of the fields and panels changed (legacy saveProperty).
                if ($labels !== []) {
                    require_once 'modules/ModuleBuilder/parsers/parser.label.php';
                    $request = [];
                    foreach ($labels as $key => $value) {
                        $request['label_' . $key] = securexss($value);
                    }
                    (new \ParserLabel($module))->handleSave($request, $current_language);
                }
            });
            $this->markCaches();
        });
    }

    /**
     * The panels of the layout, their rows of slots (legacy layoutView.tpl): name, label, label
     * key, tab order.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function panels(\GridLayoutMetaDataParser $parser, string $defaultLabel): array
    {
        $tabDefs = $parser->getTabDefs();
        $panels = [];
        foreach ($parser->getLayout() as $panelId => $panel) {
            $panelId = (string)$panelId;
            $rows = [];
            foreach ($panel as $row) {
                $slots = [];
                foreach ($row as $field) {
                    $labelKey = (string)($field['label'] ?? '');
                    $slots[] = [
                        'name' => (string)($field['name'] ?? ''),
                        'label' => $this->text($labelKey !== '' ? translate($labelKey) : ''),
                        'labelKey' => $labelKey,
                        'tabindex' => (string)($field['tabindex'] ?? ''),
                    ];
                }
                $rows[] = $slots;
            }
            $tabDef = $tabDefs[strtoupper($panelId)] ?? [];
            $panels[] = [
                'id' => $panelId,
                'label' => $panelId === 'default'
                    ? $this->text($defaultLabel)
                    : $this->text(translate(strtoupper($panelId), $parser->getLanguage())),
                'labelEditable' => $panelId !== 'default',
                'newTab' => !empty($tabDef['newTab']),
                'panelDefault' => ($tabDef['panelDefault'] ?? '') === 'collapsed' ? 'collapsed' : 'expanded',
                'rows' => $rows,
            ];
        }

        return $panels;
    }

    /**
     * The fields to add to the layout (legacy toolbox), as the legacy editor sorts them.
     *
     * @return array<int, array<string, string>>
     */
    protected function available(\GridLayoutMetaDataParser $parser): array
    {
        $fields = [];
        foreach ($parser->getAvailableFields() as $field) {
            $labelKey = (string)($field['label'] ?? '');
            $fields[] = [
                'name' => (string)$field['name'],
                'label' => $this->text($labelKey !== '' ? translate($labelKey, $parser->getLanguage()) : (string)$field['name']),
                'labelKey' => $labelKey,
                'tabindex' => '',
            ];
        }

        return $fields;
    }

    /**
     * A label as the legacy editor shows it (sugar_translate): without its colon nor spaces.
     */
    protected function text(mixed $text): string
    {
        return trim(preg_replace('/([:]|\xEF\xBC\x9A)[\s]*$/', '', $this->decode(is_string($text) ? $text : '')));
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
     * The type of the layout (its view, or the type the module maps its view to).
     */
    protected function getType(\GridLayoutMetaDataParser $parser): string
    {
        return strtolower((string)$parser->_view);
    }

    /**
     * Only the grid layouts of the Studio modules (in their Layouts of the legacy Studio).
     */
    protected function validate(string $module, string $view): void
    {
        $views = [];
        foreach ($this->getStudioModule($module)->getLayouts() as $layout) {
            parse_str((string)($layout['action'] ?? ''), $params);
            if (($params['action'] ?? '') === 'editLayout') {
                $views[] = strtolower((string)($params['view'] ?? ''));
            }
        }
        if (!in_array(strtolower($view), $views, true)) {
            throw new \InvalidArgumentException('Not a layout of the module: ' . $view);
        }
    }

    /**
     * The legacy request of the layout.
     *
     * @return array<string, string>
     */
    protected function request(string $module, string $view): array
    {
        return ['module' => 'ModuleBuilder', 'view' => $view, 'view_module' => $module];
    }

    protected function getParser(string $module, string $view): \GridLayoutMetaDataParser
    {
        require_once 'modules/ModuleBuilder/parsers/constants.php';
        require_once 'modules/ModuleBuilder/parsers/ParserFactory.php';
        $parser = \ParserFactory::getParser($view, $module);
        if (!$parser instanceof \GridLayoutMetaDataParser) {
            throw new \InvalidArgumentException('Not a grid layout: ' . $view);
        }

        return $parser;
    }
}
