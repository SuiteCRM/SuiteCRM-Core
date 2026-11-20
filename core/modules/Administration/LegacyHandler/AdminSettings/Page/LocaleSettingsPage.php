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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;

/**
 * The Locale settings (legacy Administration&action=Locale): default date, time,
 * language and name formats, default currency and separators, export, database collation; saved
 * as the legacy page (Configurator::populateFromPost over its form fields, collation in the
 * database options).
 */
class LocaleSettingsPage extends AdminSettingsPage
{
    protected const SELECTS = [
        'default_date_format',
        'default_time_format',
        'default_language',
        'default_locale_name_format',
        'default_export_charset',
    ];

    protected const TEXTS = [
        'default_currency_name',
        'default_currency_symbol',
        'default_currency_iso4217',
        'default_number_grouping_seperator',
        'default_decimal_seperator',
        'export_delimiter',
    ];

    protected const BOOLEANS = ['disable_export', 'admin_export_only'];

    public function getPageKey(): string
    {
        return 'locale';
    }

    protected function getPage(): array
    {
        global $sugar_config, $locale;

        $options = $this->getOptions();
        $values = [];
        foreach (self::SELECTS as $name) {
            $values[$name] = (string)($sugar_config[$name] ?? '');
        }
        foreach (self::TEXTS as $name) {
            $values[$name] = $this->decode((string)($sugar_config[$name] ?? ''));
        }
        foreach (self::BOOLEANS as $name) {
            $values[$name] = !empty($sugar_config[$name]);
        }

        $nameFormat = [
            $this->field('default_locale_name_format', 'LBL_LOCALE_DEFAULT_NAME_FORMAT', 'select', ['options' => $this->options($options['default_locale_name_format'])]),
        ];
        if ($locale->invalidLocaleNameFormatUpgrade()) {
            $nameFormat[] = $this->field('name_format_upgrade', '', 'html', ['help' => 'ERR_INVALID_LOCALE_NAME_FORMAT_UPGRADE']);
        }

        $sections = [
            $this->section('LBL_LOCALE_DEFAULT_SYSTEM_SETTINGS', [
                $this->field('default_date_format', 'LBL_LOCALE_DEFAULT_DATE_FORMAT', 'select', ['options' => $this->options($options['default_date_format'])]),
                $this->field('default_time_format', 'LBL_LOCALE_DEFAULT_TIME_FORMAT', 'select', ['options' => $this->options($options['default_time_format'])]),
                $this->field('default_language', 'LBL_LOCALE_DEFAULT_LANGUAGE', 'select', ['options' => $this->options($options['default_language'])]),
                ...$nameFormat,
            ]),
            $this->section('LBL_LOCALE_DEFAULT_CURRENCY', [
                $this->field('default_currency_name', 'LBL_LOCALE_DEFAULT_CURRENCY_NAME', 'text'),
                $this->field('default_currency_symbol', 'LBL_LOCALE_DEFAULT_CURRENCY_SYMBOL', 'text'),
                $this->field('default_currency_iso4217', 'LBL_LOCALE_DEFAULT_CURRENCY_ISO4217', 'text'),
                $this->field('default_number_grouping_seperator', 'LBL_LOCALE_DEFAULT_NUMBER_GROUPING_SEP', 'text', ['maxlength' => 1]),
                $this->field('default_decimal_seperator', 'LBL_LOCALE_DEFAULT_DECIMAL_SEP', 'text', ['maxlength' => 1]),
            ]),
            $this->section('EXPORT', [
                $this->field('export_delimiter', 'EXPORT_DELIMITER', 'text'),
                $this->field('default_export_charset', 'EXPORT_CHARSET', 'select', ['options' => $this->options($options['default_export_charset'])]),
                $this->field('disable_export', 'DISABLE_EXPORT', 'bool'),
                $this->field('admin_export_only', 'ADMIN_EXPORT_ONLY', 'bool'),
            ]),
        ];

        if (!empty($options['collation'])) {
            $collation = (string)($sugar_config['dbconfigoption']['collation'] ?? \DBManagerFactory::getInstance()->getDefaultCollation());
            $values['collation'] = $collation;
            // The current collation is shown even when the database does not list it (the
            // default utf8_general_ci on MySQL 8): the legacy page then saved the first one.
            $collations = isset($options['collation'][$collation]) ? $options['collation'] : [$collation => $collation] + $options['collation'];
            $sections[] = $this->section('LBL_LOCALE_DB_COLLATION_TITLE', [
                $this->field('collation', 'LBL_LOCALE_DB_COLLATION', 'select', ['options' => $this->options($collations)]),
            ]);
        }

        return ['title' => $this->text('LBL_MANAGE_LOCALE'), 'sections' => $sections, 'values' => $values];
    }

    /**
     * Saves as the legacy page, after its checks: no value made of a single quote, different
     * thousands and decimal separators.
     */
    protected function saveValues(array $values): ?string
    {
        global $sugar_config, $locale, $current_user;

        $options = $this->getOptions();
        $fields = [];
        foreach (self::SELECTS as $name) {
            if ($this->isOption($values[$name] ?? null, $options[$name])) {
                $fields[$name] = (string)$values[$name];
            }
        }
        foreach (self::TEXTS as $name) {
            // Not trimmed, as the legacy page: a space is a thousands separator.
            $fields[$name] = (string)($values[$name] ?? '');
            if ($fields[$name] === "'") {
                return $this->text('ERR_NO_SINGLE_QUOTE', 'Administration', false) . ' ' . $this->text($this->getLabel($name));
            }
        }
        if ($fields['default_number_grouping_seperator'] === $fields['default_decimal_seperator']) {
            return $this->text('ERR_DECIMAL_SEP_EQ_THOUSANDS_SEP', 'Administration', false);
        }
        foreach (self::BOOLEANS as $name) {
            $fields[$name] = !empty($values[$name]) ? 'true' : 'false';
        }

        $configurator = new \Configurator();
        $collation = (string)($values['collation'] ?? '');
        if ($collation !== '' && $this->isOption($collation, $options['collation'])) {
            if (array_key_exists('collation', $sugar_config['dbconfigoption'] ?? []) && $collation !== $sugar_config['dbconfigoption']['collation']) {
                \DBManagerFactory::getInstance()->disconnect();
                \DBManagerFactory::getInstance()->connect();
            }
            $configurator->config['dbconfigoption']['collation'] = $collation;
        }

        $this->withPost($fields, static fn () => $configurator->populateFromPost());
        $configurator->handleOverride();

        $this->markCache('app-metadata-system-configs');
        $this->markCache('app-metadata-user-preferences-' . $current_user->id);
        if ($locale->invalidLocaleNameFormatUpgrade()) {
            $locale->removeInvalidLocaleNameFormatUpgradeNotice();
        }

        return null;
    }

    /**
     * The options of the selects, value => label.
     *
     * @return array<string, array<string|int, mixed>>
     */
    protected function getOptions(): array
    {
        global $sugar_config, $locale;

        $collations = \DBManagerFactory::getInstance()->getCollationList() ?: [];

        return [
            'default_date_format' => $sugar_config['date_formats'] ?? [],
            'default_time_format' => $sugar_config['time_formats'] ?? [],
            'default_language' => get_languages(),
            'default_locale_name_format' => $locale->getUsableLocaleNameOptions($sugar_config['name_formats'] ?? []),
            'default_export_charset' => $locale->getCharsetSelect(),
            'collation' => $collations ? array_combine($collations, $collations) : [],
        ];
    }

    protected function getLabel(string $name): string
    {
        return [
            'default_currency_name' => 'LBL_LOCALE_DEFAULT_CURRENCY_NAME',
            'default_currency_symbol' => 'LBL_LOCALE_DEFAULT_CURRENCY_SYMBOL',
            'default_currency_iso4217' => 'LBL_LOCALE_DEFAULT_CURRENCY_ISO4217',
            'default_number_grouping_seperator' => 'LBL_LOCALE_DEFAULT_NUMBER_GROUPING_SEP',
            'default_decimal_seperator' => 'LBL_LOCALE_DEFAULT_DECIMAL_SEP',
            'export_delimiter' => 'EXPORT_DELIMITER',
        ][$name] ?? $name;
    }
}
