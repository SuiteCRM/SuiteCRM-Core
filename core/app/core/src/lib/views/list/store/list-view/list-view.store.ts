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

import { isArray, isEmpty, union } from 'lodash-es';
import {deepClone} from '../../../../common/utils/object-utils';
import {emptyObject} from '../../../../common/utils/object-utils';
import {isTrue} from '../../../../common/utils/value-utils';
import {Action} from '../../../../common/actions/action.model';
import {Record} from '../../../../common/record/record.model';
import {ViewContext} from '../../../../common/views/view.model';
import {RecordSelection, SelectionStatus} from '../../../../common/views/list/record-selection.model';
import {Pagination, SortDirection, SortingSelection} from '../../../../common/views/list/list-navigation.model';
import {ListViewMeta, ColumnDefinition} from '../../../../common/metadata/list.metadata.model';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {BehaviorSubject, combineLatestWith, Observable, Subscription} from 'rxjs';
import {distinctUntilChanged, map, take, tap} from 'rxjs/operators';
import {Injectable} from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {RecordList, RecordListStore} from '../../../../store/record-list/record-list.store';
import {Metadata, MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {StateStore} from '../../../../store/state';
import {LanguageStore} from '../../../../store/language/language.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {MessageService} from '../../../../services/message/message.service';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {AppData, ViewStore} from '../../../../store/view/view.store';
import {LocalStorageService} from '../../../../services/local-storage/local-storage.service';
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {ColumnChooserComponent} from "../../../../components/columnchooser/columnchooser.component";
import {SavedFilter, SavedFilterMap} from '../../../../store/saved-filters/saved-filter.model';
import {FilterListStore} from '../../../../store/saved-filters/filter-list.store';
import {FilterListStoreFactory} from '../../../../store/saved-filters/filter-list.store.factory';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {RecordPanelMetadata} from '../../../../containers/record-panel/store/record-panel/record-panel.store.model';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {ListViewUrlQueryService} from '../../services/list-view-url-query.service';
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";

export interface ListViewData {
    records: Record[];
    pagination?: Pagination;
    criteria?: SearchCriteria;
    sort?: SortingSelection;
    selection?: RecordSelection;
    loading: boolean;
}

export interface ListViewModel {
    data: ListViewData;
    appData: AppData;
    metadata: Metadata;
}

const initialFilter: SavedFilter = {
    key: 'default',
    module: 'saved-search',
    attributes: {
        contents: ''
    },
    criteria: {
        name: 'default',
        filters: {}
    }
};

const initialFilters: SavedFilterMap = {
    'default': deepClone(initialFilter)
};

const initialState: ListViewState = {
    module: '',
    widgets: false,
    actionPanel: '',
    showSidebarWidgets: false,
    recordPanelConfig: {} as RecordPanelMetadata,
    activeFilters: deepClone(initialFilters),
    openFilter: deepClone(initialFilter)
};

export interface ListViewState {
    module: string;
    widgets: boolean;
    actionPanel: string;
    showSidebarWidgets: boolean;
    recordPanelConfig: RecordPanelMetadata;
    activeFilters: SavedFilterMap;
    openFilter: SavedFilter;
}

@Injectable()
export class ListViewStore extends ViewStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    moduleName$: Observable<string>;
    columns: BehaviorSubject<ColumnDefinition[]>;
    columns$: Observable<ColumnDefinition[]>;
    lineActions$: Observable<Action[]>;
    tableActions$: Observable<Action[]>
    records$: Observable<Record[]>;
    criteria$: Observable<SearchCriteria>;
    context$: Observable<ViewContext>;
    sort$: Observable<SortingSelection>;
    pagination$: Observable<Pagination>;
    selection$: Observable<RecordSelection>;
    selectedCount$: Observable<number>;
    selectedStatus$: Observable<SelectionStatus>;
    loading$: Observable<boolean>;
    widgets$: Observable<boolean>;
    showSidebarWidgets$: Observable<boolean>;
    displayFilters$: Observable<boolean>;
    actionPanel$: Observable<string>;
    recordList: RecordListStore;
    dataUpdate$: Observable<boolean>;
    dataSetUpdate$: Observable<boolean>;
    activeFilters$: Observable<SavedFilterMap>;
    openFilter$: Observable<SavedFilter>;
    filterList: FilterListStore;
    pageKey: string = 'listview';

    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<ListViewModel>;
    vm: ListViewModel;
    data: ListViewData;

    /** Internal Properties */
    protected cache$: Observable<any> = null;
    protected internalState: ListViewState = deepClone(initialState);
    protected store = new BehaviorSubject<ListViewState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected dataUpdateState: BehaviorSubject<boolean>;
    protected subs: Subscription[] = [];

    constructor(
        protected appStateStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected moduleNavigation: ModuleNavigation,
        protected metadataStore: MetadataStore,
        protected message: MessageService,
        protected listStoreFactory: RecordListStoreFactory,
        protected modalService: NgbModal,
        protected filterListStoreFactory: FilterListStoreFactory,
        protected confirmation: ConfirmationModalService,
        protected preferences: UserPreferenceStore,
        protected route: ActivatedRoute,
        protected listViewUrlQueryService: ListViewUrlQueryService,
        protected localStorageService: LocalStorageService,
        protected systemConfigsStore: SystemConfigStore,
        protected userPreferences: UserPreferenceStore
    ) {

        super(appStateStore, languageStore, navigationStore, moduleNavigation, metadataStore);

        this.recordList = this.listStoreFactory.create();

        this.columns$ = metadataStore.listViewColumns$;
        this.lineActions$ = metadataStore.listViewLineActions$;
        this.tableActions$ = metadataStore.listViewTableActions$;
        this.records$ = this.recordList.records$;
        this.criteria$ = this.recordList.criteria$;
        this.context$ = this.recordList.criteria$.pipe(map(() => this.getViewContext()));
        this.sort$ = this.recordList.sort$;
        this.pagination$ = this.recordList.pagination$;
        this.selection$ = this.recordList.selection$;
        this.selectedCount$ = this.recordList.selectedCount$;
        this.selectedStatus$ = this.recordList.selectedStatus$;
        this.loading$ = this.recordList.loading$;
        this.moduleName$ = this.state$.pipe(map(state => state.module), distinctUntilChanged());
        this.widgets$ = this.state$.pipe(map(state => state.widgets), distinctUntilChanged());
        this.showSidebarWidgets$ = this.state$.pipe(map(state => state.showSidebarWidgets));
        this.displayFilters$ = this.state$.pipe(map(state => state.actionPanel === 'filters'), distinctUntilChanged());
        this.actionPanel$ = this.state$.pipe(map(state => state.actionPanel), distinctUntilChanged());
        this.activeFilters$ = this.state$.pipe(map(state => state.activeFilters), distinctUntilChanged());
        this.openFilter$ = this.state$.pipe(map(state => state.openFilter), distinctUntilChanged());

        const data$ = this.records$.pipe(
            combineLatestWith(this.criteria$, this.pagination$, this.selection$, this.loading$),
            map(([records, criteria, pagination, selection, loading]) => {
                this.data = {records, criteria, pagination, selection, loading} as ListViewData;
                return this.data;
            })
        );

        this.vm$ = data$.pipe(
            combineLatestWith(this.appData$, this.metadata$),
            map(([data, appData, metadata]) => {
                this.vm = {data, appData, metadata} as ListViewModel;
                return this.vm;
            })
        );

        this.columns = new BehaviorSubject<ColumnDefinition[]>([]);
        this.columns$ = this.columns.asObservable();

        this.initDataUpdateState();
        this.initDataSetUpdatedState();

        this.filterList = this.filterListStoreFactory.create();

        this.recordList.pageKey = this.pageKey;
    }

    get actionPanel(): string {
        return this.internalState.actionPanel;
    }

    get showFilters(): boolean {
        return this.internalState.actionPanel === 'filters';
    }

    set showFilters(show: boolean) {

        this.updateState({
            ...this.internalState,
            actionPanel: show ? 'filters' : ''
        });
    }

    get widgets(): boolean {
        return this.internalState.widgets;
    }

    set widgets(show: boolean) {
        this.updateState({
            ...this.internalState,
            widgets: show
        });
    }

    get showSidebarWidgets(): boolean {
        return this.internalState.showSidebarWidgets;
    }

    set showSidebarWidgets(show: boolean) {
        this.savePreference(this.getModuleName(), 'show-sidebar-widgets', show);
        this.updateState({
            ...this.internalState,
            showSidebarWidgets: show
        });
    }

    get recordPanelConfig(): RecordPanelMetadata {
        return this.internalState.recordPanelConfig;
    }

    isRecordPanelOpen(): boolean {
        return this.internalState.actionPanel === 'recordPanel';
    }

    openRecordPanel(config: RecordPanelMetadata): void {
        this.updateState({
            ...this.internalState,
            actionPanel: 'recordPanel',
            recordPanelConfig: config
        });
    }

    closeRecordPanel(): void {
        this.updateState({
            ...this.internalState,
            actionPanel: '',
            recordPanelConfig: {} as RecordPanelMetadata
        });
    }


    getModuleName(): string {
        return this.internalState.module;
    }

    getViewContext(): ViewContext {

        const context = {
            module: this.getModuleName(),
        } as ViewContext;

        context.criteria = this.recordList.criteria;
        context.sort = this.recordList.sort;

        return context;
    }

    /**
     * Clean destroy
     */
    public destroy(): void {
        this.clear();
        this.subs.forEach(sub => sub.unsubscribe());
    }

    /**
     * get active filters
     *
     * @returns {object} active filters
     */
    get activeFilters(): SavedFilterMap {
        return deepClone(this.internalState.activeFilters);
    }

    /**
     * Clear observable cache
     */
    public clear(): void {
        this.cache$ = null;
        this.updateState(deepClone(initialState));
        this.recordList.clear();
    }

    public clearAuthBased(): void {
        this.clear();
        this.recordList.clearAuthBased();
    }

    /**
     * Initial list records load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @returns {object} Observable<any>
     */
    public init(module: string): Observable<RecordList> {
        this.internalState.module = module;
        this.recordList.init(module, false);
        this.filterList.init(module);

        this.filterList.load(false).pipe(take(1)).subscribe();

        this.calculateShowWidgets();

        this.recordList.sort = {
            orderBy: this?.metadata?.listView?.orderBy ?? '',
            sortOrder: this?.metadata?.listView?.sortOrder ?? 'NONE' as SortDirection
        } as SortingSelection;

        const queryParams = this.route?.snapshot?.queryParams ?? {};
        let filterType = '';
        if (isTrue(queryParams['query'])) {
            filterType = 'query';
        }
        switch (filterType) {
            case 'query':
                this.loadQueryFilter(module, queryParams);
                break
            default:
                this.loadCurrentFilter(module);
                this.loadCurrentSort(module);
        }
        this.loadCurrentDisplayedColumns();

        const paginationType = this.userPreferences.getUserPreference('listview_pagination_type') ?? this.systemConfigsStore.getConfigValue('listview_pagination_type');

        const currentPaginationType = this.getCurrentPaginationType(module);

        this.setCurrentPaginationType(module, paginationType);

        if (queryParams['keepPagination'] && currentPaginationType === paginationType) {
            this.loadCurrentPagination(module);
        }

        return this.load();
    }

    /**
     * Set open filters
     *
     * @param {object} filter to set
     */
    public setOpenFilter(filter: SavedFilter): void {
        this.updateState({...this.internalState, openFilter: deepClone(filter)});
    }

    /**
     * Toggle Quick filter
     *
     * @param filter
     * @param {boolean} reload flag
     */
    public toggleQuickFilter(filter: SavedFilter, reload = true): void {
        let activeFilters = this.getActiveQuickFilters();

        const isActive = Object.keys(activeFilters).some(key => key === filter.key);

        if (isActive) {
            let {[filter.key]: removedFilters, ...newFilters} = activeFilters;
            activeFilters = newFilters;
        } else {
            activeFilters = {};
            activeFilters[filter.key] = filter;
        }

        if (emptyObject(activeFilters)) {
            this.resetFilters(reload);
            return;
        }

        if (Object.keys(activeFilters).length === 1) {
            this.setFilters(activeFilters);
            return;
        }

        this.updateState({
            ...this.internalState,
            activeFilters: deepClone(activeFilters),
        });

        this.updateSearchCriteria(reload)
    }


    /**
     * Set active filters
     *
     * @param {object} filters to set
     * @param {boolean} reload flag
     * @param sort
     */
    public setFilters(filters: SavedFilterMap, reload = true, sort: SortingSelection = null): void {

        const filterKey = Object.keys(filters).shift();
        const filter = filters[filterKey];

        this.updateState({...this.internalState, activeFilters: deepClone(filters), openFilter: deepClone(filter)});

        if (filter.criteria) {
            let orderBy = filter.criteria.orderBy ?? '';
            const sortOrder = filter.criteria.sortOrder ?? '';
            let direction = this.recordList.mapSortOrder(sortOrder);

            if (sort !== null) {
                orderBy = sort.orderBy;
                direction = sort.sortOrder;
            }

            this.recordList.updateSorting(orderBy, direction, false);
            this.updateSortLocalStorage();
        }

        this.updateSearchCriteria(reload)
    }

    /**
     * Update filters
     *
     * @param {object} filter to set
     */
    public addSavedFilter(filter: SavedFilter): void {

        const newState = {...this.internalState};
        const activeFilters = this.activeFilters;

        if (filter.key && activeFilters[filter.key]) {
            activeFilters[filter.key] = filter;
            newState.activeFilters = activeFilters;
        }

        newState.openFilter = filter;

        this.filterList.addFilter(filter);

        this.updateState(newState);
    }

    /**
     * Update filters
     *
     * @param {object} filter to set
     */
    public removeSavedFilter(filter: SavedFilter): void {

        if (!filter || !filter.key) {
            return;
        }

        this.filterList.removeFilter(filter);

        const newState = {...this.internalState};

        if (newState.openFilter && newState.openFilter.key === filter.key) {
            this.resetFilters(true)
        }
    }

    /**
     * Reset active filters
     *
     * @param {boolean} reload flag
     */
    public resetFilters(reload = true): void {

        this.updateState({
            ...this.internalState,
            activeFilters: deepClone(initialFilters),
            openFilter: deepClone(initialFilter),
        });

        this.recordList.clearSort();
        this.updateSortLocalStorage();

        this.updateSearchCriteria(reload)
    }

    /**
     * Update the search criteria
     *
     * @param {boolean} reload flag
     */
    public updateSearchCriteria(reload = true): void {
        const filters = {...this.internalState.activeFilters};
        let criteria = this.mergeCriteria(filters);

        this.recordList.updateSearchCriteria(criteria, reload);
        this.updateFilterLocalStorage();
    }

    public updateFilterLocalStorage(): void {
        const module = this.internalState.module;

        this.savePreference(module, 'current-filters', this.internalState.activeFilters);
    }

    public updateSortLocalStorage(): void {
        const module = this.internalState.module;

        this.savePreference(module, 'current-sort', this.recordList.sort);
    }

    public updatePaginationLocalStorage(): void {
        const module = this.internalState.module;
        const key = module + '-' + this.getPreferenceKey('current-pagination');
        this.localStorageService.set(key, this.recordList.pagination);
    }

    /**
     * Updated displayed columns' ui user preference
     * @param display
     */
    public updateDisplayedColumnsPreference(display: string[]): void {
        const module = this.internalState.module;
        this.savePreference(module, 'displayed-columns', display);
    }

    /**
     * Get displayed columns' ui user preference
     */
    public getDisplayedColumnsPreference(): string[] {
        const module = this.internalState.module;
        const displayedColumns = this.loadPreference(module, 'displayed-columns');

        if (!isArray(displayedColumns) || !displayedColumns || !displayedColumns.length) {
            return null;
        }

        return (displayedColumns as string[]);
    }


    public triggerDataUpdate(): void {
        this.dataUpdateState.next(true);
    }

    /**
     * Load / reload records using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<ListViewState>
     */
    public load(useCache = true): Observable<RecordList> {
        const module = this.internalState.module;

        this.savePreference(module, 'current-filters', this.internalState.activeFilters);
        this.savePreference(module, 'current-sort', this.recordList.sort);
        this.updatePaginationLocalStorage();

        return this.recordList.load(useCache);
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: ListViewState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Get Active quick filters
     * @protected
     */
    protected getActiveQuickFilters(): SavedFilterMap {
        let {'default': defaultFilter, ...currentQuickFilters} = this.activeFilters;
        let activeFilters = {} as SavedFilterMap;

        Object.keys(currentQuickFilters).forEach(key => {
            const activeFilter = currentQuickFilters[key] ?? null;
            if (!key) {
                return;
            }

            const isQuickFilter = activeFilter?.attributes?.quick_filter ?? false;

            if (isQuickFilter) {
                activeFilters[key] = activeFilter;
            }
        });
        return activeFilters;
    }

    /**
     * Merge Criteria
     * @protected
     */
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

    /**
     * Open columns chooser modal
     */
    openColumnChooserDialog(): void {

        const modalRef = this.modalService.open(ColumnChooserComponent, {
            ariaLabelledBy: 'modal-basic-title',
            centered: true,
            size: 'lg',
            windowClass: 'column-chooser-modal'
        });

        const displayedColumns = this.columns.getValue().filter(function (col) {
            return !col.hasOwnProperty('default')
                || (col.hasOwnProperty('default') && col.default === true);
        });

        const hiddenColumns = this.columns.getValue().filter(function (col) {
            return col.hasOwnProperty('default') && col.default === false;
        });

        modalRef.componentInstance.displayed = displayedColumns;
        modalRef.componentInstance.hidden = hiddenColumns;

        modalRef.result.then((result) => {
            if (!result.displayed || !result.hidden) {
                return;
            }

            let allColumns: ColumnDefinition[] = [];
            const selectedDisplayColumns: ColumnDefinition[] = result.displayed;
            const selectedHideColumns: ColumnDefinition[] = result.hidden;

            selectedDisplayColumns.forEach(function (column) {
                column.default = true;
            });
            selectedHideColumns.forEach(function (column) {
                column.default = false;
            });
            allColumns.push(...selectedDisplayColumns, ...selectedHideColumns);
            this.columns.next(allColumns);

            const displayedCols = selectedDisplayColumns.map(col => col.name);
            this.updateDisplayedColumnsPreference(displayedCols);
        });
    }

    /**
     * Calculate if widgets are to display
     */
    protected calculateShowWidgets(): void {
        let show = false;

        const meta = this.metadataStore.get() || {};
        const listViewMeta = meta.listView || {} as ListViewMeta;
        const sidebarWidgetsConfig = listViewMeta.sidebarWidgets || [];

        if (sidebarWidgetsConfig && sidebarWidgetsConfig.length > 0) {
            show = true;
        }

        const showSidebarWidgets = this.loadPreference(this.getModuleName(), 'show-sidebar-widgets') ?? null;

        if (showSidebarWidgets !== null) {
            this.showSidebarWidgets = showSidebarWidgets;
        } else {
            this.showSidebarWidgets = show;
        }

        this.widgets = show;
    }

    /**
     * Build ui user preference key
     * @param storageKey
     * @protected
     */
    protected getPreferenceKey(storageKey: string): string {
        return this.pageKey + '-' + storageKey;
    }

    /**
     * Save ui user preference
     * @param module
     * @param storageKey
     * @param value
     * @protected
     */
    protected savePreference(module: string, storageKey: string, value: any): void {
        this.preferences.setUi(module, this.getPreferenceKey(storageKey), value);
    }

    /**
     * Load ui user preference
     * @param module
     * @param storageKey
     * @protected
     */
    protected loadPreference(module: string, storageKey: string): any {
        return this.preferences.getUi(module, this.getPreferenceKey(storageKey));
    }

    /**
     * Load current filter
     * @param module
     * @protected
     */
    protected loadCurrentFilter(module: string): void {

        const activeFiltersPref = this.loadPreference(module, 'current-filters') ?? {} as SavedFilterMap;
        if (!activeFiltersPref || emptyObject(activeFiltersPref)) {
            return;
        }

        let currentSort = this.loadPreference(module, 'current-sort') as SortingSelection;
        if (!currentSort && emptyObject(currentSort)) {
            currentSort = null;
        }

        this.setFilters(activeFiltersPref, false, currentSort);
    }

    /**
     * Load current filter
     * @param module
     * @param queryParams
     * @protected
     */
    protected loadQueryFilter (
        module:string,
        queryParams: Params
    ): void {
        const orderBy: string = queryParams['orderBy'] ?? '';
        const sortOrder: string = queryParams['sortOrder'] ?? '';
        const direction = this.recordList.mapSortOrder(sortOrder);

        const filter = this.listViewUrlQueryService.buildUrlQueryBasedFilter(
            module,
            this.internalState.activeFilters.default,
            queryParams
        );
        if (isEmpty(filter)){
            return;
        }

        const filters = { 'default': filter };

        this.updateState({
            ...this.internalState,
            activeFilters: deepClone(filters),
            openFilter: deepClone(filter)
        });

        this.recordList.updateSorting(orderBy, direction, false);
        this.recordList.updateSearchCriteria(filter.criteria, false);
    }

    /**
     * Load current sorting
     * @param module
     * @protected
     */
    protected loadCurrentSort(module: string): void {
        const currentSort = this.loadPreference(module, 'current-sort');
        if (!currentSort || emptyObject(currentSort)) {
            return;
        }

        this.recordList.sort = currentSort;
    }

    /**
     * Load current pagination
     * @param module
     * @protected
     */
    protected loadCurrentPagination(module: string): void {
        const key = module + '-' + this.getPreferenceKey('current-pagination');
        const currentPagination = this.localStorageService.get(key) as Pagination;
        if (!currentPagination || emptyObject(currentPagination)) {
            return;
        }

        this.recordList.pagination = currentPagination;
    }

    /**
     * Get current pagination Type
     * @param module
     * @protected
     */
    protected getCurrentPaginationType(module: string): string {
        const currentPaginationType = this.loadPreference(module, 'current-pagination-type');
        if (!currentPaginationType) {
            return 'pagination';
        }

        return currentPaginationType;
    }

    /**
     * Set current pagination Type
     * @param module
     * @protected
     */
    protected setCurrentPaginationType(module: string, paginationType: string) {
        this.savePreference(module, 'current-pagination-type', paginationType);
    }



    /**
     * Load current displayed columns
     * @protected
     */
    protected loadCurrentDisplayedColumns(): void {
        this.metadataStore.listViewColumns$.pipe(take(1)).subscribe(cols => {
            const displayedColumns = this.getDisplayedColumnsPreference();

            if (!displayedColumns || !cols) {
                this.columns.next(cols);
                return;
            }

            const colMap = {} as { [key: string]: boolean };
            displayedColumns.forEach(displayedColumn => {
                colMap[displayedColumn] = true;
            })

            const displayedMap = {} as { [key: string]: ColumnDefinition };

            const hidden = [] as ColumnDefinition[];
            cols.forEach(col => {
                col.default = colMap[col.name] ?? false;
                if (col.default) {
                    displayedMap[col.name] = col;
                } else {
                    hidden.push(col);
                }
            });

            const displayed = displayedColumns.filter(col => !!displayedMap[col]).map(col => displayedMap[col]);

            this.columns.next([...displayed, ...hidden]);
        })
    }


    /**
     * Initialize data update state.
     * It should be emitted on any change in values on the record list.
     * Reload/Pagination is not considered as a data update
     */
    protected initDataUpdateState(): void {
        this.dataUpdateState = new BehaviorSubject<boolean>(true);
        this.dataUpdate$ = this.dataUpdateState.asObservable();
    }

    /**
     *  Initialize the dataSet update state.
     *  It should be emitted on any change in dataSet e.g. due to data filter, due to data delete,
     *  due to data edit or any event which causes change in the resulting dataSet.
     */
    protected initDataSetUpdatedState(): void {
        this.dataSetUpdate$ = this.criteria$.pipe(
            combineLatestWith(this.dataUpdate$),
            map(() => true)
        );
    }
}
