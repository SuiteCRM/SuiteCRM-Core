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
 * The Languages settings (legacy Administration&action=Languages): the enabled and
 * disabled languages, the default language always enabled; saved as the legacy action
 * (disabled_languages), the current language of the user cannot be disabled.
 */
class LanguagesSettingsPage extends AdminSettingsPage
{
    public function getPageKey(): string
    {
        return 'languages';
    }

    protected function getPage(): array
    {
        global $sugar_config;

        $disabled = $this->getDisabled();
        $default = (string)($sugar_config['default_language'] ?? '');
        $items = [];
        $enabled = [];
        foreach ($sugar_config['languages'] ?? [] as $language => $label) {
            $item = ['value' => (string)$language, 'label' => $this->decode((string)$label)];
            if ($language === $default) {
                $item['locked'] = true;
            }
            $items[] = $item;
            if (!in_array($language, $disabled, true)) {
                $enabled[] = (string)$language;
            }
        }

        return [
            'title' => $this->text('LBL_MANAGE_LANGUAGES'),
            'leave' => false,
            'sections' => [
                $this->section('LBL_MANAGE_LANGUAGES', [
                    $this->field('enabled_languages', '', 'lists', [
                        'items' => $items,
                        'enabledLabel' => $this->text('LBL_ENABLED_LANGS'),
                        'disabledLabel' => $this->text('LBL_DISABLED_LANGS'),
                        'wide' => true,
                    ]),
                ], 'Administration', 'LBL_CONFIG_LANGS_DESC'),
            ],
            'values' => ['enabled_languages' => $enabled],
        ];
    }

    protected function saveValues(array $values): ?string
    {
        global $sugar_config, $current_language;

        $enabled = is_array($values['enabled_languages'] ?? null) ? $values['enabled_languages'] : [];
        $default = (string)($sugar_config['default_language'] ?? '');
        $disabled = [];
        foreach (array_keys($sugar_config['languages'] ?? []) as $language) {
            if ($language !== $default && !in_array($language, $enabled, true)) {
                $disabled[] = (string)$language;
            }
        }

        if (in_array($current_language, $disabled, true)) {
            return $this->text('LBL_CANNOT_DISABLE_CURRENT_LANGUAGE', 'Administration', false);
        }

        $configurator = new \Configurator();
        $configurator->config['disabled_languages'] = implode(',', $disabled);
        $configurator->handleOverride();
        $this->markCache('app-metadata-language-strings-' . $current_language);

        return null;
    }

    /**
     * @return string[]
     */
    protected function getDisabled(): array
    {
        global $sugar_config;

        $disabled = $sugar_config['disabled_languages'] ?? [];
        if (!is_array($disabled)) {
            $disabled = explode(',', (string)$disabled);
        }

        return array_values(array_filter(array_map('strval', $disabled), static fn (string $language): bool => $language !== ''));
    }
}
