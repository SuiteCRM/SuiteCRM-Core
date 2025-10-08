/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {BehaviorSubject, combineLatest, combineLatestWith, Observable, of, Subscription} from 'rxjs';
import {catchError, distinctUntilChanged, finalize, map, take, tap} from 'rxjs/operators';
import {Metadata, MetadataStore, RecordViewMetadata} from '../../../../store/metadata/metadata.store.service';
import {StateStore} from '../../../../store/state';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {RecordStoreFactory} from "../../../../store/record/record.store.factory";
import {RecordStore} from "../../../../store/record/record.store";
import {isEmpty} from "lodash-es";
import {RecordViewData, RecordViewState} from "../../../../views/record/store/record-view/record-view.store.model";
import {RecordValidationHandler} from "../../../../services/record/validation/record-validation.handler";
import {Params} from "@angular/router";
import {LanguageStore} from "../../../../store/language/language.store";
import {PanelLogicManager} from "../../../../components/panel-logic/panel-logic.manager";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {MessageService} from "../../../../services/message/message.service";
import {NavigationStore} from "../../../../store/navigation/navigation.store";
import {ModuleNavigation} from "../../../../services/navigation/module-navigation/module-navigation.service";
import {Record} from "../../../../common/record/record.model";
import {ViewContext, ViewMode} from "../../../../common/views/view.model";
import {
    FieldActionsArrayMap,
    Panel,
    PanelRow,
    ViewFieldDefinition,
    ViewFieldDefinitionMap
} from "../../../../common/metadata/metadata.model";
import {deepClone} from "../../../../common/utils/object-utils";
import {isVoid} from "../../../../common/utils/value-utils";
import {Field, FieldDefinitionMap, FieldMetadata} from "../../../../common/record/field.model";
import {FieldLogicMap} from "../../../../common/actions/field-logic-action.model";
import {ObjectMap} from "../../../../common/types/object-map";
import {RecordModalFieldActionsAdapterFactory} from "../../adapters/record-modal-field-actions.adapter.factory";
import {BaseRecordContainerStoreInterface} from "../../../../common/containers/record/record-container.store.model";
import {ActionDataSource, ActionDataSourceBuilderFunction} from "../../../../common/actions/action.model";
import {RecordManager} from "../../../../services/record/record.manager";

const initialState: any = {
    module: '',
    recordID: '',
    loading: false,
    validating: false,
    mode: 'detail',
    params: {
        returnModule: '',
        returnId: '',
        returnAction: ''
    }
};

export class RecordModalStore implements StateStore, BaseRecordContainerStoreInterface {

    record$: Observable<Record>;
    stagingRecord$: Observable<Record>;
    loading$: Observable<boolean>;
    validating$: Observable<boolean>;
    mode$: Observable<ViewMode>;
    viewContext$: Observable<ViewContext>;
    metadata$: Observable<Metadata>;
    recordViewMetadata$: Observable<RecordViewMetadata>;

    panels$: Observable<Panel[]>;
    panels: Panel[] = [];

    metadataLoading$: Observable<boolean>;
    protected metadataLoadingState: BehaviorSubject<boolean>;
    protected fieldActionsState = new BehaviorSubject<FieldActionsArrayMap>({});
    fieldActions$: Observable<FieldActionsArrayMap> = this.fieldActionsState.asObservable();

    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<any>;
    vm: any;
    data: RecordViewData;
    recordStore: RecordStore;

    /** Internal Properties */
    protected cache$: Observable<any> = null;
    protected internalState: RecordViewState = deepClone(initialState);
    protected store = new BehaviorSubject<RecordViewState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected subs: Subscription[] = [];
    protected fieldSubs: Subscription[] = [];
    protected panelsSubject: BehaviorSubject<Panel[]> = new BehaviorSubject(this.panels);
    protected metadataState: BehaviorSubject<Metadata>;

    constructor(
        protected metadataView: string,
        protected recordStoreFactory: RecordStoreFactory,
        protected appStateStore: AppStateStore,
        protected preferences: UserPreferenceStore,
        protected meta: MetadataStore,
        protected languageStore: LanguageStore,
        protected panelLogicManager: PanelLogicManager,
        protected message: MessageService,
        protected navigationStore: NavigationStore,
        protected moduleNavigation: ModuleNavigation,
        protected fieldActionAdaptorFactory: RecordModalFieldActionsAdapterFactory,
        protected recordValidationHandler: RecordValidationHandler,
        protected recordManager: RecordManager
    ) {
        this.metadataState = new BehaviorSubject<Metadata>({});
        this.metadata$ = this.metadataState.asObservable();
        this.recordViewMetadata$ = this.metadata$.pipe(map(meta => {
            const extra = meta?.extra ?? {};
            if (extra[metadataView] ?? null) {
                return meta?.extra[metadataView] as RecordViewMetadata;
            }

            return meta?.recordView ?? {} as RecordViewMetadata;
        }));


        this.loading$ = this.state$.pipe(map(state => state.loading));
        this.validating$ = this.state$.pipe(map(state => state.validating));
        this.metadataLoadingState = new BehaviorSubject(false);
        this.metadataLoading$ = this.metadataLoadingState.asObservable();
        this.mode$ = this.state$.pipe(map(state => state.mode));
    }

    clear(): void {
        this.recordStore = null;
        this.cache$ = null;
        this.updateState(deepClone(initialState));
        this.subs = this.safeUnsubscription(this.subs);
        this.fieldSubs = this.safeUnsubscription(this.fieldSubs);
    }

    clearAuthBased(): void {
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
    public init(module: string, recordID: string, mode = 'detail' as ViewMode, params: Params = {}): void {
        this.internalState.module = module;
        this.internalState.recordID = recordID;
        this.setMode(mode);
        this.metadataLoadingState.next(true);
        this.parseParams(params);


        this.subs.push(
            this.meta.getMetadata(module).pipe(
                tap((metadata) => {
                    this.metadataState.next(metadata ?? {});
                    this.metadataLoadingState.next(false);
                })
            ).subscribe()
        );

        const initOptions = {
            initVardefBasedFieldActions: true,
            buildFieldActionAdapter: ((options?: ObjectMap): ActionDataSource => {
                return this.fieldActionAdaptorFactory.create('recordView', ((options?.field) as Field)?.name ?? '', this);
            }) as ActionDataSourceBuilderFunction
        };

        this.recordStore = this.recordStoreFactory.create(this.getViewFieldsObservable(), this.getRecordMetadata$(), initOptions);

        if (mode === 'create') {
            const blankRecord = deepClone({
                id: '',
                type: '',
                module: module,
                attributes: {
                    assigned_user_id: this.appStateStore.getCurrentUser().id,
                    assigned_user_name: {
                        id: this.appStateStore.getCurrentUser().id,
                        user_name: this.appStateStore.getCurrentUser().userName
                    },
                },
            } as Record);

            this.recordManager.injectParamFields(params, blankRecord, this.getVardefs());
            this.recordStore.init(
                blankRecord,
                true
            );
        } else {
            this.load().pipe(
                take(1)).subscribe();
        }

        this.panels$ = this.panelsSubject.asObservable();
        this.record$ = this.recordStore.state$.pipe(distinctUntilChanged());
        this.stagingRecord$ = this.recordStore.staging$.pipe(distinctUntilChanged());
        this.viewContext$ = this.record$.pipe(map(() => this.getViewContext()));

        const data$ = this.record$.pipe(
            combineLatestWith(this.loading$),
            map(([record, loading]: [Record, boolean]) => {
                this.data = {record, loading} as RecordViewData;
                return this.data;
            })
        );

        this.initPanels();
        this.initFieldActions();
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

    public loadMetadata(module: string): Observable<Metadata> {
        return this.meta.getMetadata(module);
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

    get params(): { [key: string]: string } {
        return this.internalState.params || {};
    }

    set params(params: { [key: string]: string }) {
        this.updateState({
            ...this.internalState,
            params
        });
    }

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

        this.params = currentParams;
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    protected getViewFieldsObservable(): Observable<ViewFieldDefinition[]> {
        return this.recordViewMetadata$.pipe(map((recordMetadata: RecordViewMetadata) => {
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
        return this.recordViewMetadata$.pipe(map((recordMetadata: RecordViewMetadata) => {
            return recordMetadata?.metadata ?? {};
        }));
    }

    protected updateState(state: RecordViewState): void {
        this.store.next(this.internalState = state);
    }

    setMode(mode: ViewMode): void {
        this.updateState({...this.internalState, mode});
    }

    getMode(): ViewMode {
        if (!this.internalState) {
            return null;
        }
        return this.internalState.mode;
    }

    getBaseRecord(): Record {
        if (!this.internalState) {
            return null;
        }
        return this.recordStore.getBaseRecord();
    }

    getViewContext(): ViewContext {
        return {
            module: this.getModuleName(),
            id: this.getRecordId(),
            record: this.getBaseRecord()
        } as ViewContext;
    }

    getModuleName(): string {
        return this.internalState.module;
    }

    getRecordId(): string {
        return this.internalState.recordID;
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

    protected initPanels(): void {
        const panelSub = combineLatest([
            this.recordViewMetadata$,
            this.stagingRecord$,
            this.languageStore.vm$,
        ]).subscribe(([meta, record, languages]) => {
            const panels = [];
            const module = (record && record.module) || '';

            if (!meta || !meta.panels) {
                return;
            }

            this.safeUnsubscription(this.fieldSubs);
            meta.panels.forEach(panelDefinition => {
                const label = (panelDefinition.label)
                    ? panelDefinition.label.toUpperCase()
                    : this.languageStore.getFieldLabel(panelDefinition.key.toUpperCase(), module, languages);
                const panel = {label, key: panelDefinition.key, rows: []} as Panel;

                let adaptor = null;
                const tabDef = meta.templateMeta.tabDefs[panelDefinition.key.toUpperCase()] ?? null;
                if (tabDef) {
                    panel.meta = tabDef;
                }

                panelDefinition.rows.forEach(rowDefinition => {
                    const row = {cols: []} as PanelRow;
                    rowDefinition.cols.forEach(cellDefinition => {
                        const cellDef = {...cellDefinition};
                        const fieldActions = cellDefinition?.fieldActions ?? cellDefinition?.fieldDefinition?.fieldActions ?? null;
                        if (fieldActions) {
                            adaptor = this.fieldActionAdaptorFactory.create('recordView', cellDef.name, this);
                            cellDef.adaptor = adaptor;
                        }

                        row.cols.push(cellDef);
                    });
                    panel.rows.push(row);
                });

                panel.displayState = new BehaviorSubject(tabDef?.display ?? true);
                panel.display$ = panel.displayState.asObservable();

                panels.push(panel);

                if (isEmpty(record?.fields) || isEmpty(tabDef?.displayLogic)) {
                    return;
                }

                Object.values(tabDef?.displayLogic ?? []).forEach((logicDef) => {
                    if (isEmpty(logicDef?.params?.fieldDependencies)) {
                        return;
                    }

                    logicDef.params.fieldDependencies.forEach(fieldKey => {
                        const field = record.fields[fieldKey] || null;
                        if (isEmpty(field)) {
                            return;
                        }

                        this.fieldSubs.push(
                            field.valueChanges$.subscribe(() => {
                                this.panelLogicManager.runLogic(logicDef.key, field, panel, record, this.getMode());
                            }),
                        );
                    });
                });
            });
            this.panelsSubject.next(this.panels = panels);
            return panels;
        });

        this.subs.push(panelSub);
    }

    /**
     * Get record view metadata
     *
     * @returns {object} metadata RecordViewMetadata
     */
    protected getRecordViewMetadata(): RecordViewMetadata {
        return this?.metadataState?.value?.recordView || {} as RecordViewMetadata;
    }

    protected initFieldActions(): void {

        const fieldActionsSub = this.recordViewMetadata$.subscribe(metadata => {
            const fieldActions: FieldActionsArrayMap = {};

            const panels = metadata?.panels ?? [];

            panels.forEach(panel => {
                if (panel.rows) {
                    panel.rows.forEach(row => {
                        if (row.cols) {
                            row.cols.forEach(col => {
                                if (col.fieldActions && col.fieldActions.actions) {
                                    Object.values(col.fieldActions.actions).forEach(action => {
                                        action['fieldName'] = col.name;
                                        const viewFieldActions = fieldActions[col.name] ?? [];
                                        viewFieldActions.push(action);
                                        fieldActions[col.name] = viewFieldActions;
                                    });
                                }
                            });
                        }
                    })
                }
            });

            this.fieldActionsState.next(fieldActions);
        });

        this.subs.push(fieldActionsSub);
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
}
