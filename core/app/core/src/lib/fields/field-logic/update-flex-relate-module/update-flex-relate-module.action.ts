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
import {Action} from '../../../common/actions/action.model';
import {Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';

@Injectable({
    providedIn: 'root'
})
export class UpdateFlexRelateModuleAction extends FieldLogicActionHandler {

    key = 'update-flex-relate-module';
    modes = ['edit', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor() {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const typeField = field.definition.type_name ?? '';

        if (typeField === '') {
            return;
        }

        const type = record?.fields[typeField]?.value ?? '';
        const fieldModule = field?.definition?.module ?? '';

        if (type !== fieldModule) {
            field.definition.module = record?.fields[typeField]?.value ?? '';
            this.updateValue(field, {}, '', record);
        }
    }

    protected updateValue(field: Field, valueObject: any, value: string, record: Record): void {
        field.valueObject = valueObject;
        field.value = value;
        field.formControl.setValue(value);
        // re-validate the parent form-control after value update
        record.formGroup.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

    getTriggeringStatus(): string[] {
        return ['onDependencyChange'];
    }
}
