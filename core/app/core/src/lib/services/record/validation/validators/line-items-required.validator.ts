/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {FormControlUtils} from "../../field/form-control.utils";
import {ViewFieldDefinition} from "../../../../common/metadata/metadata.model";
import {StandardValidationErrors, StandardValidatorFn} from "../../../../common/services/validators/validators.model";
import {Field} from "../../../../common/record/field.model";
import {Record} from "../../../../common/record/record.model";
import {isTrue} from "../../../../common/utils/value-utils";

export const lineItemsRequiredValidator = (viewField: ViewFieldDefinition, record: Record, utils: FormControlUtils): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {
        const name = viewField.name || '';

        if (!name || !record || !record.fields) {
            return null;
        }

        const field = record?.fields[name] ?? {} as Field;
        const items = field?.items ?? [];

        if (!field || !items) {
            return null;
        }

        const activeItems = items.filter(item => !(item && item.attributes && item.attributes.deleted));

        if (activeItems && activeItems.length > 0) {
            return null;
        }

        return {
            required: {
                required: true,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_REQUIRED',
                    context: {}
                }
            }
        };
    }
);


@Injectable({
    providedIn: 'root'
})
export class LineItemsRequiredValidator implements ValidatorInterface {

    constructor(protected utils: FormControlUtils) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        const type = viewField?.type ?? viewField?.fieldDefinition?.type ?? '';

        let required = viewField?.required ?? viewField?.fieldDefinition?.required ?? false;

        return type === 'line-items' && isTrue(required);
    }

    getValidator(viewField: ViewFieldDefinition, record: Record): StandardValidatorFn[] {

        return [lineItemsRequiredValidator(viewField, record, this.utils)];
    }
}
