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
import {AbstractControl} from '@angular/forms';
import {Injectable} from '@angular/core';
import {PhoneFormatter} from '../../../formatters/phone/phone-formatter.service';
import {Record} from '../../../../common/record/record.model';
import {StandardValidatorFn, StandardValidationErrors} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';

export const phoneValidator = (formatter: PhoneFormatter, customRegex?: string): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {

        const regexPattern = customRegex || formatter.getDefaultFormatPattern();
        const invalid = formatter.validateUserFormat(control.value, regexPattern);

        return invalid ? {
            phoneValidator: {
                valid: false,
                format: regexPattern,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_PHONE_FORMAT',
                    context: {
                        value: control.value
                    }
                }
            },
        } : null;
    }
);

@Injectable({
    providedIn: 'root'
})
export class PhoneValidator implements ValidatorInterface {

    constructor(protected formatter: PhoneFormatter) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        return viewField.type === 'phone';
    }

    getValidator(viewField: ViewFieldDefinition): StandardValidatorFn[] {

        if (!viewField || !viewField.fieldDefinition) {
            return [];
        }

        const customRegex = viewField?.fieldDefinition?.validation?.regex.toString() ?? null;

        return [phoneValidator(this.formatter, customRegex)];
    }
}
