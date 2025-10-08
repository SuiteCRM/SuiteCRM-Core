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

import {ValidatorInterface} from '../validator.Interface';
import {AbstractControl, Validators} from '@angular/forms';
import {FieldDefinition, ValidationDefinition} from '../../../../common/record/field.model';
import {Injectable} from '@angular/core';
import {Record} from '../../../../common/record/record.model';
import {StandardValidatorFn, StandardValidationErrors} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {isNumber} from "lodash-es";

export const minValidator = (min: number): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {

        const result = Validators.min(min)(control);

        if (result === null) {
            return null;
        }

        return {
            emailValidator: {
                ...result,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_MIN',
                    context: {
                        value: control.value,
                        min: '' + min
                    }
                }
            }
        };
    }
);

export const maxValidator = (max: number): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {

        const result = Validators.max(max)(control);

        if (result === null) {
            return null;
        }

        return {
            emailValidator: {
                ...result,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_MAX',
                    context: {
                        value: control.value,
                        max: '' + max
                    }
                }
            }
        };
    }
);

@Injectable({
    providedIn: 'root'
})
export class RangeValidator implements ValidatorInterface {

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        const definition = viewField.fieldDefinition;

        return this.getRangeValidation(definition) !== null;
    }

    getValidator(viewField: ViewFieldDefinition): StandardValidatorFn[] {

        if (!viewField || !viewField.fieldDefinition) {
            return [];
        }

        const validation = this.getRangeValidation(viewField.fieldDefinition);

        if (!validation) {
            return [];
        }

        let max = null;
        if (isNumber(validation?.max)) {
            max = parseInt('' + validation.max, 10);
        }

        let min = null;
        if (isNumber(validation?.min)) {
            min = parseInt('' + validation.min, 10);
        }

        const validations = [];

        if (isNumber(min) && isFinite(min)) {
            validations.push(minValidator(min));
        }

        if (isNumber(max) && isFinite(max)) {
            validations.push(maxValidator(max));
        }

        return validations;
    }

    protected getRangeValidation(definition: FieldDefinition): ValidationDefinition {

        if (this.isRangeValidation(definition.validation)) {
            return definition.validation;
        }

        if (!definition.validations || !definition.validations.length) {
            return null;
        }

        let validation: ValidationDefinition = null;

        definition.validations.some(entry => {
            validation = entry;
            return this.isRangeValidation(entry);
        });

        return validation;
    }

    protected isRangeValidation(validation: ValidationDefinition): boolean {
        return validation && validation.type === 'range';
    }
}
