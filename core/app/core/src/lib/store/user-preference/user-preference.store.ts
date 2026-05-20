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
import {BehaviorSubject, Observable, of, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, shareReplay, take, tap} from 'rxjs/operators';
import {CollectionGQL} from '../../services/api/graphql-api/api.collection.get';
import {deepClone} from '../../common/utils/object-utils';
import {StateStore} from '../state';
import {SystemConfigStore} from '../system-config/system-config.store';
import {ProcessService} from '../../services/process/process.service';
import {LocalStorageService} from '../../services/local-storage/local-storage.service';

export interface UserPreferenceMap {
    [key: string]: any;
}

export interface UserPreferences {
    userPreferences: UserPreferenceMap;
    loading: boolean;
}

const initialState: UserPreferences = {
    userPreferences: {},
    loading: false
};

let internalState: UserPreferences = deepClone(initialState);

let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class UserPreferenceStore implements StateStore {
    protected store = new BehaviorSubject<UserPreferences>(internalState);
    protected state$ = this.store.asObservable();
    protected saveBufferStore = new BehaviorSubject<boolean>(false);
    protected saveBuffer$: Observable<boolean>;
    protected subs: Subscription[] = [];
    protected resourceName = 'userPreferences';
    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
            'value',
            'items'
        ]
    };

    /**
     * Public long-lived observable streams
     */
    userPreferences$ = this.state$.pipe(map(state => state.userPreferences), distinctUntilChanged());
    loading$ = this.state$.pipe(map(state => state.loading));

    constructor(
        protected collectionGQL: CollectionGQL,
        protected config: SystemConfigStore,
        protected processService: ProcessService,
        protected localStorage: LocalStorageService,
    ) {
        const uiSettings = config.getConfigValue('ui') ?? {};
        const delay = uiSettings.user_preferences_save_delay ?? 2500;
        this.saveBuffer$ = this.saveBufferStore.asObservable().pipe(debounceTime(delay ?? 2500));
        this.subs.push(this.saveBuffer$.subscribe((value) => {
            if (!value) {
                return;
            }
            this.saveUiPreferences();
        }));
    }

    /**
     * Public Api
     */

    /**
     * Clear state
     */
    public clear(): void {
        cache$ = null;
        this.updateState(deepClone(initialState));
        this.subs.forEach(sub => sub.unsubscribe());
    }

    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Get user preferences value by key
     *
     * @param {string} key to retrieve
     * @returns any users preference
     */
    public getUserPreference(key: string): any {

        if (!internalState.userPreferences || !internalState.userPreferences[key]) {
            return null;
        }

        return internalState.userPreferences[key];
    }

    /**
     * Get ui user preferences value by key
     *
     * @param module
     * @param {string} key to retrieve
     * @returns any users preference
     */
    public getUi(module: string, key: string): any {

        const storageKey = module + '-' + key;
        const value = this.storageLoad(module, storageKey);

        if (value != null) {
            return value;
        }

        const ui = internalState?.userPreferences?.ui ?? {} as any;
        return ui[storageKey] ?? null;
    }

    /**
     * Set user preferences value by key
     *
     * @param {string} module name
     * @param {string} key to retrieve
     * @param value
     * @returns any users preference
     */
    public setUi(module: string, key: string, value: any): void {

        const storageKey = module + '-' + key;
        this.storageSave(module, storageKey, value);

        const ui = internalState?.userPreferences?.ui ?? {} as any;
        ui[storageKey] = value;

        internalState.userPreferences.ui = ui;

        this.saveBufferStore.next(true);
    }

    protected saveUiPreferences(): void {

        const processType = 'save-ui-user-preferences';

        const options = {
            preferences: internalState.userPreferences.ui
        };


        this.processService.submit(processType, options).pipe(take(1)).subscribe();
    }

    /**
     * Store the data in local storage
     *
     * @param {string} module to store in
     * @param {string} storageKey to store in
     * @param {} data to store
     */
    protected storageSave(module: string, storageKey: string, data: any): void {
        let storage = this.localStorage.get(storageKey);

        if (!storage) {
            storage = {};
        }

        storage[module] = data;

        this.localStorage.set(storageKey, storage);
    }

    /**
     * Store the key in local storage
     *
     * @param {string} module to load from
     * @param {string} storageKey from load from
     */
    protected storageLoad(module: string, storageKey: string): any {
        const storage = this.localStorage.get(storageKey);

        if (!storage || !storage[module]) {
            return null;
        }

        return storage[module];
    }


    /**
     * Initial UserPreferences load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @returns {object} Observable<any>
     */
    public load(): Observable<any> {
        this.updateState({...internalState, loading: true});

        return this.getUserPreferences().pipe(
            tap(userPreferences => {
                this.updateState({...internalState, userPreferences, loading: false});
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
     * Set pre-loaded preferences and cache
     */
    public set(userPreferences: UserPreferenceMap): void {
        cache$ = of(userPreferences).pipe(shareReplay(1));
        this.updateState({...internalState, userPreferences, loading: false});
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: UserPreferences): void {
        this.store.next(internalState = state);
    }

    /**
     * Get UserPreferences cached Observable or call the backend
     *
     * @returns {object} Observable<any>
     */
    protected getUserPreferences(): Observable<any> {

        if (cache$ == null) {
            cache$ = this.fetch().pipe(
                shareReplay(1)
            );
        }

        return cache$;
    }

    /**
     * Fetch the User Preferences from the backend
     *
     * @returns {object} Observable<any>
     */
    protected fetch(): Observable<any> {

        return this.collectionGQL
            .fetchAll(this.resourceName, this.fieldsMetadata).pipe(map(({data}) => {
                const userPreferences: UserPreferenceMap = {};

                if (data.userPreferences && data.userPreferences.edges) {
                    data.userPreferences.edges.forEach((edge) => {

                        if (!edge.node.items) {
                            return;
                        }

                        Object.keys(edge.node.items).forEach(key => {
                            userPreferences[key] = edge.node.items[key];
                        });
                    });
                }

                return userPreferences;
            }));
    }
}
