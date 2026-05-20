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
import {CollectionGQL} from '../../services/api/graphql-api/api.collection.get';
import {deepClone} from '../../common/utils/object-utils';
import {StateStore} from '../state';

export interface SystemConfig {
    id: string;
    _id: string;
    value: string;
    items: { [key: string]: any };
}

export interface SystemConfigMap {
    [key: string]: SystemConfig;
}

export interface SystemConfigs {
    configs: SystemConfigMap;
    loading: boolean;
}

const initialState: SystemConfigs = {
    configs: {},
    loading: false
};

let internalState: SystemConfigs = deepClone(initialState);

let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class SystemConfigStore implements StateStore {

    configs$: Observable<SystemConfigMap>;
    loading$: Observable<boolean>;
    protected store = new BehaviorSubject<SystemConfigs>(internalState);
    protected state$ = this.store.asObservable();
    protected resourceName = 'systemConfigs';
    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
            'value',
            'items'
        ]
    };


    constructor(private collectionGQL: CollectionGQL) {
        this.configs$ = this.state$.pipe(map(state => state.configs), distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading));
    }

    /**
     * Public Api
     */

    /**
     * Get system config value by key
     *
     * @param {string} configKey of the config
     * @returns {{}|string} config value
     */
    public getConfigValue(configKey: string): any {
        if (!internalState.configs || !internalState.configs[configKey]) {
            return null;
        }

        if (internalState.configs[configKey].value !== null) {
            return internalState.configs[configKey].value;
        }

        return internalState.configs[configKey].items;
    }

    /**
     * Get ui config value by key
     *
     * @param {string} configKey of the ui config
     * @returns {{}|string} config value
     */
    public getUi(configKey: string): any {
        const ui = this.getConfigValue('ui') ?? {};
        return ui[configKey] ?? null;
    }

    public getHomePage(): string {

        let defaultModule = 'home';
        const defaultModuleConfig = this.getConfigValue('default_module');

        if (defaultModuleConfig) {
            defaultModule = defaultModuleConfig;
        }

        return defaultModule;
    }

    /**
     * Clear state
     */
    public clear(): void {
        cache$ = null;
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Initial SystemConfigs load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @returns {Observable<{}>} observable
     */
    public load(): Observable<any> {

        this.updateState({...internalState, loading: true});

        return this.getSystemConfigs().pipe(
            tap(configs => {
                this.updateState({...internalState, configs, loading: false});
            })
        );
    }

    /**
     * Check if loaded
     */
    public isCached(): boolean {
        return cache$ !== null;
    }

    /**
     * Set pre-loaded configs and cache
     */
    public set(configs: SystemConfigMap): void {
        cache$ = of(configs).pipe(shareReplay(1));
        this.updateState({...internalState, configs, loading: false});
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {{}} state new state
     */
    protected updateState(state: SystemConfigs): void {
        this.store.next(internalState = state);
    }

    /**
     * Get SystemConfigs cached Observable or call the backend
     *
     * @returns {Observable<{}>} observable
     */
    protected getSystemConfigs(): Observable<any> {

        if (cache$ == null) {
            cache$ = this.fetch().pipe(
                shareReplay(1)
            );
        }

        return cache$;
    }

    /**
     * Fetch the App strings from the backend
     *
     * @returns {Observable<{}>} observable
     */
    protected fetch(): Observable<any> {

        return this.collectionGQL
            .fetchAll(this.resourceName, this.fieldsMetadata).pipe(map(({data}) => {
                const configs: SystemConfigMap = {};

                if (data.systemConfigs && data.systemConfigs.edges) {
                    data.systemConfigs.edges.forEach((edge) => {
                        // eslint-disable-next-line no-underscore-dangle
                        configs[edge.node._id] = edge.node;
                    });
                }

                return configs;
            }));
    }

}
