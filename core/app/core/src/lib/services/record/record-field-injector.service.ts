/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {Injectable} from "@angular/core";
import {isArray, isObject, isString} from "lodash-es";
import get from "lodash-es/get";
import {ObjectMap} from "../../common/types/object-map";
import {Record} from "../../common/record/record.model";

@Injectable({
    providedIn: 'root'
})
export class RecordFieldInjector {

    getInjectFieldsMap(parentRecord: Record, mappedFieldsConfig): ObjectMap {

        if (!parentRecord || !mappedFieldsConfig) {
            return null;
        }
        const mappedFieldsValues = {} as ObjectMap;
        Object.keys(mappedFieldsConfig).forEach((field) => {
            const path = mappedFieldsConfig[field];
            mappedFieldsValues[field] = this.mapEntry(path, parentRecord);
        });

        return mappedFieldsValues;
    }

    private mapEntry(path: any, parentRecord: Record): any {

        if (isArray(path)) {
            return this.mapValueArray(path, parentRecord);
        }

        if (isString(path)) {
            return this.mapValueString(parentRecord, path);
        }

        if (isObject(path)) {
            return this.mapValueObject(path, parentRecord);
        }
    }

    private mapValueArray(configArray: any[], parentRecord: Record) {
        const valueArray = [];
        configArray.forEach((entry) => {
            if (isObject(entry)) {
                const valueObject = this.mapValueObject(entry, parentRecord);
                valueArray.push(valueObject);
                return;
            }

            if (isString(entry)) {
                const valueString = this.mapValueString(parentRecord, entry);
                valueArray.push(valueString);
            }
        });
        return valueArray;
    }

    private mapValueObject(configObject: object, parentRecord: Record) {
        const valueObject: any = {};
        Object.keys(configObject).forEach(subField => {
            const path = configObject[subField];

            valueObject[subField] = this.mapEntry(path, parentRecord);
        });
        return valueObject;
    }

    private mapValueString(parentRecord: Record, path: any) {
        const valueHolder = get(parentRecord, path, null);

        let value = null;

        if (valueHolder?.value) {
            value = valueHolder.value;
        }

        if (valueHolder?.valueList) {
            value = valueHolder.valueList;
        }

        if (valueHolder?.valueObject) {
            value = valueHolder.valueObject;
        }

        if (valueHolder?.valueObjectArray) {
            value = valueHolder.valueObjectArray;
        }

        if (value) {
            return value;
        }

        if (valueHolder) {
            return valueHolder;
        }

        return path;
    }
}
