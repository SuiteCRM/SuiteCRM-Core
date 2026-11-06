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

namespace App\Module\SugarFeed\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The Activity Streams settings of the administration (legacy SugarFeed AdminSettings) for the
 * activity streams settings view, as the legacy page: the feed on or off, the modules whose
 * changes are posted (their feed logic hooks) and the user feed, and the deletion of all the
 * feed entries.
 */
class ActivityStreamsSettingsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'activity-streams-settings';

    /**
     * The fake module of the posts of the users (SugarFeed).
     */
    protected const USER_FEED = 'UserFeed';

    /**
     * The social feeds the legacy page adds to the modules (view.adminsettings.php): no feed
     * file, only their setting (sugarfeed_module_<name>, read by the My Activity Stream dashlet),
     * their name as label.
     */
    protected const SOCIAL_FEEDS = ['Facebook', 'Twitter'];

    protected const LABELS = [
        'LBL_MODULE_NAME', 'LBL_ENABLE_FEED', 'LBL_ENABLE_MODULE_LIST', 'LBL_ENABLE_USER_FEED', 'LBL_FLUSH_RECORDS',
        'LBL_CONFIRM_DELETE_RECORDS', 'LBL_RECORDS_DELETED',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The settings (feed, modules with a feed, user feed) and the labels of the page (SugarFeed
     * strings).
     */
    public function load(): array
    {
        return $this->inLegacy(function () {
            global $app_list_strings, $current_language;

            require_once 'modules/SugarFeed/SugarFeed.php';
            $admin = \BeanFactory::newBean('Administration');
            $admin->retrieveSettings(false, true);

            $modules = [];
            $userFeed = false;
            foreach (\SugarFeed::getAllFeedModules() as $module) {
                $enabled = ($admin->settings['sugarfeed_module_' . $module] ?? '') == '1';
                if ($module === self::USER_FEED) {
                    $userFeed = $enabled;
                    continue;
                }

                $modules[] = [
                    'module' => (string)$module,
                    'label' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? $module)),
                    'enabled' => $enabled,
                ];
            }

            foreach (self::SOCIAL_FEEDS as $module) {
                $modules[] = [
                    'module' => $module,
                    'label' => $module,
                    'enabled' => ($admin->settings['sugarfeed_module_' . $module] ?? '') == '1',
                ];
            }

            $strings = return_module_language($current_language, 'SugarFeed');
            $labels = [];
            foreach (self::LABELS as $label) {
                $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
            }

            return [
                'enabled' => ($admin->settings['sugarfeed_enabled'] ?? '') == '1',
                'modules' => $modules,
                'userFeed' => $userFeed,
                'labels' => $labels,
            ];
        });
    }

    /**
     * Saves the settings as the legacy page: on, each module feed activated or disabled (logic
     * hooks and setting); off, the hooks of all the feeds removed and their settings kept.
     *
     * @param array<string, mixed> $modules module => bool
     */
    public function save(bool $enabled, array $modules, bool $userFeed): void
    {
        $this->inLegacy(static function () use ($enabled, $modules, $userFeed) {
            require_once 'modules/SugarFeed/SugarFeed.php';

            // The cleanup logic hook of the feed, as the legacy page.
            check_logic_hook_file('Users', 'after_login', [
                1,
                'SugarFeed old feed entry remover',
                'modules/SugarFeed/SugarFeedFlush.php',
                'SugarFeedFlush',
                'flushStaleEntries'
            ]);

            $admin = \BeanFactory::newBean('Administration');
            $feedModules = \SugarFeed::getAllFeedModules();

            if ($enabled) {
                // The social feeds too, as the legacy page posts them with the modules (when the
                // feed is turned off, the legacy page leaves their settings as they are).
                foreach (array_merge($feedModules, self::SOCIAL_FEEDS) as $module) {
                    $active = $module === self::USER_FEED ? $userFeed : !empty($modules[$module]);
                    if ($active) {
                        \SugarFeed::activateModuleFeed($module);
                    } else {
                        \SugarFeed::disableModuleFeed($module);
                    }
                }
                $admin->saveSetting('sugarfeed', 'enabled', '1');
            } else {
                $admin->saveSetting('sugarfeed', 'enabled', '0');
                foreach ($feedModules as $module) {
                    \SugarFeed::disableModuleFeed($module, false);
                }
            }

            $admin->retrieveSettings(false, true);
            \SugarFeed::flushBackendCache();
        });
    }

    /**
     * Deletes all the feed entries (marked deleted), as the legacy page.
     */
    public function deleteEntries(): void
    {
        $this->inLegacy(static function () {
            \DBManagerFactory::getInstance()->query("UPDATE sugarfeed SET deleted = '1'");
        });
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
