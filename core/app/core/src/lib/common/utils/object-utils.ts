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

/**
 * Deep clones an object
 *
 * @param {object} obj to clone
 * @returns {string} any
 */
export const deepClone = (obj: any): any => JSON.parse(JSON.stringify(obj));

/**
 * Check if all entries have been loaded and are ready to use
 *
 * @param entries
 * @returns boolean
 */
export const ready = (entries: (Array<any> | Record<string, any>)[]): boolean => {
    let areReady = true;

    entries.every(entry => {

        if (!entry) {
            areReady = false;
            return false;
        }
        if (Array.isArray(entry) && entry.length <= 0) {
            areReady = false;
            return false;
        }

        if (typeof entry === 'object' && Object.keys(entry).length <= 0) {
            areReady = false;
            return false;
        }

        return true;
    });

    return areReady;
};

/**
 * Pad all values of an object
 * Singular digit numbers will be padded/prefixed with a 0
 * e.g. numbers 1-9 will be padded with a 0 in front to 01-09
 *
 * @param {object} obj to pad
 * @returns {object} any
 */
export const padObjectValues = (obj: any): any => {
    Object.keys(obj).forEach(key => {
        obj[key] = String(obj[key]).padStart(2, '0');
    });
    return obj;
};

/**
 * @param {object} obj to be checked
 * @returns {boolean} true/false
 * @description Returns true, if the object is empty
 */
export const emptyObject = (obj: any): boolean =>
    (obj && (Object.keys(obj).length === 0));


