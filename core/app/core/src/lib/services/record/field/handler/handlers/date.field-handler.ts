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
import {Injectable} from "@angular/core";
import {ProcessService} from "../../../../process/process.service";
import {take} from "rxjs/operators";
import {AsyncActionInput} from "../../../../process/processes/async-action/async-action";
import {BaseField, Field} from '../../../../../common/record/field.model';
import {Record} from '../../../../../common/record/record.model';
import {BaseFieldHandler} from "./base.field-handler";
import {MessageService} from "../../../../message/message.service";
@Injectable({
    providedIn: 'root'
})
export class DateFieldHandler extends BaseFieldHandler<BaseField> {

    constructor(
        protected processService: ProcessService,
        protected messages: MessageService,
    ) {
        super(processService, messages);
    }

    initDefaultValue(field: BaseField, record: Record): void {

        if (field.defaultValueInitialized) {
            return;
        }

        let defaultValue = field?.default ?? field?.definition?.default ?? null;
        let displayDefault = field?.definition?.display_default ?? null;
        if (!defaultValue && !displayDefault) {
            field.defaultValueInitialized = true;
            return;
        }

        if (typeof defaultValue !== "string" && typeof displayDefault !== "string") {
            return;
        }

        if (defaultValue && typeof defaultValue !== "string") {
            super.initDefaultValue(field, record);
            return;
        }

        const processType = 'calculate-date-default';

        const options = {
            action: processType,
            module: record.module ?? '',
            field: field.name,
            displayDefault: displayDefault
        } as AsyncActionInput;

        field.loading.set(true)

        this.processService.submit(processType, options).pipe(take(1)).subscribe((result) => {

            const value = result?.data?.value ?? null;
            field.loading.set(false)

            if (value === null) {
                this.messages.addDangerMessageByKey("ERR_FIELD_LOGIC_BACKEND_CALCULATION");
                return;
            }
            this.updateValue(field, value.toString(), record);
            field.defaultValueInitialized = true;

        });


    }

    protected updateValue(field: Field, value: string, record: Record): void {
        field.value = value.toString();
        field.formControl.setValue(value);
        // re-validate the parent form-control after value update
        record.formGroup.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

}
