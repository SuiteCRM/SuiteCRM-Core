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
import {DateFormatter} from '../../../formatters/datetime/date-formatter.service';
import {FormControlUtils} from '../../field/form-control.utils';
import {Record} from '../../../../common/record/record.model';
import {StandardValidatorFn, StandardValidationErrors} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';

export const dateValidator = (formatter: DateFormatter, userFormat: string): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {

        const invalid = formatter.validateUserFormat(control.value, userFormat);
        return invalid ? {
            invalidDate: {
                value: control.value,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_DATE_FORMAT',
                    context: {
                        value: control.value,
                        expected: formatter.toUserFormat('2020-01-23')
                    }
                }
            },

        } : null;
    }
);

@Injectable({
    providedIn: 'root'
})
export class DateValidator implements ValidatorInterface {

    constructor(protected formatter: DateFormatter, protected utils: FormControlUtils) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        return viewField.type === 'date';
    }

    getValidator(viewField: ViewFieldDefinition): StandardValidatorFn[] {

        if (!viewField || !viewField.fieldDefinition) {
            return [];
        }

        let userFormat = viewField?.metadata?.date_time_format || '';

        return [dateValidator(this.formatter, userFormat)];
    }


}
