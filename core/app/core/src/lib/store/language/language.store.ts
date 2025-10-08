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

import {BehaviorSubject, combineLatest, combineLatestWith, forkJoin, Observable, of} from 'rxjs';
import {distinctUntilChanged, first, map, shareReplay, take, tap} from 'rxjs/operators';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {StringMap} from '../../common/types/string-map';
import {deepClone, emptyObject} from '../../common/utils/object-utils';
import {StateStore} from '../state';
import {LocalStorageService} from '../../services/local-storage/local-storage.service';
import {Process, ProcessService} from '../../services/process/process.service';
import {SystemConfigStore} from '../system-config/system-config.store';
import {isString} from 'lodash-es';

export interface LanguageStringMap {
    [key: string]: string;
}

export interface LanguageListStringMap {
    [key: string]: string | LanguageStringMap;
}

export interface LoadedLanguageStringMap {
    [key: string]: LanguageStringMap;
}

export interface LanguageState {
    appStrings: LanguageStringMap;
    appListStrings: LanguageListStringMap;
    modStrings: LanguageListStringMap;
    languageKey: string;
    loaded?: LoadedLanguageStringMap;
    hasChanged: boolean;
}

export interface LanguageStrings {
    appStrings: LanguageStringMap;
    appListStrings: LanguageListStringMap;
    modStrings: LanguageListStringMap;
    languageKey: string;
}

export interface LanguageCache {
    [key: string]: {
        [key: string]: Observable<any>;
    };
}

const initialState: LanguageState = {
    appStrings: {},
    appListStrings: {},
    modStrings: {},
    languageKey: 'en_us',
    loaded: {},
    hasChanged: false
};

let internalState: LanguageState = deepClone(initialState);

const initialCache: LanguageCache = {
    appStrings: {},
    appListStrings: {},
    modStrings: {},
};

let loadedLanguages = {};
let cache: LanguageCache = deepClone(initialCache);

@Injectable({
    providedIn: 'root',
})
export class LanguageStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    appStrings$: Observable<LanguageStringMap>;
    appListStrings$: Observable<LanguageListStringMap>;
    modStrings$: Observable<LanguageListStringMap>;
    languageKey$: Observable<string>;

    /**
     * ViewModel that resolves once all the data is ready (or updated)...
     */
    vm$: Observable<LanguageStrings>;

    protected store = new BehaviorSubject<LanguageState>(internalState);
    protected state$ = this.store.asObservable();

    protected config = {
        appStrings: {
            fetch: 'fetchAppStrings',
            resourceName: 'appStrings',
            metadata: {
                fields: [
                    'id',
                    '_id',
                    'items'
                ]
            }
        },
        appListStrings: {
            fetch: 'fetchAppListStrings',
            resourceName: 'appListStrings',
            metadata: {
                fields: [
                    'id',
                    '_id',
                    'items'
                ]
            }
        },
        modStrings: {
            fetch: 'fetchModStrings',
            resourceName: 'modStrings',
            metadata: {
                fields: [
                    'id',
                    '_id',
                    'items'
                ]
            }
        },
    };

    constructor(
        protected recordGQL: EntityGQL,
        protected localStorage: LocalStorageService,
        protected processService: ProcessService,
        protected configs: SystemConfigStore
    ) {

        this.appStrings$ = this.state$.pipe(map(state => state.appStrings), distinctUntilChanged());
        this.appListStrings$ = this.state$.pipe(map(state => state.appListStrings), distinctUntilChanged());
        this.modStrings$ = this.state$.pipe(map(state => state.modStrings), distinctUntilChanged());
        this.languageKey$ = this.state$.pipe(map(state => state.languageKey), distinctUntilChanged());

        this.vm$ = this.appStrings$
            .pipe(
                combineLatestWith(
                    this.appListStrings$,
                    this.modStrings$,
                    this.languageKey$
                ),
                map((
                    [
                        appStrings,
                        appListStrings,
                        modStrings,
                        languageKey
                    ]) => ({appStrings, appListStrings, modStrings, languageKey})
                )
            );
    }

    /**
     * Public Api
     */

    /**
     * Clear state
     */
    public clear(): void {
        loadedLanguages = {};
        cache = deepClone(initialCache);
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
        const keysToClear = ['modStrings', 'appListStrings'];

        keysToClear.forEach(type => {
            if (loadedLanguages && loadedLanguages[type]) {
                delete loadedLanguages[type];
            }
        });

        cache.modStrings = {};
        cache.appListStrings = {};
    }

    /**
     * Update the language strings toe the given language
     *
     * @param {string} languageKey language key
     * @param {boolean} reload
     */
    public changeLanguage(languageKey: string, reload = false): Observable<any> {
        const types = [];

        Object.keys(loadedLanguages).forEach(type => loadedLanguages[type] && types.push(type));

        internalState.hasChanged = true;

        return this.load(languageKey, types, reload).pipe(
            tap(() => {
                this.localStorage.set('selected_language', languageKey, true);

            })
        );
    }

    /**
     * Get All languageStrings label by key
     *
     * @returns {object} LanguageStrings
     */
    public getLanguageStrings(): LanguageStrings {
        if (!internalState) {
            return null;
        }

        return {
            appStrings: internalState.appStrings,
            appListStrings: internalState.appListStrings,
            modStrings: internalState.modStrings,
            languageKey: internalState.languageKey
        };
    }

    /**
     * Get AppStrings label by key
     *
     * @param {string} labelKey to fetch
     * @returns {string} label
     */
    public getAppString(labelKey: string): string {
        if (!internalState.appStrings || !internalState.appStrings[labelKey]) {
            return null;
        }
        return internalState.appStrings[labelKey];
    }

    /**
     * Get AppListStrings label by key
     *
     * @param {string} labelKey to fetch
     * @returns {string|{}} app strings
     */
    public getAppListString(labelKey: string): string | LanguageStringMap {

        if (!internalState.appListStrings || !internalState.appListStrings[labelKey]) {
            return null;
        }

        return internalState.appListStrings[labelKey];
    }

    /**
     * Get ModStrings label by key
     *
     * @param {string} labelKey to fetch
     * @returns {string|{}} mod strings
     */
    public getModString(labelKey: string): string | LanguageStringMap {

        if (!internalState.modStrings || !internalState.modStrings[labelKey]) {
            return null;
        }

        return internalState.modStrings[labelKey];
    }

    /**
     * Get field label
     *
     * @param {string} labelKey to fetch
     * @param {string} module to use
     * @param {object} lang to use
     * @returns {string} label
     */
    public getFieldLabel(labelKey: string, module: string = null, lang: LanguageStrings = null): string {
        let languages = lang;

        if (!lang) {
            languages = this.getLanguageStrings();
        }

        if (!languages || !languages.modStrings || !labelKey) {
            return '';
        }

        let label = '';

        if (module) {
            label = languages.modStrings[module] && languages.modStrings[module][labelKey];
        }

        if (!label) {
            label = languages.appStrings && languages.appStrings[labelKey];
        }

        return label || '';
    }

    /**
     * Get list label
     *
     * @param {string} listKey to fetch
     * @param {string} labelKey to fetch
     * @returns {string} label
     */
    public getListLabel(listKey: string, labelKey: string): string {

        if (!listKey || !labelKey) {
            return '';
        }

        const listStrings = this.getAppListString(listKey);

        if (!listStrings) {
            return '';
        }

        return listStrings[labelKey] || '';
    }

    /**
     * Get all available string types
     *
     * @returns {string[]} string types
     */
    public getAvailableStringsTypes(): string[] {
        return Object.keys(this.config);
    }

    /**
     * Returns whether the language has changed manually
     *
     * @returns {boolean} has changed
     */
    public hasLanguageChanged(): boolean {
        return internalState.hasChanged;
    }

    /**
     * Returns the currently active language
     *
     * @returns {string} current language key
     */
    public getCurrentLanguage(): string {

        const storedLanguage = this.localStorage.get('selected_language');

        if (storedLanguage) {
            return storedLanguage;
        }

        return internalState.languageKey ?? 'en_us';
    }

    /**
     * Returns the active language
     *
     * @returns {string} active language key
     */
    public getActiveLanguage(): string {

        return internalState.languageKey ?? '';
    }

    /**
     * Returns the selected language
     *
     * @returns {string} selected language key
     */
    public getSelectedLanguage(): string {
        return this.localStorage.get('selected_language') ?? '';
    }

    /**
     * Check if language is enabled
     * @param currentLanguage
     */
    public isLanguageEnabled(currentLanguage: string): boolean {
        if (!currentLanguage) {
            return false;
        }
        const languages = this.configs.getConfigValue('languages') ?? {};
        const disabled = this.getDisabledLanguages();
        const languageKeys = Object.keys(languages);

        if (!languageKeys.length) {
            return false;
        }

        return languageKeys.includes(currentLanguage) && !disabled.includes(currentLanguage);
    }

    /**
     * Get disabled languages
     */
    public getDisabledLanguages(): string[] {
        const disabledConfig = this.configs.getConfigValue('disabled_languages') ?? '';
        if (!isString(disabledConfig) || disabledConfig === '') {
            return [];
        }
        return disabledConfig.replace(' ', '').split(',');
    }

    /**
     * Get enabled languages
     */
    public getEnabledLanguages(): StringMap {
        const languages = this.configs.getConfigValue('languages') ?? {};
        const disabled = this.getDisabledLanguages();

        const enabled = {};
        const enabledKeys = Object.keys(languages).filter(value => !disabled.includes(value));
        enabledKeys.forEach(key => {
            enabled[key] = languages[key];
        });

        return enabled;
    }

    /**
     * Get fist language in list
     * @private
     */
    public getFirstLanguage(): string {
        const languages = this.configs.getConfigValue('languages') ?? {};
        const languageKeys = Object.keys(languages);
        return languageKeys.sort()[0] ?? '';
    }


    /**
     * Initial Language Strings Load for given language and types if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} languageKey to load
     * @param {string[]} types to load
     * @param {boolean} reload
     * @returns {object} Observable
     */
    public load(languageKey: string, types: string[], reload = false): Observable<any> {

        const streams$: Array<Observable<any>> = [];

        types.forEach(type => streams$.push(this.getStrings(languageKey, type, reload)));

        return forkJoin(streams$).pipe(
            first(),
            tap(result => {
                const stateUpdate = {...internalState, languageKey};

                types.forEach((type, index) => {
                    stateUpdate[type] = result[index];
                    loadedLanguages[type] = true;
                });


                this.updateState(stateUpdate);
            })
        );
    }

    /**
     * Check if loaded
     */
    public areAllCached(): boolean {
        let isCached = true;
        isCached = isCached && !emptyObject(cache?.appStrings ?? {});
        isCached = isCached && !emptyObject(cache?.appListStrings ?? {});
        isCached = isCached && !emptyObject(cache?.modStrings ?? {});

        return isCached;
    }

    /**
     * Set pre-loaded strings and cache
     */
    public set(languageKey: string, languageStrings: LanguageStrings): void {

        const stateUpdate = {...internalState, languageKey};

        if (languageStrings.appStrings && !emptyObject(languageStrings.appStrings)) {
            cache['appStrings'][languageKey] = of(languageStrings.appStrings).pipe(shareReplay(1));
            stateUpdate['appStrings'] = languageStrings.appStrings;
            loadedLanguages['appStrings'] = true;
        }

        if (languageStrings.appListStrings && !emptyObject(languageStrings.appListStrings)) {
            cache['appListStrings'][languageKey] = of(languageStrings.appListStrings).pipe(shareReplay(1));
            stateUpdate['appListStrings'] = languageStrings.appListStrings;
            loadedLanguages['appListStrings'] = true;
        }

        if (languageStrings.modStrings && !emptyObject(languageStrings.modStrings)) {
            cache['modStrings'][languageKey] = of(languageStrings.modStrings).pipe(shareReplay(1));
            stateUpdate['modStrings'] = languageStrings.modStrings;
            loadedLanguages['modStrings'] = true;
        }

        this.updateState(stateUpdate);
    }

    /**
     * Set session language
     */
    public setSessionLanguage(): Observable<Process> {

        const processType = 'set-session-language';

        const options = {
            language: internalState.languageKey
        };

        return this.processService.submit(processType, options).pipe(take(1));
    }

    /**
     * Set session language
     */
    public setUserLanguage(): Observable<Process> {

        const processType = 'set-user-language';

        const options = {
            language: internalState.languageKey
        };

        return this.processService.submit(processType, options).pipe(take(1));
    }


    /**
     * Internal API
     */


    /**
     * Update internal state cache and emit from store...
     *
     * @param {{}} state to set
     */
    protected updateState(state: LanguageState): void {
        this.store.next(internalState = state);
    }

    /**
     * Get given $type of strings Observable from cache  or call the backend
     *
     * @param {string} language to load
     * @param {string} type load
     * @param {boolean} reload
     * @returns {object} Observable<any>
     */
    protected getStrings(language: string, type: string, reload = false): Observable<{}> {

        const stringsCache = cache[type];
        const fetchMethod = this.config[type].fetch;

        if (stringsCache[language] && reload === false) {
            return stringsCache[language];
        }

        stringsCache[language] = this[fetchMethod](language).pipe(
            shareReplay(1),
        );

        return stringsCache[language];
    }

    /**
     * Fetch the App strings from the backend
     *
     * @param {string} language to fetch
     * @returns {{}} Observable<{}>
     */
    protected fetchAppStrings(language: string): Observable<{}> {
        const resourceName = this.config.appStrings.resourceName;
        const fields = this.config.appStrings.metadata;
        return this.recordGQL.fetch(resourceName, `/api/app-strings/${language}`, fields)
            .pipe(
                map(({data}) => {
                    let items = {};

                    if (data.appStrings) {
                        items = data.appStrings.items;
                    }

                    return items;
                })
            );
    }

    /**
     * Fetch the App list strings from the backend
     *
     * @param {string} language to fetch
     * @returns {{}} Observable<{}>
     */
    protected fetchAppListStrings(language: string): Observable<{}> {
        const resourceName = this.config.appListStrings.resourceName;
        const fields = this.config.appListStrings.metadata;

        return this.recordGQL.fetch(resourceName, `/api/app-list-strings/${language}`, fields)
            .pipe(
                map(({data}) => {
                    let items = {};

                    if (data.appListStrings) {
                        items = data.appListStrings.items;
                    }

                    return items;
                })
            );
    }

    /**
     * Fetch the Mod strings from the backend
     *
     * @param {string} language to fetch
     * @returns {{}} Observable<{}>
     */
    protected fetchModStrings(language: string): Observable<{}> {
        const resourceName = this.config.modStrings.resourceName;
        const fields = this.config.modStrings.metadata;
        return this.recordGQL.fetch(resourceName, `/api/mod-strings/${language}`, fields)
            .pipe(
                map(({data}) => {
                    let items = {};

                    if (data.modStrings) {
                        items = data.modStrings.items;
                    }

                    return items;
                })
            );
    }
}
