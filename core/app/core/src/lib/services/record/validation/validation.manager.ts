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
import {ValidatorInterface} from './validator.Interface';
import {StringMatrix} from '../../../common/types/string-matrix';
import {StringMap} from '../../../common/types/string-map';
import {MapEntry, OverridableMap} from '../../../common/types/overridable-map';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {Record} from '../../../common/record/record.model';
import {AsyncValidatorFn, ValidatorFn} from '@angular/forms';
import {AsyncValidatorInterface} from './aync-validator.Interface';
import {RequiredValidator} from './validators/required.validator';
import {CurrencyValidator} from './validators/currency.validator';
import {DateValidator} from './validators/date.validator';
import {DateTimeValidator} from './validators/datetime.validator';
import {FloatValidator} from './validators/float.validator';
import {IntValidator} from './validators/int.validator';
import {EmailValidator} from './validators/email.validator';
import {PhoneValidator} from './validators/phone.validator';
import {RangeValidator} from './validators/range.validator';
import {PrimaryEmailValidator} from './validators/primary-email.validator';
import {DuplicateEmailValidator} from './validators/duplicate-email.validator';
import {LineItemsRequiredValidator} from "./validators/line-items-required.validator";
import {AsyncProcessValidator} from "./async-validators/async-process.validator";

export interface ValidationManagerInterface {
    registerSaveValidator(module: string, key: string, validator: ValidatorInterface): void;

    registerFilterValidator(module: string, key: string, validator: ValidatorInterface): void;

    excludeSaveValidator(module: string, key: string): void;

    excludeFilterValidator(module: string, key: string): void;

    registerAsyncSaveValidator(module: string, key: string, validator: AsyncValidatorInterface): void;

    excludeAsyncSaveValidator(module: string, key: string): void;

    getSaveValidations(module: string, viewField: ViewFieldDefinition, record: Record): ValidatorFn[];

    getFilterValidations(module: string, viewField: ViewFieldDefinition, record: Record): ValidatorFn[];

    getAsyncSaveValidations(module: string, viewField: ViewFieldDefinition, record: Record): AsyncValidatorFn[];
}

@Injectable({
    providedIn: 'root'
})
export class ValidationManager implements ValidationManagerInterface {
    protected saveValidators: OverridableMap<ValidatorInterface>;
    protected itemFormArraySaveValidators: OverridableMap<ValidatorInterface>;
    protected asyncSaveValidators: OverridableMap<AsyncValidatorInterface>;
    protected filterValidators: OverridableMap<ValidatorInterface>;
    protected filterFieldExclusion: StringMatrix = {
        default: {}
    };
    protected saveFieldExclusions: StringMatrix = {
        default: {}
    };

    constructor(
        protected asyncProcessValidator: AsyncProcessValidator,
        protected requiredValidator: RequiredValidator,
        protected rangeValidator: RangeValidator,
        protected currencyValidator: CurrencyValidator,
        protected dateValidator: DateValidator,
        protected datetimeValidator: DateTimeValidator,
        protected emailValidator: EmailValidator,
        protected floatValidator: FloatValidator,
        protected intValidator: IntValidator,
        protected phoneValidator: PhoneValidator,
        protected primaryEmailValidator: PrimaryEmailValidator,
        protected duplicateEmailValidator: DuplicateEmailValidator,
        protected lineItemsRequiredValidator: LineItemsRequiredValidator,
    ) {

        this.saveValidators = new OverridableMap<ValidatorInterface>();
        this.itemFormArraySaveValidators = new OverridableMap<ValidatorInterface>();
        this.asyncSaveValidators = new OverridableMap<AsyncValidatorInterface>();
        this.filterValidators = new OverridableMap<ValidatorInterface>();

        this.saveValidators.addEntry('default', this.getKey('required', 'all'), requiredValidator);
        this.saveValidators.addEntry('default', this.getKey('range', 'all'), rangeValidator);
        this.saveValidators.addEntry('default', this.getKey('currency', 'all'), currencyValidator);
        this.saveValidators.addEntry('default', this.getKey('date', 'all'), dateValidator);
        this.saveValidators.addEntry('default', this.getKey('datetime', 'all'), datetimeValidator);
        this.saveValidators.addEntry('default', this.getKey('email', 'all'), emailValidator);
        this.saveValidators.addEntry('default', this.getKey('float', 'all'), floatValidator);
        this.saveValidators.addEntry('default', this.getKey('int', 'all'), intValidator);
        this.saveValidators.addEntry('default', this.getKey('phone', 'all'), phoneValidator);
        this.itemFormArraySaveValidators.addEntry('default', this.getKey('primary-email', 'all'), primaryEmailValidator);
        this.itemFormArraySaveValidators.addEntry('default', this.getKey('duplicate-email', 'all'), duplicateEmailValidator);
        this.itemFormArraySaveValidators.addEntry('default', this.getKey('line-items-required', 'all'), lineItemsRequiredValidator);

        this.filterValidators.addEntry('default', this.getKey('date', 'all'), dateValidator);
        this.filterValidators.addEntry('default', this.getKey('datetime', 'all'), datetimeValidator);
        this.filterValidators.addEntry('default', this.getKey('float', 'all'), floatValidator);
        this.filterValidators.addEntry('default', this.getKey('currency', 'all'), currencyValidator);
        this.filterValidators.addEntry('default', this.getKey('int', 'all'), intValidator);
        this.filterValidators.addEntry('default', this.getKey('phone', 'all'), phoneValidator);
    }

    public registerFieldSaveValidator(module: string, type: string, field: string, validator: ValidatorInterface): void {
        this.saveValidators.addEntry(module, this.getKey(type, field), validator);
    }

    public registerSaveValidator(module: string, type: string, validator: ValidatorInterface): void {
        this.saveValidators.addEntry(module, this.getKey(type, 'all'), validator);
    }

    public registerFieldFilterValidator(module: string, type: string, field: string, validator: ValidatorInterface): void {
        this.filterValidators.addEntry(module, this.getKey(type, field), validator);
    }

    public registerFilterValidator(module: string, type: string, validator: ValidatorInterface): void {
        this.filterValidators.addEntry(module, this.getKey(type, 'all'), validator);
    }

    public excludeFieldSaveValidator(module: string, type: string, field: string): void {

        const moduleExclusions = this.saveFieldExclusions[module] || {};
        const key = this.getKey(type, field);
        moduleExclusions[key] = key;
        this.saveFieldExclusions[module] = moduleExclusions;
    }

    public excludeSaveValidator(module: string, type: string): void {
        this.saveValidators.excludeEntry(module, this.getKey(type, 'all'));
    }

    public excludeFieldFilterValidator(module: string, type: string, field: string): void {
        const moduleExclusions = this.filterFieldExclusion[module] || {};
        const key = this.getKey(type, field);
        moduleExclusions[key] = key;
        this.filterFieldExclusion[module] = moduleExclusions;
    }

    public excludeFilterValidator(module: string, type: string): void {
        this.filterValidators.excludeEntry(module, this.getKey(type, 'all'));
    }

    public registerAsyncSaveValidator(module: string, type: string, validator: AsyncValidatorInterface): void {
        this.asyncSaveValidators.addEntry(module, this.getKey(type, 'all'), validator);
    }

    public excludeAsyncSaveValidator(module: string, type: string): void {
        this.saveValidators.excludeEntry(module, this.getKey(type, 'all'));
    }

    public getSaveValidations(module: string, viewField: ViewFieldDefinition, record: Record): ValidatorFn[] {
        const entries = this.saveValidators.getGroupEntries(module);
        const exclusions = this.getExclusions(module, this.saveFieldExclusions);
        return this.filterValidations(entries, exclusions, record, viewField);
    }

    public getItemFormArraySaveValidations(module: string, viewField: ViewFieldDefinition, record: Record): ValidatorFn[] {
        const entries = this.itemFormArraySaveValidators.getGroupEntries(module);
        const exclusions = this.getExclusions(module, this.saveFieldExclusions);
        return this.filterValidations(entries, exclusions, record, viewField);
    }

    public getFilterValidations(module: string, viewField: ViewFieldDefinition, record: Record): ValidatorFn[] {
        const entries = this.filterValidators.getGroupEntries(module);
        const exclusions = this.getExclusions(module, this.filterFieldExclusion);
        return this.filterValidations(entries, exclusions, record, viewField);
    }

    public getAsyncSaveValidations(module: string, viewField: ViewFieldDefinition, record: Record): AsyncValidatorFn[] {
        const validations = [];

        const entries = this.asyncSaveValidators.getGroupEntries(module);

        Object.keys(entries).forEach(validatorKey => {

            const validator = entries[validatorKey];

            if (validator.applies(record, viewField)) {
                validations.push(validator.getValidator(viewField, record));
            }
        });

        const definitionValidators = viewField?.fieldDefinition?.asyncValidators ?? null;

        if (!definitionValidators){
            return validations;
        }

        Object.keys(definitionValidators).forEach((key) => {
            const validator = definitionValidators[key];
            validations.push(this.asyncProcessValidator.getValidator(validator, viewField, record));
        });

        return validations;
    }

    public getKey(type: string, field: string): string {
        return `${type}.${field}`;
    }

    protected parseType(key: string): string {
        const partsType = key.split('.') || [];
        return partsType[0] || '';
    }

    protected getExclusions(module: string, exclusionMap: StringMatrix): StringMap {
        const defaultExclusions = exclusionMap['default'] || {};
        const moduleExclusions = exclusionMap[module] || {};
        return {...defaultExclusions, ...moduleExclusions};
    }

    protected filterValidations(
        entries: MapEntry<ValidatorInterface>,
        fieldExclusions: StringMap,
        record: Record,
        viewField: ViewFieldDefinition
    ): ValidatorFn[] {
        let validations = [];

        Object.keys(entries).forEach(validatorKey => {
            const defaultTypeKey = this.getKey(this.parseType(validatorKey), viewField.name);
            if (fieldExclusions[validatorKey] || fieldExclusions[defaultTypeKey]) {
                return;
            }

            const validator = entries[validatorKey];

            if (validator.applies(record, viewField)) {
                validations = validations.concat(validator.getValidator(viewField, record));
            }
        });

        return validations;
    }
}
