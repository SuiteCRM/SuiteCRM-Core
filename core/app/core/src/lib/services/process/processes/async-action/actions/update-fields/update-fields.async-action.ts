/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {AsyncActionData, AsyncActionHandler} from '../../async-action.model';
import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {MessageService} from '../../../../../message/message.service';
import {ActionData} from "../../../../../../common/actions/action.model";
import {RecordStore} from "../../../../../../store/record/record.store";
import {Record} from "../../../../../../common/record/record.model";
import {Process} from "../../../../process.service";

@Injectable({
    providedIn: 'root'
})
export class UpdateFieldsAsyncAction extends AsyncActionHandler {
    key = 'update-fields';

    constructor(
        protected router: Router,
        protected message: MessageService,
    ) {
        super();
    }

    run(data: AsyncActionData, processResult?: Process, actionData?: ActionData): void {
        const store = actionData?.store ?? null;
        const recordStore = store?.recordStore ?? null as RecordStore;
        const stagingState = recordStore?.getStaging() ?? null as Record;
        if (!stagingState) {
            this.message.addDangerMessageByKey('LBL_MISSING_RECORD_DATA');
            return;
        }

        const values = data?.values ?? null;
        if (!values) {
            this.message.addDangerMessageByKey('LBL_MISSING_FIELDS_DATA');
            return;
        }

        Object.keys(values).forEach((key) => {
            const value = values[key];
            const field = stagingState?.fields[key] ?? null;
            if (!field || !value) {
                return;
            }

            if (value?.value) {
                field.value = value?.value;
                field.formControl.setValue(value?.value);
            }

            if (value?.valueList) {
                field.valueList = value?.valueList;
            }

            if (value?.valueObject) {
                field.valueObject = value?.valueObject;
            }

            if (value?.valueObjectArray) {
                field.valueObjectArray = value?.valueObjectArray;
            }
        });

        stagingState?.formGroup?.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }
}
