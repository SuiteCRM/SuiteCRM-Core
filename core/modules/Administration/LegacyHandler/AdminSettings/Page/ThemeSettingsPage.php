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
 * The Themes settings (legacy Administration&action=ThemeSettings): the enabled and
 * disabled themes and the default one (enabled); saved as the legacy page (disabled_themes,
 * default_theme), the SuiteCRM 8 theme images rebuilt.
 */
class ThemeSettingsPage extends AdminSettingsPage
{
    public function getPageKey(): string
    {
        return 'themes';
    }

    protected function getPage(): array
    {
        global $sugar_config;

        $items = [];
        $enabled = [];
        foreach (\SugarThemeRegistry::allThemesDefs() as $theme => $definition) {
            $items[] = ['value' => (string)$theme, 'label' => $this->decode((string)($definition['name'] ?? $theme))];
            if (!empty($definition['enabled'])) {
                $enabled[] = (string)$theme;
            }
        }

        return [
            'title' => $this->text('LBL_THEME_SETTINGS'),
            'leave' => false,
            'sections' => [
                $this->section('LBL_THEME_SETTINGS', [
                    $this->field('default_theme', 'DEFAULT_THEME', 'select', ['options' => $this->options(\SugarThemeRegistry::allThemes())]),
                    $this->field('enabled_themes', '', 'lists', [
                        'items' => $items,
                        // The default theme cannot be disabled.
                        'lockedBy' => 'default_theme',
                        'enabledLabel' => $this->text('LBL_ENABLED'),
                        'disabledLabel' => $this->text('LBL_DISABLED'),
                        'wide' => true,
                    ]),
                ]),
            ],
            'values' => [
                'default_theme' => (string)($sugar_config['default_theme'] ?? ''),
                'enabled_themes' => $enabled,
            ],
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $themes = \SugarThemeRegistry::allThemes();
        $default = (string)($values['default_theme'] ?? '');
        $enabled = is_array($values['enabled_themes'] ?? null) ? $values['enabled_themes'] : [];
        if (!array_key_exists($default, $themes) || !in_array($default, $enabled, true)) {
            return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
        }

        $disabled = array_values(array_diff(array_map('strval', array_keys($themes)), $enabled));

        $configurator = new \Configurator();
        $configurator->config['disabled_themes'] = implode(',', $disabled);
        $configurator->config['default_theme'] = $default;
        $configurator->handleOverride();
        $this->markCache('app-metadata-theme-images');

        return null;
    }
}
