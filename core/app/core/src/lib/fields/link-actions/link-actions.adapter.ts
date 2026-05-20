/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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
import {Action} from '../../common/actions/action.model';
import {Record} from '../../common/record/record.model';
import {Field} from '../../common/record/field.model';
import {ViewMode} from '../../common/views/view.model';
import {AsyncActionInput, AsyncActionService} from '../../services/process/processes/async-action/async-action';
import {MessageService} from '../../services/message/message.service';
import {ConfirmationModalService} from '../../services/modals/confirmation-modal.service';
import {SelectModalService} from '../../services/modals/select-modal.service';
import {LinkActionManager} from './link-action-manager.service';
import {LinkActionData} from './link-action.model';

@Injectable({
    providedIn: 'root'
})
export class LinkActionsAdapter {

    constructor(
        protected linkActionManager: LinkActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected selectModalService: SelectModalService
    ) {
    }

    runAction(action: Action, field: Field, record: Record, mode: ViewMode): void {
        const params = action?.params || {};
        const displayConfirmation = params.displayConfirmation || false;
        const confirmationLabel = params.confirmationLabel || '';
        const confirmationMessages = params.confirmationMessages || '';
        const selectModal = params.selectModal;
        const selectModule = selectModal?.module;

        const confirmation = [confirmationLabel, ...confirmationMessages];

        if (displayConfirmation) {
            this.confirmation.showModal(confirmation, () => {
                if (!selectModule) {
                    this.callAction(action, field, record, mode);
                    return;
                }

                this.showSelectModal(selectModule, action, field, record, mode);
            });
            return;
        }

        if (selectModule) {
            this.showSelectModal(selectModule, action, field, record, mode);
            return;
        }

        this.callAction(action, field, record, mode);
    }

    protected showSelectModal(selectModule: string, action: Action, field: Field, record: Record, mode: ViewMode): void {
        this.selectModalService.showSelectModal(selectModule, (modalRecord: Record) => {
            if (modalRecord) {
                const {fields, formGroup, ...baseRecord} = modalRecord;
                action.params.modalRecord = baseRecord;
            }
            this.callAction(action, field, record, mode);
        });
    }

    protected callAction(action: Action, field: Field, record: Record, mode: ViewMode): void {
        if (action.asyncProcess) {
            this.runAsyncAction(action, field, record);
            return;
        }

        this.runFrontEndAction(action, field, record, mode);
    }

    protected runAsyncAction(action: Action, field: Field, record: Record): void {
        const asyncData = this.buildAsyncInput(action, field, record);

        this.message.removeMessages();

        this.asyncActionService.run(action.key, asyncData).pipe(take(1)).subscribe();
    }

    protected runFrontEndAction(action: Action, field: Field, record: Record, mode: ViewMode): void {
        const data: LinkActionData = {field, record, action};
        this.linkActionManager.run(action, mode, data);
    }

    protected buildAsyncInput(action: Action, field: Field, record: Record): AsyncActionInput {
        return {
            payload: {
                fieldName: field.name ?? '',
                fieldValue: field.value ?? '',
            },
            record: {
                id: record.id ?? '',
                type: record.type ?? '',
                module: record.module ?? '',
                favorite: record.favorite ?? '',
                attributes: record.attributes ?? {},
                acls: record.acls ?? []
            } as Record
        } as AsyncActionInput;
    }
}
