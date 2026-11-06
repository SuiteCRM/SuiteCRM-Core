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
 * Maps Settings of the administration (legacy jjwg_Maps config page), as the legacy page: the
 * map provider (Google Maps or OpenStreetMap, each with its own settings: the API key, the map
 * tiles, the geocoding URL, modules and hours), the geocoded modules, the address type and the
 * marker group field of each module, the geocoding, address cache, logic hooks and map settings,
 * each with its default. Saved by jjwg_Maps::saveConfiguration as the legacy form posts it.
 */
class MapsSettingsPage extends AdminSettingsPage
{
    protected const MODULE = 'jjwg_Maps';

    /** The modules of the legacy page with their address types (others: all the types). */
    protected const ADDRESS_TYPES = [
        'Accounts' => ['billing', 'shipping'],
        'Contacts' => ['primary', 'alt'],
        'Leads' => ['primary', 'alt'],
        'Opportunities' => ['billing', 'shipping'],
        'Cases' => ['billing', 'shipping'],
        'Project' => ['billing', 'shipping'],
        'Meetings' => ['flex_relate'],
        'Prospects' => ['primary', 'alt'],
    ];

    protected const TYPE_LABELS = [
        'billing' => 'LBL_BILLING_ADDRESS',
        'shipping' => 'LBL_SHIPPING_ADDRESS',
        'primary' => 'LBL_PRIMARY_ADDRESS',
        'alt' => 'LBL_ALTERNATIVE_ADDRESS',
        'flex_relate' => 'LBL_ADDRESS_FLEX_RELATE',
        'address' => 'LBL_ADDRESS_ADDRESS',
        'custom' => 'LBL_ADDRESS_CUSTOM',
    ];

    /** Settings shown as Enabled / Disabled. */
    protected const SWITCHES = [
        'allow_approximate_location_type', 'address_cache_get_enabled', 'address_cache_save_enabled', 'logic_hooks_enabled',
    ];

    public function getPageKey(): string
    {
        return 'maps';
    }

    protected function getPage(): array
    {
        global $app_list_strings;

        \BeanFactory::newBean('jjwg_Maps');
        $config = $GLOBALS['jjwg_config'] ?? [];
        $defaults = $GLOBALS['jjwg_config_defaults'] ?? [];
        $modules = $config['valid_geocode_modules'] ?? [];

        $switch = $this->options(['0' => $this->text('LBL_DISABLED', self::MODULE), '1' => $this->text('LBL_ENABLED', self::MODULE)]);
        $values = [
            'google_maps_api_key' => (string)($config['google_maps_api_key'] ?? ''),
            'valid_geocode_modules' => implode(', ', $modules),
            'valid_geocode_tables' => implode(', ', $config['valid_geocode_tables'] ?? []),
        ];

        $addressFields = [];
        $groupFields = [];
        foreach (array_unique(array_merge(array_keys(self::ADDRESS_TYPES), $modules)) as $module) {
            if (!in_array($module, $modules, true) && !isset(self::ADDRESS_TYPES[$module])) {
                continue;
            }
            $types = [];
            foreach (self::ADDRESS_TYPES[$module] ?? array_keys(self::TYPE_LABELS) as $type) {
                $types[$type] = $this->text(self::TYPE_LABELS[$type], self::MODULE);
            }
            $label = (string)($app_list_strings['moduleList'][$module] ?? $module);
            $addressFields[] = array_merge($this->field('address_type_' . $module, '', 'select', [
                'options' => $this->options($types),
                'help' => $this->defaultHelp($types[$defaults['geocode_modules_to_address_type'][$module] ?? ''] ?? ''),
            ]), ['label' => $this->decode($label)]);
            $values['address_type_' . $module] = (string)($config['geocode_modules_to_address_type'][$module] ?? '');
            $groupFields[] = array_merge($this->field('grouping_field_' . $module, '', 'text', [
                'maxlength' => 100,
                'help' => $this->defaultHelp((string)($defaults['map_markers_grouping_field'][$module] ?? '')),
            ]), ['label' => $this->decode($label)]);
            $values['grouping_field_' . $module] = (string)($config['map_markers_grouping_field'][$module] ?? '');
        }

        foreach (['geocoding_api_url', 'geocoding_api_secret', 'geocoding_limit', 'google_geocoding_limit', 'export_addresses_limit',
                     'map_markers_limit', 'map_default_center_latitude', 'map_default_center_longitude', 'map_default_unit_type',
                     'map_default_distance', 'map_duplicate_marker_adjustment', 'map_clusterer_grid_size', 'map_clusterer_max_zoom'] as $name) {
            $values[$name] = (string)($config[$name] ?? '');
        }
        foreach (self::SWITCHES as $name) {
            $values[$name] = !empty($config[$name]) ? '1' : '0';
        }
        foreach (['map_provider', 'osm_tile_url', 'osm_attribution', 'osm_geocoding_url', 'geocoding_hour_from',
                     'geocoding_hour_to', 'geocoding_timezone'] as $name) {
            $values[$name] = (string)($config[$name] ?? '');
        }
        $values['osm_geocode_modules'] = implode(', ', $config['osm_geocode_modules'] ?? []);
        // The settings of the chosen map provider only.
        $osm = ['field' => 'map_provider', 'values' => ['osm']];
        $google = ['field' => 'map_provider', 'values' => ['google']];

        $field = fn (string $name, string $label, string $type, array $extra = []): array => $this->field($name, $label, $type, $extra + [
            'help' => $this->defaultHelp(in_array($name, self::SWITCHES, true)
                ? $this->text(!empty($defaults[$name]) ? 'LBL_ENABLED' : 'LBL_DISABLED', self::MODULE)
                : (is_array($defaults[$name] ?? null) ? implode(', ', $defaults[$name]) : (string)($defaults[$name] ?? ''))),
        ], self::MODULE);

        return [
            'title' => $this->text('LBL_JJWG_MAPS_ADMIN_CONFIG_TITLE'),
            'sections' => [
                $this->section('LBL_CONFIG_MAP_PROVIDER_SETTINGS_TITLE', [
                    $field('map_provider', 'LBL_CONFIG_MAP_PROVIDER_TITLE', 'select', [
                        'options' => $this->options([
                            'google' => $this->text('LBL_MAPS_VIEW_PROVIDER_GOOGLE', self::MODULE),
                            'osm' => $this->text('LBL_MAPS_VIEW_PROVIDER_OSM', self::MODULE),
                        ]),
                        'help' => 'LBL_CONFIG_MAP_PROVIDER_DESC',
                    ]),
                ], self::MODULE),
                $this->section('LBL_CONFIG_OSM_SETTINGS_TITLE', [
                    $field('osm_tile_url', 'LBL_CONFIG_OSM_TILE_URL_TITLE', 'text', ['maxlength' => 255, 'wide' => true]),
                    $field('osm_attribution', 'LBL_CONFIG_OSM_ATTRIBUTION_TITLE', 'text', ['maxlength' => 255, 'wide' => true]),
                    $field('osm_geocoding_url', 'LBL_CONFIG_OSM_GEOCODING_URL_TITLE', 'text', ['maxlength' => 255, 'wide' => true]),
                    $field('osm_geocode_modules', 'LBL_CONFIG_OSM_GEOCODE_MODULES_TITLE', 'text', [
                        'maxlength' => 255, 'wide' => true, 'help' => 'LBL_CONFIG_OSM_GEOCODE_MODULES_DESC',
                    ]),
                    $field('geocoding_hour_from', 'LBL_CONFIG_GEOCODING_HOUR_FROM_TITLE', 'number', ['help' => 'LBL_CONFIG_GEOCODING_HOURS_DESC']),
                    $field('geocoding_hour_to', 'LBL_CONFIG_GEOCODING_HOUR_TO_TITLE', 'number'),
                    $field('geocoding_timezone', 'LBL_CONFIG_GEOCODING_TIMEZONE_TITLE', 'select', [
                        'options' => $this->options(array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers())),
                    ]),
                ], self::MODULE) + ['showIf' => [$osm]],
                $this->section('LBL_GOOGLE_API_KEY', [
                    $this->field('google_maps_api_key', 'LBL_GOOGLE_API_KEY', 'text', [
                        'maxlength' => 255,
                        'wide' => true,
                        'links' => [['label' => $this->text('LBL_GET_GOOGLE_API_KEY', self::MODULE), 'href' => 'https://developers.google.com/maps/documentation/javascript/']],
                    ], self::MODULE),
                ], self::MODULE) + ['showIf' => [$google]],
                $this->section('LBL_CONFIG_GEOCODED_MODULES_SETTINGS_TITLE', [
                    $field('valid_geocode_modules', 'LBL_CONFIG_VALID_GEOCODE_MODULES', 'text', ['wide' => true]),
                    $field('valid_geocode_tables', 'LBL_CONFIG_VALID_GEOCODE_TABLES', 'text', ['wide' => true]),
                ], self::MODULE),
                $this->section('LBL_CONFIG_ADDRESS_TYPE_SETTINGS_TITLE', $addressFields, self::MODULE),
                $this->section('LBL_CONFIG_MARKER_GROUP_FIELD_SETTINGS_TITLE', $groupFields, self::MODULE),
                $this->section('LBL_CONFIG_GEOCODING_SETTINGS_TITLE', [
                    $field('geocoding_api_url', 'LBL_CONFIG_GEOCODING_API_URL_TITLE', 'text', ['wide' => true, 'showIf' => [$google]]),
                    $field('geocoding_api_secret', 'LBL_CONFIG_GEOCODING_API_SECRET_TITLE', 'text', ['showIf' => [$google]]),
                    $field('geocoding_limit', 'LBL_CONFIG_GEOCODING_LIMIT_TITLE', 'number'),
                    $field('google_geocoding_limit', 'LBL_CONFIG_GOOGLE_GEOCODING_LIMIT_TITLE', 'number'),
                    $field('allow_approximate_location_type', 'LBL_CONFIG_ALLOW_APPROXIMATE_LOCATION_TYPE_TITLE', 'select', ['options' => $switch]),
                    $field('export_addresses_limit', 'LBL_CONFIG_EXPORT_ADDRESSES_LIMIT_TITLE', 'number'),
                ], self::MODULE),
                $this->section('LBL_CONFIG_ADDRESS_CACHE_SETTINGS_TITLE', [
                    $field('address_cache_get_enabled', 'LBL_CONFIG_ADDRESS_CACHE_GET_ENABLED_TITLE', 'select', ['options' => $switch]),
                    $field('address_cache_save_enabled', 'LBL_CONFIG_ADDRESS_CACHE_SAVE_ENABLED_TITLE', 'select', ['options' => $switch]),
                ], self::MODULE),
                $this->section('LBL_CONFIG_LOGIC_HOOKS_SETTINGS_TITLE', [
                    $field('logic_hooks_enabled', 'LBL_CONFIG_LOGIC_HOOKS_ENABLED_TITLE', 'select', ['options' => $switch]),
                ], self::MODULE),
                $this->section('LBL_CONFIG_MARKER_MAPPING_SETTINGS_TITLE', [
                    $field('map_markers_limit', 'LBL_CONFIG_MAP_MARKERS_LIMIT_TITLE', 'number'),
                    $field('map_default_center_latitude', 'LBL_CONFIG_MAP_DEFAULT_CENTER_LATITUDE_TITLE', 'text'),
                    $field('map_default_center_longitude', 'LBL_CONFIG_MAP_DEFAULT_CENTER_LONGITUDE_TITLE', 'text'),
                    $field('map_default_unit_type', 'LBL_CONFIG_MAP_DEFAULT_UNIT_TYPE_TITLE', 'select', [
                        'options' => $this->options($app_list_strings['map_unit_type_list'] ?? ['mi' => 'mi', 'km' => 'km']),
                        'help' => $this->defaultHelp((string)($app_list_strings['map_unit_type_list'][$defaults['map_default_unit_type'] ?? '']
                            ?? ($defaults['map_default_unit_type'] ?? ''))),
                    ]),
                    $field('map_default_distance', 'LBL_CONFIG_MAP_DEFAULT_DISTANCE_TITLE', 'number'),
                    $field('map_duplicate_marker_adjustment', 'LBL_CONFIG_MAP_DUPLICATE_MARKER_ADJUSTMENT_TITLE', 'text'),
                    $field('map_clusterer_grid_size', 'LBL_CONFIG_MAP_CLUSTER_GRID_SIZE_TITLE', 'number'),
                    $field('map_clusterer_max_zoom', 'LBL_CONFIG_MAP_MARKERS_CLUSTERER_MAX_ZOOM_TITLE', 'number'),
                ], self::MODULE),
            ],
            'values' => $values,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $data = [];
        foreach ($values as $name => $value) {
            $data[$name] = is_bool($value) ? ($value ? '1' : '0') : (is_scalar($value) ? (string)$value : '');
        }
        $maps = \BeanFactory::newBean('jjwg_Maps');

        return $maps->saveConfiguration($data) ? null : $this->text('LBL_ACTION_ERROR');
    }

    protected function defaultHelp(string $value): string
    {
        return $value === '' ? '' : $this->text('LBL_CONFIG_DEFAULT', self::MODULE) . ': ' . $value;
    }
}
