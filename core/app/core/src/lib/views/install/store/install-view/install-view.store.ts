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
import {BehaviorSubject, combineLatestWith, Observable, of, Subscription} from 'rxjs';
import {isVoid} from '../../../../common/utils/value-utils';
import {deepClone} from '../../../../common/utils/object-utils';
import {Action} from '../../../../common/actions/action.model';
import {Field, FieldDefinition, FieldMetadata, Option} from '../../../../common/record/field.model';
import {Record} from '../../../../common/record/record.model';
import {Panel, PanelCell, PanelRow, TabDefinition, TabDefinitions} from '../../../../common/metadata/metadata.model';
import {ViewContext} from '../../../../common/views/view.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {ViewMode} from '../../../../common/views/view.model';
import {distinctUntilChanged, map, shareReplay} from 'rxjs/operators';
import {InstallViewMetadata, InstallViewModel, InstallViewState} from './install-view.store.model';
import {StateStore} from '../../../../store/state';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';
import {RecordTemplateMetadata} from '../../../../store/metadata/metadata.store.service';
import {MessageService} from '../../../../services/message/message.service';
import {RecordManager} from '../../../../services/record/record.manager';
import {RecordStore} from '../../../../store/record/record.store';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {Params} from '@angular/router';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';
import {LanguageStore} from '../../../../store/language/language.store';
import { trimEnd } from 'lodash-es';

const initialState: InstallViewState = {
    loading: false,
    mode: 'detail',
    params: {
        returnModule: '',
        returnId: '',
        returnAction: ''
    }
};

@Injectable()
export class InstallViewStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    record$: Observable<Record>;
    stagingRecord$: Observable<Record>;
    loading$: Observable<boolean>;
    mode$: Observable<ViewMode>;
    viewContext$: Observable<ViewContext>;

    /**
     * View-model that resolves once all the data is ready (or updated).
     */
    vm$: Observable<InstallViewModel>;
    vm: InstallViewModel;
    recordStore: RecordStore;
    url: string;

    /** Internal Properties */
    protected cache$: Observable<any> = null;
    protected internalState: InstallViewState = deepClone(initialState);
    protected store = new BehaviorSubject<InstallViewState>(this.internalState);
    protected state$ = this.store.asObservable();
    protected subs: Subscription[] = [];

    constructor(
        protected recordFetchGQL: RecordFetchGQL,
        protected recordSaveGQL: RecordSaveGQL,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected recordStoreFactory: RecordStoreFactory,
        protected language: LanguageStore
    ) {

        this.recordStore = recordStoreFactory.create(this.getViewFieldsObservable());

        this.record$ = this.recordStore.state$.pipe(distinctUntilChanged());
        this.stagingRecord$ = this.recordStore.staging$.pipe(distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading));
        this.mode$ = this.state$.pipe(map(state => state.mode));

        this.vm$ = this.record$.pipe(
            combineLatestWith(this.loading$),
            map(([record, loading]: [Record, boolean]) => {
                this.vm = {record, loading} as InstallViewModel;
                return this.vm;
            }));

        this.viewContext$ = this.record$.pipe(map(() => {
            return this.getViewContext();
        }));
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

    getViewContext(): ViewContext {
        return {
            record: this.getBaseRecord()
        };
    }

    getActions(): Observable<Action[]> {
        return of([]);
    }

    /**
     * Initial install view
     *
     * @param {string} mode to use
     * @param {object} params to set
     */
    public init(mode = 'edit' as ViewMode, params: Params = {}): void {
        this.setMode(mode);
        this.recordStore.init(
                {
                id: '',
                module: 'install',
                attributes: {}
            } as Record,
            true
        );
    }

    /**
     * Clear observable cache
     */
    public clear(): void {
        this.cache$ = null;
        this.updateState(deepClone(initialState));
    }

    /**
     * Clear
     */
    clearAuthBased(): void {
        this.clear();
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
    }

    getLicenseText(): string {
        return this.language.getFieldLabel('SUITE8_LICENSE_CONTENT');
    }

    getMetadata(): InstallViewMetadata {
        this.url = window.location.origin + window.location.pathname;
        this.url = trimEnd(this.url, '/');
        return {
            actions: [],
            templateMeta: {
                maxColumns: 2,
                useTabs: true,
                tabDefs: {
                    LBL_CONFIG: {
                        newTab: true,
                        panelDefault: 'expanded'
                    } as TabDefinition
                } as TabDefinitions
            } as RecordTemplateMetadata,
            panels: [
                {
                    key: 'LBL_CONFIG',
                    display$: of(true).pipe(shareReplay(1)),
                    rows: [
                        {
                            cols: [
                                {
                                    name: 'site_host',
                                    label: 'LBL_SITECFG_URL',
                                    type: 'varchar',
                                    fieldDefinition: {
                                        "name": "site_host",
                                        "vname": "LBL_SITECFG_URL",
                                        "type": "varchar",
                                        "required": true,
                                        "default": this.url?.toString(),
                                        "defaultValueModes": ["create", "edit"]
                                    } as FieldDefinition,
                                } as PanelCell,
                                {
                                    name: 'demoData',
                                    label: 'LBL_DBCONF_DEMO_DATA',
                                    type: 'enum',
                                    fieldDefinition: {
                                        name: "demoData",
                                        vname: "LBL_DBCONF_DEMO_DATA",
                                        type: "enum",
                                        options: "__no_options__",
                                        required: true,
                                        "default": 'no',
                                        "defaultValueModes": ["create", "edit"],
                                        metadata: {
                                            extraOptions: [
                                                {
                                                    value: "yes",
                                                    labelKey: "LBL_YES",
                                                } as Option,
                                                {
                                                    value: "no",
                                                    labelKey: "LBL_NO",
                                                } as Option,
                                            ] as Option[]
                                        } as FieldMetadata
                                    } as FieldDefinition,
                                } as PanelCell,
                            ]
                        },
                        {
                            cols: [
                                {
                                    name: 'db_config',
                                    label: 'LBL_DBCONF_TITLE',
                                    type: 'grouped-field',
                                    fieldDefinition: {
                                        name: "db_config",
                                        vname: "LBL_DBCONF_TITLE",
                                        type: "grouped-field",
                                        layout: [
                                            "db_username",
                                            "db_password",
                                            "db_host",
                                            "db_name",
                                            "db_port"
                                        ],
                                        display: "vertical",
                                        groupFields: {
                                            "db_username": {
                                                "name": "db_username",
                                                "type": "varchar",
                                                "vname": "LBL_DBCONF_SUITE_DB_USER",
                                                "labelKey": "LBL_DBCONF_SUITE_DB_USER",
                                                "label": "LBL_DBCONF_SUITE_DB_USER",
                                                "showLabel": ["*"],
                                                "required": true,
                                            },
                                            "db_password": {
                                                "name": "db_password",
                                                "type": "password",
                                                "vname": "LBL_DBCONF_DB_PASSWORD",
                                                "labelKey": "LBL_DBCONF_DB_PASSWORD",
                                                "showLabel": ["*"],
                                                "required": true,
                                            },
                                            "db_host": {
                                                "name": "db_host",
                                                "type": "varchar",
                                                "vname": "LBL_DBCONF_HOST_NAME",
                                                "labelKey": "LBL_DBCONF_HOST_NAME",
                                                "showLabel": ["*"],
                                                "required": true,
                                            },
                                            "db_name": {
                                                "name": "db_name",
                                                "type": "varchar",
                                                "vname": "LBL_DBCONF_DB_NAME",
                                                "labelKey": "LBL_DBCONF_DB_NAME",
                                                "showLabel": ["*"],
                                                "required": true,
                                            },
                                            "db_port": {
                                                "name": "db_port",
                                                "type": "varchar",
                                                "vname": "LBL_DBCONF_DB_PORT",
                                                "labelKey": "LBL_DBCONF_DB_PORT",
                                                "showLabel": ["*"],
                                                "required": false,
                                                "default":'3306',
                                                "defaultValueModes": ["create", "edit"]
                                            }
                                        },
                                        showLabel: {
                                            edit: ['*']
                                        }
                                    } as FieldDefinition,
                                } as PanelCell,
                                {
                                    name: 'admin_config',
                                    label: 'LBL_SITECFG_TITLE',
                                    type: 'grouped-field',
                                    fieldDefinition: {
                                        name: "admin_config",
                                        vname: "LBL_SITECFG_TITLE",
                                        type: "grouped-field",
                                        layout: [
                                            "site_username",
                                            "site_password",
                                        ],
                                        display: "vertical",
                                        groupFields: {
                                            "site_username": {
                                                "name": "site_username",
                                                "type": "varchar",
                                                "vname": "LBL_SITECFG_ADMIN_Name",
                                                "labelKey": "LBL_SITECFG_ADMIN_Name",
                                                "showLabel": ["edit"],
                                                "required": true,
                                                "default": "admin",
                                                "defaultValueModes": ["create", "edit"]
                                            },
                                            "site_password": {
                                                "name": "site_password",
                                                "type": "password",
                                                "vname": "LBL_SITECFG_ADMIN_PASS",
                                                "labelKey": "LBL_SITECFG_ADMIN_PASS",
                                                "showLabel": ["edit"],
                                                "required": true,
                                            },
                                        },
                                        showLabel: {
                                            edit: ['*']
                                        }
                                    } as FieldDefinition,
                                } as PanelCell
                            ] as PanelCell[]
                        } as PanelRow
                    ] as PanelRow[]
                } as Panel
            ] as Panel[],
        } as InstallViewMetadata;
    }

    getMetadata$(): Observable<InstallViewMetadata> {
        return of(this.getMetadata());
    }

    getModuleName(): string {
        return 'install';
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
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: InstallViewState): void {
        this.store.next(this.internalState = state);
    }

    getIgnoreSystemChecksField(): Field {
        return this.recordStore.getStaging().fields['sys_check_option'];
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    protected getViewFieldsObservable(): Observable<ViewFieldDefinition[]> {
        return this.getMetadata$().pipe(map((meta: InstallViewMetadata) => {
            const fields: ViewFieldDefinition[] = [];
            meta.panels.forEach(panel => {
                panel.rows.forEach(row => {
                    row.cols.forEach(col => {
                        fields.push(col);
                    });
                });
            });

            fields.push(
                {
                    "name": "sys_check_option",
                    "type": "boolean",
                    fieldDefinition: {
                        "name": "sys_check_option",
                        "type": "boolean",
                        "vname": "LBL_SYS_CHECK_WARNING",
                        "labelKey": "LBL_SYS_CHECK_WARNING",
                        "showLabel": ["edit"],
                        "required": false,
                        "value": 'false',
                        "default": 'false',
                        "defaultValueModes": ["create", "edit"]
                    }
                } as ViewFieldDefinition
            );

            return fields;
        }));
    }
}
