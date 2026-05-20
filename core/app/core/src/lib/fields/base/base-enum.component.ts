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

import {BaseFieldComponent} from './base-field.component';
import {Component, OnDestroy, OnInit} from '@angular/core';
import {Subscription} from 'rxjs';
import {Field, FieldDefinition, Option} from '../../common/record/field.model';
import {isVoid, isEmptyString} from '../../common/utils/value-utils';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {
    LanguageListStringMap,
    LanguageStore,
    LanguageStringMap,
    LanguageStrings
} from '../../store/language/language.store';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {isNull, isObject} from "lodash-es";

@Component({template: ''})
export class BaseEnumComponent extends BaseFieldComponent implements OnInit, OnDestroy {
    selectedValues: Option[] = [];
    valueLabel = '';
    optionsMap: LanguageStringMap;
    options: Option[] = [];
    labels: LanguageStringMap;
    protected subs: Subscription[] = [];
    protected mappedOptions: { [key: string]: Option[] };
    protected isDynamicEnum = false;

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {

        super.ngOnInit();

        const options$ = this?.field?.metadata?.options$ ?? null;
        if (options$) {
            this.subs.push(this.field.metadata.options$.subscribe((options: Option[]) => {
                this.buildProvidedOptions(options);

                this.initValue();

            }));
            return;

        }

        const options = this?.field?.definition?.options ?? null;
        if (options) {
            this.subs.push(this.languages.vm$.subscribe((strings: LanguageStrings) => {

                this.buildAppStringListOptions(strings.appListStrings);
                this.initValue();

            }));
        }

        if (!options && !options$) {
            this.initValue();
        }

    }

    ngOnDestroy(): void {
        this.isDynamicEnum = false;
        this.subs.forEach(sub => sub.unsubscribe());
        this.options = [];
        this.optionsMap = {};
        this.selectedValues = [];
    }

    getInvalidClass(): string {
        if (this.validateOnlyOnSubmit ? this.isInvalid() : (this.field.formControl.invalid && this.field.formControl.touched)) {
            return 'is-invalid';
        }
        return '';
    }

    protected buildProvidedOptions(options: Option[]): void {
        this.options = options;
        this.optionsMap = {};

        options.forEach(option => {
            this.optionsMap[option.value] = option.label;
        });

    }

    protected buildAppStringListOptions(appStrings: LanguageListStringMap): void {

        this.optionsMap = {} as LanguageStringMap;
        this.addExtraOptions();

        if (appStrings && this.field.definition.options && appStrings[this.field.definition.options]) {
            const options = appStrings[this.field.definition.options] as LanguageStringMap;

            if (this.options && Object.keys(this.options)) {
                this.optionsMap = {...this.optionsMap, ...options};
            }
        }

        this.buildOptionsArray(appStrings);
    }

    protected addExtraOptions(): void {
        const extraOptions = (this.field.metadata && this.field.metadata.extraOptions) || [];

        extraOptions.forEach((item: Option) => {
            if (isVoid(item.value)) {
                return;
            }

            let label = item.label || '';
            if (item.labelKey) {
                label = this.languages.getFieldLabel(item.labelKey);
            }

            this.optionsMap[item.value] = label;
        });
    }

    protected buildOptionsArray(appStrings: LanguageListStringMap): void {

        this.options = [];
        Object.keys(this.optionsMap).forEach(key => {

            const isOptionEmpty = isEmptyString(this.optionsMap[key]);

            if (isOptionEmpty && this.isSkipEmptyMode()) {
                return;
            }

            if (isOptionEmpty && !this.addEmptyStringOption()){
                return;
            }

            this.options.push({
                value: key,
                label: this.optionsMap[key]
            } as Option);
        });

        if (this.isDynamicEnum) {
            this.buildDynamicEnumOptions(appStrings);
        }
    }

    protected addEmptyStringOption(): boolean {
        return this.field.type !== 'multienum';
    }

    protected isSkipEmptyMode(): boolean {
        return this.mode === 'massupdate' || this.mode === 'filter';
    }

    protected initValue(): void {

        this.selectedValues = [];

        if (this.field?.criteria ?? false) {
            this.initValueLabel();
            return;
        }

        if (typeof this.field.value !== 'string') {
            return;
        }

        if (!this.optionsMap) {
            return;
        }

        if (this.field.value && !this.optionsMap[this.field.value]) {
            this.initValueLabel();
            return;
        }

        if (typeof this.optionsMap[this.field.value] !== 'string') {
            return;
        }

        this.initValueLabel();
    }

    protected initValueLabel() {
        const fieldValue = this.field.value || this.field.criteria?.target || undefined;
        if (fieldValue !== undefined) {
            this.valueLabel = this.optionsMap[fieldValue] ?? fieldValue;
            this.selectedValues.push({
                value: fieldValue,
                label: this.valueLabel
            } as Option);
        }
    }

    /**
     *  Initialize the default value for the enum
     *
     *  @returns {void}
     *  @description set default enum value, if defined in vardefs
     * */
    protected initEnumDefault(): void {

        if (!isEmptyString(this.record?.id)) {
            this.field?.formControl.setValue('');

            return;
        }

        let defaultVal = this?.field?.default ?? this?.field?.definition?.default ?? null;
        if (typeof defaultVal === 'string') {
            defaultVal = defaultVal.trim();
        }
        if (!defaultVal) {
            this.field.formControl.setValue('');
            return;
        }
        this.selectedValues.push({
            value: defaultVal,
            label: this.optionsMap[defaultVal]
        } as Option);
        this.initEnumDefaultFieldValues(defaultVal);
    }

    protected initEnumDefaultFieldValues(defaultVal: string): void {

        if (this.field.type === 'multienum' || this.field.type === 'multirelate') {
            const defaultValues = this.selectedValues.map(option => option.value);
            this.field.valueList = defaultValues;
            this.field.formControl.setValue(defaultValues);

        } else {
            this.field.value = defaultVal;
            this.field.formControl.setValue(defaultVal);
        }
        this.field.formControl.markAsDirty();
    }

    protected checkAndInitAsDynamicEnum(): void {

        const definition = (this.field && this.field.definition) || {} as FieldDefinition;
        const dynamic = (definition && definition.dynamic) || false;
        const parentEnumKey = (definition && definition.parentenum) || '';
        const fields = (this.record && this.record.fields) || null;

        if (dynamic && parentEnumKey && fields) {
            this.isDynamicEnum = true;
            const parentEnum: Field = fields[parentEnumKey];
            if (parentEnum) {
                this.subscribeToParentValueChanges(parentEnum);
            }
        }
    }

    protected buildDynamicEnumOptions(appStrings: LanguageListStringMap): void {

        const parentEnum = this.record.fields[this.field.definition.parentenum];

        if (parentEnum) {

            const parentOptionMap: LanguageStringMap = appStrings[parentEnum.definition.options] as LanguageStringMap;

            if (parentOptionMap && Object.keys(parentOptionMap).length !== 0) {

                this.mappedOptions = this.createParentChildOptionsMap(parentOptionMap, this.options);

                let parentValues: string[] = [];
                if (parentEnum.definition.type === 'multienum' || parentEnum.definition.type === 'multirelate') {
                    parentValues = parentEnum.valueList;
                } else {
                    parentValues.push(parentEnum.value);
                }
                this.options = this.filterMatchingOptions(parentValues);

                if (parentValues && parentValues.length) {
                    this.setValueToAvailableOption();
                }

            }
        }
    }

    protected filterMatchingOptions(values: string[]): Option[] {

        let filteredOptions: Option[] = [];

        if (!values || !values.length) {
            return [];
        }

        values.forEach(value => {
            if (!this.mappedOptions[value]) {
                return;
            }
            filteredOptions = filteredOptions.concat([...this.mappedOptions[value]]);
        });

        return filteredOptions;
    }

    protected createParentChildOptionsMap(parentOptions: LanguageStringMap, childOptions: Option[]): { [key: string]: Option[] } {
        const mappedOptions: { [key: string]: Option[] } = {};
        Object.keys(parentOptions).forEach(key => {
            mappedOptions[key] = childOptions.filter(
                option => String(option.value).startsWith(key)
            );
        });
        return mappedOptions;
    }

    protected subscribeToParentValueChanges(parentEnum: Field): void {
        if (parentEnum.formControl) {
            this.subs.push(parentEnum.formControl.valueChanges.subscribe(values => {

                if (typeof values === 'string') {
                    values = [values];
                }

                // Rebuild available enum options
                this.options = this.filterMatchingOptions(values);
                this.setValueToAvailableOption();

                this.initValue();
            }));
        }
    }

    protected setValueToAvailableOption(): void {
        if (!this?.options?.length) {
            this.field.value = '';
            this.field.formControl.setValue('');
            return;
        }

        if (!this.options.some(option => option.value === this.field.value)) {
            this.field.value = this.options[0].value;
            this.field.formControl.setValue(this.options[0].value);
        }
    }

    protected buildOptionFromValue(value: string, link = ''): Option {
        const option: Option = {value: '', label: ''};

        if (isNull(value)) {
            return option;
        }
        option.value = (typeof value !== 'string' ? JSON.stringify(value) : value).trim();
        option.label = option.value;

        const valueLabel = this.optionsMap[option.value] ?? option.label;
        if (isObject(valueLabel)) {
            return option;
        }
        option.label = (typeof valueLabel !== 'string' ? JSON.stringify(valueLabel) : valueLabel).trim();

        return option;
    }

}
