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

import {Injectable} from '@angular/core';
import {take} from 'rxjs/operators';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {NotificationStore} from '../../../../store/notification/notification.store';
import {RecordPaginationService} from "../../store/record-pagination/record-pagination.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {ViewMode} from "../../../../common/views/view.model";
import {Router} from "@angular/router";
import {Field} from "../../../../common/record/field.model";

@Injectable({
    providedIn: 'root'
})
export class RecordSaveScheduleAction extends RecordActionHandler {

    key = 'saveSchedule';
    modes = ['edit' as ViewMode, 'create' as ViewMode];

    constructor(
        protected message: MessageService,
        protected router: Router,
        protected navigation: ModuleNavigation,
        protected notificationStore: NotificationStore,
        protected systemConfigStore: SystemConfigStore,
        protected recordPaginationService: RecordPaginationService
    ) {
        super();
    }

    run(data: RecordActionData): void {
        const fields = data.store.recordStore.getStaging().fields;
        const params = data.action.params;

        data.store.recordStore.validate().pipe(take(1)).subscribe(valid => {
            if (valid) {
                const statusKey = params.statusKey ?? 'status';
                fields[statusKey].value = 'scheduled';
                data.store.save().pipe(take(1)).subscribe(record => {
                    const moduleName = data.store.getModuleName();
                    const id = record.id;

                    this.navigateBackToDetail(this.navigation, this.router, this.recordPaginationService, id, moduleName, {});
                });
                return;
            }

            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
        });
    }

    shouldDisplay(data: RecordActionData): boolean {
        const currentModule = data.store.getModuleName();
        const modules = data.action?.params?.modules;
        let display = false;

        modules.forEach((module) => {
            if (currentModule === module) {
                display = true;
            }
        })

        return display;
    }
}
