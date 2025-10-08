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
import {Injectable} from '@angular/core';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {SavedFilter} from '../../../store/saved-filters/saved-filter.model';
import {deepClone} from '../../../common/utils/object-utils';
import {Field, FieldDefinition, Option} from '../../../common/record/field.model';
import {SearchCriteria, SearchCriteriaFieldFilter} from '../../../common/views/list/search-criteria.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {LanguageStore} from '../../../store/language/language.store';
import {AsyncValidatorFn, ValidatorFn} from '@angular/forms';
import {FieldObjectRegistry} from "./field-object-type.registry";

@Injectable({
    providedIn: 'root'
})
export class FilterFieldBuilder extends FieldBuilder {

    constructor(
        protected validationManager: ValidationManager,
        protected typeFormatter: DataTypeFormatter,
        protected fieldRegistry: FieldObjectRegistry
    ) {
        super(validationManager, typeFormatter, fieldRegistry);
    }

    /**
     * Build filter field
     *
     * @param {object} savedFilter SavedFilter
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @returns {object} Field
     */
    public buildFilterField(savedFilter: SavedFilter, viewField: ViewFieldDefinition, language: LanguageStore = null): Field {

        const definition = (viewField && viewField.fieldDefinition) || {} as FieldDefinition;
        const {validators, asyncValidators} = this.getFilterValidators(savedFilter, viewField);

        const field = this.setupField(
            savedFilter.searchModule,
            viewField,
            null,
            null,
            null,
            savedFilter,
            definition,
            validators,
            asyncValidators,
            language
        );
        field.criteria = this.initFieldFilter(savedFilter.criteria, viewField, field);
        return field;
    }

    /**
     * Get Filter Validators for given field definition
     *
     * @param {object} record  Record
     * @param {object} viewField ViewFieldDefinition
     * @returns {object} validator map
     */
    public getFilterValidators(
        record: SavedFilter,
        viewField: ViewFieldDefinition
    ): { validators: ValidatorFn[]; asyncValidators: AsyncValidatorFn[] } {

        const validators = this.validationManager.getFilterValidations(record.searchModule, viewField, record);
        const asyncValidators: AsyncValidatorFn[] = [];

        return {validators, asyncValidators};
    }

    /**
     * Init filter fields
     *
     * @param {object} searchCriteria to use
     * @param {object} viewField to init
     * @param {object} field to init
     * @returns {object} SearchCriteriaFieldFilter
     */
    public initFieldFilter(searchCriteria: SearchCriteria, viewField: ViewFieldDefinition, field: Field): SearchCriteriaFieldFilter {
        let fieldCriteria: SearchCriteriaFieldFilter;

        const fieldName = viewField.name;
        let fieldType = viewField.type;
        let rangeSearch = false;

        if (fieldType === 'composite') {
            fieldType = field.definition.type;
            rangeSearch = true;
        }

        if (searchCriteria.filters[fieldName] && searchCriteria.filters[fieldName].fieldType) {
            fieldCriteria = deepClone(searchCriteria.filters[fieldName]);
        } else {
            fieldCriteria = {
                field: fieldName,
                fieldType,
                operator: '',
                values: []
            } as SearchCriteriaFieldFilter;
        }

        fieldCriteria.rangeSearch = rangeSearch;

        this.mapEnumEmptyOption(fieldCriteria, field);

        this.mapRelateFields(fieldCriteria, field, searchCriteria);

        return fieldCriteria;
    }

    protected mapEnumEmptyOption(fieldCriteria: SearchCriteriaFieldFilter, field: Field): void {
        if (!['multienum', 'enum'].includes(fieldCriteria.fieldType)) {
            return;
        }

        let emptyOption = this.getEmptyOption(field);

        if (!emptyOption) {
            return;
        }

        if (!fieldCriteria.values || !fieldCriteria.values.length) {
            return;
        }

        fieldCriteria.values = fieldCriteria.values.map(value => {
            if (value !== '') {
                return value;
            }

            return '__SuiteCRMEmptyString__';
        });
    }

    protected mapRelateFields(fieldCriteria: SearchCriteriaFieldFilter, field: Field, searchCriteria: SearchCriteria): void {
        if (!['relate'].includes(fieldCriteria.fieldType)) {
            return;
        }

        if (!fieldCriteria.values || !fieldCriteria.values.length) {
            return;
        }

        const idFieldName = (field && field.definition && field.definition.id_name) || '';
        const relateFieldName = (field && field.definition && field.definition.rname) || 'name';
        const idValues = searchCriteria?.filters[idFieldName]?.values ?? [];

        fieldCriteria.valueObjectArray = fieldCriteria.valueObjectArray ?? [];
        const relateFieldMap = {};
        if (fieldCriteria.valueObjectArray.length) {
            fieldCriteria.valueObjectArray.forEach(value => {
                relateFieldMap[value.id] = value;
            })
        }

        for (let i = 0; i < fieldCriteria.values.length; i++) {
            let value = fieldCriteria.values[i];

            const relateValue = {};
            relateValue[relateFieldName] = value;
            relateValue['id'] = idValues[i] ?? '';

            if (!relateFieldMap[relateValue['id']]) {
                relateFieldMap[relateValue['id']] = relateValue;
                fieldCriteria.valueObjectArray.push(relateValue);
            }
        }
    }

    protected getEmptyOption(field: Field): Option {
        let emptyOption = null;
        const extraOptions = field?.definition?.metadata?.extraOptions ?? [];

        extraOptions.forEach(option => {
            if (option.value === '__SuiteCRMEmptyString__') {
                emptyOption = option;
            }
        });

        return emptyOption;
    }

    /**
     * Is criteria field initialized in record
     *
     * @param {object} record Record
     * @param {string} fieldName field
     * @returns {boolean} isInitialized
     */
    public isCriteriaFieldInitialized(record: SavedFilter, fieldName: string): boolean {
        const criteriaField = record.criteriaFields[fieldName];
        return !!criteriaField && !criteriaField.vardefBased;
    }

    /**
     * Add field to SavedFilter
     *
     * @param {object} savedFilter SavedFilter
     * @param {string} name string
     * @param {object} field Field
     */
    public addToSavedFilter(savedFilter: SavedFilter, name: string, field: Field): void {

        if (!savedFilter || !name || !field) {
            return;
        }

        if (!savedFilter.criteriaFields) {
            savedFilter.criteriaFields = {};
        }

        savedFilter.criteriaFields[name] = field;

        if (!savedFilter.criteria.filters) {
            savedFilter.criteria.filters = {};
        }

        savedFilter.criteria.filters[name] = field.criteria;

        if (savedFilter.criteriaFormGroup && field.formControl) {
            savedFilter.criteriaFormGroup.addControl(name, field.formControl);
        }
    }
}
