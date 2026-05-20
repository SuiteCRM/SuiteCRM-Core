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
import {FieldActionData, FieldActionHandler} from "../field.action";
import {AsyncActionInput, AsyncActionService} from "../../../../services/process/processes/async-action/async-action";
import {ActiveFieldsChecker} from "../../../../services/condition-operators/active-fields-checker.service";
import {MessageService} from "../../../../services/message/message.service";
import {ProcessService} from "../../../../services/process/process.service";
import {take} from "rxjs/operators";
import {ViewMode} from "../../../../common/views/view.model";
import {StringArrayMap} from "../../../../common/types/string-map";
import {Record} from "../../../../common/record/record.model";
import {Field} from "../../../../common/record/field.model";
import {RecordManager} from "../../../../services/record/record.manager";
import {ObjectArrayMatrix} from "../../../../common/types/object-map";


@Injectable({
    providedIn: 'root'
})
export class CalculateValueBackendAction extends FieldActionHandler {

    key = 'calculateValueBackend';
    modes = ['edit', 'create'] as ViewMode[];

    constructor(
        protected asyncActionService: AsyncActionService,
        protected processService: ProcessService,
        protected messages: MessageService,
        protected recordManager: RecordManager,
        protected activeFieldsChecker: ActiveFieldsChecker
    ) {
        super();
    }

    run(data: FieldActionData): void {
        let record = data.store.recordStore.getStaging();
        const field = record.fields[data.action.fieldName];
        const action = data.action;

        if (!record || !field) {
            return;
        }

        const activeOnFields: StringArrayMap = (action.params && action.params.activeOnFields) || {} as StringArrayMap;
        const relatedFields: string[] = Object.keys(activeOnFields);

        const activeOnAttributes: ObjectArrayMatrix = (action.params && action.params.activeOnAttributes) || {} as ObjectArrayMatrix;
        const relatedAttributesFields: string[] = Object.keys(activeOnAttributes);


        const process = action.params && action.params.process;

        if (!process) {
            return;
        }

        const isActive = this.activeFieldsChecker.isActive(relatedFields, record, activeOnFields, relatedAttributesFields, activeOnAttributes);

        if (isActive) {
            const processType = process;

            const baseRecord = this.recordManager.getBaseRecord(record);

            const options = {
                action: processType,
                module: record.module ?? '',
                record: baseRecord
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

            }, (error) => {
                field.loading.set(false)
                this.messages.addDangerMessageByKey("ERR_FIELD_LOGIC_BACKEND_CALCULATION");
            });
        }
    }

    /**
     * Update the new value
     * @param {object} field
     * @param {string} value
     * @param {object} record
     */
    protected updateValue(field: Field, value: string, record: Record): void {
        field.value = value.toString();
        field.formControl.setValue(value);
        // re-validate the parent form-control after value update
        record.formGroup.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

}
