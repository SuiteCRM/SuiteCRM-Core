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
import {Record} from '../../../../common/record/record.model';
import {ViewMode} from '../../../../common/views/view.model';
import {take} from 'rxjs/operators';
import {InstallViewActionData, InstallViewActionHandler} from '../install-view.action';
import {MessageService} from '../../../../services/message/message.service';
import {AsyncActionInput, AsyncActionService} from '../../../../services/process/processes/async-action/async-action';
import {Process} from '../../../../services/process/process.service';
import {Router} from '@angular/router';
import {InstallErrorModalComponent} from '../../../../components/install-error-modal/install-error-modal.component';
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {StateManager} from '../../../../store/state-manager';
import {LocalStorageService} from '../../../../services/local-storage/local-storage.service';

@Injectable({
    providedIn: 'root'
})
export class InstallAction extends InstallViewActionHandler {

    key = 'install';
    modes = ['edit' as ViewMode];

    constructor(
        protected message: MessageService,
        protected asyncActionService: AsyncActionService,
        protected router: Router,
        protected modalService: NgbModal,
        protected state: StateManager,
        protected localStorage: LocalStorageService
    ) {
        super();
    }

    run(data: InstallViewActionData): void {
        data.store.recordStore.validate().pipe(take(1)).subscribe(valid => {
            if (valid) {
                const stagingRecord = data.store.recordStore.getStaging();
                this.runInstall(stagingRecord);
                return;
            }

            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
        });
    }

    shouldDisplay(): boolean {
        return true;
    }

    runInstall(stagingRecord: Record): void {

        const actionName = `suitecrm-app-${this.key}`;

        this.message.removeMessages();

        const asyncData = {
            action: actionName,
            module: stagingRecord.module,
            id: stagingRecord.id,
            payload: stagingRecord.formGroup.value
        } as AsyncActionInput;

        this.asyncActionService.run(
            actionName,
            asyncData,
            null,
            {
                errorMessageLabel: 'An error occurred while installing SuiteCRM. Please check the \'/logs/install.log\'.'
            }
        ).pipe(take(1)).subscribe((process: Process) => {

            if(process.data.statusCode === 3) {
                // system validation pre-check failed; display errors modal
                this.openErrorModalDialog(process.data.errors);
            }

            // redirect to /, if request is successful
            if(process.data.statusCode === 0) {
                this.state.clear();
                this.localStorage.clear();
                this.router.navigate(['/'], {}).then();
            }
        });
    }

    openErrorModalDialog(errors: []): void {

        const modalRef = this.modalService.open(InstallErrorModalComponent, {
            ariaLabelledBy: 'modal-basic-title',
            centered: true,
            size: 'lg',
            windowClass: 'custom-modal',
            modalDialogClass: 'custom-modal'
        });

        modalRef.componentInstance.errors = errors;
    }

}
