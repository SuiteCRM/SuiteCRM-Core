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
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {RecordStoreList} from './base-record-thread.store';
import {Observable, timer} from 'rxjs';
import {ActionContext} from '../../../../common/actions/action.model';
import {Record} from '../../../../common/record/record.model';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {SortDirection} from '../../../../common/views/list/list-navigation.model';
import {map, takeWhile, tap} from 'rxjs/operators';
import {RecordThreadItemStoreFactory} from './record-thread-item.store.factory';
import {RecordThreadItemMetadata} from './record-thread-item.store.model';
import {RecordThreadItemStore} from './record-thread-item.store';
import {RecordThreadListMetadata} from "./record-thread-list.store.model";

@Injectable()
export class RecordThreadStore extends RecordStoreList<RecordThreadItemStore, RecordThreadItemMetadata> {

    itemMetadata: RecordThreadItemMetadata;
    listMetadata: RecordThreadListMetadata;
    $loading: Observable<boolean>;
    autoRefreshEnabled = true;

    constructor(
        protected listStoreFactory: RecordListStoreFactory,
        protected recordStoreFactory: RecordThreadItemStoreFactory
    ) {
        super(listStoreFactory, recordStoreFactory);
        this.$loading = this.recordList.loading$;
    }

    public init(module: string, load = true, pageSize: number = null): void {
        super.init(module, load, pageSize);
        this.autoRefreshEnabled = true;
    }

    setFilters(filters: SearchCriteria): Observable<Record[]> {

        let criteria = this.recordList.criteria;
        criteria = {
            ...criteria,
            ...filters
        };

        if (filters && filters.orderBy) {
            let sortOrder = SortDirection.DESC;
            if (filters.sortOrder && String(filters.sortOrder).toUpperCase() === 'ASC') {
                sortOrder = SortDirection.ASC;
            }

            this.recordList.updateSorting(filters.orderBy, sortOrder, false);
        }

        this.recordList.updateSearchCriteria(criteria, false);
        return this.load(false).pipe(
            map(value => value.records),
        );
    }

    public getItemMetadata(): RecordThreadItemMetadata {
        return this.itemMetadata;
    }

    public getListMetadata(): RecordThreadListMetadata {
        return this.listMetadata;
    }

    public setItemMetadata(meta: RecordThreadItemMetadata) {
        return this.itemMetadata = meta;
    }

    public setListMetadata(meta: RecordThreadListMetadata) {
        return this.listMetadata = meta;
    }

    public allLoaded(): boolean {
        const pagination = this.recordList.getPagination();
        if (!pagination) {
            return false;
        }

        return pagination.pageSize >= pagination.total;
    }

    public loadMore(jump: number = null): void {

        if (!jump) {
            jump = this.pageSize;
        }

        const pagination = this.recordList.getPagination();
        const currentPageSize = pagination.pageSize || 0;
        let newPageSize = currentPageSize + jump;

        this.recordList.setPageSize(newPageSize);
        this.recordList.updatePagination(0);
    }

    public reload(): void {
        this.recordList.updatePagination(0);
    }

    public getViewContext(): ActionContext {

        return {
            module: this.module,
            ids: this.getRecordIds(),
        } as ActionContext;
    }

    public initAutoRefresh(autoRefreshFrequency: number, min: number, max: number, onRefresh: Function): Observable<number> {
        const currentDate = new Date();
        const startOfNextMinute = new Date(
            currentDate.getFullYear(),
            currentDate.getMonth(),
            currentDate.getDate(),
            currentDate.getHours(),
            currentDate.getMinutes() + 1
        );

        const autoRefreshTime = this.getAutoRefreshTime(autoRefreshFrequency, min, max);

        return timer(startOfNextMinute, autoRefreshTime).pipe(
            takeWhile(() => {
                return this.autoRefreshEnabled;
            }),
            tap(() => {
                this.load(false).subscribe(
                    () => {
                        if (onRefresh) {
                            onRefresh();
                        }
                    }
                );
            })
        );
    }

    disableAutoRefresh() {
        this.autoRefreshEnabled = false;
    }

    getAutoRefreshTime(autoRefreshFrequency: number, min: number, max: number) {

        let autoRefreshTime = (autoRefreshFrequency * (60000));

        if (min === 0 && max === 0) {
            return autoRefreshTime;
        }

        return autoRefreshTime + this.getRandomDeviation(min, max);
    }

    getRandomDeviation(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1) + min) * 1000;
    }
}
