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

namespace App\Module\Administration\LegacyHandler\AdminSettings;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * A settings page of the administration (legacy Administration action) shown by the
 * admin settings view: its panels of fields, labels translated, and its values, saved
 * as the legacy page.
 *
 * The fields, by type (value):
 * - bool (bool), number (digits), text, password (never sent: kept when left empty), textarea,
 *   select (one of its options), html (text only, no value);
 * - lists (the enabled items, in order): items moved between an enabled and a disabled list,
 *   some locked in the enabled one;
 * - pairs (list of [left, right]): rows of two selects, added or removed.
 * A field can be shown only when another one has some values (showIf).
 */
abstract class AdminSettingsPage extends LegacyHandler
{
    /**
     * The key of the page in the process options.
     */
    abstract public function getPageKey(): string;

    /**
     * The page, in legacy: title, sections and values.
     *
     * @return array{title: string, sections: array<int, array>, values: array<string, mixed>, leave?: bool}
     */
    abstract protected function getPage(): array;

    /**
     * Saves the values, in legacy.
     *
     * @param array<string, mixed> $values
     * @return string|null the error, null when saved
     */
    abstract protected function saveValues(array $values): ?string;

    public function getHandlerKey(): string
    {
        return 'admin-settings-' . $this->getPageKey();
    }

    /**
     * The page (leave: whether the save goes back to the administration, as most legacy pages).
     */
    public function load(): array
    {
        return $this->inLegacy(fn (): array => $this->getPage() + ['leave' => true]);
    }

    /**
     * @param array<string, mixed> $values
     * @return string|null the error, null when saved
     */
    public function save(array $values): ?string
    {
        return $this->inLegacy(fn (): ?string => $this->saveValues($values));
    }

    /**
     * Runs an action of a button of the page (as a connection test), with the values of the page.
     *
     * @param array<string, mixed> $values
     * @return array{status: string, message?: string, html?: string}
     */
    public function run(string $action, array $values): array
    {
        return $this->inLegacy(fn (): array => $this->runAction($action, $values));
    }

    /**
     * @param array<string, mixed> $values
     * @return array{status: string, message?: string, html?: string}
     */
    protected function runAction(string $action, array $values): array
    {
        throw new \InvalidArgumentException('Unknown action ' . $action);
    }

    /**
     * A section of the page: title (label key), fields, and optionally intro (label key) and
     * actions (name => label key).
     *
     * @param array<int, array> $fields
     * @param array<string, string> $actions
     */
    protected function section(string $title, array $fields, string $module = 'Administration', ?string $intro = null, array $actions = []): array
    {
        $section = ['title' => $this->text($title, $module), 'fields' => $fields];
        if ($intro !== null) {
            $section['intro'] = $this->text($intro, $module, false);
        }
        foreach ($actions as $name => $label) {
            $section['actions'][] = ['name' => $name, 'label' => $this->text($label, $module)];
        }

        return $section;
    }

    /**
     * A field: name, label key, type, and its other properties (help as a label key).
     *
     * @param array<string, mixed> $extra
     */
    protected function field(string $name, string $label, string $type, array $extra = [], string $module = 'Administration'): array
    {
        if (isset($extra['help'])) {
            $extra['help'] = $this->text($extra['help'], $module, false);
        }

        return ['name' => $name, 'label' => $label === '' ? '' : $this->text($label, $module), 'type' => $type] + $extra;
    }

    /**
     * The options of a select (value => label), labels decoded.
     *
     * @param array<string|int, mixed> $list
     * @return array<int, array{value: string, label: string}>
     */
    protected function options(array $list): array
    {
        $options = [];
        foreach ($list as $value => $label) {
            $options[] = ['value' => (string)$value, 'label' => $this->decode(is_scalar($label) ? (string)$label : (string)$value)];
        }

        return $options;
    }

    /**
     * Whether a value is one of the options of a select.
     *
     * @param array<string|int, mixed> $list
     */
    protected function isOption(mixed $value, array $list): bool
    {
        return is_scalar($value) && array_key_exists((string)$value, $list);
    }

    /**
     * A label of a module (or of the application), decoded, without the colon some end with.
     */
    protected function text(string $key, string $module = 'Administration', bool $trim = true): string
    {
        $text = $this->decode((string)translate($key, $module));

        return $trim ? rtrim($text, ': ') : $text;
    }

    /**
     * Runs a legacy save over the given form fields in $_POST (and $_REQUEST), as the legacy form
     * posts them (encoded as the legacy entry point does), as Configurator::populateFromPost() and
     * the legacy pages read them.
     *
     * @param array<string, mixed> $fields
     */
    protected function withPost(array $fields, callable $callback): mixed
    {
        $post = $_POST;
        $request = $_REQUEST;
        $_POST = $this->encode($fields);
        $_REQUEST = $_POST;

        try {
            return $callback();
        } finally {
            $_POST = $post;
            $_REQUEST = $request;
        }
    }

    /**
     * A posted value as the legacy pages saved it: its texts encoded by the legacy entry point
     * (securexss() of clean_incoming_data()), the page decoding them.
     */
    protected function encode(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->encode($item), $value);
        }

        return is_string($value) ? securexss($value) : $value;
    }

    /**
     * Whether a checkbox of the config checked when the option is not set is checked, as the
     * legacy templates ({if isset(x) && x != "true"}unchecked).
     */
    protected function isCheckedByDefault(mixed $value): bool
    {
        return $value === null || $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    /**
     * Marks a cache of the SuiteCRM 8 metadata to rebuild.
     */
    protected function markCache(string $key): void
    {
        require_once 'include/portability/Services/Cache/CacheManager.php';
        (new \CacheManager())->markAsNeedsUpdate($key);
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
            require_once 'modules/Configurator/Configurator.php';

            return $callback();
        } finally {
            $this->close();
        }
    }
}
