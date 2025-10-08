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
import {BehaviorSubject, Observable, of} from 'rxjs';
import {distinctUntilChanged, map, shareReplay, tap} from 'rxjs/operators';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {Action} from '../../common/actions/action.model';
import {ColumnDefinition, ListViewMeta, MassUpdateMeta, SearchMeta} from '../../common/metadata/list.metadata.model';
import {FieldDefinitionMap} from '../../common/record/field.model';
import {deepClone} from '../../common/utils/object-utils';
import {RecentlyViewed} from '../../common/record/recently-viewed.model';
import {Favorite} from '../../common/record/favorites.model';
import {FieldActions, Panel, TabDefinitions} from '../../common/metadata/metadata.model';
import {SubPanelMeta} from '../../common/metadata/subpanel.metadata.model';
import {WidgetMetadata} from '../../common/metadata/widget.metadata';
import {StateStore} from '../state';
import {AppStateStore} from '../app-state/app-state.store';
import {ObjectMap} from "../../common/types/object-map";

export interface SummaryTemplates {
    [key: string]: string;
}

export interface BackButtonConfig {
    display?: boolean;
    navigate?: ParentNavigation;

    [key: string]: any;
}

export interface ParentNavigation {
    parentIdParam?: string;
    parentModuleParam?: string;
    parentModule?: string;
    parentId?: string;
}

export interface HeaderMetadata {
    backButton?: BackButtonConfig;

    [key: string]: any;
}

export interface RecordViewMetadata {
    topWidget?: WidgetMetadata;
    sidebarWidgets?: WidgetMetadata[];
    bottomWidgets?: WidgetMetadata[];
    headerWidgets?: WidgetMetadata[];
    actions?: Action[];
    templateMeta?: RecordTemplateMetadata;
    panels?: Panel[];
    summaryTemplates?: SummaryTemplates;
    vardefs?: FieldDefinitionMap;
    metadata?: ObjectMap;
    header?: HeaderMetadata;
    sections?: RecordViewSectionMetadataMap;
}

export interface RecordViewSectionMetadataMap {
    [key: string]: RecordViewSectionMetadata;
}

export interface RecordViewSectionMetadata {
    order?: number;
    tabAction?: Action;
    topWidget?: WidgetMetadata;
    sidebarWidgets?: WidgetMetadata[];
    bottomWidgets?: WidgetMetadata[];
    headerWidgets?: WidgetMetadata[];
    templateMeta?: RecordTemplateMetadata;
    panels?: Panel[];
    subpanels?: any[];
    metadata?: ObjectMap;
}

export interface RecordModalMetadata {
    actions?: Action[];
    templateMeta?: RecordTemplateMetadata;
    panels?: Panel[];
    vardefs?: FieldDefinitionMap;
    metadata?: ObjectMap;
}

export interface RecordTemplateMetadata {
    maxColumns: number;
    useTabs: boolean;
    tabDefs: TabDefinitions;
    colClasses?: string[];
}

export interface Metadata {
    module?: string;
    detailView?: any;
    editView?: any;
    listView?: ListViewMeta;
    search?: SearchMeta;
    recordView?: RecordViewMetadata;
    recordModal?: RecordModalMetadata;
    subPanel?: SubPanelMeta;
    massUpdate?: MassUpdateMeta;
    recentlyViewed?: RecentlyViewed[];
    favorites?: Favorite[];
    fieldActions?: FieldActions;
    extra?: ObjectMap;
}

export interface MetadataMap {
    [key: string]: Metadata;
}

const initialState: Metadata = {
    module: '',
    detailView: {},
    editView: {},
    listView: {} as ListViewMeta,
    search: {} as SearchMeta,
    recordView: {} as RecordViewMetadata,
    recordModal: {} as RecordModalMetadata,
    subPanel: {} as SubPanelMeta,
    massUpdate: {} as MassUpdateMeta,
    recentlyViewed: [],
    favorites: [],
    fieldActions: {} as FieldActions
};

const initialModuleMetadataState: MetadataMap = {};


let internalState: Metadata = deepClone(initialState);
let allModulesState: MetadataMap = deepClone(initialModuleMetadataState);


export interface MetadataCache {
    [key: string]: Observable<Metadata>;
}

const initialCache: MetadataCache = {} as MetadataCache;

let cache: MetadataCache = deepClone(initialCache);

@Injectable({
    providedIn: 'root',
})
export class MetadataStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    listViewColumns$: Observable<ColumnDefinition[]>;
    listViewLineActions$: Observable<Action[]>;
    listViewTableActions$: Observable<Action[]>;
    listMetadata$: Observable<ListViewMeta>;
    searchMetadata$: Observable<SearchMeta>;
    recordViewMetadata$: Observable<RecordViewMetadata>;
    recordModalMetadata$: Observable<RecordModalMetadata>;
    fieldActions$: Observable<any>;
    metadata$: Observable<Metadata>;
    allModuleMetadata$: Observable<MetadataMap>;
    subPanelMetadata$: Observable<SubPanelMeta>;

    public typeKeys = {
        listView: 'listView',
        search: 'search',
        recordView: 'recordView',
        subPanel: 'subPanel',
        massUpdate: 'massUpdate',
        recentlyViewed: 'recentlyViewed',
        favorites: 'favorites'
    };

    protected store = new BehaviorSubject<Metadata>(internalState);
    protected state$ = this.store.asObservable();
    protected allModuleStore = new BehaviorSubject<MetadataMap>(allModulesState);
    protected allModulesState$ = this.allModuleStore.asObservable();
    protected resourceName = 'moduleMetadata';
    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
        ]
    };
    protected types = [
        'listView',
        'search',
        'recordView',
        'subPanel',
        'massUpdate',
        'recentlyViewed',
        'favorites'
    ];
    protected baseTypes = [
        'listView',
        'search',
        'recordView',
        'subPanel',
        'favorites'
    ];

    constructor(protected recordGQL: EntityGQL, protected appState: AppStateStore) {
        this.listViewColumns$ = this.state$.pipe(map(state => state.listView.fields), distinctUntilChanged());
        this.listViewLineActions$ = this.state$.pipe(map(state => state.listView.lineActions), distinctUntilChanged());
        this.listViewTableActions$ = this.state$.pipe(map(state => state.listView.tableActions), distinctUntilChanged());
        this.listMetadata$ = this.state$.pipe(map(state => state.listView), distinctUntilChanged());
        this.searchMetadata$ = this.state$.pipe(map(state => state.search), distinctUntilChanged());
        this.recordViewMetadata$ = this.state$.pipe(map(state => state.recordView), distinctUntilChanged());
        this.recordModalMetadata$ = this.state$.pipe(map(state => state.recordModal), distinctUntilChanged());
        this.fieldActions$ = this.state$.pipe(map(state => state.fieldActions), distinctUntilChanged());
        this.subPanelMetadata$ = this.state$.pipe(map(state => state.subPanel), distinctUntilChanged());
        this.metadata$ = this.state$;
        this.allModuleMetadata$ = this.allModulesState$;
    }

    /**
     * Clear state
     */
    public clear(): void {
        cache = deepClone(initialCache);
        allModulesState = deepClone(initialModuleMetadataState);
        this.updateState('', deepClone(initialState));
    }

    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Get all metadata types
     *
     * @returns {string[]} metadata types
     */
    public getMetadataTypes(): string[] {
        return this.types;
    }

    public getModule(): string {
        return internalState.module;
    }

    public get(): Metadata {
        return internalState;
    }

    public getModuleMeta(module: string): Metadata {
        const meta = allModulesState[module] ?? {};
        return {...meta};
    }

    public setModuleMetadata(module: string, metadata: Metadata): void {
        cache[module] = of(metadata).pipe(shareReplay(1));
        this.updateAllModulesState(module, metadata);
    }

    /**
     * Initial ListViewMeta load if not cached and update state.
     *
     * @param {string} moduleID to fetch
     * @param {string[]} types to fetch
     * @param useCache
     * @returns any data
     */
    public reloadModuleMetadata(moduleID: string, types: string[], useCache: boolean = true): any {

        if (!types) {
            types = this.getMetadataTypes();
        }

        return this.getMetadata(moduleID, types, useCache).pipe(
            tap((metadata: Metadata) => {
                this.updateAllModulesState(moduleID, metadata);
            })
        );
    }

    /**
     * Initial ListViewMeta load if not cached and update state.
     *
     * @param {string} moduleID to fetch
     * @param {string[]} types to fetch
     * @param useCache
     * @returns any data
     */
    public load(moduleID: string, types: string[], useCache: boolean = true): Observable<any> {

        if (!types) {
            types = this.getMetadataTypes();
        }

        return this.getMetadata(moduleID, types, useCache).pipe(
            tap((metadata: Metadata) => {
                this.updateState(moduleID, metadata);
            })
        );
    }

    /**
     * Check if loaded
     */
    public isCached(module: string): boolean {
        return (cache[module] ?? null) !== null;
    }

    /**
     * Get empty Metadata
     */
    public getEmpty(): Metadata {
        return deepClone(initialState);
    }

    /**
     * Set pre-loaded navigation and cache
     */
    public set(module: string, metadata: Metadata): void {
        cache[module] = of(metadata).pipe(shareReplay(1));
        this.updateState(module, metadata);
    }

    /**
     * Get ListViewMeta cached Observable or call the backend
     *
     * @param {string} module to fetch
     * @param {string[]} types to retrieve
     * @param useCache
     * @returns {object} Observable<any>
     */
    public getMetadata(module: string, types: string[] = null, useCache: boolean = true): Observable<Metadata> {

        if (cache[module] == null || useCache === false) {
            cache[module] = this.fetchMetadata(module, types).pipe(
                shareReplay(1)
            );
        }

        return cache[module];
    }

    /**
     * Internal API
     */

    public mapMetadata(module: string, data: any): Metadata {
        const moduleMetadata: Metadata = allModulesState[module] ?? {};
        const metadata: Metadata = {...moduleMetadata};
        this.parseListViewMetadata(data, metadata);
        this.parseSearchMetadata(data, metadata);
        this.parseRecordViewMetadata(data, metadata);
        this.parseRecordModalMetadata(data, metadata);
        this.parseSubPanelMetadata(data, metadata);
        this.parseMassUpdateMetadata(data, metadata);
        this.parseRecentlyViewedMetadata(data, metadata);
        this.parseFavoritesMetadata(data, metadata);
        this.parseFieldViewMetada(data, metadata);

        if (data.extra) {
            metadata.extra = data.extra;
        }

        return metadata;
    }

    /**
     * Update the state
     *
     * @param {string} module
     * @param {object} state to set
     */
    protected updateState(module: string, state: Metadata): void {

        this.updateAllModulesState(module, state);

        this.store.next(internalState = {...state, module});
    }

    /**
     * Update the state
     *
     * @param {string} module
     * @param {object} state to set
     */
    protected updateAllModulesState(module: string, state: Metadata): void {

        if (module !== '') {
            const newState = {
                ...allModulesState
            };
            newState[module] = {...state};

            this.allModuleStore.next(allModulesState = newState);
        }

    }

    /**
     * Fetch the Metadata from the backend
     *
     * @param {string} module to fetch
     * @param {string[]} types to retrieve
     * @returns {object} Observable<{}>
     */
    protected fetchMetadata(module: string, types: string[]): Observable<Metadata> {

        const fieldsToRetrieve = {
            fields: [
                ...this.fieldsMetadata.fields,
                ...(types ?? this.baseTypes)
            ]
        };

        return this.recordGQL.fetch(this.resourceName, `/api/module-metadata/${module}`, fieldsToRetrieve)
            .pipe(
                map(({data}) => {
                    return this.mapMetadata(module, data.moduleMetadata);
                })
            );
    }

    protected parseListViewMetadata(data, metadata: Metadata): void {

        if (!data || !data.listView) {
            return;
        }

        const listViewMeta: ListViewMeta = {
            fields: [],
            bulkActions: {},
            lineActions: [],
            tableActions: [],
            chartTypes: {},
            filters: []
        };

        if (data.listView.columns) {
            data.listView.columns.forEach((field: ColumnDefinition) => {
                listViewMeta.fields.push(
                    field
                );
            });
        }

        const entries = {
            bulkActions: 'bulkActions',
            lineActions: 'lineActions',
            tableActions: 'tableActions',
            sidebarWidgets: 'sidebarWidgets',
            availableFilters: 'filters',
            paginationType: 'paginationType'
        };

        this.addDefinedMeta(listViewMeta, data.listView, entries);

        metadata.listView = listViewMeta;
    }

    protected parseFieldViewMetada(data, metadata: Metadata): void {

        if (!data || !data.recordView || !data.recordView.panels) {
            return;
        }

        const fieldActions: any = {
            recordView: {}
        };

        data.recordView.panels.forEach(panel => {
            if (panel.rows) {
                panel.rows.forEach(row => {
                    if (row.cols) {
                        row.cols.forEach(col => {
                            if (col.fieldActions && col.fieldActions.actions) {
                                Object.values(col.fieldActions.actions).forEach(action => {
                                    action['fieldName'] = col.name;
                                    const viewFieldActions = fieldActions['recordView'][col.name] ?? [];
                                    viewFieldActions.push(action);
                                    fieldActions['recordView'][col.name] = viewFieldActions;
                                });
                            }
                        });
                    }
                })
            }
        })

        metadata.fieldActions = fieldActions;

    }

    protected parseSearchMetadata(data, metadata: Metadata): void {
        if (data && data.search) {
            metadata.search = data.search;
        }
    }

    protected parseSubPanelMetadata(data, metadata: Metadata): void {
        if (data && data.subPanel) {
            metadata.subPanel = data.subPanel;
        }
    }

    protected parseMassUpdateMetadata(data, metadata: Metadata): void {
        if (data && data.massUpdate) {
            metadata.massUpdate = data.massUpdate;
        }
    }

    protected parseRecordViewMetadata(data, metadata: Metadata): void {
        if (!data || !data.recordView) {
            return;
        }

        const recordViewMeta: RecordViewMetadata = {
            actions: [] as Action[],
            templateMeta: {} as RecordTemplateMetadata,
            panels: []
        };

        const receivedMeta = data.recordView;
        const entries = {
            templateMeta: 'templateMeta',
            actions: 'actions',
            panels: 'panels',
            topWidget: 'topWidget',
            sidebarWidgets: 'sidebarWidgets',
            bottomWidgets: 'bottomWidgets',
            headerWidgets: 'headerWidgets',
            summaryTemplates: 'summaryTemplates',
            vardefs: 'vardefs',
            metadata: 'metadata',
            header: 'header',
            sections: 'sections'
        };

        this.addDefinedMeta(recordViewMeta, receivedMeta, entries);

        metadata.recordView = recordViewMeta;
    }

    protected parseRecordModalMetadata(data, metadata: Metadata): void {
        if (!data || !data.recordModal) {
            return;
        }

        const recordModalMeta: RecordModalMetadata = {
            actions: [] as Action[],
            templateMeta: {} as RecordTemplateMetadata,
            panels: []
        };

        const receivedMeta = data.recordModal;
        const entries = {
            templateMeta: 'templateMeta',
            actions: 'actions',
            panels: 'panels',
            vardefs: 'vardefs',
            metadata: 'metadata'
        };

        this.addDefinedMeta(recordModalMeta, receivedMeta, entries);

        metadata.recordModal = recordModalMeta;
    }

    protected parseRecentlyViewedMetadata(data, metadata: Metadata): void {
        if (data && data.recentlyViewed) {
            metadata.recentlyViewed = data.recentlyViewed;
        }
    }

    protected parseFavoritesMetadata(data, metadata: Metadata): void {
        if (data && data.favorites) {
            metadata.favorites = data.favorites;
        }
    }

    protected addDefinedMeta(
        metadata: { [key: string]: any },
        received: { [key: string]: any },
        keyMap: { [key: string]: string }
    ): void {
        Object.keys(keyMap).forEach(dataKey => {
            const metadataKey = keyMap[dataKey];
            if (received[dataKey]) {
                metadata[metadataKey] = received[dataKey];
            }
        });
    }
}
