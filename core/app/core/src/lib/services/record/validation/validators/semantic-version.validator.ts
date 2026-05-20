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

import {ValidatorInterface} from '../validator.Interface';
import {AbstractControl} from '@angular/forms';
import {Injectable} from '@angular/core';
import {Record} from '../../../../common/record/record.model';
import {StandardValidatorFn, StandardValidationErrors} from '../../../../common/services/validators/validators.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {ValidationDefinitionManager} from "../validation-definition.manager";

export const semanticVersionValidator = (): StandardValidatorFn => (
    (control: AbstractControl): StandardValidationErrors | null => {
        const value = control.value;
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const regex = /^\d+(\.\d+)*$/;

        const isValid = regex.test(String(value));

        return isValid ? null : {
            semanticVersion: {
                valid: false,
                message: {
                    labelKey: 'LBL_VALIDATION_ERROR_VERSIONING',
                    context: {
                        expected: 'x.y.z (e.g. 1.0.0)',
                    }
                }
            }
        };
    }
);

@Injectable({
    providedIn: 'root'
})
export class SemanticVersionValidator implements ValidatorInterface {

    constructor(
        protected validationManager: ValidationDefinitionManager
    ) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        if (!viewField || !viewField.fieldDefinition) {
            return false;
        }

        return this.validationManager.getValidationDefinition(viewField.fieldDefinition, 'semantic-version') !== null;
    }

    getValidator(viewField: ViewFieldDefinition, record: Record): StandardValidatorFn[] {
        if (!viewField || !viewField.fieldDefinition) {
            return [];
        }

        return [semanticVersionValidator()];
    }
}
