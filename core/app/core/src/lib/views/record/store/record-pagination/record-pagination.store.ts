/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {BehaviorSubject, Observable, of, Subscription} from "rxjs";
import {distinctUntilChanged, map, shareReplay} from "rxjs/operators";
import {isArray, union} from "lodash-es";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {RecordListStore} from "../../../../store/record-list/record-list.store";
import {RecordListStoreFactory} from "../../../../store/record-list/record-list.store.factory";
import {SavedFilterMap} from "../../../../store/saved-filters/saved-filter.model";
import {LocalStorageService} from "../../../../services/local-storage/local-storage.service";
import {RecordPaginationService} from "./record-pagination.service";
import {RecordPaginationModel} from "./record-pagination.model";
import {ObjectMap} from "../../../../common/types/object-map";
import {Pagination, SortingSelection} from "../../../../common/views/list/list-navigation.model";
import {deepClone, emptyObject} from "../../../../common/utils/object-utils";
import {SearchCriteria} from "../../../../common/views/list/search-criteria.model";

export interface RecordPaginationState {
    paginationEnabled?: boolean;
    recordIds?: ObjectMap[];
    pagination?: Pagination;
}

const initialState: RecordPaginationState = {
    paginationEnabled: false,
    recordIds: null,
    pagination: null
};

@Injectable()
export class RecordPaginationStore {

    recordListStore: RecordListStore;

    /**
     * Public long-lived observable streams
     */
    recordIds$: Observable<ObjectMap[]>;
    pagination$: Observable<Pagination>;
    paginationEnabled$: Observable<boolean>;

    protected internalState: RecordPaginationState = deepClone(initialState);
    protected cache$: Observable<any> = null;
    protected store = new BehaviorSubject<RecordPaginationState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected subs: Subscription[] = [];

    constructor(
        protected preferences: UserPreferenceStore,
        protected systemConfigStore: SystemConfigStore,
        protected listStoreFactory: RecordListStoreFactory,
        protected localStorageService: LocalStorageService,
        protected recordPaginationService: RecordPaginationService
    ) {
        this.recordListStore = this.listStoreFactory.create();
        this.recordIds$ = this.state$.pipe(map(state => state.recordIds), distinctUntilChanged());
        this.pagination$ = this.state$.pipe(map(state => state.pagination), distinctUntilChanged());
        this.paginationEnabled$ = this.state$.pipe(map(state => state.paginationEnabled), distinctUntilChanged());
    }

    public clear(): void {
        this.cache$ = null;
        this.updateState(deepClone(initialState));
        this.subs.forEach(sub => sub.unsubscribe());
    }

    public init(): void {
        const module = this.getModule();
        this.recordListStore.init(module, false);
        this.enableRecordPagination();
        this.loadDataLocalStorage();
        this.loadCurrentPagination(module);
        this.loadCurrentSort(module);
        this.loadCurrentFilter(module);
    }

    protected enableRecordPagination(): void {
        let isEnabled = this.systemConfigStore.getConfigValue('enable_record_pagination');
        if (isEnabled === "") {
            isEnabled = false;
        }
        this.updateState({...this.internalState, paginationEnabled: !!(isEnabled ?? false)});
    }

    public loadDataLocalStorage(): void {
        const data: RecordPaginationModel = this.getRecordListPreference();
        this.updateState({...this.internalState, recordIds: data?.recordIds, pagination: data?.pagination});
    }

    protected getRecordListPreference(): RecordPaginationModel {
        const module = this.getModule();
        const data = this.loadPreference(module, 'current-record-pagination');
        this.checkPaginationExist(data);

        if (!data?.recordIds || !isArray(data?.recordIds) || !data?.recordIds?.length) {
            return null;
        }
        return data;
    }

    protected checkPaginationExist(data: RecordPaginationState): void {
        const module = this.getModule();
        const hasPagination = this.loadPreference(module, 'current-pagination', 'listview');
        if (!hasPagination) {
            this.recordListStore.pagination = data?.pagination ?? {} as Pagination;
        }
    }

    public loadPreference(module: string, storageKey: string, pageKey?: string): any {
        if (!pageKey) {
            return this.preferences.getUi(module, this.getPreferenceKey(storageKey));
        }
        return this.preferences.getUi(module, (pageKey + '-' + storageKey));
    }

    protected getPreferenceKey(storageKey: string): string {
        return 'recordview-' + storageKey;
    }

    protected loadCurrentPagination(module: string): void {
        const key = module + '-' + 'listview-current-pagination';
        const currentPagination = this.localStorageService.get(key) as Pagination;
        if (!currentPagination || emptyObject(currentPagination)) {
            return;
        }
        this.recordListStore.pagination = currentPagination;
    }

    protected loadCurrentSort(module: string): void {
        const currentSort = this.loadPreference(module, 'current-sort', 'listview');
        if (!currentSort || emptyObject(currentSort)) {
            return;
        }

        this.recordListStore.sort = currentSort;
    }

    protected loadCurrentFilter(module: string): void {

        const activeFiltersPref = this.loadPreference(module, 'current-filters', 'listview') ?? {} as SavedFilterMap;
        if (!activeFiltersPref || emptyObject(activeFiltersPref)) {
            return;
        }

        let currentSort = this.loadPreference(module, 'current-sort', 'listview') as SortingSelection;
        if (!currentSort && emptyObject(currentSort)) {
            currentSort = null;
        }

        this.setFilters(activeFiltersPref, false, currentSort);
    }

    protected setFilters(filters: SavedFilterMap, reload = true, sort: SortingSelection = null): void {

        const filterKey = Object.keys(filters).shift();
        const filter = filters[filterKey];

        this.recordListStore.setFilters(filters, reload, sort);

        if (filter.criteria) {
            let orderBy = filter.criteria.orderBy ?? '';
            const sortOrder = filter.criteria.sortOrder ?? '';
            let direction = this.recordListStore.mapSortOrder(sortOrder);

            if (sort !== null) {
                orderBy = sort.orderBy;
                direction = sort.sortOrder;
            }

            this.recordListStore.updateSorting(orderBy, direction, false);
        }

        this.updateSearchCriteria(filters, reload)
    }

    protected updateSearchCriteria(filters: SavedFilterMap, reload = true): void {
        let criteria = this.mergeCriteria(filters);
        this.recordListStore.updateSearchCriteria(criteria, reload);
    }

    protected mergeCriteria(filters: SavedFilterMap): SearchCriteria {

        let criteria = {} as SearchCriteria;

        const keys = Object.keys(filters ?? {}) ?? [];

        keys.forEach(key => {
            const filter = filters[key] ?? null;
            const filterCriteria = filter?.criteria ?? null;
            const filterCriteriaKeys = Object.keys(filterCriteria?.filters ?? {});
            if (filterCriteria === null || (filterCriteriaKeys && !filterCriteriaKeys.length)) {
                return;
            }

            if (emptyObject(criteria)) {
                criteria = deepClone(filterCriteria);
                return;
            }

            filterCriteriaKeys.forEach(criteriaKey => {
                const filterCriteriaContent = filterCriteria?.filters[criteriaKey] ?? null;
                const criteriaContent = criteria?.filters[criteriaKey] ?? null;
                if (!filterCriteriaContent) {
                    return;
                }

                const criteriaOperator = criteriaContent?.operator ?? null

                if (!criteriaContent || !criteriaOperator) {
                    criteria.filters[criteriaKey] = deepClone(filterCriteriaContent);
                    return;
                }

                const filterCriteriaOperator = filterCriteriaContent?.operator ?? null
                if (filterCriteriaOperator !== criteriaOperator || filterCriteriaOperator !== '=') {
                    delete criteria.filters[criteriaKey];
                    return;
                }

                criteriaContent.values = union(criteriaContent.values ?? [], filterCriteriaContent.values ?? []);
            });
        });

        return criteria;
    }

    public getModule(): string {
        return this.recordPaginationService.getModule();
    }

    public getCurrentPage(): number {
        const pageSize = this.internalState.pagination?.pageSize;
        const pageLast = this.internalState.pagination?.pageLast;
        const currentPage = Math.ceil(pageLast / pageSize);
        return currentPage;
    }

    public getPageSize(): number {
        return this.internalState.pagination?.pageSize;
    }

    public getRecordsCount(): number {
        return this.internalState.pagination?.total;
    }

    protected updateState(state: RecordPaginationState): void {
        this.store.next(this.internalState = state);
    }

    protected set(state: RecordPaginationState): void {
        this.cache$ = of(state).pipe(shareReplay(1));
        this.updateState(state);
    }

    protected isCached(): boolean {
        return this.cache$ !== null;
    }
}
