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
import {FieldDefinition, FieldDefinitionMap} from "../../../common/record/field.model";
import {ActiveFieldsChecker} from "../../../services/condition-operators/active-fields-checker.service";
import {ObjectArrayMatrix} from "../../../common/types/object-map";
import {StringArrayMap} from "../../../common/types/string-map";

interface AttributeOverrides {
    [key: string]: any;
}

interface LineItemMappingOverrides {
    recordOverrides: AttributeOverrides;
    itemOverrides: AttributeOverrides;
}

@Injectable({
    providedIn: 'root'
})
export class BaseSaveRecordMapper implements RecordMapper {

    constructor(protected activeFieldsChecker: ActiveFieldsChecker) {
    }

    getKey(): string {
        return 'base';
    }

    map(record: Record): void {

        if (!record.fields || !Object.keys(record.fields).length) {
            return;
        }

        let attributeOverrides = {} as AttributeOverrides;

        Object.keys(record.fields).forEach(fieldName => {
            const field = record.fields[fieldName];

            const type = field.type || '';
            const source = field.definition.source || '';
            const rname = field.definition.rname || 'name';
            const idName = field.definition.id_name || '';

            if (type === 'relate' && source === 'non-db' && idName === fieldName) {
                const overrides = this.conditionalAttributeMapping(field.value, field.definition, record);
                attributeOverrides = {...attributeOverrides, ...overrides};

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

                const overrides = this.conditionalAttributeMapping(field.valueObject, field.definition, record);
                attributeOverrides = {...attributeOverrides, ...overrides};

                return;
            }

            if (field.items) {
                const itemDefinition = field.definition.lineItems ?? null;
                const attributeFieldsDefinition = itemDefinition?.definition?.attributeFields || {} as FieldDefinitionMap;

                record.attributes[fieldName] = [];
                field.items.forEach(item => {
                    if (!item?.id && item?.attributes?.deleted) {
                        return;
                    }
                    const {
                        recordOverrides,
                        itemOverrides
                    } = this.lineItemConditionalAttributeMapping(attributeFieldsDefinition, record, item);

                    attributeOverrides = {...attributeOverrides, ...recordOverrides};

                    Object.keys(itemOverrides).forEach(attrKey => {
                        item.attributes[attrKey] = itemOverrides[attrKey];
                    });

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

                const overrides = this.conditionalAttributeMapping(field.valueObjectArray, field.definition, record);
                attributeOverrides = {...attributeOverrides, ...overrides};

                return;
            }

            if (field.valueList) {
                record.attributes[fieldName] = field.valueList;

                const overrides = this.conditionalAttributeMapping(field.valueList, field.definition, record);
                attributeOverrides = {...attributeOverrides, ...overrides};

                return;
            }

            if (field.vardefBased && (isNil(field.value) || field.value === '')) {

                if (!isNil(record.attributes[fieldName])) {
                    delete record.attributes[fieldName];
                }
                return;
            }

            const overrides = this.conditionalAttributeMapping(field.value, field.definition, record);
            attributeOverrides = {...attributeOverrides, ...overrides};

            record.attributes[fieldName] = field.value;
        });

        Object.keys(attributeOverrides).forEach(attrKey => {
            record.attributes[attrKey] = attributeOverrides[attrKey];
        });
    }

    protected conditionalAttributeMapping(value: any, fieldDefinition: FieldDefinition, record: Record): AttributeOverrides {

        const mappingField = fieldDefinition?.metadata?.attributeMapping?.field ?? ''
        const activeOnAttributes = fieldDefinition?.metadata?.attributeMapping?.activeOnAttributes ?? null
        const activeOnFields = fieldDefinition?.metadata?.attributeMapping?.activeOnFields ?? null;

        const overrides = {} as AttributeOverrides;

        if (!mappingField) {
            return overrides;
        }

        if (!activeOnAttributes && !activeOnFields) {
            overrides[mappingField] = value;
            return overrides;
        }

        const isActive = this.isConditionActive(activeOnFields, activeOnAttributes, record);

        if (isActive) {
            overrides[mappingField] = value;
        }

        return overrides;
    }

    protected lineItemConditionalAttributeMapping(attributeFieldsDefinition: FieldDefinitionMap, record: Record, item: Record): LineItemMappingOverrides {

        const overrides = {recordOverrides: {}, itemOverrides: {}};
        if (!attributeFieldsDefinition || !Object.keys(attributeFieldsDefinition).length) {
            return overrides;
        }

        Object.keys(attributeFieldsDefinition).forEach(attrFieldName => {
            const attrFieldDef = attributeFieldsDefinition[attrFieldName];

            const mappingField = attrFieldDef?.metadata?.attributeMapping?.field ?? ''
            const mappingAttribute = attrFieldDef?.metadata?.attributeMapping?.attribute ?? ''
            const activeOnAttributes = attrFieldDef?.metadata?.attributeMapping?.activeOnAttributes ?? null
            const activeOnFields = attrFieldDef?.metadata?.attributeMapping?.activeOnFields ?? null;

            if (!mappingField && !mappingAttribute) {
                return;
            }

            if (!activeOnAttributes && !activeOnFields) {
                if (mappingAttribute) {
                    overrides.itemOverrides[mappingAttribute] = item.attributes[attrFieldName] ?? null;
                    return;
                }

                if (mappingField) {
                    overrides.recordOverrides[mappingField] = item.attributes[attrFieldName] ?? null;
                }
                return;
            }
            const isActive = this.isLineItemConditionActive(activeOnFields, activeOnAttributes, record, item);

            if (isActive) {
                if (mappingAttribute) {
                    overrides.itemOverrides[mappingAttribute] = item.attributes[attrFieldName] ?? null;
                    return;
                }

                if (mappingField) {
                    overrides.recordOverrides[mappingField] = item.attributes[attrFieldName] ?? null;
                }
            }
        });

        return overrides;
    }

    protected isConditionActive(activeOnFields: StringArrayMap, activeOnAttributes: ObjectArrayMatrix, record: Record): boolean {
        const relatedFields = Object.keys(activeOnFields ?? {}) ?? [];
        const relatedAttributesFields = Object.keys(activeOnAttributes ?? {}) ?? [];

        if (relatedFields.length) {
            return this.activeFieldsChecker.isActive(relatedFields, record, activeOnFields, [], {});
        }

        if (relatedAttributesFields.length) {
            return this.activeFieldsChecker.isActive([], record, {}, relatedAttributesFields, activeOnAttributes);
        }
    }

    protected isLineItemConditionActive(activeOnFields: StringArrayMap, activeOnAttributes: ObjectArrayMatrix, record: Record, item: Record): boolean {
        const relatedFields = Object.keys(activeOnFields ?? {}) ?? [];
        const relatedAttributesFields = Object.keys(activeOnAttributes ?? {}) ?? [];

        if (relatedFields.length) {
            return this.activeFieldsChecker.isActive(relatedFields, record, activeOnFields, [], {});
        }

        if (relatedAttributesFields.length) {
            const attributeFields = Object.keys(item.fields ?? {}) ?? [];
            return this.activeFieldsChecker.isActive([], item, {}, attributeFields, activeOnAttributes);
        }
    }

}
