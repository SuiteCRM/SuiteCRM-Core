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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Intl\Countries;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Geocodes an address with OpenStreetMap (Nominatim search API) and answers as the Google geocoding
 * API does (status, results[0].geometry.location / location_type, formatted_address), so that the
 * geocoding of the maps module (jjwg_Maps::getGoogleMapsGeocode(): address cache, limits, statuses)
 * runs unchanged.
 *
 * Nominatim usage policy (https://operations.osmfoundation.org/policies/nominatim/): at most one
 * request per second, an identifying User-Agent, results cached (the address cache of the module).
 *
 * An address ending with an ISO 3166 alpha-3 country code (USA, GBR, CHE...), which Nominatim
 * doesn't read in a free-form query, filters the search by that country (countrycodes) instead.
 * Not found, the address is searched again without its first parts (street, company name...), at
 * most twice: the city or the postcode at least.
 */
class NominatimGeocoder
{
    /** Minimum time between two requests, in seconds. */
    protected const INTERVAL = 1.1;

    /** Searches without the first parts of an address not found. */
    protected const MAX_FALLBACKS = 2;

    /** After a network error, the next calls of the run fail at once for that long, in seconds. */
    protected const PAUSE_AFTER_ERROR = 60;

    protected static float $lastRequest = 0.0;
    protected static float $failedAt = 0.0;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @return array{status: string, results: array<int, array<string, mixed>>, provider?: string}
     */
    public function geocode(string $address, string $url, string $userAgent, ?string $proxy = null): array
    {
        $address = trim($address, " \t\n\r\0\x0B,");
        if ($address === '') {
            return ['status' => 'INVALID_REQUEST', 'results' => []];
        }
        if (self::$failedAt > 0 && microtime(true) - self::$failedAt < self::PAUSE_AFTER_ERROR) {
            return ['status' => 'UNKNOWN_ERROR', 'results' => []];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn ($part) => $part !== ''));
        $country = $parts !== [] ? $this->countryCode((string)end($parts)) : null;
        if ($country !== null) {
            array_pop($parts);
            if ($parts === []) {
                // A country only: its name (a code alone could match any place with that name).
                $parts = [Countries::getName(strtoupper($country), 'en')];
            }
        }

        $response = ['status' => 'ZERO_RESULTS', 'results' => []];
        for ($skip = 0; $skip <= self::MAX_FALLBACKS && $skip < count($parts); $skip++) {
            $response = $this->search(implode(', ', array_slice($parts, $skip)), $country, $url, $userAgent, $proxy);
            if ($response['status'] !== 'ZERO_RESULTS') {
                break;
            }
        }
        $response['provider'] = 'OpenStreetMap (Nominatim)';

        return $response;
    }

    /**
     * The ISO 3166 alpha-2 code (lower case) of an alpha-3 country code part. Not alpha-2: a
     * state code (CA, DE, IN...) would be read as a country.
     */
    protected function countryCode(string $part): ?string
    {
        $code = strtoupper(trim($part));
        try {
            if (preg_match('/^[A-Z]{3}$/', $code) && Countries::alpha3CodeExists($code)) {
                return strtolower(Countries::getAlpha2Code($code));
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * One Nominatim search, as a Google geocoding answer.
     */
    protected function search(string $query, ?string $country, string $url, string $userAgent, ?string $proxy): array
    {
        $this->throttle();

        $parameters = ['q' => $query, 'format' => 'jsonv2', 'limit' => 1];
        if ($country !== null) {
            $parameters['countrycodes'] = $country;
        }
        $options = [
            'query' => $parameters,
            'headers' => ['User-Agent' => $userAgent, 'Accept-Language' => 'en'],
            'timeout' => 15,
        ];
        if ($proxy) {
            $options['proxy'] = $proxy;
        }

        try {
            $response = HttpClient::create()->request('GET', $url, $options);
            $status = $response->getStatusCode();
            if ($status === 429 || $status === 509) {
                return ['status' => 'OVER_QUERY_LIMIT', 'results' => []];
            }
            if ($status === 403 || $status === 401) {
                return ['status' => 'REQUEST_DENIED', 'results' => []];
            }
            if ($status !== 200) {
                $this->logger->warning('Maps: Nominatim answered HTTP ' . $status);

                return ['status' => 'UNKNOWN_ERROR', 'results' => []];
            }
            $places = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            self::$failedAt = microtime(true);
            $this->logger->error('Maps: Nominatim request failed: ' . $exception->getMessage());

            return ['status' => 'UNKNOWN_ERROR', 'results' => []];
        } finally {
            self::$lastRequest = microtime(true);
        }

        $place = is_array($places) ? ($places[0] ?? null) : null;
        if (!is_array($place) || !is_numeric($place['lat'] ?? null) || !is_numeric($place['lon'] ?? null)) {
            return ['status' => 'ZERO_RESULTS', 'results' => []];
        }

        return [
            'status' => 'OK',
            'results' => [[
                'formatted_address' => (string)($place['display_name'] ?? ''),
                'geometry' => [
                    'location' => ['lat' => (float)$place['lat'], 'lng' => (float)$place['lon']],
                    'location_type' => $this->locationType((int)($place['place_rank'] ?? 0)),
                ],
                'types' => array_values(array_filter([(string)($place['category'] ?? ''), (string)($place['type'] ?? '')])),
                'place_id' => (string)($place['osm_type'] ?? '') . (string)($place['osm_id'] ?? ''),
            ]],
        ];
    }

    /**
     * The Google location type of a Nominatim place rank: 30 a building or a house number,
     * 26-29 a street, below a postcode, a city or larger.
     */
    protected function locationType(int $placeRank): string
    {
        if ($placeRank >= 30) {
            return 'ROOFTOP';
        }

        return $placeRank >= 26 ? 'GEOMETRIC_CENTER' : 'APPROXIMATE';
    }

    protected function throttle(): void
    {
        $wait = self::INTERVAL - (microtime(true) - self::$lastRequest);
        if (self::$lastRequest > 0 && $wait > 0) {
            usleep((int)ceil($wait * 1000000));
        }
    }
}
