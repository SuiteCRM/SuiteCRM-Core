/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {Action} from '../../../common/actions/action.model';
import {Record} from '../../../common/record/record.model';
import {Field} from '../../../common/record/field.model';
import {StringArrayMap} from '../../../common/types/string-map';
import {ViewMode} from '../../../common/views/view.model';
import {ActiveFieldsChecker} from "../../../services/condition-operators/active-fields-checker.service";
import {CurrencyFormatter} from "../../../services/formatters/currency/currency-formatter.service";
import {ObjectArrayMatrix} from "../../../common/types/object-map";

@Injectable({
    providedIn: 'root'
})
export class UpdateValueAction extends FieldLogicActionHandler {

    key = 'updateValue';
    modes = ['edit', 'detail', 'list', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor(protected activeFieldsChecker: ActiveFieldsChecker, protected currencyFormatter: CurrencyFormatter) {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const activeOnFields: StringArrayMap = (action.params && action.params.activeOnFields) || {} as StringArrayMap;
        const relatedFields: string[] = Object.keys(activeOnFields);

        const activeOnAttributes: ObjectArrayMatrix = (action.params && action.params.activeOnAttributes) || {} as ObjectArrayMatrix;
        const relatedAttributesFields: string[] = Object.keys(activeOnAttributes);

        if (!relatedFields.length && !relatedAttributesFields.length) {
            return;
        }

        const targetValue = action.params && action.params.targetValue;

        if (!targetValue) {
            return;
        }

        const isActive = this.activeFieldsChecker.isActive(relatedFields, record, activeOnFields, relatedAttributesFields, activeOnAttributes);

        let value = data.field?.value;

        if (isActive) {
            value = targetValue;
        }

        if (this.isCurrencyField(field)) {
            const options = {
                mode: 'edit' as ViewMode,
                fromFormat: 'system'
            }
            value = this.currencyFormatter.toUserFormat(value, options);
        }

        this.updateValue(field, value.toString(), record);

    }

    getTriggeringStatus(): string[] {
        return ['onDependencyChange'];
    }

    /**
     * Update the new value
     * @param {object} field
     * @param value
     * @param {object} record
     */
    protected updateValue(field: Field, value: string, record: Record): void {

        field.value = value.toString();
        field.formControl.setValue(value);
        // re-validate the parent form-control after value update
        record.formGroup.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

    protected isCurrencyField(field: Field): boolean {
        return field.type === 'currency';
    }
}
