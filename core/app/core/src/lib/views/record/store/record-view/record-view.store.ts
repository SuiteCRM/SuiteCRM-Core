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

import {isEmpty} from 'lodash-es';
import {BehaviorSubject, combineLatestWith, Observable, of, Subscription} from 'rxjs';
import {catchError, distinctUntilChanged, finalize, map, take, tap} from 'rxjs/operators';
import {inject, Injectable, signal, WritableSignal} from '@angular/core';
import {Params} from '@angular/router';
import {isVoid} from '../../../../common/utils/value-utils';
import {deepClone} from '../../../../common/utils/object-utils';
import {BooleanMap} from '../../../../common/types/boolean-map';
import {Field, FieldDefinitionMap, FieldMetadata} from '../../../../common/record/field.model';
import {FieldLogicMap} from '../../../../common/actions/field-logic-action.model';
import {Record} from '../../../../common/record/record.model';
import {ViewFieldDefinition, ViewFieldDefinitionMap} from '../../../../common/metadata/metadata.model';
import {StatisticsMap, StatisticsQueryMap} from '../../../../common/statistics/statistics.model';
import {SubPanelMeta} from '../../../../common/metadata/subpanel.metadata.model';
import {ViewContext, ViewMode} from '../../../../common/views/view.model';
import {RecordViewData, RecordViewModel, RecordViewState} from './record-view.store.model';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {StateStore} from '../../../../store/state';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';
import {LanguageStore} from '../../../../store/language/language.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {
    Metadata,
    MetadataStore,
    RecordViewSectionMetadata,
    RecordViewMetadata,
    SummaryTemplates
} from '../../../../store/metadata/metadata.store.service';
import {MessageService} from '../../../../services/message/message.service';
import {SubpanelStoreMap} from '../../../../containers/subpanel/store/subpanel/subpanel.store';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {RecordManager} from '../../../../services/record/record.manager';
import {RecordStore} from '../../../../store/record/record.store';
import {LocalStorageService} from '../../../../services/local-storage/local-storage.service';
import {SubpanelStoreFactory} from '../../../../containers/subpanel/store/subpanel/subpanel.store.factory';
import {ViewStore} from '../../../../store/view/view.store';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {StatisticsBatch} from '../../../../store/statistics/statistics-batch.service';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {RecordConvertService} from "../../../../services/record/record-convert.service";
import {RecordValidationHandler} from "../../../../services/record/validation/record-validation.handler";
import {ObjectMap} from "../../../../common/types/object-map";
import {WidgetMetadata} from "../../../../common/metadata/widget.metadata";
import {BaseRecordContainerStoreInterface} from "../../../../common/containers/record/record-container.store.model";
import {toObservable} from "@angular/core/rxjs-interop";
import {ActionDataSource, ActionDataSourceBuilderFunction} from "../../../../common/actions/action.model";
import {FieldActionsAdapterFactory} from "../../../../components/field-layout/adapters/field.actions.adapter.factory";

const initialState: RecordViewState = {
    module: '',
    recordID: '',
    validating: false,
    loading: false,
    widgets: false,
    showSidebarWidgets: false,
    showBottomWidgets: false,
    showHeaderWidgets: false,
    showTopWidget: false,
    showSubpanels: false,
    mode: 'detail',
    section: '',
    params: {
        returnModule: '',
        returnId: '',
        returnAction: ''
    }
};

@Injectable()
export class RecordViewStore extends ViewStore implements StateStore, BaseRecordContainerStoreInterface {

    /**
     * Public long-lived observable streams
     */
    record$: Observable<Record>;
    stagingRecord$: Observable<Record>;
    loading$: Observable<boolean>;
    validating$: Observable<boolean>;
    widgets$: Observable<boolean>;
    showSidebarWidgets$: Observable<boolean>;
    showBottomWidgets$: Observable<boolean>;
    showHeaderWidgets$: Observable<boolean>;
    showTopWidget$: Observable<boolean>;
    showSubpanels$: Observable<boolean>;
    mode$: Observable<ViewMode>;
    subpanels$: Observable<SubpanelStoreMap>;
    viewContext$: Observable<ViewContext>;
    subpanelReload$: Observable<BooleanMap>;
    section: WritableSignal<string> = signal('');
    sectionKeys: WritableSignal<string[]> = signal([]);
    section$: Observable<string> = toObservable(this.section);


    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<RecordViewModel>;
    vm: RecordViewModel;
    data: RecordViewData;
    recordStore: RecordStore;

    /** Internal Properties */
    protected cache$: Observable<any> = null;
    protected internalState: RecordViewState = deepClone(initialState);
    protected store = new BehaviorSubject<RecordViewState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected subpanels: SubpanelStoreMap;
    protected subpanelsState: BehaviorSubject<SubpanelStoreMap>;
    protected subpanelReloadSubject = new BehaviorSubject<BooleanMap>({} as BooleanMap);
    protected subpanelReloadSub: Subscription[] = [];
    protected sectionMetadataSubject = new BehaviorSubject<RecordViewSectionMetadata>({} as RecordViewSectionMetadata);
    sectionMetadata$: Observable<RecordViewSectionMetadata> = this.sectionMetadataSubject.asObservable();
    protected subs: Subscription[] = [];

    protected recordValidationHandler: RecordValidationHandler;

    constructor(
        protected recordFetchGQL: RecordFetchGQL,
        protected recordSaveGQL: RecordSaveGQL,
        protected appStateStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected moduleNavigation: ModuleNavigation,
        protected metadataStore: MetadataStore,
        protected localStorage: LocalStorageService,
        protected message: MessageService,
        protected subpanelFactory: SubpanelStoreFactory,
        protected recordManager: RecordManager,
        protected statisticsBatch: StatisticsBatch,
        protected recordStoreFactory: RecordStoreFactory,
        protected preferences: UserPreferenceStore,
        protected recordConvertService: RecordConvertService,
        protected fieldActionAdaptorFactory: FieldActionsAdapterFactory,
    ) {

        super(appStateStore, languageStore, navigationStore, moduleNavigation, metadataStore);

        const initOptions = {
            initVardefBasedFieldActions: true,
            buildFieldActionAdapter: ((options?: ObjectMap): ActionDataSource => {
                return this.fieldActionAdaptorFactory.create('recordView', ((options?.field) as Field)?.name ?? '', this);
            }) as ActionDataSourceBuilderFunction
        };
        this.recordStore = recordStoreFactory.create(this.getViewFieldsObservable(), this.getRecordMetadata$(), initOptions);

        this.record$ = this.recordStore.state$.pipe(distinctUntilChanged());
        this.stagingRecord$ = this.recordStore.staging$.pipe(distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading));
        this.validating$ = this.state$.pipe(map(state => state.validating));
        this.widgets$ = this.state$.pipe(map(state => state.widgets));
        this.showSidebarWidgets$ = this.state$.pipe(map(state => state.showSidebarWidgets));
        this.showBottomWidgets$ = this.state$.pipe(map(state => state.showBottomWidgets));
        this.showHeaderWidgets$ = this.state$.pipe(map(state => state.showHeaderWidgets));
        this.showTopWidget$ = this.state$.pipe(map(state => state.showTopWidget));
        this.showSubpanels$ = this.state$.pipe(map(state => state.showSubpanels));
        this.mode$ = this.state$.pipe(map(state => state.mode));
        this.subpanelReload$ = this.subpanelReloadSubject.asObservable();

        const data$ = this.record$.pipe(
            combineLatestWith(this.loading$),
            map(([record, loading]: [Record, boolean]) => {
                this.data = {record, loading} as RecordViewData;
                return this.data;
            })
        );

        this.vm$ = data$.pipe(
            combineLatestWith(this.appData$, this.metadata$),
            map(([data, appData, metadata]) => {
                this.vm = {data, appData, metadata} as RecordViewModel;
                return this.vm;
            }));

        this.subpanelsState = new BehaviorSubject<SubpanelStoreMap>({} as SubpanelStoreMap);
        this.subpanels$ = this.subpanelsState.asObservable();


        this.viewContext$ = this.record$.pipe(map(() => this.getViewContext()));

        this.recordValidationHandler = inject(RecordValidationHandler);
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

        if (this.getMode() !== 'create') {
            this.savePreference(this.getModuleName(), 'show-sidebar-widgets', show);
        }

        this.updateState({
            ...this.internalState,
            showSidebarWidgets: show
        });
    }

    get showBottomWidgets(): boolean {
        return this.internalState.showBottomWidgets;
    }

    set showBottomWidgets(show: boolean) {
        this.updateState({
            ...this.internalState,
            showBottomWidgets: show
        });
    }

    get showHeaderWidgets(): boolean {
        return this.internalState.showHeaderWidgets;
    }

    set showHeaderWidgets(show: boolean) {
        this.updateState({
            ...this.internalState,
            showHeaderWidgets: show
        });
    }

    get showTopWidget(): boolean {
        return this.internalState.showTopWidget;
    }

    set showTopWidget(show: boolean) {
        this.updateState({
            ...this.internalState,
            showTopWidget: show
        });
    }

    get showSubpanels(): boolean {
        return this.internalState.showTopWidget;
    }

    set showSubpanels(show: boolean) {
        this.updateState({
            ...this.internalState,
            showSubpanels: show
        });
    }

    get params(): { [key: string]: string } {
        return this.internalState.params || {};
    }

    set params(params: { [key: string]: string }) {
        this.updateState({
            ...this.internalState,
            params
        });
    }

    getModuleName(): string {
        return this.internalState.module;
    }

    getRecordId(): string {
        return this.internalState.recordID;
    }

    getViewContext(): ViewContext {
        return {
            module: this.getModuleName(),
            id: this.getRecordId(),
            record: this.getBaseRecord()
        };
    }

    getSubpanels(): SubpanelStoreMap {
        return this.subpanels;
    }

    setSection(section: string): void {
        const recordViewMetadata = this.getRecordViewMetadata() ?? {} as RecordViewMetadata;
        const sections = recordViewMetadata.sections ?? {};
        if (section && sections[section]) {
            this.sectionMetadataSubject.next(sections[section]);
        } else {

            const subpanelKeys = Object.keys(this.subpanels ?? {});

            this.sectionMetadataSubject.next({
                topWidget: recordViewMetadata?.topWidget,
                sidebarWidgets: recordViewMetadata?.sidebarWidgets,
                bottomWidgets: recordViewMetadata?.bottomWidgets,
                headerWidgets: recordViewMetadata?.headerWidgets,
                templateMeta: recordViewMetadata?.templateMeta,
                panels: recordViewMetadata?.panels,
                metadata: recordViewMetadata?.metadata,
                subpanels: subpanelKeys,
            } as RecordViewSectionMetadata);
        }

        this.section.set(section);
    }

    getCurrentSectionMetadata(): RecordViewSectionMetadata {
        return {...this.sectionMetadataSubject.value ?? {} as RecordViewSectionMetadata};
    }

    /**
     * Clean destroy
     */
    public destroy(): void {
        this.clear();
    }

    /**
     * Initial record load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @param {string} recordID to use
     * @param {string} mode to use
     * @param {object} params to set
     * @returns {object} Observable<any>
     */
    public init(module: string, recordID: string, mode = 'detail' as ViewMode, params: Params = {}): Observable<Record> {
        this.internalState.module = module;
        this.internalState.recordID = recordID;
        this.setMode(mode);
        this.initSubpanels(module, recordID);
        this.calculateCurrentSection(params);

        this.calculateShowWidgets();

        return this.load().pipe(
            tap(() => {
                this.showTopWidget = true;
                setTimeout(() => this.loadSubpanelStatistics(module), 1500);
                this.parseParams(params);
            })
        );
    }

    /**
     * Clear observable cache
     */
    public clear(): void {
        this.cache$ = null;
        this.clearSubpanels();
        this.subpanelsState.unsubscribe();
        this.updateState(deepClone(initialState));
        this.subs = this.safeUnsubscription(this.subs);
    }

    /**
     * Get staging record
     *
     * @returns {string} ViewMode
     */
    getBaseRecord(): Record {
        if (!this.internalState) {
            return null;
        }
        return this.recordStore.getBaseRecord();
    }

    /**
     * Get current view mode
     *
     * @returns {string} ViewMode
     */
    getMode(): ViewMode {
        if (!this.internalState) {
            return null;
        }
        return this.internalState.mode;
    }

    /**
     * Set new mode
     *
     * @param {string} mode ViewMode
     */
    setMode(mode: ViewMode): void {
        this.updateState({...this.internalState, mode});
        this.calculateShowWidgets();
    }

    setValidating(value: boolean): void {
        this.updateState({
            ...this.internalState,
            validating: value
        });
    }

    save(): Observable<Record> {
        this.appStateStore.updateLoading(`${this.internalState.module}-record-save`, true);

        this.updateState({
            ...this.internalState,
            loading: true
        });

        return this.recordStore.save().pipe(
            catchError(() => {
                this.message.addDangerMessageByKey('LBL_ERROR_SAVING');
                return of({} as Record);
            }),
            finalize(() => {
                this.setMode('detail' as ViewMode);
                this.appStateStore.updateLoading(`${this.internalState.module}-record-save`, false);
                this.updateState({
                    ...this.internalState,
                    loading: false
                });
            })
        );
    }

    saveOnEdit(): Observable<Record> {
        return this.recordStore.save().pipe(
            catchError(() => {
                this.message.addDangerMessageByKey('LBL_ERROR_SAVING');
                return of({} as Record);
            }),
            finalize(() => {
                this.appStateStore.updateLoading(`${this.internalState.module}-record-save`, false);
                this.updateState({
                    ...this.internalState,
                    loading: false
                });
            })
        );
    }

    showBackButton(): boolean {
        const metadata = this.getRecordViewMetadata();
        return metadata?.header?.backButton?.display ?? true;
    }

    backButtonNavigation() {
        const metadata = this.getRecordViewMetadata();
        return metadata?.header?.backButton?.navigate ?? null;
    }

    /**
     * Load / reload record using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordViewState>
     */
    public load(useCache = true): Observable<Record> {

        this.updateState({
            ...this.internalState,
            loading: true
        });

        return this.recordStore.retrieveRecord(
            this.internalState.module,
            this.internalState.recordID,
            useCache
        ).pipe(
            tap((data: Record) => {

                this.updateState({
                    ...this.internalState,
                    recordID: data.id,
                    module: data.module,
                    loading: false
                });
            })
        );
    }

    /**
     * Get summary template
     *
     * @returns {string} summary template label
     */
    getSummaryTemplate(): string {
        const metadata = this.metadata || {} as Metadata;
        const recordMeta = metadata.recordView || {} as RecordViewMetadata;
        const templates = recordMeta.summaryTemplates || {} as SummaryTemplates;
        return templates[this.getMode()] || '';
    }


    /**
     * Parse query params
     *
     * @param {object} params to set
     */
    protected parseParams(params: Params = {}): void {
        if (!params) {
            return;
        }

        const currentParams = {...this.internalState.params};
        Object.keys(params).forEach(paramKey => {
            if (!isVoid(currentParams[paramKey])) {
                currentParams[paramKey] = params[paramKey];
                return;
            }
        });

        this.params = params;
    }


    /**
     * Load all statistics
     *
     * @param {string} module if to use cache
     */
    protected loadSubpanelStatistics(module: string): void {
        const subpanels = this.subpanelsState.value;

        if (!subpanels) {
            return;
        }

        const queries: StatisticsQueryMap = {};

        Object.keys(subpanels).forEach(subpanelKey => {

            const subpanel = subpanels[subpanelKey];
            const statsMap = subpanel.statistics;

            if (!statsMap || !Object.keys(statsMap).length) {
                return;
            }

            if (subpanel.shouldBatchStatistic() === false) {
                subpanel.loadAllStatistics().pipe(take(1)).subscribe();
                return;
            }

            const subpanelQueries = subpanel.getAllStatisticQuery();

            Object.keys(subpanelQueries).forEach(subpanelQueryKey => {
                const queryKey = this.buildStatKey(subpanelKey, subpanelQueryKey);
                queries[queryKey] = subpanelQueries[subpanelQueryKey];
            });

            subpanel.setAllStatisticsLoading(true);
        });

        this.statisticsBatch.fetch(module, queries)
            .pipe(take(1))
            .subscribe((stats: StatisticsMap) => {

                Object.keys(subpanels).forEach(subpanelKey => {

                    const subpanel = subpanels[subpanelKey];
                    const subpanelQueries = subpanel.getAllStatisticQuery();

                    Object.keys(subpanelQueries).forEach(subpanelQueryKey => {
                        const queryKey = this.buildStatKey(subpanelKey, subpanelQueryKey);
                        const stat = stats[queryKey];
                        if (!stat) {
                            return;
                        }
                        subpanel.setStatistics(subpanelQueryKey, stat, true);
                    });

                    subpanel.setAllStatisticsLoading(false);
                });
            });
    }

    protected buildStatKey(subpanelKey: string, subpanelQueryKey: string): string {
        subpanelKey = subpanelKey.replace(/_/g, '-');
        subpanelQueryKey = subpanelQueryKey.replace(/_/g, '-');

        return subpanelKey + '-' + subpanelQueryKey;
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: RecordViewState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Init subpanels
     *
     * @param {string} module parent module
     * @param {string} recordId id
     */
    protected initSubpanels(module: string, recordId: string): void {
        this.showSubpanels = true;
        this.metadataStore.subPanelMetadata$.subscribe((meta: SubPanelMeta) => {
            this.clearSubpanels();

            Object.keys(meta).forEach((key: string) => {
                this.subpanels[key] = this.subpanelFactory.create();
                this.subpanels[key].init(module, recordId, meta[key], this.record$);
            });

            this.subpanelsState.next(this.subpanels);

            Object.keys(this.subpanels).forEach(subpanelKey => {
                const subpanel = this.subpanels[subpanelKey];
                this.subpanelReloadSub.push(subpanel.recordList.records$.pipe(tap(() => {
                    const update = {} as BooleanMap;
                    update[subpanelKey] = true;
                    this.subpanelReloadSubject.next(update);
                })).subscribe());
            });
        });
    }

    protected clearSubpanels(): void {
        if (this.subpanels) {
            Object.keys(this.subpanels).forEach((key: string) => {
                this.subpanels[key].clear();
            });
        }

        if (this.subpanelReloadSub.length) {
            this.subpanelReloadSub.forEach(sub => sub.unsubscribe());
            this.subpanelReloadSub = [];
        }

        this.subpanels = {};
    }

    /**
     * Calculate if widgets are to display
     */
    protected calculateShowWidgets(): void {
        let show = false;
        const recordViewMeta = this.getRecordViewMetadata();
        const sidebarWidgetsConfig = recordViewMeta.sidebarWidgets || [];

        if (sidebarWidgetsConfig && sidebarWidgetsConfig.length > 0) {
            show = true;
        }

        if (this.section()) {
            const sections = recordViewMeta.sections ?? {};
            show = Object.values(sections).some(section => section?.sidebarWidgets?.length);
        }

        const showSidebarWidgets = this.loadPreference(this.getModuleName(), 'show-sidebar-widgets') ?? null;

        if (showSidebarWidgets !== null) {
            this.showSidebarWidgets = showSidebarWidgets;
        } else {
            this.showSidebarWidgets = show;
        }

        this.showBottomWidgets = true;
        this.showHeaderWidgets = true;

        this.widgets = true;
    }

    /**
     * Calculate current section
     */
    protected calculateCurrentSection(params: Params = {}): void {
        const recordViewMeta = this.getRecordViewMetadata();
        const sections = recordViewMeta?.sections ?? {};
        const sectionKeys = Object.keys(sections);

        if (!sections || !sectionKeys.length) {
            this.setSection('');
            return;
        }

        this.sectionKeys.set(sectionKeys);

        const section = params?.section ?? ''
        if (section && sectionKeys.includes(section)) {
            this.setSection(section);
            return;
        }

        this.setSection(sectionKeys[0]);
    }

    /**
     * Get record view metadata
     *
     * @returns {object} metadata RecordViewMetadata
     */
    protected getRecordViewMetadata(): RecordViewMetadata {
        const meta = this.metadataStore.get() || {};
        return meta.recordView || {} as RecordViewMetadata;
    }

    /**
     * Get vardefs
     *
     * @returns {object} vardefs FieldDefinitionMap
     */
    protected getVardefs(): FieldDefinitionMap {
        const meta = this.getRecordViewMetadata();
        return meta.vardefs || {} as FieldDefinitionMap;
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    protected getViewFieldsObservable(): Observable<ViewFieldDefinition[]> {
        return this.metadataStore.recordViewMetadata$.pipe(map((recordMetadata: RecordViewMetadata) => {
            const fieldsMap: ViewFieldDefinitionMap = {};

            recordMetadata.panels.forEach(panel => {
                panel.rows.forEach(row => {
                    row.cols.forEach(col => {
                        const fieldName = col.name ?? col.fieldDefinition.name ?? '';
                        fieldsMap[fieldName] = col;
                    });
                });
            });

            Object.keys(recordMetadata.vardefs).forEach(fieldKey => {
                const vardef = recordMetadata.vardefs[fieldKey] ?? null;
                if (!vardef || isEmpty(vardef)) {
                    return;
                }

                // already defined. skip
                if (fieldsMap[fieldKey]) {
                    return;
                }

                if (vardef.type == 'relate') {
                    return;
                }

                fieldsMap[fieldKey] = {
                    name: fieldKey,
                    vardefBased: true,
                    label: vardef.vname ?? '',
                    type: vardef.type ?? '',
                    display: vardef.display ?? '',
                    fieldDefinition: vardef,
                    metadata: vardef.metadata ?? {} as FieldMetadata,
                    logic: vardef.logic ?? {} as FieldLogicMap
                } as ViewFieldDefinition;
            });

            return Object.values(fieldsMap);
        }));
    }

    protected getRecordMetadata$(): Observable<ObjectMap> {
        return this.metadataStore.recordViewMetadata$.pipe(map((recordMetadata: RecordViewMetadata) => {
            return recordMetadata?.metadata ?? {};
        }));
    }

    /**
     * Build ui user preference key
     *
     * @param {string} storageKey Storage Key
     * @protected
     * @returns {string} Preference Key
     */
    protected getPreferenceKey(storageKey: string): string {
        return 'recordview-' + storageKey;
    }

    /**
     * Save ui user preference
     *
     * @param {string} module Module
     * @param {string} storageKey Storage Key
     * @param {any} value Value
     * @protected
     */
    protected savePreference(module: string, storageKey: string, value: any): void {
        this.preferences.setUi(module, this.getPreferenceKey(storageKey), value);
    }

    /**
     * Load ui user preference
     *
     * @param {string} module Module
     * @param {string} storageKey Storage Key
     * @protected
     * @returns {any} User Preference
     */
    protected loadPreference(module: string, storageKey: string): any {
        return this.preferences.getUi(module, this.getPreferenceKey(storageKey));
    }

    public filterWidgetsByMode(widgets: WidgetMetadata[]): WidgetMetadata[] {
        return widgets.filter(widget => {
            const modes = widget?.modes ?? [];

            if (modes.length === 0) {
                return true;
            }

            const mode = this.getMode();

            return modes.includes(mode);
        });
    }

    private safeUnsubscription(subscriptionArray: Subscription[]): Subscription[] {
        subscriptionArray.forEach(sub => {
            if (sub.closed) {
                return;
            }

            sub.unsubscribe();
        });
        subscriptionArray = [];

        return subscriptionArray;
    }
}
