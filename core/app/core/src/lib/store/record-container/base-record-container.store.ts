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

import {BehaviorSubject, forkJoin, Observable, of, Subscription} from 'rxjs';
import {ViewContext, ViewMode} from '../../common/views/view.model';
import {deepClone} from '../../common/utils/object-utils';
import {Record} from '../../common/record/record.model';
import {ViewFieldDefinition} from '../../common/metadata/metadata.model';
import {catchError, distinctUntilChanged, finalize, map, take, tap} from 'rxjs/operators';
import {RecordStore} from '../record/record.store';
import {AppStateStore} from '../app-state/app-state.store';
import {MetadataStore} from '../metadata/metadata.store.service';
import {FieldManager} from '../../services/record/field/field.manager';
import {MessageService} from '../../services/message/message.service';
import {LanguageStore} from '../language/language.store';
import {RecordStoreFactory} from '../record/record.store.factory';
import {StateStore} from '../state';
import {RecordContainerState} from './record-container.store.model';
import {BaseRecordContainerStoreInterface} from "../../common/containers/record/record-container.store.model";

const initialState: RecordContainerState = {
    module: '',
    recordId: '',
    loading: {
        data: false,
        metadata: false
    },
    mode: 'detail',
};

export abstract class BaseRecordContainerStore<M> implements StateStore, BaseRecordContainerStoreInterface {

    /**
     * Public long-lived observable streams
     */
    record$: Observable<Record>;
    stagingRecord$: Observable<Record>;
    loading$: Observable<boolean>;
    mode$: Observable<ViewMode>;
    meta$: Observable<M>;

    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<RecordContainerState>;
    recordStore: RecordStore;

    /** Internal Properties */
    protected internalState: RecordContainerState = deepClone(initialState);
    protected metadataState: M = deepClone({} as M);
    protected store = new BehaviorSubject<RecordContainerState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected metadataStore = new BehaviorSubject<M>({} as M);
    protected metadataState$ = this.metadataStore.asObservable();
    protected subs: Subscription[] = [];

    constructor(
        protected appStateStore: AppStateStore,
        protected meta: MetadataStore,
        protected message: MessageService,
        protected fieldManager: FieldManager,
        protected language: LanguageStore,
        protected storeFactory: RecordStoreFactory
    ) {
        this.meta$ = this.metadataState$;

        this.recordStore = storeFactory.create(this.getViewFields$());

        this.record$ = this.recordStore.state$.pipe(distinctUntilChanged());
        this.stagingRecord$ = this.recordStore.staging$.pipe(distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading.data || state.loading.metadata));
        this.mode$ = this.state$.pipe(map(state => state.mode));
        this.vm$ = this.state$;
    }

    /**
     * Get current module name
     * @returns {string} module
     */
    public getModuleName(): string {
        return this.internalState.module;
    }

    /**
     * Get current record id
     * @returns {string} id
     */
    public getRecordId(): string {
        return this.internalState.recordId;
    }

    /**
     * Get View Context
     * @returns {object} ViewContext
     */
    public getViewContext(): ViewContext {
        return {
            module: this.getModuleName(),
            id: this.getRecordId(),
        };
    }

    /**
     * Initial record load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @param {string} recordId to use
     * @param {string} mode to use
     * @returns {object} Observable<any>
     */
    public init(module: string, recordId: string, mode: ViewMode = 'detail' as ViewMode): Observable<Record> {

        this.baseInit(module, recordId, mode);

        this.setMetadataLoading(true);

        const $data = forkJoin([this.loadMetadata(), this.load()]);

        return $data.pipe(
            map(([meta, record]) => record),
        );
    }

    /**
     * Init record
     *
     * @param {object} record to use
     * @param {string} mode to use
     * @param {boolean} loadMetadata to use
     * @returns {object} Observable<any>
     */
    public initRecord(record: Record, mode: ViewMode = 'detail' as ViewMode, loadMetadata = true): void {

        this.baseInit(record.module, record.id, mode);

        if (loadMetadata) {
            this.loadMetadata().pipe(
                take(1),
                tap(() => {
                    this.setRecord(record);
                }),
            ).subscribe();
        }
    }

    /**
     * Init staging
     * @param {object} record
     */
    public initStaging(record: Record) {
        const baseRecord: Record = deepClone(this.recordStore.extractBaseRecord(record));
        this.recordStore.setStaging(baseRecord);
    }

    /**
     * Set Record
     * @param {object} record
     * @param {boolean} initDefaultValues
     */
    public setRecord(record: Record, initDefaultValues: boolean = false) {
        const baseRecord: Record = deepClone(this.recordStore.extractBaseRecord(record));
        this.recordStore.setRecord(baseRecord, initDefaultValues);
    }

    /**
     * Set Metadata
     * @param {object} meta
     */
    public setMetadata(meta: M) {
        this.updateMetadataState(meta);
        this.setMetadataLoading(false);
    }

    /**
     * Clean destroy
     */
    public destroy(): void {
        this.clear();
    }

    /**
     * Clear observable cache
     */
    public clear(): void {
        this.updateState(deepClone(initialState));
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
    public getBaseRecord(): Record {
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
     * Set loading flag
     *
     * @param {boolean} loading flag
     */
    public setDataLoading(loading: boolean): void {
        this.updateState({
            ...this.internalState,
            loading: {
                ...this.internalState.loading,
                data: loading
            }
        });
    }

    /**
     * Set loading flag
     *
     * @param {boolean} loading flag
     */
    public setMetadataLoading(loading: boolean): void {
        this.updateState({
            ...this.internalState,
            loading: {
                ...this.internalState.loading,
                metadata: loading
            }
        });
    }

    /**
     * Save record
     */
    public save(): Observable<Record> {
        this.setDataLoading(true);

        return this.recordStore.save().pipe(
            catchError(() => {
                this.message.addDangerMessageByKey('LBL_ERROR_SAVING');
                return of({} as Record);
            }),
            finalize(() => {
                this.setDataLoading(false);
            })
        );
    }

    /**
     * Validate search filter fields
     *
     * @returns {object} Observable<boolean>
     */
    public validate(): Observable<boolean> {

        return this.recordStore.validate();
    }

    /**
     * Load / reload record using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordViewState>
     */
    public load(useCache = true): Observable<Record> {

        this.setDataLoading(true);

        return this.recordStore.retrieveRecord(
            this.internalState.module,
            this.internalState.recordId,
            useCache
        ).pipe(
            tap((data: Record) => {
                this.updateState({
                    ...this.internalState,
                    recordId: data.id,
                    module: data.module,
                });
            }),
            finalize(() => {
                this.setDataLoading(false);
            })
        );
    }

    /**
     * Load / reload record using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordViewState>
     */
    public loadMetadata(useCache = true): Observable<M> {

        this.setMetadataLoading(true);

        return this.meta.getMetadata(this.internalState.module).pipe(
            map(metadata => metadata.recordView),
            tap((meta: M) => {
                this.updateMetadataState(meta);
            }),
            finalize(() => {
                this.setMetadataLoading(false);
            })
        );
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    public abstract getViewFields$(): Observable<ViewFieldDefinition[]>;


    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: RecordContainerState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Update the metadata state
     *
     * @param {object} state to set
     */
    protected updateMetadataState(state: M): void {
        this.metadataStore.next(this.metadataState = state);
    }

    /**
     * Get record view metadata
     *
     * @returns {object} metadata M
     */
    protected getMetadata(): M {
        return deepClone(this.metadataState);
    }

    /**
     * Base store initialization
     * @param module
     * @param recordId
     * @param mode
     */
    protected baseInit(module: string, recordId: string, mode: ViewMode = 'detail' as ViewMode) {
        this.updateState({
            ...this.internalState,
            module,
            recordId,
            mode
        });
    }

}
