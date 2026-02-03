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

import {Observable, Subscription} from 'rxjs';
import {StateStore} from "../state";
import {RecordThreadStore} from "../../containers/record-thread/store/record-thread/record-thread.store";
import {AppStateStore} from "../app-state/app-state.store";
import {SystemConfigStore} from "../system-config/system-config.store";
import {RecordThreadModalService} from "./record-thread-modal.service";
import {BaseRecordContainerStoreInterface} from "../../common/containers/record/record-container.store.model";
import {RecordStore} from "../record/record.store";
import {RecordThreadModalModel} from "../../services/modals/record-thread-modal.model";

let cache$: Observable<any> = null;

export class RecordThreadModalStore implements StateStore, BaseRecordContainerStoreInterface {

    /**
     * Public long-lived observable streams
     */
    recordStore: RecordStore;
    optionsKey = '';
    config: RecordThreadModalModel;


    protected recordThreadStore: RecordThreadStore;
    protected subs: Subscription[] = [];

    constructor(
        protected appStateStore: AppStateStore,
        protected configs: SystemConfigStore,
        protected recordThreadModalService: RecordThreadModalService,
    ) {
    }

    /**
     * Clear state
     */
    public clear(): void {
        cache$ = null;
    }

    public clearAuthBased(): void {
        this.clear();
        this.recordThreadStore.clear();
        this.recordThreadStore = null;
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
    }

    public init(): void {
        if (this.appStateStore.isLoggedIn()) {
            this.initStore();
        }
    }


    /**
     * Initialize store
     */
    public initStore(optionsKey?: string): void {
        if (this.recordThreadStore) {
            return;
        }

        const key = optionsKey ?? this.optionsKey;

        if (!key) {
            return;
        }

        this.config = this.recordThreadModalService.getOptions(key);
        const options = this.config?.recordThreadConfig?.recordThreadOptions ?? {};

        this.recordThreadStore = this.recordThreadModalService.buildRecordThreadStore();
        this.subs.push(this.recordThreadModalService.initRecordThreadStore(options, this.recordThreadStore).subscribe((records) => {
            if (records?.length) {
                const options = {
                    ...this.config.modalConfig,
                    modalStore: this,
                }
                this.recordThreadModalService.showModal(options, {
                    addToAppState: false,
                })
            }
        }));

    }
    /**
     *
     * @returns {object}
     */
    public getRecordThreadStore(): RecordThreadStore {
        return this.recordThreadStore;
    }

    /**
     * Get the current module
     *
     * @returns {string} current view
     */
    public getModule(): string {
        return this.appStateStore.getModule();
    }

    /**
     * Get the current view
     *
     * @returns {string} current view
     */
    public getView(): string {
        return this.appStateStore.getView();
    }

    /**
     * On login handlers
     * @protected
     */
    protected onLogin(): void {
    }

    /**
     * On logout handlers
     * @protected
     */
    protected onLogout(): void {
        this.clearAuthBased();
    }

    public reloadRecordThread(): void {
        if (this.recordThreadStore) {
            this.recordThreadStore.reload();
        }
    }

    /**
     * Check if loaded
     */
    public isCached(): boolean {
        return cache$ !== null;
    }

}
