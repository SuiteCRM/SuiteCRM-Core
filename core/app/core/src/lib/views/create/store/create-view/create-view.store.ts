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
import {Observable, of} from 'rxjs';
import {catchError, finalize, map, shareReplay, tap} from 'rxjs/operators';
import {Params} from '@angular/router';
import {StatisticsBatch} from '../../../../store/statistics/statistics-batch.service';
import {RecordViewStore} from '../../../record/store/record-view/record-view.store';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {LanguageStore} from '../../../../store/language/language.store';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {RecordManager} from '../../../../services/record/record.manager';
import {LocalStorageService} from '../../../../services/local-storage/local-storage.service';
import {SubpanelStoreFactory} from '../../../../containers/subpanel/store/subpanel/subpanel.store.factory';
import {AuthService} from '../../../../services/auth/auth.service';
import {MessageService} from '../../../../services/message/message.service';
import {Record} from '../../../../common/record/record.model';
import {ViewMode} from '../../../../common/views/view.model';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {RecordConvertService} from "../../../../services/record/record-convert.service";
import {RecordDuplicateService} from "../../../../services/record/record-duplicate.service";
import {FieldActionsAdapterFactory} from "../../../../components/field-layout/adapters/field.actions.adapter.factory";

@Injectable()
export class CreateViewStore extends RecordViewStore {

    protected duplicateService: RecordDuplicateService;

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
        protected auth: AuthService,
        protected recordStoreFactory: RecordStoreFactory,
        protected preferences: UserPreferenceStore,
        protected recordConvertService: RecordConvertService,
        protected fieldActionAdaptorFactory: FieldActionsAdapterFactory,
    ) {
        super(
            recordFetchGQL,
            recordSaveGQL,
            appStateStore,
            languageStore,
            navigationStore,
            moduleNavigation,
            metadataStore,
            localStorage,
            message,
            subpanelFactory,
            recordManager,
            statisticsBatch,
            recordStoreFactory,
            preferences,
            recordConvertService,
            fieldActionAdaptorFactory
        );

        this.duplicateService = inject(RecordDuplicateService);
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
        this.parseParams(params);
        this.showTopWidget = false;
        this.showBottomWidgets = false;
        this.showHeaderWidgets = false;
        this.showSidebarWidgets = false;
        this.showSubpanels = false;
        this.calculateCurrentSection(params);
        this.calculateShowWidgets();

        const isDuplicate = this.params.isDuplicate ?? false;
        const isConvert = this.params.isConvert ?? false;
        const isOriginalId = this.params.originalId ?? false;
        const convertModule = this.params.convertModule ?? '';

        if (!isDuplicate && !isConvert && !isOriginalId) {
            this.initRecord(params);
        }

        if (isConvert && isOriginalId && convertModule) {
            return this.duplicateOnModule();
        }

        return this.load();
    }

    save(): Observable<Record> {
        this.appStateStore.updateLoading(`${this.internalState.module}-record-save-new`, true, false);

        return this.recordStore.save().pipe(
            catchError(() => {
                this.message.addDangerMessageByKey('LBL_ERROR_SAVING');
                return of({} as Record);
            }),
            finalize(() => {
                this.setMode('detail' as ViewMode);
                this.appStateStore.updateLoading(`${this.internalState.module}-record-save-new`, false, false);
            })
        );
    }

    /**
     * Init record using params
     *
     * @param {object} params queryParams
     */
    public initRecord(params: Params = {}): void {
        const user = this.auth.getCurrentUser();
        const blankRecord = {
            id: '',
            type: '',
            module: this.internalState.module,
            /* eslint-disable camelcase,@typescript-eslint/camelcase */
            attributes: {
                assigned_user_id: user.id,
                assigned_user_name: {
                    id: user.id,
                },
                relate_to: params?.return_relationship,
                relate_id: params?.parent_id
            }
            /* eslint-enable camelcase,@typescript-eslint/camelcase */
        } as Record;

        if (this.preferences.getUserPreference('use_real_names') !== 'off') {
            blankRecord.attributes.assigned_user_name.full_name = (user?.firstName ?? '') + (user?.lastName ?? '');
        } else {
            blankRecord.attributes.assigned_user_name.user_name = user.userName;
        }

        this.recordManager.injectParamFields(params, blankRecord, this.getVardefs());

        this.recordStore.init(blankRecord, true);
    }

    /**
     * Load / reload record using current pagination and criteria
     *
     * @returns {object} Observable<RecordViewState>
     */
    public load(): Observable<Record> {
        if ((this.params.isDuplicate ?? false) && (this.params.originalId ?? false)) {
            this.updateState({
                ...this.internalState,
                loading: true
            });
            return this.recordStore.retrieveRecord(
                this.internalState.module,
                this.params.originalId,
                false
            ).pipe(
                tap((data: Record) => {

                    const vardefs = this.getVardefs();

                    const parsedRecord = this.duplicateService.duplicateParse(data, vardefs);

                    this.recordManager.injectParamFields(this.params, parsedRecord, vardefs);

                    this.recordStore.setRecord(data);
                    this.updateState({
                        ...this.internalState,
                        module: data.module,
                        loading: false
                    });
                })
            );
        }
        return of(this.recordStore.getBaseRecord()).pipe(shareReplay());
    }

    public duplicateOnModule(): Observable<Record> {

        this.initRecord();
        const newRecord = this.getBaseRecord();
        const moduleMetadata = this.metadataStore.getModuleMeta(this.params.convertModule);
        const viewDefinitions$ = this.recordConvertService.getViewFieldsObservable(moduleMetadata);
        const prevModuleRecordStore = this.recordStoreFactory.create(viewDefinitions$);
        const prevRecord = prevModuleRecordStore.retrieveRecord(
            this.params.convertModule,
            this.params.originalId,
            false
        ).pipe(
            map((record: Record) => {
                return this.recordConvertService.duplicateOnModule(record, newRecord, this.getVardefs(), moduleMetadata)
            }),
            tap((record: Record) => {
                this.recordStore.setRecord(record);
                this.updateState({
                    ...this.internalState,
                    module: this.internalState.module,
                    loading: false
                })
            })
        );

        return prevRecord;
    }

    /**
     * Calculate if widgets are to display
     */
    protected calculateShowWidgets(): void {

        this.showBottomWidgets = true;
        this.showHeaderWidgets = true;

        if (this.getMode() === 'create') {
            this.showSidebarWidgets = false;
            this.widgets = true;
            return;
        }

        const recordViewMeta = this.getRecordViewMetadata();
        const sidebarWidgetsConfig = recordViewMeta?.sidebarWidgets || [];
        const hasSidebarConfig = sidebarWidgetsConfig?.length > 0;

        const userPreference = this.loadPreference(this.getModuleName(), 'show-sidebar-widgets');
        this.showSidebarWidgets = userPreference ?? hasSidebarConfig;
        this.widgets = hasSidebarConfig;
    }
}
