/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {Injectable} from '@angular/core';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';
import {StringMap} from '../../../common/types/string-map';

@Injectable({providedIn: 'root'})
export class ActionNameMapper {

    constructor(private systemConfig: SystemConfigStore) {
    }

    /**
     * Public Api
     */

    /**
     * Map the legacy name to frontend
     *
     * @param {string} action the action name
     * @returns {string} frontend name
     */
    public toFrontend(action: string): string {
        const map = this.getLegacyToFrontendMap();

        if (!map[action]){
            return action;
        }

        return map[action];
    }

    /**
     * Map the frontend name to legacy
     *
     * @param {string} action the action name
     * @returns {string} frontend name
     */
    public toLegacy(action: string): string {
        const map = this.getFrontendToLegacyMap();

        if (!map[action]){
            return action;
        }

        return map[action];
    }

    /**
     * Check if action is valid
     *
     * @param {string} action the action name
     * @returns {boolean} is valid
     */
    public isValid(action: string): boolean {
        const map = this.getFrontendToLegacyMap();
        let valid = false;

        if (map[action]) {
            valid = true;
        }

        return valid;
    }


    /**
     * Internal API
     */

    /**
     * Get the legacy to frontend map
     *
     * @returns {{}} legacy to frontend map
     */
    protected getLegacyToFrontendMap(): StringMap {
        return this.systemConfig.getConfigValue('action_name_map');
    }

    /**
     * Get the frontend to legacy map
     *
     * @returns {{}} frontend to legacy map
     */
    protected getFrontendToLegacyMap(): StringMap {
        const map = this.systemConfig.getConfigValue('action_name_map');
        const invertedMap = {};

        Object.keys(map).forEach((legacyName) => {
            const frontendName = map[legacyName];
            invertedMap[frontendName] = legacyName;
        });

        return invertedMap;
    }
}
