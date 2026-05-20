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

import {inject, Injectable} from '@angular/core';
import {BehaviorSubject, combineLatestWith, forkJoin, Observable, of, Subscription} from 'rxjs';
import {Record} from '../../../../common/record/record.model';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {ColumnDefinition, SearchMetaFieldMap} from '../../../../common/metadata/list.metadata.model';
import {ViewContext, ViewMode} from '../../../../common/views/view.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {deepClone} from '../../../../common/utils/object-utils';
import {catchError, distinctUntilChanged, filter, finalize, map, startWith, take, tap} from 'rxjs/operators';
import {StateStore} from '../../../../store/state';
import {MetadataStore, RecordViewMetadata} from '../../../../store/metadata/metadata.store.service';
import {MessageService} from '../../../../services/message/message.service';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {FilterContainerData, FilterContainerState} from './saved-filter.store.model';
import {SavedFilter} from '../../../../store/saved-filters/saved-filter.model';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {SavedFilterRecordStore} from './saved-filter-record.store';
import {SavedFilterRecordStoreFactory} from './saved-filter-record.store.factory';
import {RecordValidationHandler} from "../../../../services/record/validation/record-validation.handler";
import {ObjectMap} from "../../../../common/types/object-map";

const initialState: FilterContainerState = {
    module: '',
    searchModule: '',
    recordID: '',
    loading: false,
    mode: 'detail',
};

@Injectable()
export class SavedFilterStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    record$: Observable<SavedFilter>;
    stagingRecord$: Observable<SavedFilter>;
    loading$: Observable<boolean>;
    mode$: Observable<ViewMode>;
    meta$: Observable<RecordViewMetadata>;
    metadataLoading$: Observable<boolean>;

    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<FilterContainerData>;
    vm: FilterContainerData;
    recordStore: SavedFilterRecordStore;

    searchCriteria: SearchCriteria;
    filter: SavedFilter;

    /** Internal Properties */
    protected cache$: Observable<any> = null;
    protected internalState: FilterContainerState = deepClone(initialState);
    protected store = new BehaviorSubject<FilterContainerState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected subs: Subscription[] = [];
    protected metadataLoadingState: BehaviorSubject<boolean>;
    protected recordValidationHandler: RecordValidationHandler;

    constructor(
        protected appStateStore: AppStateStore,
        protected meta: MetadataStore,
        protected message: MessageService,
        protected fieldManager: FieldManager,
        protected language: LanguageStore,
        protected savedFilterStoreFactory: SavedFilterRecordStoreFactory
    ) {
        this.metadataLoadingState = new BehaviorSubject(false);
        this.metadataLoading$ = this.metadataLoadingState.asObservable();

        this.meta$ = this.meta.getMetadata('saved-search', ['recordView']).pipe(
            tap(() => this.metadataLoadingState.next(false)),
            map(definitions => {
                const recordViewMeta = {...definitions.recordView};
                recordViewMeta.actions = (recordViewMeta?.actions ?? []).filter(value => {
                    return value.key !== 'cancel'
                });
                return recordViewMeta;
            })
        );

        this.recordStore = savedFilterStoreFactory.create(this.getViewFields$(), this.getRecordMeta$());

        this.record$ = this.recordStore.state$.pipe(distinctUntilChanged(), map(record => record as SavedFilter));
        this.stagingRecord$ = this.recordStore.staging$.pipe(distinctUntilChanged(), map(record => record as SavedFilter));
        this.loading$ = this.state$.pipe(map(state => state.loading));
        this.mode$ = this.state$.pipe(map(state => state.mode));

        this.vm$ = this.stagingRecord$.pipe(
            combineLatestWith(this.mode$),
            map(([record, mode]: [SavedFilter, ViewMode]) => {
                this.vm = {record, mode} as FilterContainerData;
                return this.vm;
            })
        );

        this.recordValidationHandler = inject(RecordValidationHandler);
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
        };
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
     * @param {string} recordID to use
     * @param {string} mode to use
     * @returns {object} Observable<any>
     */
    public init(recordID: string, mode = 'detail' as ViewMode): Observable<Record> {
        this.internalState.module = 'saved-search';
        this.internalState.recordID = recordID;
        this.setMode(mode);

        this.metadataLoadingState.next(true);

        const $data = forkJoin([this.meta$, this.load()]);

        return $data.pipe(map(([meta, record]) => record));
    }

    /**
     * Init record
     *
     * @param {string} searchModule name
     * @param {object} filter to use
     * @param {object} searchFields to use
     * @param {object} listColumns ColumnDefinition[]
     * @param {string} mode to use
     * @returns {object} Observable<any>
     */
    public initRecord(
        searchModule: string,
        filter: SavedFilter,
        searchFields: SearchMetaFieldMap,
        listColumns: ColumnDefinition[],
        mode = 'detail' as ViewMode): void {

        this.updateState({
            ...this.internalState,
            recordID: filter.id,
            module: 'saved-search',
            searchModule,
            mode
        });

        this.metadataLoadingState.next(true);

        this.meta$.pipe(
            take(1),
            tap(() => {
                this.metadataLoadingState.next(false);
                this.initStaging(searchModule, filter, searchFields, listColumns, null);
            })
        ).subscribe();
    }

    public initStaging(
        searchModule: string,
        filter: SavedFilter,
        searchFields: SearchMetaFieldMap,
        listColumns: ColumnDefinition[],
        metadata: ObjectMap
    ) {

        const filterRecord: SavedFilter = deepClone(this.recordStore.extractBaseRecord(filter));

        filterRecord.searchModule = searchModule;
        this.recordStore.setSearchFields(searchFields);
        this.recordStore.setListColumns(listColumns);
        this.recordStore.setMetadata(metadata);
        this.recordStore.setStaging(filterRecord);
        this.recordValidationHandler.initValidators(this.recordStore.getStaging());
    }

    /**
     * Clear observable cache
     */
    public clear(): void {
        this.cache$ = null;
        this.updateState(deepClone(initialState));
        this.metadataLoadingState.unsubscribe();
        this.metadataLoadingState = null;
        this.recordStore.destroy();
        this.recordStore = null;
    }

    /**
     * Clear observable cache
     */
    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Get staging record
     *
     * @returns {string} ViewMode
     */
    public getBaseRecord(): SavedFilter {
        return this.recordStore.getBaseRecord();
    }


    /**
     * Get current view mode
     *
     * @returns {string} ViewMode
     */
    public getMode(): ViewMode {
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
    public setMode(mode: ViewMode): void {
        this.updateState({...this.internalState, mode});
    }

    /**
     * Save record
     */
    public save(): Observable<Record> {
        this.appStateStore.updateLoading(`${this.internalState.module}-record-save`, true);

        return this.recordStore.save().pipe(
            catchError(() => {
                this.message.addDangerMessageByKey('LBL_ERROR_SAVING');
                return of({} as Record);
            }),
            finalize(() => {
                this.appStateStore.updateLoading(`${this.internalState.module}-record-save`, false);
            })
        );
    }

    /**
     * Validate search filter fields
     *
     * @returns {object} Observable<boolean>
     */
    public validate(): Observable<boolean> {

        return forkJoin([
            this.recordStore.validate(),
            this.validateCriteria()
        ]).pipe(map(([fields, criteria]) => fields && criteria));
    }

    /**
     * Validate search current input
     *
     * @returns {object} Observable<boolean>
     */
    public validateCriteria(): Observable<boolean> {

        const currentFilter = this.recordStore.getStaging() as SavedFilter;
        const formGroup = currentFilter.criteriaFormGroup;
        formGroup.markAllAsTouched();
        return formGroup.statusChanges.pipe(
            startWith(formGroup.status),
            filter(status => status !== 'PENDING'),
            take(1),
            map(status => status === 'VALID')
        );
    }

    /**
     * Load / reload record using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordViewState>
     */
    public load(useCache = true): Observable<Record> {
        this.appStateStore.updateLoading(`${this.internalState.module}-record-fetch`, true);

        return this.recordStore.retrieveRecord(
            this.internalState.module,
            this.internalState.recordID,
            useCache
        ).pipe(
            tap((data: Record) => {
                this.appStateStore.updateLoading(`${this.internalState.module}-record-fetch`, false);

                this.updateState({
                    ...this.internalState,
                    recordID: data.id,
                    module: data.module,
                });
            })
        );
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<string[]>
     */
    public getViewFieldsKeys$(): Observable<string[]> {
        return this.meta$.pipe(map((recordMetadata: RecordViewMetadata) => {
            const fields: string[] = [];
            recordMetadata.panels.forEach(panel => {
                panel.rows.forEach(row => {
                    row.cols.forEach(col => {
                        fields.push(col.name);
                    });
                });
            });

            return fields;
        }));
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    public getViewFields$(): Observable<ViewFieldDefinition[]> {
        return this.meta$.pipe(map((recordMetadata: RecordViewMetadata) => {
            const fields: ViewFieldDefinition[] = [];
            recordMetadata.panels.forEach(panel => {
                panel.rows.forEach(row => {
                    row.cols.forEach(col => {
                        fields.push(col);
                    });
                });
            });

            return fields;
        }));
    }

    public getRecordMeta$(): Observable<ObjectMap> {
        return this.meta$.pipe(map((recordMetadata: RecordViewMetadata) => {
            return recordMetadata.metadata || {};
        }));
    }


    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: FilterContainerState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Get record view metadata
     *
     * @returns {object} metadata RecordViewMetadata
     */
    protected getMetadata(): RecordViewMetadata {
        const meta = this.meta.get() || {};
        return meta.recordView || {} as RecordViewMetadata;
    }
}
