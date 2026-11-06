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
 * The Google Maps settings of the maps modules (jjwg_Maps::configuration, config
 * table category jjwg) for the maps of the SuiteCRM 8 views, as the legacy map pages put them in
 * their scripts: the API key, the default center and the marker images.
 */
class MapsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'maps';

    /** Labels of the map widgets (jjwg_Maps strings). */
    protected const VIEW_LABELS = [
        'LBL_MAPS_VIEW_NO_API_KEY', 'LBL_MAPS_VIEW_MAP_LOAD_ERROR', 'LBL_MAPS_VIEW_MARKER_STATUS',
        'LBL_MAPS_VIEW_MARKER_POSITION', 'LBL_MAPS_VIEW_MARKER_ADDRESS', 'LBL_MAPS_VIEW_DRAGGING',
        'LBL_MAPS_VIEW_DRAG_ENDED', 'LBL_MAPS_VIEW_DRAG_HELP', 'LBL_MAPS_VIEW_NO_ADDRESS',
        'LBL_MAPS_VIEW_OSM_LOAD_ERROR', 'LBL_MAPS_VIEW_OSM_MARKER', 'LBL_MAPS_VIEW_OSM_AREA',
    ];

    /** Labels of the map display and quick radius pages (jjwg_Maps strings). */
    protected const MAP_LABELS = [
        'LBL_MAP', 'LBL_MAP_QUICK_RADIUS', 'LBL_MAP_ADDRESS', 'LBL_MODULE_TYPE', 'LBL_DISTANCE', 'LBL_MAP_DISPLAY',
        'LBL_MAP_LEGEND', 'LBL_MAP_GROUP', 'LBL_MAP_CUSTOM_MARKER', 'LBL_MAP_CUSTOM_AREA', 'LBL_MAP_NULL_GROUP_NAME',
        'LBL_ADD_TO_TARGET_LIST', 'LBL_MAP_LAST_STATUS',
    ];

    protected const MAP_VIEW_LABELS = [
        'LBL_MAPS_VIEW_MAP_SHOW', 'LBL_MAPS_VIEW_MAP_NO_MARKER', 'LBL_MAPS_VIEW_MAP_SELECT_ALL',
        'LBL_MAPS_VIEW_MAP_TARGET_LIST', 'LBL_MAPS_VIEW_MAP_ADDED', 'LBL_MAPS_VIEW_MAP_CENTER',
        'LBL_MAPS_VIEW_MAP_ACCOUNT', 'LBL_MAPS_VIEW_MAP_NAME', 'LBL_MAPS_VIEW_MAP_ADDRESS',
        'LBL_MAPS_VIEW_MAP_PHONE', 'LBL_MAPS_VIEW_MAP_USER',
    ];

    protected const AREA_LABELS = [
        'LBL_AREA_EDIT_TITLE', 'LBL_AREA_EDIT_DESC_1', 'LBL_AREA_EDIT_DESC_2', 'LBL_AREA_EDIT_RESET',
        'LBL_AREA_EDIT_USE_AREA_COORDINATES', 'LBL_AREA_EDIT_COORDINATE_RESULTS',
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
     * The settings of the map widgets.
     */
    public function config(): array
    {
        return $this->inLegacy(function () {
            // The bean loads the settings (with their defaults) in $GLOBALS['jjwg_config'].
            $maps = \BeanFactory::newBean('jjwg_Maps');
            $config = $GLOBALS['jjwg_config'] ?? $maps->settings ?? [];

            // The instructions and buttons of the legacy area edit map.
            $areaStrings = return_module_language($GLOBALS['current_language'] ?? 'en_us', 'jjwg_Areas');
            $labels = $this->viewLabels(self::VIEW_LABELS);
            foreach (self::AREA_LABELS as $label) {
                $labels[$label] = rtrim(html_entity_decode((string)($areaStrings[$label] ?? $label), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ': ');
            }

            return [
                // The map provider (Google Maps or OpenStreetMap) and the OpenStreetMap tiles.
                'provider' => MapsGeocoding::isOsm($config) ? MapsGeocoding::OSM : MapsGeocoding::GOOGLE,
                'tileUrl' => (string)($config['osm_tile_url'] ?? ''),
                'attribution' => (string)($config['osm_attribution'] ?? ''),
                'apiKey' => (string)($config['google_maps_api_key'] ?? ''),
                'center' => [
                    'lat' => (float)($config['map_default_center_latitude'] ?? 39.5),
                    'lng' => (float)($config['map_default_center_longitude'] ?? -99.5),
                ],
                'markerImages' => 'legacy/themes/default/images/jjwg_Markers/',
                'labels' => $labels,
            ];
        });
    }

    /**
     * The markers of a map, as the legacy map page (jjwg_MapsController::action_map_markers, run as
     * the page runs it): the center, the records of the display module within the distance (or of a
     * target list), their groups, and the custom markers and areas of a map record.
     *
     * @param array<string, mixed> $params record (a map) | relate_module, relate_id | quick_address;
     *                                     display_module, distance, unit_type | list_id
     */
    public function markers(array $params): array
    {
        return $this->inLegacy(function () use ($params) {
            global $current_language, $app_list_strings;

            require_once 'modules/jjwg_Maps/controller.php';
            $GLOBALS['currentModule'] = 'jjwg_Maps';
            $GLOBALS['mod_strings'] = return_module_language($current_language, 'jjwg_Maps');
            $request = array_intersect_key($params, array_flip([
                'record', 'relate_module', 'relate_id', 'quick_address', 'display_module', 'distance', 'unit_type', 'list_id',
            ]));
            $request = array_map(static fn ($value) => is_scalar($value) ? (string)$value : '', $request);

            $controller = new \jjwg_MapsController();
            $this->geocoding->apply($controller);
            // The legacy page stops with its message when the quick address is not found: checked first.
            if (!empty($request['quick_address'])) {
                $info = $controller->bean->getGoogleMapsGeocode($request['quick_address'], false, true);
                if (empty($info['status']) || $info['status'] !== 'OK') {
                    return ['error' => trim(($GLOBALS['mod_strings']['LBL_MAP_LAST_STATUS'] ?? 'Status') . ': ' . ($info['status'] ?? ''))];
                }
            }

            $previous = $_REQUEST;
            $_REQUEST = array_merge($_REQUEST, $request);
            ob_start();
            try {
                $controller->action_map_markers();
            } finally {
                ob_end_clean();
                $_REQUEST = $previous;
            }

            $bean = $controller->bean;
            $strip = static fn (array $marker): array => array_diff_key($marker, ['html' => true]);

            return [
                'center' => !empty($bean->map_center) ? $strip($bean->map_center) : null,
                'markers' => array_map($strip, $bean->map_markers ?? []),
                'groups' => array_values($bean->map_markers_groups ?? []),
                'customMarkers' => array_map($strip, $bean->custom_markers ?? []),
                'customAreas' => array_map(function (array $area): array {
                    $area = array_diff_key($area, ['html' => true]);
                    $area['points'] = $this->areaPoints((string)($area['coordinates'] ?? ''));

                    return $area;
                }, $bean->custom_areas ?? []),
                'displayModule' => (string)($request['display_module'] ?? ''),
                'unitType' => (string)($request['unit_type'] ?? ''),
                'targetLists' => $this->targetLists(),
                'moduleLabels' => array_map(fn ($label) => $this->decode((string)$label), $app_list_strings['moduleList'] ?? []),
                'labels' => $this->mapLabels(),
            ];
        });
    }

    /**
     * The options of the quick radius map: the modules to map, the units, the default distance.
     */
    public function options(): array
    {
        return $this->inLegacy(function () {
            global $app_list_strings;

            \BeanFactory::newBean('jjwg_Maps');
            $config = $GLOBALS['jjwg_config'] ?? [];
            $modules = [];
            foreach ($config['valid_geocode_modules'] ?? [] as $module) {
                $modules[] = ['value' => (string)$module, 'label' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? $module))];
            }
            $units = [];
            foreach ($app_list_strings['map_unit_type_list'] ?? [] as $value => $label) {
                $units[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
            }

            return [
                'modules' => $modules,
                'units' => $units,
                'distance' => (string)($config['map_default_distance'] ?? ''),
                'unitType' => (string)($config['map_default_unit_type'] ?? 'mi'),
                'labels' => $this->mapLabels(),
            ];
        });
    }

    /**
     * Adds mapped records to a target list, as the legacy map page (action_add_to_target_list):
     * accounts, contacts, leads, targets or users.
     *
     * @param string[] $ids
     */
    public function addToTargetList(string $listId, string $module, array $ids): array
    {
        return $this->inLegacy(function () use ($listId, $module, $ids) {
            $links = ['Accounts' => 'accounts', 'Contacts' => 'contacts', 'Leads' => 'leads', 'Prospects' => 'prospects', 'Users' => 'users'];
            $ids = array_values(array_filter(array_map('strval', $ids), static fn ($id) => is_guid($id)));
            if (!isset($links[$module]) || $ids === [] || !is_guid($listId)) {
                throw new \InvalidArgumentException('Nothing to add to the target list');
            }

            $list = \BeanFactory::getBean('ProspectLists', $listId);
            if (empty($list->id) || !$list->ACLAccess('save')) {
                throw new \InvalidArgumentException('Unknown target list');
            }
            $list->load_relationship($links[$module]);
            foreach ($ids as $id) {
                $list->{$links[$module]}->add($id);
            }

            return ['added' => count($ids), 'list' => $this->decode((string)$list->name)];
        });
    }

    /**
     * The labels of the map pages (jjwg_Maps strings).
     */
    protected function mapLabels(): array
    {
        $strings = return_module_language($GLOBALS['current_language'] ?? 'en_us', 'jjwg_Maps');
        $labels = $this->viewLabels(array_merge(self::VIEW_LABELS, self::MAP_VIEW_LABELS));
        foreach (self::MAP_LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }

        return $labels;
    }

    /**
     * The target lists of the user, to add mapped records to.
     */
    protected function targetLists(): array
    {
        $lists = [];
        foreach (\BeanFactory::newBean('ProspectLists')->get_full_list('name') ?: [] as $list) {
            $lists[] = ['id' => (string)$list->id, 'name' => $this->decode((string)$list->name)];
        }

        return $lists;
    }

    /**
     * The points of area coordinates ("lng,lat,elv", jjwg_Areas::define_polygon).
     */
    protected function areaPoints(string $coordinates): array
    {
        $points = [];
        foreach (preg_split('/[\n\r\s]+/', $coordinates, -1, PREG_SPLIT_NO_EMPTY) as $coordinate) {
            $parts = preg_split('/[\s()]*,[\s()]*/', $coordinate, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])
                && abs((float)$parts[0]) <= 180 && abs((float)$parts[1]) <= 90) {
                $points[] = ['lat' => (float)$parts[1], 'lng' => (float)$parts[0]];
            }
        }

        return $points;
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
