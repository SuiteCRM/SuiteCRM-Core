<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\SystemConfig\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\SystemConfig\Service\SettingsProviderInterface;

class LegacySettingsProvider extends LegacyHandler implements SettingsProviderInterface
{

    public function getHandlerKey(): string
    {
        return 'legacy-settings-provider';
    }

    public function get(string $category, string $key): ?string
    {
        $this->init();
        $this->startLegacyApp();

        global $sugar_config;

        /** @var \Administration $administration */
        $administration = \BeanFactory::newBean('Administration');
        $administration->retrieveSettings();

        if (empty($category)) {
            $value = $administration->settings[$key] ?? null;
        } else {
            $value = $administration->settings[$category . '_' . $key] ?? null;
        }

        if ($value === null && isset($sugar_config[$key])) {
            // Fallback to sugar_config if the setting is not found in Settings
            $value = $sugar_config[$key];
        }

        $this->close();

        return $value;
    }

    public function save(string $category, string $key, string $value): void
    {
        $this->init();
        $this->startLegacyApp();

        /** @var \Administration $administration */
        $administration = \BeanFactory::newBean('Administration');
        $administration->saveSetting($category, $key, $value);

        $this->close();
    }


}
