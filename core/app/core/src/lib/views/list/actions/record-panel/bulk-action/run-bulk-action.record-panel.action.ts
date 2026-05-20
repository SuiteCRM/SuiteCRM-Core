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
import {ViewMode} from '../../../../../common/views/view.model';
import {isFalse} from '../../../../../common/utils/value-utils';
import {take} from 'rxjs/operators';
import {Process} from '../../../../../services/process/process.service';
import {ConfirmationModalService} from '../../../../../services/modals/confirmation-modal.service';
import {ListViewRecordPanelActionData, ListViewRecordPanelActionHandler} from '../record-panel.action';
import {MessageService} from '../../../../../services/message/message.service';
import {
    AsyncActionInput,
    AsyncActionService
} from '../../../../../services/process/processes/async-action/async-action';

@Injectable({
    providedIn: 'root'
})
export class RunBulkActionRecordPanelAction extends ListViewRecordPanelActionHandler {

    key = 'bulk-action';
    modes = [
        'detail' as ViewMode,
        'edit' as ViewMode,
        'list' as ViewMode,
        'create' as ViewMode,
        'massupdate' as ViewMode
    ];

    constructor(
        protected message: MessageService,
        protected asyncActionService: AsyncActionService,
        protected confirmation: ConfirmationModalService,
        protected asyncAction: AsyncActionService
    ) {
        super();
    }

    run(data: ListViewRecordPanelActionData): void {

        const definition = data.action;
        const selection = data.listStore.recordList.selection;

        const params = (definition && definition.params) || {} as { [key: string]: any };

        if (isFalse(params.allowAll) && selection.all) {
            let message = data.listStore.appStrings.LBL_SELECT_ALL_NOT_ALLOWED;
            this.message.addDangerMessage(message);
            return;
        }

        if (params.min && selection.count < params.min) {
            let message = data.listStore.appStrings.LBL_TOO_FEW_SELECTED;
            message = message.replace('{min}', params.min);
            this.message.addDangerMessage(message);
            return;
        }

        if (params.max && selection.count > params.max) {
            let message = data.listStore.appStrings.LBL_TOO_MANY_SELECTED;
            message = message.replace('{max}', params.max);
            this.message.addDangerMessage(message);
            return;
        }

        this.runBulkAction(data);
    }

    shouldDisplay(): boolean {
        return true;
    }

    /**
     * Run async buk action
     *
     * @returns void
     * @param {AsyncActionInput} data: data passed to the async process
     */
    public runBulkAction(data: ListViewRecordPanelActionData): void {

        const actionName = `bulk-${data.action.params.bulkAction}`;

        const asyncData = this.buildActionInput(actionName, data);

        this.asyncAction.run(actionName, asyncData).subscribe((process: Process) => {
            this.handleProcessResult(process, data);
        });
    }

    /**
     * Build backend bulk action input
     * @param actionName
     * @param data
     */
    protected buildActionInput(actionName: string, data: ListViewRecordPanelActionData) {

        const displayedFields = [];

        data.listStore.metadata.listView.fields.forEach(value => {
            displayedFields.push(value.name);
        });

        const asyncData = {
            action: actionName,
            module: data.listStore.getModuleName(),
            criteria: null,
            sort: null,
            ids: null,
            fields: displayedFields,
            payload: {
                panelRecord: data.store.recordStore.getBaseStaging()
            }
        } as AsyncActionInput;

        const selection = data.listStore.recordList.selection;

        if (selection.all && selection.count > data.listStore.recordList.records.length) {
            asyncData.criteria = data.listStore.recordList.criteria;
            asyncData.sort = data.listStore.recordList.sort;
        }

        if (selection.all && selection.count <= data.listStore.recordList.records.length) {
            asyncData.ids = [];
            data.listStore.recordList.records.forEach(record => {
                data.ids.push(record.id);
            });
        }

        if (!selection.all) {
            asyncData.ids = Object.keys(selection.selected);
        }

        return asyncData;
    }

    /**
     * Run this function once the process is executed
     *
     * @returns void
     * @param {object} process Process data returned by the process once the process is executed
     * @param {object} data ListViewRecordPanelActionData
     */
    protected handleProcessResult(process: Process, data: ListViewRecordPanelActionData): void {

        if (process.data && process.data.reload) {
            data.listStore.recordList.clearSelection();
            data.listStore.load(false).pipe(take(1)).subscribe();
        }

        if (process.data && process.data.dataUpdated) {
            data.listStore.triggerDataUpdate();
        }

        data.listStore.closeRecordPanel();
    }
}
