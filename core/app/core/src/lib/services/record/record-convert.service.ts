/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {Observable, of} from "rxjs";
import {map} from "rxjs/operators";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {Metadata, RecordViewMetadata} from "../../store/metadata/metadata.store.service";
import {isEmpty} from "lodash-es";
import {FieldDefinitionMap, FieldMetadata} from "../../common/record/field.model";
import {Record} from "../../common/record/record.model";
import {isVoid} from "../../common/utils/value-utils";
import {ViewFieldDefinition, ViewFieldDefinitionMap} from "../../common/metadata/metadata.model";
import {FieldLogicMap} from "../../common/actions/field-logic-action.model";

@Injectable({
    providedIn: 'root'
})
export class RecordConvertService {

    constructor(protected systemConfigStore: SystemConfigStore) {
    }

    public duplicateOnModule(prevRecord: Record, newRecord: Record, vardefs: FieldDefinitionMap, moduleMetadata: Metadata): Record {

        const excludedFields = this.systemConfigStore.getConfigValue('convert_ignore') ?? [];
        const nextModule = newRecord.module ?? '';

        newRecord.id = '';
        newRecord.attributes.id = '';

        Object.keys(vardefs).forEach((fieldName: string) => {
            if (isVoid(prevRecord?.attributes[fieldName]) || excludedFields?.default.includes(fieldName)) {
                return;
            }

            if (Object.keys(excludedFields).includes(nextModule) && excludedFields[nextModule].includes(fieldName)) {
                return;
            }

            if (moduleMetadata.recordView?.vardefs[fieldName].type != vardefs[fieldName].type) {
                return;
            }

            newRecord.attributes[fieldName] = prevRecord.attributes[fieldName];
        })

        return newRecord;
    }

    public getViewFieldsObservable(meta: Metadata): Observable<ViewFieldDefinition[]> {
        return of(meta.recordView).pipe(map((recordMetadata: RecordViewMetadata) => {
            const fieldsMap = {} as ViewFieldDefinitionMap;

            recordMetadata.panels.forEach(panel => {
                panel.rows.forEach(row => {
                    row.cols.forEach(col => {
                        const fieldName = col.name ?? col.fieldDefinition.name ?? '';
                        fieldsMap[fieldName] = col;
                    });
                });
            });

            Object.keys(recordMetadata.vardefs).forEach(fieldKey => {
                const vardef = recordMetadata.vardefs[fieldKey] ?? null;
                if (!vardef || isEmpty(vardef)) {
                    return;
                }

                // already defined. skip
                if (fieldsMap[fieldKey]) {
                    return;
                }

                if (vardef.type == 'relate') {
                    return;
                }

                fieldsMap[fieldKey] = {
                    name: fieldKey,
                    vardefBased: true,
                    label: vardef.vname ?? '',
                    type: vardef.type ?? '',
                    display: vardef.display ?? 'default',
                    fieldDefinition: vardef,
                    metadata: vardef.metadata ?? {} as FieldMetadata,
                    logic: vardef.logic ?? {} as FieldLogicMap
                } as ViewFieldDefinition;
            });

            return Object.values(fieldsMap);
        }));
    }

}
