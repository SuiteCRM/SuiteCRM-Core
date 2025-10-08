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
import {StateStore} from '../state';
import {AppState, AppStateStore} from '../app-state/app-state.store';
import {map, tap} from 'rxjs/operators';
import {combineLatestWith, Observable} from 'rxjs';
import {LanguageListStringMap, LanguageStore, LanguageStringMap, LanguageStrings} from '../language/language.store';
import {NavbarModule, Navigation, NavigationStore} from '../navigation/navigation.store';
import {ModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service';
import {Metadata, MetadataStore} from '../metadata/metadata.store.service';
import {SearchMeta} from '../../common/metadata/list.metadata.model';

export interface AppData {
    appState: AppState;
    module: NavbarModule;
    language: LanguageStrings;
    navigation: Navigation;
}

@Injectable()
export class ViewStore implements StateStore {

    appState$: Observable<AppState>;
    module$: Observable<NavbarModule>;
    language$: Observable<LanguageStrings>;
    navigation$: Observable<Navigation>;
    appData$: Observable<AppData>;
    metadata$: Observable<Metadata>;

    appData: AppData;
    metadata: Metadata;

    constructor(
        protected appStateStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected moduleNavigation: ModuleNavigation,
        protected metadataStore: MetadataStore
    ) {
        this.appState$ = this.appStateStore.vm$;
        this.language$ = this.languageStore.vm$;
        this.navigation$ = this.navigationStore.vm$;
        this.module$ = this.appState$.pipe(
            combineLatestWith(this.navigation$),
            map(([appStateInfo, navigationInfo]: [AppState, Navigation]) =>
                this.moduleNavigation.getModuleInfo(appStateInfo.module, navigationInfo))
        );

        this.appData$ = this.appState$.pipe(
            combineLatestWith(this.module$, this.language$, this.navigation$),
            map(([appState, module, language, navigation]) => {
                this.appData = {appState, module, language, navigation} as AppData;
                return this.appData;
            })
        );

        this.metadata$ = metadataStore.metadata$.pipe(
            tap(metadata => {this.metadata = metadata;})
        );
    }

    clear(): void {
    }

    public clearAuthBased(): void {
        this.clear();
    }

    get appState(): AppState {
        if (!this.appData.appState) {
            return {};
        }
        return this.appData.appState;
    }

    get module(): NavbarModule {
        return this.appData.module;
    }

    get language(): LanguageStrings {
        if (!this.appData.language) {
            return {
                appStrings: {},
                appListStrings: {},
                modStrings: {},
                languageKey: ''
            };
        }
        return this.appData.language;
    }

    get appStrings(): LanguageStringMap {
        return this.language.appStrings;
    }

    get appListStrings(): LanguageListStringMap {
        return this.language.appListStrings;
    }

    get modStrings(): LanguageListStringMap {
        return this.language.modStrings;
    }

    get navigation(): Navigation {
        return this.appData.navigation;
    }

    get searchMeta(): SearchMeta {
        if (!this.metadata.search) {
            return {
                layout: {
                    basic: {},
                    advanced: {}
                }
            };
        }

        return this.metadata.search;
    }
}
