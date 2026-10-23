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
import {ViewMode} from '../../../../common/views/view.model';
import {take} from 'rxjs/operators';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {
    allValidationErrorsSilent,
    collectRecordValidationErrors,
    collectValidationErrors
} from "../../../../common/services/validators/validators.model";

@Injectable({
    providedIn: 'root'
})
export class RecordSaveNewAction extends RecordActionHandler {

    key = 'saveNew';
    modes = ['create' as ViewMode];

    constructor(
        protected message: MessageService,
        protected navigation: ModuleNavigation
    ) {
        super();
    }

    run(data: RecordActionData): void {

        const record = data.store.recordStore.getStaging();
        const fields = record.fields;

        data.action.isRunning.set(true);
        this.setAsyncValidators(fields);
        const formGroup = record.formGroup;
        this.setRecordAsyncValidators(record, formGroup);

        data.store.recordStore.validate().pipe(take(1)).subscribe(valid => {
            const collectedErrors = collectValidationErrors(formGroup, fields);
            const recordErrors = collectRecordValidationErrors(formGroup);
            this.clearAsyncValidators(fields);
            this.clearRecordAsyncValidators(formGroup);
            data.action.isRunning.set(false);

            if (valid) {
                data.store.save().pipe(take(1)).subscribe(
                    record => {
                        const store = data.store;
                        const params = store.params;
                        const moduleName = store.getModuleName();
                        this.navigation.navigateBack(record, moduleName, params);
                    }
                );
                return;
            }

            if (!allValidationErrorsSilent(collectedErrors)) {
                this.validationMessage.reportValidationErrors(recordErrors, record?.module);
            }
        });
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }
}
