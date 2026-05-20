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
import {RecordActionData, RecordActionHandler} from '../record.action';
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {RecordModalComponent} from "../../../../containers/record-modal/components/record-modal/record-modal.component";
import {take} from "rxjs/operators";
import {ActivatedRoute} from "@angular/router";
import {ViewMode} from "../../../../common/views/view.model";

@Injectable({
    providedIn: 'root'
})
export class ModalEditAction extends RecordActionHandler {

    key = 'modal-edit';
    modes = ['detail' as ViewMode];

    constructor(
        protected modalService: NgbModal,
        protected activatedRoute: ActivatedRoute
    ) {
        super();
    }

    run(data: RecordActionData): void {
        const modal = this.modalService.open(RecordModalComponent, {size: 'xl', scrollable: true});
        const module = data.store.getModuleName();
        const recordId = data.store.getRecordId();
        const mode = 'edit' as ViewMode;

        modal.componentInstance.mode = mode;
        modal.componentInstance.module = module;
        modal.componentInstance.recordId = recordId;

        modal.dismissed.pipe(take(1)).subscribe(reason => {
            if (reason === 'save') {
                data.store.load(false).pipe(take(1)).subscribe();
            }
        });
    }

    shouldDisplay(data: RecordActionData): boolean {
        return this.checkRecordAccess(data, ['edit']);
    }
}
