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
 * Loads Leaflet once (its own chunk) for the OpenStreetMap maps of the maps module.
 */
let loading: Promise<any> = null;

export function loadLeaflet(): Promise<any> {
    if (!loading) {
        loading = import('leaflet')
            .then((module: any) => module?.default ?? module)
            .catch(error => {
                loading = null;
                throw error;
            });
    }

    return loading;
}

/** The default OpenStreetMap tiles (jjwg_Maps setting osm_tile_url). */
export const OSM_DEFAULT_TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

/**
 * The attribution of the map tiles (a setting, shown as text), OpenStreetMap linked to its
 * copyright page as its licence asks.
 */
export function osmAttribution(text: string): string {
    const escaped = (text || '© OpenStreetMap contributors').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', '\'': '&#39;'
    }[char]));

    return escaped.replace('OpenStreetMap',
        '<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>');
}
