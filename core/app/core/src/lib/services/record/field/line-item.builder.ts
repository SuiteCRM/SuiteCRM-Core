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

import {deepClone} from '../../../common/utils/object-utils';
import {Field, FieldMap, FieldDefinition} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {LanguageStore} from '../../../store/language/language.store';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {Injectable} from '@angular/core';
import {AttributeBuilder} from './attribute.builder';
import {UntypedFormGroup} from '@angular/forms';
import {FieldObjectRegistry} from "./field-object-type.registry";

@Injectable({
    providedIn: 'root'
})
export class LineItemBuilder extends AttributeBuilder {

    constructor(
        protected validationManager: ValidationManager,
        protected typeFormatter: DataTypeFormatter,
        protected fieldRegistry: FieldObjectRegistry
    ) {
        super(validationManager, typeFormatter, fieldRegistry);
    }


    /**
     * Create and add attributes fields to field
     *
     * @param {object} record Record
     * @param {object} fields FieldMap
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @param {function} buildLineItemFunction
     */
    public addLineItems(
        record: Record,
        fields: FieldMap,
        viewField: ViewFieldDefinition,
        language: LanguageStore,
        buildLineItemFunction: Function,
    ): void {
        const fieldKeys = Object.keys(fields) || [];


        if (fieldKeys.length < 1) {
            return;
        }

        fieldKeys.forEach(key => {
            const field = fields[key];
            this.addFieldLineItems(
                record,
                field,
                language,
                buildLineItemFunction,
            )
        });

    }

    /**
     * Create and add attributes fields to field
     *
     * @param {object} record Record
     * @param {object} field Field
     * @param {object} language LanguageStore
     * @param {function} buildLineItemFunction
     */
    public addFieldLineItems(
        record: Record,
        field: Field,
        language: LanguageStore,
        buildLineItemFunction: Function,
    ): void {

        const definition = (field && field.definition) || {};
        const type = (field && field.type) || '';
        const items = (field.valueObjectArray && field.valueObjectArray) || [];

        if (type !== 'line-items' || !items.length) {
            return;
        }

        const itemDefinition: FieldDefinition = definition.lineItems?.definition || {};
        field.items = [];

        items.forEach(item => {
            this.addLineItem(itemDefinition, item as Record, buildLineItemFunction, language, record, field);
        });
    }

    /**
     * Build line item and and to record
     * @param {object} itemDefinition
     * @param {object }item
     * @param {object} buildLineItemFunction
     * @param {object} language
     * @param {object} parentRecord
     * @param {object} parentField
     */
    public addLineItem(
        itemDefinition: FieldDefinition,
        item: Record,
        buildLineItemFunction: Function,
        language: LanguageStore,
        parentRecord: Record,
        parentField: Field
    ) {

        const itemViewField = {
            name: itemDefinition.name,
            label: itemDefinition.vname,
            type: itemDefinition.type,
            fieldDefinition: deepClone(itemDefinition)
        };

        const itemRecord = {
            id: item.id || '',
            type: item.type || '',
            module: item.module || '',
            attributes: item.attributes || {},
            fields: {},
            formGroup: new UntypedFormGroup({})
        } as Record;

        buildLineItemFunction(itemRecord, itemViewField, language);

        parentField.itemFormArray.push(itemRecord.formGroup);

        parentField.items.push(itemRecord);
    }
}
