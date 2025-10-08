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
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {ViewMode} from "../../../../common/views/view.model";
import {SubpanelActionData, SubpanelActionHandler} from "../subpanel.action";
import {RecordModalOptions} from "../../../../services/modals/record-modal.model";

@Injectable({
    providedIn: 'root'
})
export class SubpanelModalCreateAction extends SubpanelActionHandler {

    key = 'modal-create';
    modes = ['list' as ViewMode];

    constructor(
        protected modalService: NgbModal,
        protected appState: AppStateStore,
    ) {
        super();
    }

    shouldDisplay(data: SubpanelActionData): boolean {
        return true;
    }

    run(data: SubpanelActionData): void {
        const options = {
            ...data.action.params,
            module: data.module,
            parentId: data.parentId,
            record: data.store.parentRecord,
            parentModule: data.parentModule,
            metadataView: data.action.metadataView ?? 'recordView'
        } as RecordModalOptions;

        options.mode = 'create' as ViewMode;

        this.appState.openRecordModal(options);
    }
}
