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
import {Router} from '@angular/router';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {ActionNameMapper} from '../../../../services/navigation/action-name-mapper/action-name-mapper.service';
import {MessageModalComponent} from '../../../../components/modal/components/message-modal/message-modal.component';

@Injectable({
    providedIn: 'root'
})
export class CancelCreateAction extends RecordActionHandler {

    key = 'cancelCreate';
    modes = ['create' as ViewMode];

    constructor(
        private modalService: NgbModal,
        protected router: Router,
        protected moduleNameMapper: ModuleNameMapper,
        protected actionNameMapper: ActionNameMapper
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
        const store = data.store;

        let returnAction = store.params.return_action || '';
        let returnId = store.params.return_id || '';
        let returnModule = store.params.returnModule ?? store.getModuleName();

        if (store.params.return_module) {
            returnModule = this.moduleNameMapper.toFrontend(store.params.return_module);
        }

        if (store.params.parent_id) {
            returnModule = this.moduleNameMapper.toFrontend(store.params.parent_type);
            returnId = store.params.parent_id;
        }

        if (store.params.isConvert) {
            returnModule = this.moduleNameMapper.toFrontend(store.params.convertModule);
            returnId = store.params.originalId;
        }

        let route = '/' + returnModule;

        if (returnAction) {
            returnAction = this.actionNameMapper.toFrontend(returnAction);

            if (returnAction !== 'record' || returnId) {
                route += '/' + returnAction;
            }
        }

        if (returnId) {
            route += '/' + returnId;
        }

        this.router.navigate([route]).then();
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
                labelKey: 'LBL_OK',
                klass: ['btn-main'],
                onClick: activeModal => {
                    this.cancel(data);
                    activeModal.close();
                }
            } as ModalButtonInterface,
        ];
    }
}
