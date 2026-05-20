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
import {ListViewStore} from '../store/list-view/list-view.store';
import {
    RecordPanelActionData,
    RecordPanelConfig
} from '../../../containers/record-panel/components/record-panel/record-panel.model';
import {RecordPanelStore} from '../../../containers/record-panel/store/record-panel/record-panel.store';
import {BaseRecordActionsAdapter} from '../../../services/actions/base-record-action.adapter';
import {ListViewRecordPanelActionAdapterFactory} from './record-panel-actions.adapter.factory';
import {RecordPanelStoreFactory} from '../../../containers/record-panel/store/record-panel/record-panel.store.factory';
import {ViewMode} from '../../../common/views/view.model';
import {RecordManager} from '../../../services/record/record.manager';

@Injectable()
export class RecordPanelAdapter {

    constructor(
        protected store: ListViewStore,
        protected recordPanelStoreFactory: RecordPanelStoreFactory,
        protected actionAdapterFactory: ListViewRecordPanelActionAdapterFactory,
        protected recordManager: RecordManager
    ) {
    }

    getConfig(): RecordPanelConfig {
        const store: RecordPanelStore = this.createStore();


        return {
            module: this.getModule(),

            title: (this.store.recordPanelConfig && this.store.recordPanelConfig.title) || '',
            store: store,
            meta: this.store.recordPanelConfig,

            actions: this.createActionAdapter(store),

            onClose: (): void => {
                this.store.closeRecordPanel();
            },

        } as RecordPanelConfig;
    }

    /**
     * Get configured module
     * @returns {string} module
     */
    protected getModule(): string {
        return this.store.recordPanelConfig.module || this.store.getModuleName();
    }

    /**
     * Get configured view mode
     * @returns {string} ViewMode
     */
    protected getViewMode(): ViewMode {
        return this.store.recordPanelConfig.mode || 'edit' as ViewMode;
    }

    /**
     * Create and init store
     * @returns {object} RecordPanelStore
     */
    protected createStore(): RecordPanelStore {
        const store = this.recordPanelStoreFactory.create();
        const blankRecord = this.recordManager.buildEmptyRecord(this.getModule());

        store.setMetadata(this.store.recordPanelConfig);
        store.initRecord(blankRecord, this.getViewMode(), false);

        return store;
    }

    /**
     * Create action adapter
     * @returns {object} BaseRecordActionsAdapter
     */
    protected createActionAdapter(store: RecordPanelStore): BaseRecordActionsAdapter<RecordPanelActionData> {
        return this.actionAdapterFactory.create(
            store,
            this.store
        )
    }
}
