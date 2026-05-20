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

export interface MapEntry<T> {
    [key: string]: T;
}

export interface MapGroupEntry<T> {
    values?: MapEntry<T>;
    exclude?: string[];
}

export interface OverridableMapType<T> {
    [key: string]: MapGroupEntry<T>;
}

export interface OverridableMapInterface<T> {

    init(entryMap: OverridableMapType<T>): void;

    addEntry(group: string, key: string, value: T): void;

    excludeEntry(group: string, key: string);

    getGroupEntries(group: string);
}

export class OverridableMap<T> implements OverridableMapInterface<T> {
    public map: OverridableMapType<T>;

    constructor() {
        this.map = {
            default: {
                values: {},
                exclude: []
            }
        };
    }

    public init(entryMap: OverridableMapType<T>): void {
        Object.keys(entryMap).forEach(group => {

            if (entryMap[group].values) {
                Object.keys(entryMap[group].values).forEach(key => {
                    this.addEntry(group, key, entryMap[group].values[key]);
                });
            }

            if (entryMap[group].exclude) {
                entryMap[group].exclude.forEach(excluded => this.excludeEntry(group, excluded));
            }
        });
    }

    public addEntry(group: string, key: string, value: T): void {

        if (!(group in this.map)) {
            this.map[group] = {
                values: {},
                exclude: []
            };
        }

        this.map[group].values[key] = value;
    }

    public excludeEntry(group: string, key: string): void {

        if (!(group in this.map)) {
            this.map[group] = {
                values: {},
                exclude: []
            };
        }

        this.map[group].exclude.push(key);
    }

    public getGroupEntries(group: string): MapEntry<T> {
        const values = {};

        const allValues = {...this.map.default.values};
        let groupEntry: MapGroupEntry<T> = {
            values: {},
            exclude: []
        };

        if (group in this.map) {
            groupEntry = this.map[group];
            groupEntry.values = groupEntry.values || {};
            groupEntry.exclude = groupEntry.exclude || [];
        }

        Object.keys(groupEntry.values).forEach(key => {
            allValues[key] = groupEntry.values[key];
        });

        Object.keys(allValues).forEach(key => {
            if (this.map.default.exclude.includes(key)) {
                return;
            }

            if (groupEntry.exclude.includes(key)) {
                return;
            }

            values[key] = allValues[key];
        });

        return values;
    }
}
