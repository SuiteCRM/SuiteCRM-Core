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
import {concatMap, map, tap} from 'rxjs/operators';
import {ModuleNameMapper} from '../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {AppStateStore} from '../../../store/app-state/app-state.store';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';
import {NavigationStore} from '../../../store/navigation/navigation.store';
import {UserPreferenceStore} from '../../../store/user-preference/user-preference.store';
import {BaseMetadataResolver} from '../../../services/metadata/base-metadata.resolver';
import {RouteConverter} from '../../../services/navigation/route-converter/route-converter.service';
import {LanguageStore} from '../../../store/language/language.store';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';
import {MessageService} from '../../../services/message/message.service';
import {AppMetadataStore} from '../../../store/app-metadata/app-metadata.store.service';
import {AuthService} from '../../../services/auth/auth.service';
import {RecentlyViewedService} from '../../../services/navigation/recently-viewed/recently-viewed.service';
import {forkJoin} from 'rxjs';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {RecordModalService} from "../../../services/modals/record-modal.service";

@Injectable({providedIn: 'root'})
export class ClassicViewResolver extends BaseMetadataResolver {

    constructor(
        protected systemConfigStore: SystemConfigStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected userPreferenceStore: UserPreferenceStore,
        protected themeImagesStore: ThemeImagesStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected routeConverter: RouteConverter,
        protected messageService: MessageService,
        protected appStateStore: AppStateStore,
        protected appMetadata: AppMetadataStore,
        protected auth: AuthService,
        protected recentlyViewed: RecentlyViewedService,
        protected recordModalService: RecordModalService,
        protected metadataStore: MetadataStore,
    ) {
        super(
            systemConfigStore,
            languageStore,
            navigationStore,
            userPreferenceStore,
            themeImagesStore,
            appStateStore,
            moduleNameMapper,
            messageService,
            appMetadata,
            recordModalService,
            auth
        );
    }

    resolve(route: ActivatedRouteSnapshot): any {
        const module = this.calculateActiveModule(route);
        return super.resolve(route).pipe(
            concatMap(() => {
                return forkJoin([
                    this.metadataStore.load(module, this.metadataStore.getMetadataTypes()),
                ]);
            }),
            map(() => this.routeConverter.toLegacy(route.params, route.queryParams)),
            tap(
                () => {
                    if (route.params.module) {
                        const module = this.calculateActiveModule(route);

                        this.appStateStore.setModule(module);
                    }
                    const info = this.routeConverter.parseRouteURL(route.url);
                    const action = info.action ?? 'index';
                    this.appStateStore.setView(action);

                    setTimeout(() => {
                        this.recentlyViewed.onNavigationAdd(this.appStateStore.getModule(), route);
                    }, 800);

                },
                () => {
                    this.addMetadataLoadErrorMessage();
                }),
        );
    }
}
