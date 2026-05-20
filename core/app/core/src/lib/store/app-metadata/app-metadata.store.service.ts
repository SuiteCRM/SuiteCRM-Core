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
import {catchError, map, shareReplay, tap} from 'rxjs/operators';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {deepClone, emptyObject} from '../../common/utils/object-utils';
import {StateStore} from '../state';
import {LanguageStore, LanguageStrings} from '../language/language.store';
import {SystemConfigStore} from '../system-config/system-config.store';
import {ThemeImagesStore} from '../theme-images/theme-images.store';
import {UserPreferenceMap, UserPreferenceStore} from '../user-preference/user-preference.store';
import {NavigationStore} from '../navigation/navigation.store';
import {Metadata, MetadataMap, MetadataStore} from '../metadata/metadata.store.service';
import {ApolloQueryResult} from '@apollo/client/core';
import {AdminMetadataStore} from '../admin-metadata/admin-metadata.store';
import {AdminMetadata} from '../admin-metadata/admin-metadata.model';
import {GlobalRecentlyViewedStore} from "../global-recently-viewed/global-recently-viewed.store";

export interface AppMetadata {
    loaded?: boolean;
    moduleMetadataLoaded?: boolean;
    systemConfig?: any;
    userPreferences?: any;
    language?: any;
    themeImages?: any;
    navigation?: any;
    moduleMetadata?: MetadataMap;
    minimalModuleMetadata?: MetadataMap;
    adminMetadata?: AdminMetadata;
    globalRecentlyViewed?: any;
}

export interface AppMetadataFlags {
    systemConfig?: boolean;
    userPreferences?: boolean;
    appStrings?: boolean;
    appListStrings?: boolean;
    modStrings?: boolean;
    themeImages?: boolean;
    navigation?: boolean;
    moduleMetadata?: boolean;
    minimalModuleMetadata?: boolean;
    adminMetadata?: boolean;
    globalRecentlyViewed?: boolean;
}


const initialState: AppMetadataFlags = {
    systemConfig: false,
    userPreferences: false,
    appStrings: false,
    appListStrings: false,
    modStrings: false,
    themeImages: false,
    navigation: false,
    moduleMetadata: false,
    adminMetadata: false,
    globalRecentlyViewed: false
};

let internalState: AppMetadataFlags = deepClone(initialState);

let cache$: Observable<AppMetadataFlags> = null;

@Injectable({
    providedIn: 'root',
})
export class AppMetadataStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    metadata$: Observable<AppMetadataFlags>;

    protected store = new BehaviorSubject<AppMetadataFlags>(internalState);
    protected state$ = this.store.asObservable();
    protected resourceName = 'appMetadata';
    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
        ]
    };
    protected types = [
        'systemConfig',
        'userPreferences',
        'language',
        'themeImages',
        'navigation',
        'moduleMetadata',
        'adminMetadata',
        'globalRecentlyViewed'
    ];

    constructor(
        protected recordGQL: EntityGQL,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected themeImages: ThemeImagesStore,
        protected config: SystemConfigStore,
        protected preferences: UserPreferenceStore,
        protected navigation: NavigationStore,
        protected adminMetadataStore:AdminMetadataStore,
        protected globalRecentlyViewedStore: GlobalRecentlyViewedStore
    ) {
        this.metadata$ = this.state$;
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

    public get(): AppMetadataFlags {
        return internalState;
    }

    /**
     * Initial AppMetadata load if not cached and update state.
     *
     * @returns any data
     */
    public load(module: string = 'login', types: string[] = [], useCache = true): Observable<AppMetadataFlags> {

        const notLoaded = this.getNotLoadedTypes();
        useCache = useCache && notLoaded.length < 1;

        if (!types || types.length < 1) {
            types = notLoaded;
            types.push('minimalModuleMetadata');
        }

        return this.getMetadata(module, types, useCache).pipe(
            tap((metadata: AppMetadataFlags) => {
                this.updateState(metadata);
            })
        );
    }

    /**
     * Initial AppMetadata load if not cached and update state.
     *
     * @returns any data
     */
    public loadModuleMetadata(module: string = 'login', useCache = true): Observable<AppMetadataFlags> {
        let isLoaded = internalState?.moduleMetadata ?? false;

        useCache = useCache && isLoaded;

        return this.getMetadata(module, ['moduleMetadata'], useCache).pipe(
            tap((metadata: AppMetadataFlags) => {
                this.updateState(metadata);
            })
        );
    }

    /**
     * Get metadata cached Observable or call the backend
     *
     * @returns {object} Observable<AppMetadataFlags>
     */
    public getMetadata(module: string = 'app', types: string[] = [], useCache = true): Observable<AppMetadataFlags> {

        if (!types || types.length < 1) {
            types = [...this.types];
        }

        if (cache$ == null || useCache !== true) {
            cache$ = this.fetch(module, types).pipe(
                shareReplay(1)
            );
        }

        return cache$;
    }

    protected getNotLoadedTypes(): string[] {
        const types = [];

        if (!this.isNavigationLoaded()) {
            types.push('navigation')
        }

        if (!this.arePreferencesLoaded()) {
            types.push('userPreferences')
        }

        if (!this.areSystemConfigsLoaded()) {
            types.push('systemConfig')
        }

        if (!this.areAllLanguageStringsLoaded()) {
            types.push('language')
        }

        if (!this.areThemeImagesLoaded()) {
            types.push('themeImages')
        }

        if (!this.isAdminMetadataLoaded()) {
            types.push('adminMetadata');
        }

        if (!this.isGlobalRecentlyViewedLoaded()) {
            types.push('globalRecentlyViewed');
        }

        return types;
    }

    protected areAllLanguageStringsLoaded(): boolean {
        return this.language.areAllCached();
    }

    protected arePreferencesLoaded(): boolean {
        return this.preferences.isCached();
    }

    protected areSystemConfigsLoaded(): boolean {
        return this.config.isCached();
    }

    protected areThemeImagesLoaded(): boolean {
        return this.themeImages.isCached();
    }

    protected isNavigationLoaded(): boolean {
        return this.navigation.isCached();
    }

    protected isAdminMetadataLoaded(): boolean {
        return !!(internalState.adminMetadata ?? false);
    }

    protected isGlobalRecentlyViewedLoaded(): boolean {
        return !!(internalState.globalRecentlyViewed ?? false);
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: AppMetadataFlags): void {
        this.store.next(internalState = state);
    }

    /**
     * Fetch the Metadata from the backend
     *
     * @returns {object} Observable<{}>
     */
    protected fetch(module: string, types: string[] = []): Observable<AppMetadataFlags> {
        const fieldsToRetrieve = {
            fields: [
                ...this.fieldsMetadata.fields,
                ...types
            ]
        };

        return this.recordGQL.fetch(this.resourceName, `/api/app-metadata/${module}`, fieldsToRetrieve)
            .pipe(
                catchError(() => {
                    return of({} as ApolloQueryResult<any>);
                }),
                map(({data}) => {

                    const result = data?.appMetadata as AppMetadata;
                    const appMetadata = {...internalState} as AppMetadataFlags;
                    if (emptyObject(result)) {
                        return appMetadata;
                    }

                    this.setConfig(appMetadata, result);
                    this.setPreferences(appMetadata, result);
                    this.setThemeImages(appMetadata, result);
                    this.setNavigation(appMetadata, result);
                    this.setLanguages(appMetadata, result);
                    this.setModuleMetadata(appMetadata, result);
                    this.setAdminMetadata(appMetadata,result);
                    this.setGlobalRecentlyViewed(appMetadata,result);
                    return appMetadata;
                })
            );
    }

    protected setModuleMetadata(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        let moduleMetadata = appMetadata?.moduleMetadata ?? {};
        let metaKey = 'moduleMetadata';

        if (emptyObject(moduleMetadata)) {
            moduleMetadata = appMetadata?.minimalModuleMetadata ?? {};
            metaKey = 'minimalModuleMetadata';
        }

        if (!emptyObject(moduleMetadata)) {

            currentMetadata[metaKey] = true;

            Object.keys(moduleMetadata).forEach(module => {
                const meta = moduleMetadata[module] ?? {} as Metadata;
                if (!emptyObject(meta)) {

                    const parsedMeta = this.metadata.mapMetadata(module, moduleMetadata[module]);

                    if (this.metadata.getModule() !== module) {
                        this.metadata.setModuleMetadata(module, parsedMeta);
                    } else if (!this.metadata.isCached(module)) {
                        this.metadata.set(module, parsedMeta);
                    }
                }
            });
        }
    }

    protected setLanguages(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const lang = appMetadata?.language ?? {};
        if (!emptyObject(lang)) {
            const languageStrings = {} as LanguageStrings;
            languageStrings.languageKey = lang.id ?? '';
            languageStrings.appStrings = lang?.appStrings?.items ?? {};
            languageStrings.appListStrings = lang?.appListStrings?.items ?? {};
            languageStrings.modStrings = lang?.modStrings?.items ?? {};

            currentMetadata.appStrings = !emptyObject(languageStrings.appStrings);
            currentMetadata.appListStrings = !emptyObject(languageStrings.appListStrings);
            currentMetadata.modStrings = !emptyObject(languageStrings.modStrings);

            this.language.set(languageStrings.languageKey, languageStrings);
        }
    }

    protected setNavigation(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const navigation = appMetadata?.navigation ?? {};
        if (!emptyObject(navigation)) {
            currentMetadata.navigation = true;
            this.navigation.set(navigation);
        }
    }

    protected setThemeImages(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const themeImages = appMetadata?.themeImages ?? {};
        const images = themeImages?.items ?? {};
        if (!emptyObject(themeImages) && !emptyObject(images)) {
            currentMetadata.themeImages = true;
            const theme = themeImages.id;
            this.themeImages.set(theme, images);
        }
    }

    protected setPreferences(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const prefs = appMetadata?.userPreferences ?? {};
        if (!emptyObject(prefs)) {
            currentMetadata.userPreferences = true;
            const userPreferences = this.mapPreferences(prefs);
            this.preferences.set(userPreferences);
        }
    }

    protected setConfig(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const configs = appMetadata?.systemConfig ?? {};
        if (!emptyObject(configs)) {
            currentMetadata.systemConfig = true;
            this.config.set(configs);
        }
    }

    protected setAdminMetadata(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const adminMetadata = appMetadata?.adminMetadata ?? {};
        if (!emptyObject(adminMetadata)) {
            currentMetadata.adminMetadata = true;
            this.adminMetadataStore.set(adminMetadata);
        }
    }

    protected setGlobalRecentlyViewed(currentMetadata: AppMetadataFlags, appMetadata: AppMetadata) {
        const globalRecentlyViewed = appMetadata?.globalRecentlyViewed ?? [];
        if (globalRecentlyViewed.length) {
            currentMetadata.globalRecentlyViewed = true;
            this.globalRecentlyViewedStore.set(globalRecentlyViewed);
        } else if(appMetadata?.globalRecentlyViewed) {
                this.globalRecentlyViewedStore.set(globalRecentlyViewed);
        }
    }

    protected mapPreferences(preferences: any): UserPreferenceMap {
        const userPreferences: UserPreferenceMap = {};
        Object.keys(preferences).forEach((prefKey) => {

            if (!preferences[prefKey].items) {
                return;
            }

            Object.keys(preferences[prefKey].items).forEach(key => {
                userPreferences[key] = preferences[prefKey].items[key];
            });
        });
        return userPreferences;
    }
}
