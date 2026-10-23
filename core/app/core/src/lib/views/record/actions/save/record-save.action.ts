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
import {NotificationStore} from '../../../../store/notification/notification.store';
import {RecentlyViewedService} from "../../../../services/navigation/recently-viewed/recently-viewed.service";
import {Router} from "@angular/router";
import {RecordPaginationService} from "../../store/record-pagination/record-pagination.service";
import {
    allValidationErrorsSilent,
    collectRecordValidationErrors,
    collectValidationErrors
} from "../../../../common/services/validators/validators.model";

@Injectable({
    providedIn: 'root'
})
export class RecordSaveAction extends RecordActionHandler {

    key = 'save';
    modes = ['edit' as ViewMode];

    constructor(
        protected router: Router,
        protected message: MessageService,
        protected navigation: ModuleNavigation,
        protected notificationStore: NotificationStore,
        protected recentlyViewedService: RecentlyViewedService,
        protected recordPaginationService: RecordPaginationService
    ) {
        super();
    }

    run(data: RecordActionData): void {
        const record = data.store.recordStore.getStaging();
        const fields = record.fields;
        const isFieldLoading = Object.keys(fields).some(fieldKey => {
            const field = fields[fieldKey];
            return field?.loading() ?? false;
        });

        if (isFieldLoading) {
            this.message.addWarningMessageByKey('LBL_LOADING_IN_PROGRESS');
            return;
        }

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
                data.store.save().pipe(take(1)).subscribe(record => {
                    const params = data.store.params;
                    const moduleName = data.store.getModuleName();
                    const id = record.id;
                    this.notificationStore.conditionalNotificationRefresh('edit');
                    const recentlyViewed = this.recentlyViewedService.buildRecentlyViewed(moduleName, id);
                    this.recentlyViewedService.addRecentlyViewed(moduleName, recentlyViewed);

                    if (data?.action?.params?.reloadSubpanels ?? true) {
                        data.store.reloadSubpanels();
                    }

                    const currentUrl = this.router.url;

                    if (currentUrl.includes('edit')) {
                        this.navigateBackToDetail(this.navigation, this.router, this.recordPaginationService, id, moduleName, params);
                    } else {
                        this.navigateBack(this.navigation, params, id, moduleName, record);
                    }
                });
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
