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

import {AttributeMap, Record} from '../../../common/record/record.model';
import {Injectable} from '@angular/core';
import {MapEntry} from "../../../common/types/overridable-map";
import {RecordMapper} from "../../../common/record/record-mappers/record-mapper.model";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {BaseSaveRecordMapper} from "../../../store/record/record-mappers/base-save.record-mapper";
import {deepClone} from "../../../common/utils/object-utils";

@Injectable({
    providedIn: 'root'
})
export class FieldMapper {

    constructor(
        protected recordMappers: RecordMapperRegistry,
        protected baseMapper: BaseSaveRecordMapper,
    ) {
        recordMappers.register('default', baseMapper.getKey(), baseMapper);
    }

    /**
     * Map staging fields
     */
    public mapFieldsToAttributes(record: Record): void {
        const mappers: MapEntry<RecordMapper> = this.recordMappers.get(record.module);

        Object.keys(mappers).forEach(key => {
            const mapper = mappers[key];
            mapper.map(record);
        });
    }

    /**
     * Get attributes mapped from fields without modifying the original record
     * @param record
     */
    public getAttributesMappedFromFields(record: Record): AttributeMap {
        const mappers: MapEntry<RecordMapper> = this.recordMappers.get(record.module);

        const baseRecord = deepClone({
            id: record?.id ?? '',
            type: record?.type ?? '',
            module: record?.module ?? '',
            attributes: record?.attributes ?? {},
            acls: record?.acls ?? []
        }) as Record;

        baseRecord.fields = record.fields;
        baseRecord.formGroup = record?.formGroup ?? null;
        baseRecord.metadata = record?.metadata ?? {};

        Object.keys(mappers).forEach(key => {
            const mapper = mappers[key];
            mapper.map(baseRecord);
        });

        return baseRecord.attributes;
    }
}
