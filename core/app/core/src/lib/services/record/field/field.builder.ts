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

import {Injectable, signal} from '@angular/core';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {isFalse, isTrue} from '../../../common/utils/value-utils';
import {ObjectMap} from '../../../common/types/object-map';
import {AttributeDependency, BaseField, DisplayType, Field, FieldDefinition} from '../../../common/record/field.model';
import {FieldLogic, FieldLogicMap} from '../../../common/actions/field-logic-action.model';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {AsyncValidatorFn, UntypedFormArray, UntypedFormControl, ValidatorFn} from '@angular/forms';
import {LanguageStore} from '../../../store/language/language.store';
import get from 'lodash-es/get';
import {isEmpty, isObject, merge} from 'lodash-es';
import {FieldObjectRegistry} from "./field-object-type.registry";
import {deepClone} from "../../../common/utils/object-utils";


@Injectable({
    providedIn: 'root'
})
export class FieldBuilder {

    constructor(
        protected validationManager: ValidationManager,
        protected typeFormatter: DataTypeFormatter,
        protected fieldRegistry: FieldObjectRegistry
    ) {
    }

    /**
     * Build field
     *
     * @param {object} record Record
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @returns {object}Field
     */
    public buildField(record: Record, viewField: ViewFieldDefinition, language: LanguageStore = null): Field {

        const module = record?.module ?? '';
        let definition = viewField?.fieldDefinition ?? {} as FieldDefinition;
        const multiModuleDefinitions = viewField?.multiModuleDefinitions ?? {} as ObjectMap;
        const currentModuleDefinitions = multiModuleDefinitions[module] ?? {} as FieldDefinition;

        if (!isEmpty(currentModuleDefinitions)) {
            definition = currentModuleDefinitions;
        }

        if (!isEmpty(definition)) {
            definition = deepClone(definition);
        }

        const {value, valueList, valueObject} = this.parseValue(viewField, definition, record);
        const {validators, asyncValidators, itemFormArraySaveValidators} = this.getSaveValidators(record, viewField);

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

        field.itemFormArraySaveValidators = itemFormArraySaveValidators;

        return field;
    }

    public getFieldLabel(label: string, module: string, language: LanguageStore): string {
        const languages = language.getLanguageStrings();
        return language.getFieldLabel(label, module, languages);
    }

    /**
     * Parse value from record
     *
     * @param {object} viewField ViewFieldDefinition
     * @param {object} definition FieldDefinition
     * @param {object} record Record
     * @returns {object} value object
     */
    protected parseValue(
        viewField: ViewFieldDefinition,
        definition: FieldDefinition,
        record: Record
    ): { value: string; valueList: string[]; valueObject?: any } {

        const type = (viewField && viewField.type) || '';
        const source = (definition && definition.source) || '';
        const rname = (definition && definition.rname) || 'name';
        const viewName = viewField.name || '';
        let value: string = null;
        let valueList: string[] = null;

        if (!viewName || (!record.attributes[viewName] && !isFalse(record.attributes[viewName]))) {
            value = '';
        } else if (type === 'relate' && source === 'non-db' && rname !== '') {
            value = record.attributes[viewName][rname];
            const valueObject = record.attributes[viewName];
            return {value, valueList, valueObject};
        } else {
            value = record.attributes[viewName];
        }

        if (type === 'line-items') {
            return {value: null, valueList};
        }

        if (Array.isArray(value)) {
            valueList = value;
            value = null;
        } else if (typeof value === 'object' && value !== null) {
            return {value, valueList, valueObject: value};
        }

        return {value, valueList};
    }

    /**
     * Build and initialize field object
     *
     * @param {string} module to use
     * @param {object} viewField ViewFieldDefinition
     * @param {string} value string
     * @param {[]} valueList string[]
     * @param {} valueObject value object
     * @param {object} record Record
     * @param {object} definition FieldDefinition
     * @param {[]} validators ValidatorFn[]
     * @param {[]} asyncValidators AsyncValidatorFn[]
     * @param {object} language LanguageStore
     * @returns {object} BaseField
     */
    protected setupField(
        module: string,
        viewField: ViewFieldDefinition,
        value: string,
        valueList: string[],
        valueObject: any,
        record: Record,
        definition: FieldDefinition,
        validators: ValidatorFn[],
        asyncValidators: AsyncValidatorFn[],
        language: LanguageStore
    ): BaseField {

        const metadata = merge(definition?.metadata ?? {}, viewField?.metadata ?? {});

        const formattedValue = this.typeFormatter.toUserFormat(viewField.type, value, {mode: 'edit', metadata});

        if (viewField.link) {
            metadata.link = viewField.link;
        }

        const type = viewField.type || definition.type;
        const FieldObjectType = this.fieldRegistry.get(module, type);

        const field = new FieldObjectType();

        field.type = type;
        field.name = viewField.name || definition.name || '';
        field.vardefBased = viewField?.vardefBased ?? definition?.vardefBased ?? false;
        field.readonly = isTrue(viewField.readonly) || isTrue(definition.readonly) || false;
        field.display = signal((viewField.display || definition.display || 'default') as DisplayType);
        field.required = signal(isTrue(definition?.required) ?? isTrue(viewField?.fieldDefinition?.required) ?? false);
        field.defaultDisplay = field?.display();
        if (field.defaultDisplay === 'default') {
            field.defaultDisplay = 'show';
        }
        field.metadata = metadata;
        field.definition = definition;
        field.initDefaultProcess = viewField?.initDefaultProcess ?? definition?.initDefaultProcess ?? '';

        if (viewField?.lineItems) {
            field.definition.lineItems = viewField.lineItems;
        }
        field.labelKey = viewField.label || definition.vname || '';
        field.dynamicLabelKey = viewField.dynamicLabelKey || definition.dynamicLabelKey || '';

        const defaultValue = viewField?.defaultValue ?? definition?.default ?? definition?.defaultValue ?? null;
        if (defaultValue) {
            field.default = defaultValue;
        }

        const defaultValueObject = viewField?.defaultValueObject ?? definition?.defaultValueObject ?? null;
        if (defaultValueObject) {
            field.defaultValueObject = defaultValueObject;
        }

        field.defaultValueModes = viewField?.defaultValueModes ?? definition?.defaultValueModes ?? [];

        field.value = value;

        field.validators = validators;
        field.asyncValidators = asyncValidators;

        if (field.type === 'line-items') {
            field.valueObjectArray = record?.attributes[field.name] ?? [];
            field.itemFormArray = new UntypedFormArray([]);
            field.formControl = new UntypedFormControl(formattedValue);
        } else {
            field.formControl = new UntypedFormControl(formattedValue);
        }

        const fieldActions = viewField?.fieldActions ?? definition?.fieldActions ?? null;
        field.fieldActions = null;

        if (fieldActions && isObject(fieldActions)) {
            field.fieldActions = deepClone(fieldActions);
        }

        field.displayType =  viewField?.displayType ?? definition?.displayType ?? '';

        field.useFullColumn = viewField?.useFullColumn || definition?.useFullColumn || null;
        field.attributes = {};
        field.source = 'field';
        field.logic = viewField.logic || definition.logic || null;
        field.displayLogic = viewField.displayLogic || definition.displayLogic || null;
        const fieldDependencies: ObjectMap = {};
        const attributeDependencies: { [key: string]: AttributeDependency } = {};


        this.addFieldDependencies(field.logic, fieldDependencies, attributeDependencies, 'logic');
        this.addFieldDependencies(field.displayLogic, fieldDependencies, attributeDependencies, 'displayLogic');

        field.attributeDependencies = Object.keys(attributeDependencies).map(key => attributeDependencies[key]);
        field.fieldDependencies = fieldDependencies;

        if (valueList) {
            field.valueList = valueList;
        }

        if (valueObject) {
            field.valueObject = valueObject;
        }

        if (language) {
            field.label = this.getFieldLabel(viewField.label, module, language);
        }

        if (!field.labelKey && viewField.label) {
            field.labelKey = viewField.label;
        }
        return field;
    }

    protected addFieldDependencies(config: FieldLogicMap, fieldDependencies: ObjectMap, attributeDependencies: {
        [key: string]: AttributeDependency
    }, type: string) {
        if (config && Object.keys(config).length) {

            Object.keys(config).forEach(logicKey => {
                const entry = config[logicKey] || {} as FieldLogic;

                if (!entry.params) {
                    return;
                }

                if (entry.params && entry.params.attributeDependencies) {
                    entry.params.attributeDependencies.forEach(dependency => {
                        const dependencyKey = dependency.field + '.' + dependency.attribute;
                        attributeDependencies[dependencyKey] = dependency;
                    });

                }

                if (entry.params && entry.params.fieldDependencies) {
                    entry.params.fieldDependencies.forEach(dependency => {
                        const fieldDependency = fieldDependencies[dependency] ?? {}
                        const types = fieldDependency['types'] ?? [];
                        types.push(type);

                        if (fieldDependencies[dependency]) {
                            types.push(...fieldDependencies[dependency]['type']);
                        }

                        fieldDependencies[dependency] = {
                            field: dependency,
                            type: types
                        };
                    });
                }

            });
        }
    }

    /**
     * Get save validators for the given field definition
     *
     * @param {object} record Record
     * @param {object} viewField ViewFieldDefinition
     * @returns {object} Validator map
     */
    protected getSaveValidators(
        record: Record,
        viewField: ViewFieldDefinition
    ): {
        validators: ValidatorFn[];
        asyncValidators: AsyncValidatorFn[];
        itemFormArraySaveValidators?: ValidatorFn[];
    } {

        const validators = this.validationManager.getSaveValidations(record.module, viewField, record);
        const asyncValidators = this.validationManager.getAsyncSaveValidations(record.module, viewField, record);
        const itemFormArraySaveValidators = this.validationManager.getItemFormArraySaveValidations(record.module, viewField, record);
        return {validators, asyncValidators, itemFormArraySaveValidators};
    }

    /**
     * Set attribute value on parent
     *
     * @param {object} record Record
     * @param {object} field Field
     * @param {string} name String
     * @param {object} definition FieldDefinition
     * @returns any
     */
    protected getParentValue(record: Record, field: Field, name: string, definition: FieldDefinition): any {
        const valueParent = definition.valueParent ?? 'field';
        const parent = valueParent === 'record' ? record : field;

        if (definition.valuePath) {
            return get(parent, definition.valuePath, '');
        }

        if (valueParent === 'record') {
            return get(record.attributes, name, '');
        }

        return get(field.valueObject, name, '');
    }

}
