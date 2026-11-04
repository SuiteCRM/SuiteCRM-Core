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

namespace App\Module\MailAccounts\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;
use SugarBean;

/**
 * Base of the mail account editors of SuiteCRM 8 (view mail-account-editor): the
 * form of a legacy edit view as data (panels of fields, with the legacy rules that show them), the
 * values of the account, and the legacy code that saves and tests it.
 *
 * A field: name, label, type (text, textarea, password, int, bool, enum, relate, readonly,
 * stringmap; and the types of an editor, as the IMAP folders), required, options (enum), module and
 * idField (relate), show (shown when each of these fields has one of these values, as the toggles
 * of the legacy page), clear (emptied when hidden, as the legacy page), isSet (a write-only value is
 * saved: kept when the field is left empty).
 */
abstract class MailAccountEditor extends LegacyHandler
{
    /** The module of the account (legacy name). */
    abstract protected function module(): string;

    /**
     * A field of the form, labelled as in the legacy edit view (vname of the field).
     *
     * @param array<string, mixed> $extra
     */
    protected function field(SugarBean $bean, string $name, string $type, array $extra = []): array
    {
        $vname = (string)($bean->field_defs[$name]['vname'] ?? $name);

        return array_merge(['name' => $name, 'label' => $this->label($vname), 'type' => $type], $extra);
    }

    /**
     * A drop-down list as options (value, label).
     *
     * @param array<string|int, string> $list
     */
    protected function options(array $list): array
    {
        $options = [];
        foreach ($list as $value => $label) {
            $options[] = ['value' => (string)$value, 'label' => $this->decode($label)];
        }

        return $options;
    }

    /** The name of a related record ('' when it does not exist). */
    protected function relatedName(string $module, string $id): string
    {
        if ($id === '') {
            return '';
        }
        $bean = BeanFactory::getBean($module, $id);

        return $bean instanceof SugarBean && !empty($bean->id) ? $this->decode($bean->get_summary_text()) : '';
    }

    /** A label of the module, or of the application. */
    protected function label(string $key): string
    {
        $label = translate($key, $this->module());

        return rtrim($this->decode(is_string($label) ? $label : $key), ': ');
    }

    protected function decode($value): string
    {
        return html_entity_decode(str_replace('&nbsp;', ' ', (string)$value), ENT_QUOTES);
    }

    /**
     * A key-value list of a stringmap field (JSON) as rows (key, value), as the legacy field shows it.
     *
     * @return array<int, array{key: string, value: string}>
     */
    protected function stringMapRows($json): array
    {
        $map = json_decode(html_entity_decode((string)$json, ENT_QUOTES), true);
        $rows = [];
        foreach (is_array($map) ? $map : [] as $key => $value) {
            $rows[] = ['key' => is_int($key) ? '' : (string)$key, 'value' => is_scalar($value) ? (string)$value : ''];
        }

        return $rows;
    }

    /**
     * Rows (key, value) as the JSON of a stringmap field, as the legacy field saves them
     * (SugarFieldStringmap::getStringMapFromRequest(): empty rows skipped, a row without key keyed by
     * its position).
     */
    protected function stringMapValue($rows): string
    {
        $map = [];
        foreach (is_array($rows) ? array_values($rows) : [] as $index => $row) {
            $key = trim((string)($row['key'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));
            if ($key === '' && $value === '') {
                continue;
            }
            $map[$key !== '' ? $key : $index] = $value;
        }

        return $map ? json_encode($map, JSON_THROW_ON_ERROR) : '';
    }

    protected function isOption($value, array $list): bool
    {
        return is_scalar($value) && array_key_exists((string)$value, $list);
    }

    protected function bool($value): bool
    {
        return $value === true || in_array((string)$value, ['1', 'true', 'on'], true);
    }

    /**
     * Runs a legacy action with the given request values.
     *
     * @param array<string, mixed> $values
     */
    protected function withRequest(array $values, callable $callback): mixed
    {
        $request = $_REQUEST;
        $post = $_POST;
        $_REQUEST = $values;
        $_POST = $values;
        try {
            return $callback();
        } finally {
            $_REQUEST = $request;
            $_POST = $post;
        }
    }

    /**
     * Runs in the legacy application with the strings of the module (the legacy code reads them
     * from $mod_strings); the legacy code does not stop on its warnings, nor print into the
     * response.
     */
    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();
        $ignored = E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED;
        set_error_handler(static fn(int $level): bool => ($level & $ignored) !== 0);
        ob_start();

        global $current_language, $mod_strings;
        $previousStrings = $mod_strings;
        $mod_strings = return_module_language($current_language, $this->module());

        try {
            return $callback();
        } finally {
            $mod_strings = $previousStrings;
            ob_end_clean();
            restore_error_handler();
            $this->close();
        }
    }
}
