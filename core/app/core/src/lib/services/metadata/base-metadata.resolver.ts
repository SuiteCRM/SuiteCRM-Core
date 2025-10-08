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
import {ActivatedRouteSnapshot} from '@angular/router';
import {concatAll, map, take, tap, toArray} from 'rxjs/operators';
import {forkJoin, Observable} from 'rxjs';

import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {LanguageStore} from '../../store/language/language.store';
import {NavigationStore} from '../../store/navigation/navigation.store';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {MessageService} from '../message/message.service';
import {ModuleNameMapper} from '../navigation/module-name-mapper/module-name-mapper.service';
import {AppMetadataStore} from '../../store/app-metadata/app-metadata.store.service';
import {AuthService} from '../auth/auth.service';
import {RecordModalService} from "../modals/record-modal.service";


@Injectable({providedIn: 'root'})
export class BaseMetadataResolver  {

    constructor(
        protected systemConfigStore: SystemConfigStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected userPreferenceStore: UserPreferenceStore,
        protected themeImagesStore: ThemeImagesStore,
        protected appState: AppStateStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected messageService: MessageService,
        protected appMetadata: AppMetadataStore,
        protected recordModalService: RecordModalService,
        protected auth: AuthService
    ) {
    }

    resolve(route: ActivatedRouteSnapshot): Observable<any> {
        const module = this.calculateActiveModule(route);
        return this.appMetadata.load(module).pipe(
            tap(() => {
                if (this.auth.isLoggedIn()) {
                    setTimeout(() => {
                        this.appMetadata.loadModuleMetadata(module).pipe(take(1)).subscribe(() => {
                            if (!this.recordModalService.initialized) {
                                this.recordModalService.init();
                            }
                        });
                    }, 0)
                }
            })
        );
    }

    /**
     * @param route
     */
    protected sequentialLoad(route: ActivatedRouteSnapshot) {
        const streams$: { [key: string]: Observable<any> } = {};

        if (this.isToLoadNavigation(route)) {
            streams$.navigation = this.navigationStore.load();
        }

        if (this.isToLoadConfigs(route)) {

            let configs$ = this.systemConfigStore.load();

            if (this.isToLoadLanguageStrings(route)) {
                const langStrings = this.getLanguagesToLoad(route);

                configs$ = configs$.pipe(
                    map(
                        (configs: any) => {

                            let language = configs.default_language.value;
                            const storedLanguage = this.languageStore.getCurrentLanguage();

                            if (storedLanguage) {
                                language = storedLanguage;
                            }

                            return this.languageStore.load(language, langStrings);
                        },
                    ),
                    concatAll(),
                    toArray()
                );
            }

            streams$.configs = configs$;
        }

        if (this.isToLoadUserPreferences(route)) {

            streams$.preferences = this.userPreferenceStore.load();
        }


        const parallelStream$ = forkJoin(streams$);

        return parallelStream$.pipe(
            map((data: any) => {

                let theme = null;

                if (this.systemConfigStore.getConfigValue('default_theme')) {
                    theme = this.systemConfigStore.getConfigValue('default_theme');
                }

                if (this.userPreferenceStore.getUserPreference('user_theme')) {
                    theme = this.userPreferenceStore.getUserPreference('user_theme');
                }

                if (this.themeImagesStore.getTheme()) {
                    theme = this.themeImagesStore.getTheme();
                }

                if (theme !== null) {
                    return this.themeImagesStore.load(theme);
                }

                return data;
            }),
            concatAll(),
            toArray(),
            tap(() => this.appState.setLoaded(true))
        );
    }

    /**
     * Get Languages to Load
     *
     * @param {{}} route activated
     * @returns {string[]} languages
     */
    protected getLanguagesToLoad(route: ActivatedRouteSnapshot): string[] {
        let langStrings: string[] = this.languageStore.getAvailableStringsTypes();

        if (this.isToLoadNavigation(route)) {
            return langStrings;
        }

        if (!route.data || !route.data.load) {
            return [];
        }

        if (Array.isArray(route.data.load.languageStrings)) {
            langStrings = route.data.load.languageStrings;
        }

        return langStrings;
    }

    /**
     * Should load language strings. True if navigation is to load
     *
     * @param {{}} route activated
     * @returns {boolean} is to load
     */
    protected isToLoadLanguageStrings(route: ActivatedRouteSnapshot): boolean {

        if (this.isToLoadNavigation(route)) {
            return true;
        }

        if (!route.data || !route.data.load) {
            return false;
        }

        return Array.isArray(route.data.load.languageStrings) || route.data.load.languageStrings === true;
    }

    /**
     * Should load navigation. If not set defaults to true
     *
     * @param {{}} route activated
     * @returns {boolean} is to load
     */
    protected isToLoadConfigs(route: ActivatedRouteSnapshot): boolean {
        if (!route.data || !route.data.load) {
            return true;
        }

        return route.data.load.configs !== false;
    }

    /**
     * Should load navigation, If not set defaults to true
     *
     * @param {{}} route activated
     * @returns {boolean} is to load
     */
    protected isToLoadNavigation(route: ActivatedRouteSnapshot): boolean {
        if (!route.data || !route.data.load) {
            return true;
        }

        return route.data.load.navigation !== false;
    }

    /**
     * Should load user preferences. If not set defaults to true
     *
     * @param {{}} route activated
     * @returns {boolean} is to load
     */
    protected isToLoadUserPreferences(route: ActivatedRouteSnapshot): boolean {
        if (!route.data || !route.data.load) {
            return true;
        }

        return route.data.load.preferences !== false;
    }

    protected addMetadataLoadErrorMessage(): void {
        let message = this.languageStore.getAppString('LBL_ERROR_FETCHING_METADATA');

        if (!message) {
            message = 'Error occurred while fetching metadata';
        }

        this.messageService.addDangerMessage(message);
    }

    /**
     * Calculate the active module
     *
     * @param {{}} route active
     * @returns {string} active module
     */
    protected calculateActiveModule(route: ActivatedRouteSnapshot): string {

        let module = route.params.module;

        if (!module) {
            module = route.data.module;
        }

        const rootPath = route?.url[0]?.path ?? '';
        if (!module && rootPath !== '') {
            module = rootPath;
        }

        const parentModuleParam = this.getParentModuleMap()[module] || '';
        const parentModule = route.queryParams[parentModuleParam] || '';

        if (parentModule) {
            module = this.moduleNameMapper.toFrontend(parentModule);
        }
        return module;
    }

    /**
     * Get Parent Module Map
     *
     * @returns {{}} parent module map
     */
    protected getParentModuleMap(): { [key: string]: string } {
        return {
            'merge-records': 'return_module',
            import: 'import_module'
        };
    }
}
