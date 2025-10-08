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
import {BulkActionsMap} from '../../../common/actions/bulk-action.model';
import {Record} from '../../../common/record/record.model';
import {isFalse} from '../../../common/utils/value-utils';
import {Observable} from 'rxjs';
import {map, take} from 'rxjs/operators';
import {AsyncActionInput, AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ListViewStore} from '../store/list-view/list-view.store';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {BulkActionDataSource} from '../../../components/bulk-action-menu/bulk-action-menu.component';
import {Metadata, MetadataStore} from '../../../store/metadata/metadata.store.service';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";

@Injectable()
export class BulkActionsAdapter implements BulkActionDataSource {

    constructor(
        protected store: ListViewStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected selectModalService: SelectModalService,
        protected asyncAction: AsyncActionService,
        protected metadata: MetadataStore,
        protected appMetadataStore: AppMetadataStore
    ) {
    }

    /**
     * Get bulk action
     * @returns {object} Observable<BulkActionsMap>
     */
    public getBulkActions(): Observable<BulkActionsMap> {
        return this.store.metadata$.pipe(
            map((metadata: Metadata) => metadata.listView.bulkActions)
        );
    }

    /**
     * Execute bulk actions
     * @param {string} action
     */
    public executeBulkAction(action: string): void {
        const selection = this.store.recordList.selection;
        const definition = this.store.metadata.listView.bulkActions[action];
        const actionName = `bulk-${action}`;

        this.message.removeMessages();

        if (isFalse(definition.params.allowAll) && selection.all) {
            let message = this.store.appStrings.LBL_SELECT_ALL_NOT_ALLOWED;
            this.message.addDangerMessage(message);
            return;
        }

        if (definition.params.min && selection.count < definition.params.min) {
            let message = this.store.appStrings.LBL_TOO_FEW_SELECTED;
            message = message.replace('{min}', definition.params.min);
            this.message.addDangerMessage(message);
            return;
        }

        if (definition.params.max && selection.count > definition.params.max) {
            let message = this.store.appStrings.LBL_TOO_MANY_SELECTED;
            message = message.replace('{max}', definition.params.max);
            this.message.addDangerMessage(message);
            return;
        }

        const displayedFields = [];

        this.store.metadata.listView.fields.forEach(value => {
            displayedFields.push(value.name);
        });

        const data = {
            action: actionName,
            module: this.store.getModuleName(),
            criteria: null,
            sort: null,
            ids: null,
            fields: displayedFields
        } as AsyncActionInput;

        if (selection.all && selection.count > this.store.recordList.records.length) {
            data.criteria = this.store.recordList.criteria;
            data.sort = this.store.recordList.sort;
        }

        if (selection.all && selection.count <= this.store.recordList.records.length) {
            data.ids = [];
            this.store.recordList.records.forEach(record => {
                data.ids.push(record.id);
            });
        }

        if (!selection.all) {
            data.ids = Object.keys(selection.selected);
        }

        const params = (definition && definition.params) || {} as { [key: string]: any };
        const displayConfirmation = params.displayConfirmation || false;
        const confirmationLabel = params.confirmationLabel || '';
        const confirmationMessages = params.confirmationMessages || [];
        const selectModal = definition.params && definition.params.selectModal;
        const selectModule = selectModal && selectModal.module;
        const recordPanel = definition.params && definition.params.recordPanel;

        if (recordPanel) {
            this.store.openRecordPanel(recordPanel);
            return;
        }

        const confirmation = [confirmationLabel, ...confirmationMessages];

        if (displayConfirmation) {
            this.confirmation.showModal(confirmation, () => {
                if (!selectModule) {
                    this.runBulkAction(actionName, data);
                    return;
                }
                this.showSelectModal(selectModal.module, actionName, data);
            });

            return;
        }

        if (!selectModule) {
            this.runBulkAction(actionName, data);
            return;
        }
        this.showSelectModal(selectModal.module, actionName, data);

    }

    /**
     * Run async buk action
     *
     * @returns void
     * @param {string} selectModule: module for which records are listed in Select Modal/Popup
     * @param {string} asyncAction: bulk action name
     * @param {AsyncActionInput} asyncData: data passed to the async process
     */
    public showSelectModal(selectModule: string, asyncAction: string, asyncData: AsyncActionInput) {

        this.selectModalService.showSelectModal(selectModule, (modalRecord: Record) => {
            if (modalRecord) {
                const {fields, formGroup, ...baseRecord} = modalRecord;
                asyncData.modalRecord = baseRecord;
            }
            this.runBulkAction(asyncAction, asyncData);
        });
    }

    /**
     * Run async buk action
     *
     * @returns void
     * @param {string} asyncAction: bulk action name
     * @param {AsyncActionInput} asyncData: data passed to the async process
     */
    public runBulkAction(asyncAction: string, asyncData: AsyncActionInput): void {

        this.asyncAction.run(asyncAction, asyncData).subscribe((process: Process) => {
            this.handleProcessResult(process);
        });
    }

    /**
     * Run this function once the process is executed
     *
     * @returns void
     * @param {Process} process: data returned by the process once the process is executed
     */
    public handleProcessResult(process: Process): void {

        if (process?.data && process?.data?.reload) {
            this.store.recordList.clearSelection();
            this.store.load(false).pipe(take(1)).subscribe();
        }

        if (process?.data && process?.data?.dataUpdated) {
            this.store.triggerDataUpdate();
        }

        this.reloadMetadata(this.store.getModuleName(), process);
    }


    /**
     * Reload the metadata for the module
     * @param moduleName
     * @param process
     * @protected
     */
    protected reloadMetadata(moduleName: string, process: Process): void {
        const typesToLoad = [];

        if (this.shouldReloadRecentlyViewed(process)) {
            typesToLoad.push(this.metadata.typeKeys.recentlyViewed);
        }

        if (this.shouldReloadFavorites(process)) {
            typesToLoad.push(this.metadata.typeKeys.favorites);
        }

        if (typesToLoad && typesToLoad.length) {
            this.metadata.reloadModuleMetadata(moduleName, typesToLoad, false).pipe(take(1)).subscribe();
        }

        if (this.shouldReloadGlobalRecentlyViewed(process)) {
            this.appMetadataStore.load(moduleName, ['globalRecentlyViewed'], false).pipe(take(1)).subscribe();
        }
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldReloadGlobalRecentlyViewed(process: Process): boolean {
        return !!(process?.data && process?.data?.reloadGlobalRecentlyViewed);
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldReloadRecentlyViewed(process: Process): boolean {
        return !!(process?.data && process?.data?.reloadRecentlyViewed);
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldReloadFavorites(process: Process): boolean {
        return !!(process?.data && process.data?.reloadFavorites);
    }
}
