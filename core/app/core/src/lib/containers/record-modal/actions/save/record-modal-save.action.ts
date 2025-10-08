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

import {Injectable} from '@angular/core';
import {take} from 'rxjs/operators';
import {Router} from "@angular/router";
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {NotificationStore} from '../../../../store/notification/notification.store';
import {RecordModalActionData, RecordModalActionHandler} from "../record-modal.action";
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {ViewMode} from "../../../../common/views/view.model";
import {FieldMap} from "../../../../common/record/field.model";

@Injectable({
    providedIn: 'root'
})
export class RecordModalSaveAction extends RecordModalActionHandler {

    key = 'save';
    modes = ['edit', 'create'] as ViewMode[];

    constructor(
        protected router: Router,
        protected message: MessageService,
        protected navigation: ModuleNavigation,
        protected notificationStore: NotificationStore,
        protected modalService: NgbModal
    ) {
        super();
    }

    run(data: RecordModalActionData): void {
        const record = data.store.recordStore.getStaging();
        const fields = record.fields;
        const isFieldLoading = Object.keys(fields).some(fieldKey => {
            const field = fields[fieldKey];
            return field.loading() ?? false;
        });

        if (isFieldLoading) {
            this.message.addWarningMessageByKey('LBL_LOADING_IN_PROGRESS');
            return;
        }

        data.action.isRunning.set(true);
        this.setAsyncValidators(fields);

        data.store.recordStore.validate().pipe(take(1)).subscribe(valid => {
            this.clearAsyncValidators(fields);
            data.action.isRunning.set(false);

            if (valid) {
                data.store.save().pipe(take(1)).subscribe(record => {
                    this.notificationStore.conditionalNotificationRefresh('edit');
                });

                this.modalService.dismissAll(this.key);
                return;
            }
            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
        });
    }

    shouldDisplay(data: RecordModalActionData): boolean {
        return true;
    }

    setAsyncValidators(fields: FieldMap): void {
        Object.keys(fields).forEach(fieldKey => {
            const field = fields[fieldKey];

            field.asyncValidationErrors = null;

            if (field?.asyncValidators?.length) {
                field.formControl.setAsyncValidators(field?.asyncValidators);
                field.formControl.updateValueAndValidity();
            }
        });
    }

    clearAsyncValidators(fields: FieldMap): void {
        Object.keys(fields).forEach(fieldKey => {
            const field = fields[fieldKey];

            if (field?.asyncValidators?.length) {
                field.formControl.clearAsyncValidators();
                field.formControl.updateValueAndValidity();
            }
        });
    }
}
