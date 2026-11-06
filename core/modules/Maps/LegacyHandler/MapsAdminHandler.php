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

namespace App\Module\Maps\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Module\Maps\Service\Geocoding\MapsGeocoding;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * The geocoding screens of the Google Maps administration (legacy jjwg_Maps
 * geocoded_counts, geocode_addresses, reset_geocoding, delete_all_address_cache,
 * export_geocoding_addresses and geocoding_test actions) for the SuiteCRM 8 page, run with the
 * legacy controller and module as the legacy pages run them.
 */
class MapsAdminHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'maps-admin';

    protected const LABELS = [
        'LBL_GEOCODED_COUNTS', 'LBL_GEOCODED_COUNTS_DESCRIPTION', 'LBL_MODULE_HEADING', 'LBL_MODULE_TOTAL_HEADING',
        'LBL_MODULE_RESET_HEADING', 'LBL_CRON_URL', 'LBL_CRON_INSTRUCTIONS', 'LBL_EXPORT_ADDRESS_URL', 'LBL_EXPORT_INSTRUCTIONS',
        'LBL_ADDRESS_CACHE', 'LBL_MAP_LAST_STATUS', 'LBL_MAP_ADDRESS_TEST', 'LBL_MAP_ADDRESS', 'LBL_MAP_PROCESS',
    ];

    protected const VIEW_LABELS = [
        'LBL_MAPS_VIEW_GEOCODE', 'LBL_MAPS_VIEW_GEOCODE_ALL', 'LBL_MAPS_VIEW_EXPORT',
        'LBL_MAPS_VIEW_DELETE_CACHE', 'LBL_MAPS_VIEW_RESET_CONFIRM', 'LBL_MAPS_VIEW_DELETE_CACHE_CONFIRM',
        'LBL_MAPS_VIEW_PROVIDER', 'LBL_MAPS_VIEW_PROVIDER_GOOGLE', 'LBL_MAPS_VIEW_PROVIDER_OSM', 'LBL_MAPS_VIEW_OSM_MODULES',
    ];

    protected MapsGeocoding $geocoding;

    #[Required]
    public function setGeocoding(MapsGeocoding $geocoding): void
    {
        $this->geocoding = $geocoding;
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The geocoded counts of the modules (legacy geocoded_counts page), with the last geocoding
     * status if any.
     */
    public function counts(string $lastStatus = ''): array
    {
        return $this->inLegacy(fn () => $this->countsData($lastStatus));
    }

    /**
     * Geocodes the addresses of a module, or of all the modules (legacy geocode_addresses, run as
     * the cron does: within the geocoding limits of the settings), then the counts.
     */
    public function geocode(string $module): array
    {
        return $this->inLegacy(function () use ($module) {
            return $this->countsData($this->geocodeModules($module === '' ? [] : [$module]));
        });
    }

    /**
     * The scheduled geocoding (MapsGeocodeScheduler): with OpenStreetMap only, within its geocoding
     * hours, the modules geocoded with it. Returns what was done, for the log.
     */
    public function geocodeScheduled(\DateTimeInterface $now): string
    {
        return $this->inLegacy(function () use ($now) {
            \BeanFactory::newBean('jjwg_Maps');
            $settings = $GLOBALS['jjwg_config'] ?? [];
            if (!MapsGeocoding::isOsm($settings)) {
                return 'the map provider is not OpenStreetMap: nothing to do';
            }
            if (!MapsGeocoding::inGeocodingHours($now, $settings)) {
                return 'outside the geocoding hours: nothing to do';
            }
            $lastStatus = $this->geocodeModules([]);

            return 'geocoded, last status: ' . ($lastStatus !== '' ? $lastStatus : 'none');
        });
    }

    /**
     * Geocodes the addresses of modules (all when none) as the legacy geocode_addresses action run
     * by the cron, within the geocoding limits. With OpenStreetMap, only the modules geocoded with
     * it, one after the other. Returns the last geocoding status.
     *
     * @param string[] $modules
     */
    protected function geocodeModules(array $modules): string
    {
        $controller = $this->controller();
        if (!MapsGeocoding::isOsm($controller->settings)) {
            $this->withRequest(['display_module' => $modules[0] ?? '', 'cron' => '1'], fn () => $controller->action_geocode_addresses());

            return (string)($controller->last_status ?? '');
        }

        $allowed = MapsGeocoding::osmModules($controller->settings);
        $modules = $modules === [] ? $allowed : array_values(array_intersect($modules, $allowed));
        if ($modules === []) {
            throw new \InvalidArgumentException('Module not geocoded with OpenStreetMap');
        }
        foreach ($modules as $module) {
            $this->withRequest(['display_module' => $module, 'cron' => '1'], fn () => $controller->action_geocode_addresses());
            if (in_array($controller->last_status ?? '', ['OVER_QUERY_LIMIT', 'REQUEST_DENIED', 'UNKNOWN_ERROR'], true)) {
                break;
            }
        }

        return (string)($controller->last_status ?? '');
    }

    /**
     * Resets the geocoding information of a module (legacy reset_geocoding), then the counts.
     */
    public function reset(string $module): array
    {
        return $this->inLegacy(function () use ($module) {
            $controller = $this->controller();
            if (!in_array($module, $controller->settings['valid_geocode_modules'] ?? [], true)) {
                throw new \InvalidArgumentException('Unknown module');
            }
            // Taken by reference by the bean.
            $display = get_module_info($module);
            $controller->bean->deleteAllGeocodeInfoByBeanQuery($display);

            return $this->countsData();
        });
    }

    /**
     * Deletes all the address cache (legacy delete_all_address_cache), then the counts.
     */
    public function deleteCache(): array
    {
        return $this->inLegacy(function () {
            get_module_info('jjwg_Address_Cache')->deleteAllAddressCache();

            return $this->countsData();
        });
    }

    /**
     * The CSV of the addresses of a module that need geocoding (legacy export_geocoding_addresses).
     */
    public function export(string $module): array
    {
        return $this->inLegacy(function () use ($module) {
            $controller = $this->controller();
            ob_start();
            try {
                // The rows of the legacy action, whose file output ends with an exit.
                $this->withRequest(['display_module' => $module], fn () => $controller->exportAddresses());
            } finally {
                $csv = (string)ob_get_clean();
            }

            return ['csv' => $csv, 'filename' => $module . '_Addresses_' . date('Ymd') . '.csv'];
        });
    }

    /**
     * The geocoding of an address (legacy geocoding_test): the Google answer.
     */
    public function test(string $address): array
    {
        return $this->inLegacy(function () use ($address) {
            $address = trim($address);
            if ($address === '' || strlen($address) > 255) {
                throw new \InvalidArgumentException('Address');
            }
            $controller = $this->controller();
            $result = $controller->bean->getGoogleMapsGeocode($address, true, true);
            if (is_array($result) && isset($result['provider'])) {
                // The OpenStreetMap answer, in the Google format of the maps module.
                $result = ['provider' => $result['provider']] + $result;
            }

            return ['result' => print_r($result, true), 'labels' => $this->labels()];
        });
    }

    protected function countsData(string $lastStatus = ''): array
    {
        global $app_list_strings, $sugar_config;

        $controller = $this->controller();
        $controller->action_geocoded_counts();
        $bean = $controller->bean;
        $strings = return_module_language($GLOBALS['current_language'], 'jjwg_Maps');

        $headings = [];
        foreach ($bean->geocoded_headings ?? [] as $heading) {
            $headings[] = ['key' => (string)$heading, 'label' => $this->decode((string)($strings['LBL_' . $heading] ?? $heading))];
        }
        $modules = [];
        foreach ($controller->settings['valid_geocode_modules'] ?? [] as $module) {
            $modules[] = [
                'module' => (string)$module,
                'label' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? $module)),
                'counts' => array_map('intval', $bean->geocoded_counts[$module] ?? []),
                'total' => (int)($bean->geocoded_module_totals[$module] ?? 0),
            ];
        }

        return [
            'headings' => $headings,
            'modules' => $modules,
            'lastStatus' => preg_match('/^[A-Z_]+$/', $lastStatus) ? $lastStatus : '',
            'exportModules' => array_values(array_intersect(['Accounts', 'Contacts', 'Leads', 'Prospects'], $controller->settings['valid_geocode_modules'] ?? [])),
            'cronUrl' => rtrim((string)($sugar_config['site_url'] ?? ''), '/') . '/legacy/index.php?module=jjwg_Maps&entryPoint=jjwg_Maps&cron=1',
            // The map provider, the modules a Geocode button geocodes and the OpenStreetMap scheduler.
            'provider' => MapsGeocoding::isOsm($controller->settings) ? MapsGeocoding::OSM : MapsGeocoding::GOOGLE,
            'geocodeModules' => MapsGeocoding::isOsm($controller->settings)
                ? MapsGeocoding::osmModules($controller->settings)
                : array_values($controller->settings['valid_geocode_modules'] ?? []),
            'osmScheduler' => $this->schedulerText($controller->settings),
            'labels' => $this->labels(),
        ];
    }

    /**
     * What the OpenStreetMap scheduler does, with its hours.
     *
     * @param array<string, mixed> $settings
     */
    protected function schedulerText(array $settings): string
    {
        $strings = return_module_language($GLOBALS['current_language'], 'jjwg_Maps');
        $from = (int)($settings['geocoding_hour_from'] ?? 0);
        $to = (int)($settings['geocoding_hour_to'] ?? 0);
        $text = (string)($strings[$from === $to ? 'LBL_MAPS_VIEW_OSM_SCHEDULER_ANYTIME' : 'LBL_MAPS_VIEW_OSM_SCHEDULER'] ?? '');

        return $this->decode(strtr($text, [
            '{from}' => sprintf('%02d:00', $from),
            '{to}' => sprintf('%02d:00', $to),
            '{timezone}' => (string)($settings['geocoding_timezone'] ?? 'UTC'),
        ]));
    }

    protected function labels(): array
    {
        $strings = return_module_language($GLOBALS['current_language'], 'jjwg_Maps');
        $labels = $this->viewLabels(self::VIEW_LABELS);
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }

        return $labels;
    }

    /**
     * The legacy controller of the maps module, with an export of the addresses that returns
     * instead of exiting.
     */
    protected function controller(): \jjwg_MapsController
    {
        require_once 'modules/jjwg_Maps/controller.php';
        $GLOBALS['currentModule'] = 'jjwg_Maps';
        $GLOBALS['mod_strings'] = return_module_language($GLOBALS['current_language'], 'jjwg_Maps');

        $controller = new class extends \jjwg_MapsController {
            /** The CSV of action_export_geocoding_addresses, without its headers and exit. */
            public function exportAddresses(): void
            {
                $module = $_REQUEST['display_module'] ?? '';
                $module = in_array($module, $this->settings['valid_geocode_modules'], true) ? $module : $this->settings['valid_geocode_modules'][0];
                $display = get_module_info($module);
                $result = $this->bean->getGeocodeAddressesResult($display->table_name, $this->settings['export_addresses_limit']);
                $addresses = [];
                while ($row = $this->bean->db->fetchByAssoc($result)) {
                    $info = $this->bean->defineMapsAddress($display->object_name, $row);
                    $info = $this->defineMapsAddressCustom($info, $display->object_name, $row);
                    if (!empty($info['address'])) {
                        $addresses[] = trim($info['address'], ' ,;."\'');
                    }
                }
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['address', 'lat', 'lng']);
                foreach (array_unique($addresses) as $address) {
                    fputcsv($out, [$address, '', '']);
                }
                fclose($out);
            }
        };
        // The geocoding of the map provider.
        $this->geocoding->apply($controller);

        return $controller;
    }

    /**
     * Runs a legacy action with request values.
     *
     * @param array<string, string> $values
     */
    protected function withRequest(array $values, callable $callback): mixed
    {
        $request = $_REQUEST;
        $_REQUEST = array_merge($_REQUEST, $values);
        try {
            return $callback();
        } finally {
            $_REQUEST = $request;
        }
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

    /**
     * Labels of the view, from the jjwg_Maps strings.
     *
     * @param string[] $keys
     * @return array<string, string>
     */
    protected function viewLabels(array $keys): array
    {
        $strings = return_module_language($GLOBALS['current_language'] ?? 'en_us', 'jjwg_Maps');
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = html_entity_decode((string)($strings[$key] ?? $key), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $labels;
    }
}
