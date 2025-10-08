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
import {BaseMetadataResolver} from './base-metadata.resolver';
import {ModuleNameMapper} from '../navigation/module-name-mapper/module-name-mapper.service';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {LanguageStore} from '../../store/language/language.store';
import {NavigationStore} from '../../store/navigation/navigation.store';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {forkJoin} from 'rxjs';
import {MetadataStore} from '../../store/metadata/metadata.store.service';
import {MessageService} from '../message/message.service';
import {concatMap, map, tap} from 'rxjs/operators';
import {RouteConverter} from "../navigation/route-converter/route-converter.service";
import {AppMetadataStore} from '../../store/app-metadata/app-metadata.store.service';
import {AuthService} from '../auth/auth.service';
import {RecordModalService} from "../modals/record-modal.service";

@Injectable({providedIn: 'root'})
export class BaseModuleResolver extends BaseMetadataResolver {

    constructor(
        protected systemConfigStore: SystemConfigStore,
        protected languageStore: LanguageStore,
        protected navigationStore: NavigationStore,
        protected userPreferenceStore: UserPreferenceStore,
        protected themeImagesStore: ThemeImagesStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected appStateStore: AppStateStore,
        protected metadataStore: MetadataStore,
        protected messageService: MessageService,
        protected routeConverter: RouteConverter,
        protected appMetadata: AppMetadataStore,
        protected recordModalService: RecordModalService,
        protected auth: AuthService
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
        let routeModule = route.params.module;

        if (!routeModule) {
            routeModule = route.data.module;
        }
        return super.resolve(route).pipe(
            concatMap(
                () => forkJoin([
                    this.metadataStore.load(routeModule, this.metadataStore.getMetadataTypes()),
                    this.metadataStore.getMetadata('saved-search', ['recordView'])
                ]),
            ),
            map(value => {
                return {
                    metadata: value[0] ?? {},
                    savedSearchMeta: value[1] ?? {},
                }
            }),
            tap(
                () => {
                    if (routeModule) {
                        const module = this.calculateActiveModule(route);

                        this.appStateStore.setModule(module);
                    }
                    const info = this.routeConverter.parseRouteURL(route.url);
                    const action = info.action ?? 'index';
                    this.appStateStore.setView(action);
                },
                () => {
                    this.addMetadataLoadErrorMessage();
                }),
        );
    }
}
