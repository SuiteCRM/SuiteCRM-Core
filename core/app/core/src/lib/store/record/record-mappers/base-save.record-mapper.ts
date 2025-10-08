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

import {RecordMapper} from '../../../common/record/record-mappers/record-mapper.model';
import {Record} from '../../../common/record/record.model';
import {deepClone} from '../../../common/utils/object-utils';
import {Injectable} from '@angular/core';
import {isNil} from "lodash-es";

@Injectable({
    providedIn: 'root'
})
export class BaseSaveRecordMapper implements RecordMapper {

    getKey(): string {
        return 'base';
    }

    map(record: Record): void {

        if (!record.fields || !Object.keys(record.fields).length) {
            return;
        }

        Object.keys(record.fields).forEach(fieldName => {
            const field = record.fields[fieldName];

            const type = field.type || '';
            const source = field.definition.source || '';
            const rname = field.definition.rname || 'name';
            const idName = field.definition.id_name || '';

            if (type === 'relate' && source === 'non-db' && idName === fieldName) {
                record.attributes[fieldName] = field.value;
                return;
            }

            if (type === 'relate' && source === 'non-db' && rname !== '' && field.valueObject) {
                const attribute = record.attributes[fieldName] || {} as any;

                attribute[rname] = field.valueObject[rname];
                attribute.id = field.valueObject.id;

                record.attributes[fieldName] = attribute;
                record.attributes[idName] = field.valueObject.id;

                return;
            }

            if (field.valueObject) {
                record.attributes[fieldName] = field.valueObject;
                return;
            }

            if (field.items) {
                record.attributes[fieldName] = [];
                field.items.forEach(item => {
                    if(!item?.id && item?.attributes?.deleted){
                        return;
                    }
                    record.attributes[fieldName].push({
                        id: item.id,
                        module: item.module,
                        attributes: deepClone(item.attributes)
                    } as Record)
                });
                return;
            }

            if (field.valueObjectArray) {
                record.attributes[fieldName] = field.valueObjectArray;
                return;
            }

            if (field.valueList) {
                record.attributes[fieldName] = field.valueList;
                return;
            }

            if (field.vardefBased && (isNil(field.value) || field.value === '') ) {

                if (!isNil(record.attributes[fieldName])) {
                    delete record.attributes[fieldName];
                }
                return;
            }

            record.attributes[fieldName] = field.value;
        });
    }
}
