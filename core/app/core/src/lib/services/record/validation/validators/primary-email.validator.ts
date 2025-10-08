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
import {Field} from '../../../../common/record/field.model';
import {Record} from '../../../../common/record/record.model';
import {StandardValidatorFn, StandardValidationErrors} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {isTrue} from '../../../../common/utils/value-utils';
import {Injectable} from '@angular/core';

export const primaryEmailValidator = (viewField: ViewFieldDefinition, record: Record): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {
        const name = viewField.name || '';

        if (!name || !record || !record.fields) {
            return null;
        }

        const field = record.fields[name] || {} as Field;
        const items = field.items;

        if (!field || !items || !items.length) {
            return null;
        }

        let count = 0;

        const activeItems = items.filter(item => !(item && item.attributes && item.attributes.deleted));

        if (activeItems && activeItems.length < 1) {
            return null;
        }

        let nonEmpty = 0;

        activeItems.some(item => {
            const emailField = (item.fields && item.fields['email-fields']) || {} as Field;
            const primary = (emailField.attributes && emailField.attributes['primary_address']) || null;
            const emailAddress = (emailField.attributes && emailField.attributes['email_address']) || null;

            if(!emailAddress?.value) {
                return false;
            }

            nonEmpty++;

            if (!primary) {
                return false;
            }

            if (isTrue(primary.value)) {
                count++;
            }

            return count > 1;
        });

        if (count == 1) {
            return null;
        }

        if (count == 0 && nonEmpty === 0) {
            return null;
        }

        if (count == 0) {
            return {
                primaryEmailValidation: {
                    valid: false,
                    message: {
                        labelKey: 'LBL_NO_PRIMARY_EMAIL_VALIDATION_ERROR',
                        context: {}
                    }
                },
            };
        }

        return {
            primaryEmailValidation: {
                valid: false,
                message: {
                    labelKey: 'LBL_MULTIPLE_PRIMARY_EMAIL_VALIDATION_ERROR',
                    context: {}
                }
            },
        };
    }
);


@Injectable({
    providedIn: 'root'
})
export class PrimaryEmailValidator implements ValidatorInterface {

    constructor() {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        const type = viewField.type || viewField.fieldDefinition.type || '';
        const name = viewField.name || viewField.fieldDefinition.name || '';

        return type === 'line-items' && name === 'email_addresses';
    }

    getValidator(viewField: ViewFieldDefinition, record: Record): StandardValidatorFn[] {

        if (!viewField || !viewField.fieldDefinition || !record) {
            return [];
        }

        return [primaryEmailValidator(viewField, record)];
    }
}
