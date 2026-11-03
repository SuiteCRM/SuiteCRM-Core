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

namespace App\Module\ModuleBuilder\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The Dropdown Editor of the administration (legacy ModuleBuilder dropdowns and dropdown views)
 * for the dropdown editor view, as the legacy editor:
 *  - the lists (DropDownBrowser), the installed languages and the labels of the page;
 *  - the items of a list in a language;
 *  - a list saved in a language as the legacy editor (ParserDropDown::saveDropDown()), a new
 *    list included.
 */
class DropdownEditorHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'dropdown-editor';

    protected const LABELS = [
        'LBL_DROPDOWNEDITOR', 'LBL_BTN_ADDDROPDOWN', 'LBL_DROPDOWN_TITLE_NAME', 'LBL_DROPDOWN_LANGUAGE',
        'LBL_DROPDOWN_ITEMS', 'LBL_DROPDOWN_ITEM_NAME', 'LBL_DROPDOWN_ITEM_LABEL', 'LBL_BTN_SORT_ASCENDING',
        'LBL_BTN_SORT_DESCENDING', 'LBL_BLANK', 'LBL_MB_DELETE', 'LBL_NONE', 'LBL_JS_VALIDATE_NAME', 'LBL_JS_VALIDATE_KEY',
        'LBL_CONFIRM_SAVE_DROPDOWN', 'LBL_SEARCH', 'LBL_BTN_CANCEL', 'LBL_DROPDOWN_EDITOR_NO_MATCH',
        'LBL_DROPDOWN_EDITOR_EXISTS', 'LBL_DROPDOWN_EDITOR_NOT_FOUND', 'LBL_DROPDOWN_EDITOR_SAVED',
        'LBL_DROPDOWN_EDITOR_MOVE_UP', 'LBL_DROPDOWN_EDITOR_MOVE_DOWN',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The lists, the languages and the labels of the page.
     */
    public function getLists(): array
    {
        return $this->inLegacy(function () {
            global $current_language;

            require_once 'modules/ModuleBuilder/Module/DropDownBrowser.php';
            $lists = array_map('strval', array_keys((new \DropDownBrowser())->getNodes()));
            sort($lists, SORT_NATURAL | SORT_FLAG_CASE);

            $languages = [];
            foreach (get_languages() as $key => $label) {
                $languages[] = ['value' => (string)$key, 'label' => $this->decode((string)$label)];
            }

            $strings = return_module_language($current_language, 'ModuleBuilder');
            $labels = [];
            foreach (self::LABELS as $label) {
                $labels[$label] = $this->decode((string)($strings[$label] ?? $label));
            }

            return [
                'lists' => $lists,
                'languages' => $languages,
                'language' => (string)$current_language,
                'labels' => $labels,
            ];
        });
    }

    /**
     * The items of a list in a language, in their order (null: no such list).
     *
     * @return array<int, array{key: string, label: string}>|null
     */
    public function getItems(string $name, string $language): ?array
    {
        return $this->inLegacy(function () use ($name, $language) {
            $strings = return_app_list_strings_language($this->getLanguage($language));
            if (!isset($strings[$name]) || !is_array($strings[$name])) {
                return null;
            }

            $items = [];
            foreach ($strings[$name] as $key => $label) {
                $items[] = ['key' => (string)$key, 'label' => is_scalar($label) ? (string)$label : ''];
            }

            return $items;
        });
    }

    /**
     * Saves a list in a language as the legacy editor. Errors: the label keys of the editor.
     *
     * @param array<int, mixed> $items
     * @return array{saved: bool, error: string}
     */
    public function save(string $name, string $language, array $items, bool $new): array
    {
        return $this->inLegacy(function () use ($name, $language, $items, $new) {
            if ($new && !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return ['saved' => false, 'error' => 'LBL_JS_VALIDATE_NAME'];
            }

            $strings = return_app_list_strings_language($this->getLanguage($language));
            if ($new && isset($strings[$name])) {
                return ['saved' => false, 'error' => 'LBL_DROPDOWN_EDITOR_EXISTS'];
            }
            if (!$new && (!isset($strings[$name]) || !is_array($strings[$name]))) {
                return ['saved' => false, 'error' => 'LBL_DROPDOWN_EDITOR_NOT_FOUND'];
            }

            $pairs = [];
            $keys = [];
            foreach ($items as $item) {
                $key = trim((string)($item['key'] ?? ''));
                $label = (string)($item['label'] ?? '');
                if (($key !== '' && !is_safe_label_key($key)) || isset($keys[$key])) {
                    return ['saved' => false, 'error' => 'LBL_JS_VALIDATE_KEY'];
                }
                $keys[$key] = true;
                $pairs[] = [$key, $label];
            }

            require_once 'modules/ModuleBuilder/parsers/parser.dropdown.php';
            $previous = $_REQUEST['view_package'] ?? null;
            // The legacy editor saves the lists of the studio package.
            $_REQUEST['view_package'] = 'studio';
            try {
                (new \ParserDropDown())->saveDropDown([
                    'dropdown_name' => $name,
                    'dropdown_lang' => $this->getLanguage($language),
                    'view_package' => 'studio',
                    'list_value' => rawurlencode(json_encode($pairs, JSON_UNESCAPED_UNICODE)),
                ]);
            } finally {
                if ($previous === null) {
                    unset($_REQUEST['view_package']);
                } else {
                    $_REQUEST['view_package'] = $previous;
                }
            }

            return ['saved' => true, 'error' => ''];
        });
    }

    /**
     * An installed language, or the current one.
     */
    protected function getLanguage(string $language): string
    {
        global $current_language;

        return $language !== '' && array_key_exists($language, get_languages()) ? $language : (string)$current_language;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

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
}
