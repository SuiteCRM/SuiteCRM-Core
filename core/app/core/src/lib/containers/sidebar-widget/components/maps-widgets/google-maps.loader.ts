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

/**
 * Loads the Google Maps JavaScript API once for the map widgets, with the API key
 * of the Google Maps settings (as the legacy map pages: maps.googleapis.com/maps/api/js?key=...).
 */
let loading: Promise<any> = null;
let loadedKey = '';

export function loadGoogleMaps(apiKey: string): Promise<any> {
    const w = window as any;
    if (w.google?.maps && loadedKey === apiKey) {
        return Promise.resolve(w.google);
    }
    if (loading && loadedKey === apiKey) {
        return loading;
    }

    loadedKey = apiKey;
    loading = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey);
        script.async = true;
        script.onload = () => w.google?.maps ? resolve(w.google) : reject(new Error('Google Maps not loaded'));
        script.onerror = () => {
            loading = null;
            reject(new Error('Google Maps not loaded'));
        };
        document.head.appendChild(script);
    });

    return loading;
}

/** The settings of the `maps` process (Maps::config). */
export interface MapsConfig {
    /** The map provider and the OpenStreetMap tiles. */
    provider?: 'google' | 'osm';
    tileUrl?: string;
    attribution?: string;
    apiKey: string;
    center: { lat: number; lng: number };
    markerImages: string;
    labels: { [key: string]: string };
}
