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

import {isString} from "lodash-es";

/**
 * Check if value is false
 *
 * @param {any} value to check
 * @returns {boolean} isFalse
 */
export const isFalse = (value: any): boolean => (value === false || value === 'false' || value === '0' || value === 0);

/**
 * Check if value is true
 *
 * @param {any} value to check
 * @returns {boolean} isFalse
 */
export const isTrue = (value: any): boolean => (value === true || value === 'true' || value === '1' || value === 1);

/**
 * Check if value is null or undefined
 *
 * @param {any} value to check
 * @returns {boolean} isVoid
 */
export const isVoid = (value: any): boolean => (value === null || typeof value === 'undefined');
/**
 * Check if value is an empty string
 *
 * @param {any} value to check
 * @returns {boolean} isEmptyString
 */
export const isEmptyString = (value: any): boolean => (typeof value === 'string' && !value.trim());
/**
 * Check if value is an empty string
 *
 * @param {any} value to check
 * @returns {boolean} isEmptyString
 */
export const isEmail = (value: any): boolean => (typeof value === 'string' && /[\w-\.]+@([\w-]+\.)+[\w-]+$/.test(value ?? ''));
/**
 * Check if value is an empty string
 *
 * @param {any} value to check
 * @returns {boolean} isEmptyString
 */
export const isURL = (value: any): boolean => {
    if (!isString(value)) {
        return false;
    }
    if(value.includes('javascript:')) {
        return false;
    }

    if(!value.includes('https://') && !value.includes('http://') && value.includes('.')) {
        value = 'http://' + value;
    }

    let url: URL;

    try {
        url = new URL(value);
    } catch (e) {
        return false;
    }

    return url?.protocol === "http:" || url?.protocol === "https:";
}
