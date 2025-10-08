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
import {ModalButtonInterface} from '../../../../common/components/modal/modal.model';
import {ViewMode} from '../../../../common/views/view.model';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {MessageModalComponent} from '../../../../components/modal/components/message-modal/message-modal.component';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {Router} from "@angular/router";
import {RecordPaginationService} from "../../store/record-pagination/record-pagination.service";

@Injectable({
    providedIn: 'root'
})
export class RecordCancelAction extends RecordActionHandler {

    key = 'cancel';
    modes = ['edit' as ViewMode, 'detail' as ViewMode];

    constructor(
        private modalService: NgbModal,
        private navigation: ModuleNavigation,
        private router: Router,
        private recordPaginationService: RecordPaginationService
    ) {
        super();
    }

    run(data: RecordActionData): void {

        if (data.store.recordStore.isDirty()) {
            this.showConfirmationModal(data);
            return;
        }

        this.cancel(data);
    }

    shouldDisplay(): boolean {
        return true;
    }

    protected cancel(data: RecordActionData): void {

        const params = data.store.params;
        const moduleName = data.store.getModuleName();
        const id = data.store.getRecordId();
        const record = data.store.getBaseRecord();

        const currentUrl = this.router.url;

        if (currentUrl.includes('edit')) {
            this.navigateBackToDetail(this.navigation, this.router, this.recordPaginationService, id, moduleName, params);
        } else {
            this.navigateBack(this.navigation, params, id, moduleName, record);
        }

        data.store.recordStore.resetStaging();
        data.store.setMode('detail' as ViewMode);
    }

    protected showConfirmationModal(data: RecordActionData): void {
        const modal = this.modalService.open(MessageModalComponent);

        modal.componentInstance.textKey = 'WARN_UNSAVED_CHANGES';
        modal.componentInstance.buttons = [
            {
                labelKey: 'LBL_CANCEL',
                klass: ['btn-secondary'],
                onClick: activeModal => activeModal.dismiss()
            } as ModalButtonInterface,
            {
                labelKey: 'LBL_PROCEED',
                klass: ['btn-main'],
                onClick: activeModal => {
                    this.cancel(data);
                    activeModal.close();
                }
            } as ModalButtonInterface,
        ];
    }
}
