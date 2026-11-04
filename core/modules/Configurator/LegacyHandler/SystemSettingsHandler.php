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

namespace App\Module\Configurator\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The System Settings of the administration (legacy Configurator&action=EditView) for
 * the SuiteCRM 8 view, as the legacy page and its SaveConfig action: the user interface, advanced
 * and logger options (config), the system name and the proxy (settings) and the company logo.
 */
class SystemSettingsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'system-settings';

    /**
     * The checkbox options of the config, with whether checked when not set (legacy template).
     */
    protected const BOOLEANS = [
        'lock_homepage' => false,
        'lock_subpanels' => false,
        'use_real_names' => false,
        'calculate_response_time' => false,
        'default_module_favicon' => false,
        'disable_convert_lead' => false,
        'enable_action_menu' => true,
        'enable_line_editing_list' => true,
        'enable_line_editing_detail' => true,
        'hide_subpanels' => false,
        'verify_client_ip' => false,
        'log_memory_usage' => false,
        'dump_slow_queries' => false,
        'stack_trace_errors' => false,
        'developerMode' => false,
        'stackTrace' => false,
    ];

    /**
     * The number options of the config.
     */
    protected const NUMBERS = [
        'list_max_entries_per_page',
        'list_max_entries_per_subpanel',
        'list_max_entries_per_modal',
        'max_dashlets_homepage',
        'slow_query_time_msec',
        'upload_maxsize',
        'vcal_time',
        'import_max_records_total_limit',
    ];

    /**
     * The select options of the config, with their value when not set (legacy view).
     */
    protected const SELECTS = [
        'listview_pagination_type' => 'pagination',
        'subpanel_pagination_type' => 'pagination',
        'record_modal_pagination_type' => 'pagination',
        'dashlet_auto_refresh_min' => '30',
        'snooze_alert_timer' => '300',
        'lead_conv_activity_opt' => '',
    ];

    /**
     * The logger options, form name => path in the logger config (populateFromPost sets them
     * from these names).
     */
    protected const LOGGER = [
        'logger_file_name' => ['file', 'name'],
        'logger_file_ext' => ['file', 'ext'],
        'logger_file_suffix' => ['file', 'suffix'],
        'logger_file_maxSize' => ['file', 'maxSize'],
        'logger_file_dateFormat' => ['file', 'dateFormat'],
        'logger_level' => ['level'],
        'logger_file_maxLogs' => ['file', 'maxLogs'],
    ];

    /**
     * The logger config when not set (Configurator::parseLoggerSettings).
     */
    protected const LOGGER_DEFAULTS = [
        'file' => ['ext' => '.log', 'name' => 'sugarcrm', 'dateFormat' => '%c', 'maxSize' => '10MB', 'maxLogs' => 10, 'suffix' => ''],
        'level' => 'fatal',
    ];

    /**
     * The settings of the page (category, name), saved by Administration::saveConfig() in the
     * legacy page.
     */
    protected const SETTINGS = [
        'system_name' => ['system', 'name'],
        'proxy_on' => ['proxy', 'on'],
        'proxy_host' => ['proxy', 'host'],
        'proxy_port' => ['proxy', 'port'],
        'proxy_auth' => ['proxy', 'auth'],
        'proxy_username' => ['proxy', 'username'],
        'proxy_password' => ['proxy', 'password'],
    ];

    /**
     * The folder of the uploaded logo, as UploadFileCheck.php.
     */
    protected const LOGO_UPLOAD_DIR = 'tmp_logo_company_upload';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The options (the proxy password only as set or not), the options of the selects, the
     * logo, and the labels.
     */
    public function load(): array
    {
        return $this->inLegacy(function () {
            global $sugar_config, $current_language;

            $admin = \BeanFactory::newBean('Administration');
            $admin->retrieveSettings(false, true);
            $settings = $admin->settings;

            $values = [];
            foreach (self::BOOLEANS as $name => $default) {
                $values[$name] = $this->isChecked($sugar_config, $name, $default);
            }
            foreach (self::NUMBERS as $name) {
                $values[$name] = (string)($sugar_config[$name] ?? '');
            }
            foreach (self::SELECTS as $name => $default) {
                $value = $sugar_config[$name] ?? '';
                $values[$name] = (string)(($value === '' || $value === null) ? $default : $value);
            }

            $logger = is_array($sugar_config['logger'] ?? null) && !empty($sugar_config['logger'])
                ? $sugar_config['logger'] : self::LOGGER_DEFAULTS;
            foreach (self::LOGGER as $name => $path) {
                $value = $logger;
                foreach ($path as $key) {
                    $value = is_array($value) ? ($value[$key] ?? '') : '';
                }
                $values[$name] = is_scalar($value) ? (string)$value : '';
            }

            $values['system_name'] = $this->decode((string)($settings['system_name'] ?? ''));
            $values['proxy_on'] = !empty($settings['proxy_on']);
            $values['proxy_host'] = (string)($settings['proxy_host'] ?? '');
            $values['proxy_port'] = (string)($settings['proxy_port'] ?? '');
            $values['proxy_auth'] = !empty($settings['proxy_auth']);
            $values['proxy_username'] = (string)($settings['proxy_username'] ?? '');
            $values['proxy_password'] = '';

            $options = [];
            foreach ($this->getOptions() as $name => $list) {
                $options[$name] = [];
                foreach ($list as $value => $label) {
                    $options[$name][] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
                }
            }

            $labels = [];
            foreach (return_module_language($current_language, 'Configurator') as $key => $label) {
                if (is_string($label)) {
                    $labels[$key] = $this->decode($label);
                }
            }

            return [
                'values' => $values,
                'proxyPasswordSet' => (string)($settings['proxy_password'] ?? '') !== '',
                'options' => $options,
                'loggerVisible' => !isset($sugar_config['logger_visible']) || !empty($sugar_config['logger_visible']),
                'logo' => $this->getLogoUrl(),
                'labels' => $labels,
            ];
        });
    }

    /**
     * Saves the page as its SaveConfig action: the company logo and the options of the config
     * (Configurator::saveConfig over the form fields), then the system name and the proxy, and
     * clears the template, language and theme caches. Nothing is saved when the logo is not a
     * valid image.
     *
     * @param array<string, mixed> $values form field => value
     * @param array{content?: string}|null $logo the new logo (base64 content)
     * @return string|null the error (label key) of the save, null when saved
     */
    public function save(array $values, ?array $logo): ?string
    {
        return $this->inLegacy(function () use ($values, $logo) {
            global $sugar_config;

            require_once 'modules/Configurator/Configurator.php';
            require_once 'modules/Administration/QuickRepairAndRebuild.php';

            $admin = \BeanFactory::newBean('Administration');
            $admin->retrieveSettings(false, true);
            $settings = $this->getSettings($values, (string)($admin->settings['proxy_password'] ?? '') !== '');
            $post = $this->getConfigFields($values, !isset($sugar_config['logger_visible']) || !empty($sugar_config['logger_visible']));
            if ($settings === null || $post === null) {
                return 'LBL_ACTION_ERROR';
            }

            $logoFile = null;
            if (!empty($logo['content'])) {
                $logoFile = $this->storeLogo((string)$logo['content']);
                if ($logoFile === null) {
                    return 'LBL_ALERT_TYPE_IMAGE';
                }
                $post['company_logo'] = $logoFile;
            }

            $request = $_POST;
            $_POST = $post;
            try {
                $configurator = new \Configurator();
                if ($configurator->saveConfig() === false) {
                    return 'LBL_ALERT_TYPE_IMAGE';
                }
            } finally {
                $_POST = $request;
                if ($logoFile !== null) {
                    @unlink('upload://' . $logoFile);
                }
            }

            // Administration::saveConfig() reads the settings from $_GET only since SuiteCRM 8
            // (core commit "Fix email settings not saving"): they are saved here.
            foreach ($settings as $name => $value) {
                [$category, $key] = self::SETTINGS[$name];
                $admin->saveSetting($category, $key, $value);
            }
            $admin->retrieveSettings(false, true);

            $repair = new \RepairAndClear();
            $repair->clearTpls();
            $repair->clearLanguageCache();
            $repair->clearSmarty();
            $repair->clearThemeCache();

            return null;
        });
    }

    /**
     * The form fields of the config saved by Configurator::populateFromPost(), as the legacy
     * form posts them: checkboxes as 'true' or 'false', the selects with one of their options,
     * and the system name.
     *
     * @param array<string, mixed> $values
     * @return array<string, string>|null null when a number is not valid (an empty one is not
     *     saved, as a number not in the config by populateFromPost)
     */
    protected function getConfigFields(array $values, bool $logger): ?array
    {
        // The system name is in the config too (shown by SuiteCRM 8), as in the settings.
        $fields = ['system_name' => trim((string)($values['system_name'] ?? ''))];
        foreach (array_keys(self::BOOLEANS) as $name) {
            if ($name === 'stackTrace' && !$logger) {
                continue;
            }
            $fields[$name] = !empty($values[$name]) ? 'true' : 'false';
        }

        foreach (self::NUMBERS as $name) {
            $value = trim((string)($values[$name] ?? ''));
            if ($value === '') {
                continue;
            }
            if (!ctype_digit($value)) {
                return null;
            }
            $fields[$name] = $value;
        }

        $options = $this->getOptions();
        foreach (array_keys(self::SELECTS) as $name) {
            $value = (string)($values[$name] ?? '');
            if (array_key_exists($value, $options[$name])) {
                $fields[$name] = $value;
            }
        }

        if ($logger) {
            foreach (array_keys(self::LOGGER) as $name) {
                $value = trim((string)($values[$name] ?? ''));
                if (isset($options[$name]) && !array_key_exists($value, $options[$name])) {
                    continue;
                }
                $fields[$name] = $value;
            }
        }

        return $fields;
    }

    /**
     * The settings to save: the proxy password only when given (it is not sent to the page),
     * the proxy host and port required with the proxy, the user and password with its
     * authentication.
     *
     * @param array<string, mixed> $values
     * @return array<string, string>|null null when a required one is missing
     */
    protected function getSettings(array $values, bool $passwordSet): ?array
    {
        $settings = [
            'system_name' => trim((string)($values['system_name'] ?? '')),
            'proxy_on' => !empty($values['proxy_on']) ? '1' : '0',
            'proxy_host' => trim((string)($values['proxy_host'] ?? '')),
            'proxy_port' => trim((string)($values['proxy_port'] ?? '')),
            'proxy_auth' => !empty($values['proxy_auth']) ? '1' : '0',
            'proxy_username' => trim((string)($values['proxy_username'] ?? '')),
        ];
        $password = trim((string)($values['proxy_password'] ?? ''));
        if ($password !== '') {
            $settings['proxy_password'] = $password;
        }

        if ($settings['proxy_on'] === '1') {
            if ($settings['proxy_host'] === '' || !ctype_digit($settings['proxy_port'])) {
                return null;
            }
            if ($settings['proxy_auth'] === '1' && ($settings['proxy_username'] === '' || ($password === '' && !$passwordSet))) {
                return null;
            }
        }

        return $settings;
    }

    /**
     * The options of the selects, value => label.
     *
     * @return array<string, array<string|int, string>>
     */
    protected function getOptions(): array
    {
        global $app_list_strings;

        require_once 'modules/Leads/Lead.php';
        require_once 'include/SugarLogger/SugarLogger.php';

        return [
            'listview_pagination_type' => $app_list_strings['listview_pagination_type'] ?? [],
            'subpanel_pagination_type' => $app_list_strings['subpanel_pagination_type'] ?? [],
            'record_modal_pagination_type' => $app_list_strings['record_modal_pagination_type'] ?? [],
            'dashlet_auto_refresh_min' => $app_list_strings['dashlet_auto_refresh_options_admin'] ?? [],
            'snooze_alert_timer' => $app_list_strings['snooze_alert_timer'] ?? [],
            'lead_conv_activity_opt' => \Lead::getActivitiesOptions(),
            'logger_file_suffix' => \SugarLogger::$filename_suffix,
            'logger_level' => \LoggerManager::getLoggerLevels(),
        ];
    }

    /**
     * Stores the uploaded logo with the uploads, as UploadFileCheck.php, when it is an image.
     * Without extension: verify_uploaded_image() then checks its type (JPEG, PNG or GIF) only.
     *
     * @return string|null its path in the uploads
     */
    protected function storeLogo(string $content): ?string
    {
        global $sugar_config;

        $data = base64_decode(preg_replace('/^data:[^,]*,/', '', $content), true);
        $maxSize = (int)($sugar_config['upload_maxsize'] ?? 0);
        if ($data === false || $data === '' || ($maxSize > 0 && strlen($data) > $maxSize)) {
            return null;
        }

        require_once 'include/upload_file.php';
        \UploadStream::ensureDir('upload://' . self::LOGO_UPLOAD_DIR);
        $file = self::LOGO_UPLOAD_DIR . '/company_logo_' . bin2hex(random_bytes(8));
        if (file_put_contents('upload://' . $file, $data) === false) {
            return null;
        }

        if (!verify_uploaded_image('upload://' . $file)) {
            @unlink('upload://' . $file);

            return null;
        }

        return $file;
    }

    /**
     * The URL of the company logo, from the SuiteCRM 8 view: the uploaded one (saved in the
     * custom images of the default theme) or the default one.
     */
    protected function getLogoUrl(): string
    {
        $path = \SugarThemeRegistry::current()->getImageURL('company_logo.png', false);
        $custom = 'custom/' . \SugarThemeRegistry::current()->getDefaultImagePath() . '/company_logo.png';
        if (is_file($custom)) {
            $path = $custom . '?v=' . filemtime($custom);
        }

        return 'legacy/' . ltrim((string)$path, '/');
    }

    /**
     * Whether a checkbox option of the config is checked, as the legacy template.
     *
     * @param array<string, mixed> $config
     */
    protected function isChecked(array $config, string $name, bool $default): bool
    {
        if (!array_key_exists($name, $config)) {
            return $default;
        }

        $value = $config[$name];

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
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
