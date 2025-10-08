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

import {Injectable} from '@angular/core';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {SavedFilter} from '../../../store/saved-filters/saved-filter.model';
import {Field, FieldAttribute, FieldDefinition} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {LanguageStore} from '../../../store/language/language.store';
import {FilterFieldBuilder} from './filter-field.builder';
import isObjectLike from 'lodash-es/isObjectLike';
import {FieldObjectRegistry} from "./field-object-type.registry";

@Injectable({
    providedIn: 'root'
})
export class FilterAttributeBuilder extends FilterFieldBuilder {

    constructor(
        protected validationManager: ValidationManager,
        protected typeFormatter: DataTypeFormatter,
        protected fieldRegistry: FieldObjectRegistry
    ) {
        super(validationManager, typeFormatter, fieldRegistry);
    }

    /**
     * Build filter attribute
     *
     * @param {object} savedFilter SavedFilter
     * @param {object} parentField Field
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @returns {object} FieldAttribute
     */
    public buildFilterAttribute(
        savedFilter: SavedFilter,
        parentField: Field,
        viewField: ViewFieldDefinition,
        language: LanguageStore = null
    ): FieldAttribute {

        const definition = (viewField && viewField.fieldDefinition) || {} as FieldDefinition;

        if (!definition.valuePath) {
            definition.valuePath = 'criteria.' + (viewField.name || definition.name);
        }

        const {value, valueList, valueObject} = this.parseFilterAttributeValue(viewField, definition, savedFilter, parentField);
        const {validators, asyncValidators} = this.getFilterValidators(savedFilter, viewField);

        const field = this.setupField(
            savedFilter.searchModule,
            viewField,
            value,
            valueList,
            valueObject,
            savedFilter,
            definition,
            validators,
            asyncValidators,
            language
        );

        const fieldAttribute = field as FieldAttribute;
        fieldAttribute.valuePath = definition.valuePath;
        fieldAttribute.source = 'attribute';
        fieldAttribute.parentKey = parentField.definition.name;

        return fieldAttribute;
    }

    /**
     * Add attribute to SavedFilter
     *
     * @param {object} savedFilter SavedFilter
     * @param {object} field Field
     * @param {string} name string
     * @param {object} attribute FieldAttribute
     */
    public addAttributeToSavedFilter(savedFilter: SavedFilter, field: Field, name: string, attribute: FieldAttribute): void {

        if (!savedFilter || !name || !field || !attribute) {
            return;
        }

        field.attributes = field.attributes || {};

        field.attributes[name] = attribute;

        if (savedFilter.criteriaFormGroup && attribute.formControl) {
            savedFilter.criteriaFormGroup.addControl(name, attribute.formControl);
        }
    }

    /**
     * Parse filter attribute from field
     *
     * @param {object} viewField ViewFieldDefinition
     * @param {object} definition FieldDefinition
     * @param {object} record Record
     * @param {object} field Field
     * @returns {object} value object
     */
    protected parseFilterAttributeValue(
        viewField: ViewFieldDefinition,
        definition: FieldDefinition,
        record: Record,
        field: Field
    ): { value: string; valueList: string[]; valueObject?: any } {

        const viewName = viewField.name || '';
        let value: any;

        if (!viewName) {
            value = '';
        } else {
            value = this.getParentValue(record, field, viewName, definition);
        }

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
