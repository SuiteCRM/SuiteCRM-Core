/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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
import {ValidatorInterface} from '../validator.Interface';
import {AbstractControl} from '@angular/forms';
import {FormControlUtils} from '../../field/form-control.utils';
import {Record} from '../../../../common/record/record.model';
import {StandardValidationErrors, StandardValidatorFn} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {Field} from "../../../../common/record/field.model";

export const attachmentsValidator = (viewField: ViewFieldDefinition, record: Record, utils: FormControlUtils): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {

        const name = viewField.name || '';

        if (!name || !record || !record.fields) {
            return null;
        }

        const field = record?.fields[name] ?? {} as Field;

        if (!field) {
            return null;
        }

        let activeItems: any = field?.valueList ?? field?.valueObject ?? null;

        if (activeItems === null) {
            return null;
        }

        let valid = true;

        Object.values(activeItems).forEach((value: Record) => {
            const attributes = value?.attributes ?? [];
            if (attributes['status'] === 'error') {
                valid = false;
            }
        });

        if (valid) {
            return null;
        }

        return {
            required: {
                required: true,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_ATTACHMENT',
                    context: {}
                }
            }
        };
    }
);


@Injectable({
    providedIn: 'root'
})
export class AttachmentValidator implements ValidatorInterface {

    constructor(protected utils: FormControlUtils) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        return (viewField?.type ?? viewField?.fieldDefinition?.type) === 'attachment';
    }

    getValidator(viewField: ViewFieldDefinition, record: Record): StandardValidatorFn[] {

        if (!(viewField?.type ?? viewField?.fieldDefinition?.type)) {
            return [];
        }

        return [attachmentsValidator(viewField, record, this.utils)];
    }

}
