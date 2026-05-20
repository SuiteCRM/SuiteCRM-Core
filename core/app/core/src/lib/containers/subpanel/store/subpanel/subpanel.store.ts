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

import {Injectable, signal, WritableSignal} from '@angular/core';
import {StateStore} from '../../../../store/state';
import {RecordList, RecordListStore} from '../../../../store/record-list/record-list.store';
import {BehaviorSubject, forkJoin, Observable, Subscription} from 'rxjs';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {LanguageStore} from '../../../../store/language/language.store';
import {deepClone} from '../../../../common/utils/object-utils';
import {Record} from '../../../../common/record/record.model';
import {SearchCriteria, SearchCriteriaFilter} from '../../../../common/views/list/search-criteria.model';
import {ColumnDefinition, SearchMeta, RecordListMeta} from '../../../../common/metadata/list.metadata.model';
import {Statistic, StatisticsQuery, StatisticsQueryMap} from '../../../../common/statistics/statistics.model';
import {StatisticWidgetOptions} from '../../../../common/metadata/widget.metadata';
import {SubPanelDefinition} from '../../../../common/metadata/subpanel.metadata.model';
import {SingleValueStatisticsStore} from '../../../../store/single-value-statistics/single-value-statistics.store';
import {
    SingleValueStatisticsStoreFactory
} from '../../../../store/single-value-statistics/single-value-statistics.store.factory';
import {FilterListStore} from "../../../../store/saved-filters/filter-list.store";
import {FilterListStoreFactory} from "../../../../store/saved-filters/filter-list.store.factory";
import {map, take, tap} from "rxjs/operators";
import {MetadataStore} from "../../../../store/metadata/metadata.store.service";
import {SavedFilter, SavedFilterMap} from "../../../../store/saved-filters/saved-filter.model";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {PanelCollapseMode} from "../../../../components/panel/panel.component";

export interface SubpanelStoreMap {
    [key: string]: SubpanelStore;
}

export interface SingleValueStatisticsStoreMap {
    [key: string]: SingleValueStatisticsStore;
}

@Injectable()
export class SubpanelStore implements StateStore {
    show = false;
    parentModule: string;
    parentId: string;
    parentRecord$: Observable<Record>;
    parentRecord: Record;
    recordList: RecordListStore;
    statistics: SingleValueStatisticsStoreMap;
    metadata$: Observable<SubPanelDefinition>;
    listMetadata$: Observable<RecordListMeta>;
    searchMetadata$: Observable<SearchMeta>;
    columns$: Observable<ColumnDefinition[]>;
    metadata: SubPanelDefinition;
    loading$: Observable<boolean>;
    panelCollapseMode: WritableSignal<PanelCollapseMode> = signal('closable');

    // Filter variables
    filterList: FilterListStore;
    criteria$: Observable<SearchCriteria>;
    showFilter: WritableSignal<boolean> = signal(false);
    loaded: WritableSignal<boolean> = signal(false);
    filterApplied = false;

    preferenceKey = null;

    protected metadataState: BehaviorSubject<SubPanelDefinition>;
    protected subs: Subscription[] = [];


    constructor(
        protected listStoreFactory: RecordListStoreFactory,
        protected languageStore: LanguageStore,
        protected statisticsStoreFactory: SingleValueStatisticsStoreFactory,
        protected filterListStoreFactory: FilterListStoreFactory,
        protected meta: MetadataStore,
        protected preferences: UserPreferenceStore,
    ) {
        this.recordList = listStoreFactory.create();
        this.filterList = this.filterListStoreFactory.create();
        this.criteria$ = this.recordList.criteria$;
        this.statistics = {};
        this.metadataState = new BehaviorSubject<SubPanelDefinition>({} as SubPanelDefinition);
        this.metadata$ = this.metadataState.asObservable();
        this.loading$ = this.recordList.loading$;
    }

    getTitle(): string {
        let label = this.languageStore.getFieldLabel(this.metadata.title_key, this.parentModule);

        if (!label) {
            const moduleList = this.languageStore.getAppListString('moduleList');
            label = (moduleList && moduleList[this.metadata.title_key]) || '';
        }

        return label ?? this.metadata.title_key;
    }

    getIcon(): string {
        return this.metadata.icon;
    }

    clear(): void {
        this.metadataState.unsubscribe();
        this.metadataState = null;
        this.recordList.clear();
        this.recordList = null;
        this.subs.forEach(sub => sub.unsubscribe());
    }

    clearAuthBased(): void {
        this.recordList.clearAuthBased();
    }

    searchFilter() {
        this.filterApplied = true;
        this.showFilter.set(false);
    }

    /**
     * Initial list records load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} parentModule name
     * @param {string} parentId id
     * @param {object} meta to use
     * @param {object} parentRecord$ to use
     */
    public init(parentModule: string, parentId: string, meta: SubPanelDefinition, parentRecord$: Observable<Record> = null): void {
        this.parentModule = parentModule;
        this.parentId = parentId;
        this.metadata = meta;
        this.metadataState.next(this.metadata);
        const meta$ = this.meta.getMetadata(meta.module).pipe(
            tap(() => {
                this.recordList.load().pipe(
                    take(1)
                ).subscribe();
            })
        );

        this.searchMetadata$ = meta$.pipe(map(meta => meta.search));
        const filter = this.initSearchCriteria(this.parentModule, this.parentId, meta);
        this.recordList.init(meta.module, false, 'list_max_entries_per_subpanel', filter)

        this.initStatistics(meta, parentModule, parentId);

        if (parentRecord$) {
            this.parentRecord$ = parentRecord$;
            this.parentRecord$.subscribe(record => this.parentRecord = record);
        }

    }

    public setFilters(filters: SavedFilterMap, reload = true) {
        this.recordList.setFilters(filters, reload, null);
    }

    public isAnyFilterApplied(): boolean {
        return this.hasActiveFilter() || !this.areAllCurrentCriteriaFilterEmpty();
    }

    public hasActiveFilter(): boolean {
        const activeFilters = this.recordList.criteria;

        if (activeFilters) {
            return false;
        }

        const filterKeys = Object.keys(activeFilters) ?? [];

        if (!filterKeys || !filterKeys.length) {
            return false;
        }

        if (filterKeys.length > 1) {
            return true;
        }

        const currentFilter = activeFilters[filterKeys[0]];

        return currentFilter.key && currentFilter.key !== '' && currentFilter.key !== 'default'
    }

    public areAllCurrentCriteriaFilterEmpty(): boolean {
        return Object.keys(this.getFilters() ?? {}).every(key => this.getFilters()[key].operator === '');
    }

    public getFilters(): SearchCriteriaFilter {
        return this.recordList?.criteria?.filters ?? {};
    }

    /**
     * Load / reload records using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordList>
     */
    public load(useCache = true): Observable<RecordList> {
        this.loaded.set(true);
        return this.recordList.load(useCache);
    }

    /**
     * Get statistic store
     *
     * @param {string} key key of statistic
     * @returns {object} SingleValueStatisticsStore
     */
    public getStatistic(key: string): SingleValueStatisticsStore {
        if (!this.statistics[key]) {
            return null;
        }

        return this.statistics[key];
    }

    public resetFilters(reload = true): void {
        this.recordList.resetFilters(reload);
    }

    public clearFilter(): void {
        this.resetFilters();
        this.filterApplied = false;
        this.showFilter.set(false);
    }

    /**
     * add search criteria
     *
     * @param {string} parentModule name
     * @param {string} parentId id
     * @param meta
     */
    initSearchCriteria(parentModule: string, parentId: string, meta: SubPanelDefinition) {
        const sortOrder = meta?.sort_order ?? 'desc';
        const orderBy = meta?.sort_by ?? '';
        return {
            key: 'default',
            module: 'saved-search',
            attributes: {contents: ''},
            criteria: {
                name: 'default',
                filters: {},
                preset: {
                    type: 'subpanel',
                    params: {
                        subpanel: meta?.name,
                        parentModule,
                        parentId
                    }
                },
                sortOrder,
                orderBy
            },
        } as SavedFilter;
    }

    /**
     * Load / reload statistics
     *
     * @param {string} key of statistic
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<Statistic>
     */
    public loadStatistics(key: string, useCache = true): Observable<Statistic> {
        if (!this.statistics[key]) {
            return null;
        }

        return this.statistics[key].load(useCache);
    }

    /**
     * Load / reload all statistics
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<Statistic>
     */
    public loadAllStatistics(useCache = true): Observable<Statistic[]> {
        if (!this.statistics || !Object.keys(this.statistics).length) {
            return null;
        }


        const stats$ = [];
        Object.keys(this.statistics).forEach(statisticKey => {

            if (!this.statistics[statisticKey]) {
                return;
            }
            stats$.push(this.loadStatistics(statisticKey, useCache));
        });

        return forkJoin(stats$);
    }

    /**
     * Should batch statistic
     *
     * @returns {boolean} shouldBatch
     */
    public shouldBatchStatistic(): boolean {
        const metadata: SubPanelDefinition = this.metadata || {} as SubPanelDefinition;
        return !(metadata.subpanelWidget && metadata.subpanelWidget.batch && metadata.subpanelWidget.batch === false);
    }

    /**
     * Set loading
     *
     * @param {string} key of statistic
     * @param {boolean} loading bool
     */
    public setStatisticsLoading(key: string, loading: boolean): void {
        if (!this.statistics[key]) {
            return;
        }
        this.statistics[key].setLoading(loading);
    }

    /**
     * Set all statistics loading
     *
     * @param {boolean} loading bool
     */
    public setAllStatisticsLoading(loading: boolean): void {

        if (!this.statistics || !Object.keys(this.statistics).length) {
            return;
        }

        Object.keys(this.statistics).forEach(statisticKey => {

            if (!this.statistics[statisticKey]) {
                return;
            }
            this.setStatisticsLoading(statisticKey, loading);
        });
    }

    /**
     * Set Statistic value
     *
     * @param {string} key of statistic
     * @param {object} statistic Statistic
     * @param {boolean} cache bool
     */
    public setStatistics(key: string, statistic: Statistic, cache = false): void {
        if (!this.statistics[key]) {
            return;
        }
        this.statistics[key].setStatistic(key, statistic, cache);
    }

    /**
     * Get statistic query
     *
     * @param {string} key of statistic
     * @returns {object} StatisticsQuery
     */
    public getStatisticQuery(key: string): StatisticsQuery {
        return this.statistics[key].getQuery();
    }

    /**
     * Get all statistics queries
     *
     * @returns {object} StatisticsQuery
     */
    public getAllStatisticQuery(): StatisticsQueryMap {

        if (!this.statistics || !Object.keys(this.statistics).length) {
            return {};
        }

        const queriesMap = {};

        Object.keys(this.statistics).forEach(statisticKey => {

            if (!this.statistics[statisticKey]) {
                return;
            }
            queriesMap[statisticKey] = this.getStatisticQuery(statisticKey);
        });

        return queriesMap;
    }

    /**
     * Get widget layout
     *
     * @returns {any} any
     */
    public getWidgetLayout(): StatisticWidgetOptions {

        const meta = this.metadata;
        if (!meta || !meta.subpanelWidget || !meta.subpanelWidget.options || !meta.subpanelWidget.options.subpanelWidget) {
            return {rows: []} as StatisticWidgetOptions;
        }

        const layout = deepClone(meta.subpanelWidget.options.subpanelWidget);

        if (!layout.rows || !layout.rows.length) {
            layout.rows = {};
        }

        return layout;
    }

    public toggleFilter(): boolean {
        this.showFilter.set(!this.showFilter());
        return this.showFilter();
    }

    /**
     * Init statistics store
     *
     * @param {object} meta for subpanel
     * @param {string} parentModule name
     * @param {string} parentId {id}
     */
    protected initStatistics(meta: SubPanelDefinition, parentModule: string, parentId: string): void {

        const layout = this.getWidgetLayout();

        layout.rows.forEach(row => {

            if (!row.cols || !row.cols.length) {
                return;
            }

            row.cols.forEach(col => {

                if (!col.statistic || typeof col.statistic !== 'string') {
                    return;
                }

                this.initStatistic(col.statistic, meta, parentModule, parentId);
                col.store = this.statistics[col.statistic];
            });
        });
    }

    /**
     * Init a single value statistic
     *
     * @param {string} statisticKey to use
     * @param {object} meta SubPanelDefinition
     * @param {string} parentModule to use
     * @param {string} parentId to use
     */
    protected initStatistic(statisticKey: string, meta: SubPanelDefinition, parentModule: string, parentId: string): void {
        this.statistics[statisticKey] = this.statisticsStoreFactory.create();

        this.statistics[statisticKey].init(
            meta.module,
            {
                key: statisticKey,
                context: {module: parentModule, id: parentId},
                params: {subpanel: meta.name}
            } as StatisticsQuery,
            false
        );
    }
}
