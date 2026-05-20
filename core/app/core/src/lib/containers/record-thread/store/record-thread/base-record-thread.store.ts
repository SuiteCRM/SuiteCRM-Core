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
import {StateStore} from '../../../../store/state';
import {BehaviorSubject, Observable, Subscription} from 'rxjs';
import {RecordList, RecordListStore} from '../../../../store/record-list/record-list.store';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {Record} from '../../../../common/record/record.model';
import {map, take, tap} from 'rxjs/operators';
import {BaseRecordContainerStore} from '../../../../store/record-container/base-record-container.store';
import {BaseRecordItemStoreFactoryInterface} from './base-record-thread-thread.model';

export interface RecordStoreMap<T extends BaseRecordContainerStore<M>, M> {
    [key: string]: T;
}

@Injectable()
export abstract class RecordStoreList<T extends BaseRecordContainerStore<M>, M> implements StateStore {

    storesMap$: Observable<RecordStoreMap<T, M>>;
    stores$: Observable<T[]>;
    module: string;
    protected subs: Subscription[] = [];
    protected recordList: RecordListStore;
    protected stores: T[] = [];
    protected storeSubject = new BehaviorSubject<T[]>([]);
    protected state$ = this.storeSubject.asObservable();
    protected pageSize: number = 10;

    protected constructor(
        protected listStoreFactory: RecordListStoreFactory,
        protected recordStoreFactory: BaseRecordItemStoreFactoryInterface<T, M>
    ) {
        this.recordList = listStoreFactory.create();
        this.stores$ = this.state$;
        this.storesMap$ = this.stores$.pipe(map(stores => {
            return this.getStoreMap(stores);
        }));
    }

    clear(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    clearAuthBased(): void {
    }

    public getItemMetadata(): M {
        return null;
    }

    public getRecordList():RecordListStore {
        return this.recordList;
    }

    /**
     * Initial list records load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @param {boolean} load
     * @param {number} pageSize
     */
    public init(module: string, load = true, pageSize: number = null): void {
        let pageSizeConfigKey = 'list_max_entries_per_record_thread';
        if (pageSize && isFinite(pageSize)) {
            pageSizeConfigKey = '';
            this.recordList.setPageSize(pageSize)
        }

        const load$ = this.recordList.init(module, load, pageSizeConfigKey);

        this.pageSize = this.recordList.getPageSize();

        this.subs.push(this.recordList.records$.subscribe(records => {
            this.initStores(records);
        }));

        if (!load$) {
            return;
        }

        load$.pipe(
            tap((recordList) => {
                this.initStores(recordList.records);
            })
        ).pipe(take(1)).subscribe();
        this.module = module;
    }

    /**
     * Load / reload records using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordList>
     */
    public load(useCache = true): Observable<RecordList> {

        return this.recordList.load(useCache).pipe(
            tap((recordList) => {
                this.initStores(recordList.records);
            })
        );
    }

    /**
     * Init record stores using records
     * @param records
     */
    protected initStores(records: Record[]) {
        if (!records) {
            return;
        }

        const newStores: RecordStoreMap<T, M> = {};
        const storesArray: T[] = [];
        const storesMap = this.getStoreMap(this.stores);

        records.forEach(record => {
            if (!record || !record.id) {
                return
            }

            const id = record.id;
            if (storesMap[id]) {
                const store: T = storesMap[id];

                store.setRecord(record);
                newStores[id] = store;
                storesArray.push(store);
                return;
            }

            newStores[id] = this.recordStoreFactory.create();

            if (this.getItemMetadata()) {
                newStores[id].setMetadata(this.getItemMetadata());
            }

            newStores[id].initRecord(record, 'detail', false);
            storesArray.push(newStores[id]);
        });

        const existingIds = Object.keys(storesMap);
        existingIds.forEach(id => {
            if (newStores[id]) {
                return;
            }

            storesMap[id].destroy();
        });

        this.updateState(storesArray);
    }

    protected updateState(stores: T[]) {
        this.storeSubject.next(this.stores = stores);
    }


    public getStoreMap(stores: T[]): RecordStoreMap<T, M> {
        const map: RecordStoreMap<T, M> = {};

        if (!stores || !stores.length) {
            return map;
        }

        stores.forEach(store => {
            map[store.getBaseRecord().id] = store;
        });

        return map;
    }

    public getItemStores(): T[] {
        const stores = this.stores ?? [];
        if(stores && stores.length) {
            return stores;
        }

        return [];
    }

    getRecordIds(): string[] {
        const ids: string[] = [];

        if (!this.stores || !this.stores.length) {
            return ids;
        }

        this.stores.forEach(store => {
            ids.push(store.getRecordId());
        });

        return ids;
    }

}
