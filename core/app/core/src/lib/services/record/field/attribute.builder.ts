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

import {FieldBuilder} from './field.builder';
import {Field, FieldAttribute, FieldMap, FieldDefinition} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {LanguageStore} from '../../../store/language/language.store';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {Injectable} from '@angular/core';
import isObjectLike from 'lodash-es/isObjectLike';
import {FieldObjectRegistry} from "./field-object-type.registry";

@Injectable({
    providedIn: 'root'
})
export class AttributeBuilder extends FieldBuilder {

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
     * @param {function} buildAttributeFunction
     * @param {function} addAttributeFunction
     */
    public addAttributes(
        record: Record,
        fields: FieldMap,
        viewField: ViewFieldDefinition,
        language: LanguageStore,
        buildAttributeFunction: Function,
        addAttributeFunction: Function,
    ): void {
        const fieldKeys = Object.keys(fields) || [];


        if (fieldKeys.length < 1) {
            return;
        }

        fieldKeys.forEach(key => {
            const field = fields[key];
            this.addFieldAttributes(
                record,
                field,
                language,
                buildAttributeFunction,
                addAttributeFunction
            )
        });

    }

    /**
     * Create and add attributes fields to field
     *
     * @param {object} record Record
     * @param {object} field Field
     * @param {object} language LanguageStore
     * @param {function} buildAttributeFunction
     * @param {function} addAttributeFunction
     */
    public addFieldAttributes(
        record: Record,
        field: Field,
        language: LanguageStore,
        buildAttributeFunction: Function,
        addAttributeFunction: Function
    ): void {

        const definition = (field && field.definition) || {};
        const attributes = definition.attributeFields || {};
        const attributeKeys = Object.keys(attributes);

        attributeKeys.forEach(key => {
            const attributeDefinition = attributes[key];

            if (!!field.attributes[key]) {
                return;
            }

            const attributeViewField = {
                name: attributeDefinition.name,
                label: attributeDefinition.vname,
                type: attributeDefinition.type,
                fieldDefinition: attributeDefinition
            };

            const attributeField = buildAttributeFunction(record, field, attributeViewField, language);
            addAttributeFunction(record, field, attributeDefinition.name, attributeField);
        });
    }

    /**
     * Build field
     *
     * @param {object} record Record
     * @param {object} parentField Field
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @returns {object} FieldAttribute
     */
    public buildAttribute(record: Record, parentField: Field, viewField: ViewFieldDefinition, language: LanguageStore = null): FieldAttribute {

        const definition = (viewField && viewField.fieldDefinition) || {} as FieldDefinition;
        const {value, valueList, valueObject} = this.parseAttributeValue(viewField, definition, record, parentField);
        const {validators, asyncValidators} = this.getSaveValidators(record, viewField);

        const field = this.setupField(
            record.module,
            viewField,
            value,
            valueList,
            valueObject,
            record,
            definition,
            validators,
            asyncValidators,
            language
        );

        const fieldAttribute = field as FieldAttribute;
        fieldAttribute.valuePath = definition.valuePath;
        fieldAttribute.valueParent = definition.valueParent;
        fieldAttribute.source = 'attribute';
        fieldAttribute.parentKey = parentField.name;
        fieldAttribute.useFullColumn = definition?.useFullColumn || field?.definition?.useFullColumn || null;

        return fieldAttribute;
    }

    /**
     * Add attribute to record
     *
     * @param {object} record Record
     * @param {object} field Field
     * @param {string} name string
     * @param {object} attribute FieldAttribute
     */
    public addAttributeToRecord(record: Record, field: Field, name: string, attribute: FieldAttribute): void {

        if (!record || !name || !field || !attribute) {
            return;
        }

        field.attributes = field.attributes || {};

        field.attributes[name] = attribute;

        if (record.formGroup && attribute.formControl) {
            record.formGroup.addControl(name, attribute.formControl);
        }

    }

    /**
     * Parse attribute from field
     *
     * @param {object} viewField ViewFieldDefinition
     * @param {object} definition FieldDefinition
     * @param {object} record Record
     * @param {object} field Field
     * @returns {object} value object
     */
    protected parseAttributeValue(
        viewField: ViewFieldDefinition,
        definition: FieldDefinition,
        record: Record,
        field: Field
    ): { value: string; valueList: string[]; valueObject?: any } {

        const type = (viewField && viewField.type) || '';
        const source = (definition && definition.source) || '';
        const rname = (definition && definition.rname) || 'name';
        const viewName = viewField.name || '';
        let value: string;
        let valueList: string[] = null;

        if (type === 'relate' && source === 'non-db' && rname !== '') {
            value = this.getParentValue(record, field, viewName, definition)[rname];
            const valueObject = this.getParentValue(record, field, viewName, definition);
            return {value, valueList, valueObject};
        }

        if (!viewName) {
            value = '';
        } else {
            value = this.getParentValue(record, field, viewName, definition);
        }

        value = this.getParentValue(record, field, viewName, definition);

        if (Array.isArray(value)) {
            return {
                value: null,
                valueList: value,
                valueObject: null
            }
        }

        if (isObjectLike(value)) {
            return {
                value: null,
                valueList: null,
                valueObject: value
            }
        }

        return {value, valueList: null, valueObject: null};
    }

}
