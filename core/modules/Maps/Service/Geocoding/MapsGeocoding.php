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

namespace App\Module\Maps\Service\Geocoding;

/**
 * The map provider of the maps module (jjwg_Maps settings, map_provider): with OpenStreetMap, the
 * legacy maps controller geocodes with Nominatim (jjwg_Maps::$geocoder); with Google Maps, nothing
 * changes. Also the modules geocoded with OpenStreetMap and the hours of its scheduler. Runs in a
 * legacy scope (MapsHandler, MapsAdminHandler).
 */
class MapsGeocoding
{
    public const GOOGLE = 'google';
    public const OSM = 'osm';

    public function __construct(protected NominatimGeocoder $geocoder)
    {
    }

    /**
     * @param array<string, mixed> $settings jjwg_Maps settings
     */
    public static function isOsm(array $settings): bool
    {
        return ($settings['map_provider'] ?? self::GOOGLE) === self::OSM;
    }

    /**
     * Gives the bean of the controller the geocoding of the map provider.
     */
    public function apply(\jjwg_MapsController $controller): void
    {
        $settings = $controller->settings ?? [];
        if (!self::isOsm($settings)) {
            return;
        }

        global $sugar_config;
        $siteUrl = rtrim((string)($sugar_config['site_url'] ?? ''), '/');
        $url = (string)($settings['osm_geocoding_url'] ?? '');
        $userAgent = 'SuiteCRM maps' . ($siteUrl !== '' ? ' (' . $siteUrl . ')' : '');
        $proxy = $this->proxy();
        // The answers by address: the quick radius map geocodes its address twice.
        $answers = [];
        $controller->bean->geocoder = function (string $address) use ($url, $userAgent, $proxy, &$answers): array {
            return $answers[$address] ??= $this->geocoder->geocode($address, $url, $userAgent, $proxy);
        };
    }

    /**
     * The modules geocoded with OpenStreetMap, among the valid geocode modules (all when none set).
     *
     * @param array<string, mixed> $settings jjwg_Maps settings
     * @return string[]
     */
    public static function osmModules(array $settings): array
    {
        $valid = array_values($settings['valid_geocode_modules'] ?? []);
        $modules = $settings['osm_geocode_modules'] ?? [];

        return empty($modules) ? $valid : array_values(array_intersect($valid, $modules));
    }

    /**
     * Whether a time is within the geocoding hours of the scheduler (from-to, may span midnight;
     * the same hour: at any time).
     *
     * @param array<string, mixed> $settings jjwg_Maps settings
     */
    public static function inGeocodingHours(\DateTimeInterface $now, array $settings): bool
    {
        $from = (int)($settings['geocoding_hour_from'] ?? 0);
        $to = (int)($settings['geocoding_hour_to'] ?? 0);
        if ($from === $to) {
            return true;
        }
        try {
            $timezone = new \DateTimeZone((string)($settings['geocoding_timezone'] ?? 'UTC'));
        } catch (\Exception) {
            $timezone = new \DateTimeZone('UTC');
        }
        $hour = (int)\DateTimeImmutable::createFromInterface($now)->setTimezone($timezone)->format('G');

        return $from < $to ? ($hour >= $from && $hour < $to) : ($hour >= $from || $hour < $to);
    }

    /**
     * The proxy of the administration (System > Proxy settings), as the Google geocoding uses it.
     */
    protected function proxy(): ?string
    {
        $admin = \BeanFactory::newBean('Administration');
        $admin->retrieveSettings('proxy');
        $settings = $admin->settings ?? [];
        if (empty($settings['proxy_on']) || empty($settings['proxy_host'])) {
            return null;
        }
        $auth = '';
        if (!empty($settings['proxy_auth'])) {
            $auth = rawurlencode((string)($settings['proxy_username'] ?? '')) . ':' . rawurlencode((string)($settings['proxy_password'] ?? '')) . '@';
        }
        $host = preg_replace('#^https?://#i', '', (string)$settings['proxy_host']);

        return 'http://' . $auth . $host . (!empty($settings['proxy_port']) ? ':' . (int)$settings['proxy_port'] : '');
    }
}
