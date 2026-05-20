/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {RecentlyViewed} from '../../../common/record/recently-viewed.model';
import {deepClone} from '../../../common/utils/object-utils';
import {ViewMode} from '../../../common/views/view.model';
import {ProcessService} from '../../process/process.service';
import {take} from 'rxjs/operators';
import {ActivatedRouteSnapshot} from '@angular/router';
import {GlobalRecentlyViewedStore} from "../../../store/global-recently-viewed/global-recently-viewed.store";
import {ModuleNameMapper} from "../module-name-mapper/module-name-mapper.service";
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {AppStateStore} from "../../../store/app-state/app-state.store";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";

@Injectable({providedIn: 'root'})
export class RecentlyViewedService {

    constructor(
        protected metadata: MetadataStore,
        protected globalRecentlyViewedStore: GlobalRecentlyViewedStore,
        protected processService: ProcessService,
        protected moduleNameMapper: ModuleNameMapper,
        protected appMetadataStore: AppMetadataStore,
        protected systemConfigs: SystemConfigStore,
        protected appStateStore: AppStateStore
    ) {
    }

    /**
     * Public Api
     */

    /**
     * On navigation add
     * @param module
     * @param route
     */
    public onNavigationAdd(module: string, route: ActivatedRouteSnapshot) {

        let mode = 'detail' as ViewMode;
        const data = (route && route.data) || {};

        if (data.mode) {
            mode = data.mode;
        }

        const recordId = route?.params?.record ?? null;

        if (recordId && mode !== 'create') {
            const recentlyViewed = this.buildRecentlyViewed(module, recordId);
            this.addRecentlyViewed(module, recentlyViewed)
        }
    }

    /**
     * Build new recently viewed
     * @param module
     * @param id
     * @param view
     */
    public buildRecentlyViewed(module: string, id: string, view = 'detailview'): RecentlyViewed {
        module = this.moduleNameMapper.toLegacy(module);
        return deepClone({
            module: 'Tracker',
            type: 'Tracker',
            attributes: {
                module_name: module ?? '',
                item_id: id ?? '',
                action: view ?? '',
            },
        } as RecentlyViewed);
    }

    /**
     * Add recently viewed
     * @param module
     * @param viewed
     */
    public addRecentlyViewed(module: string, viewed: RecentlyViewed): void {
        this.saveRecentlyViewed(module, viewed);
    }

    /**
     * Save recently viewed to backend
     * @param module
     * @param viewed
     */
    protected saveRecentlyViewed(module: string, viewed: RecentlyViewed): void {

        const processType = 'add-recently-viewed';

        const options = {
            recentlyViewed: viewed
        };

        setTimeout(() => {
            this.processService.submit(processType, options).pipe(take(1)).subscribe(result => {

                const saved = {
                    id: viewed.id ?? '',
                    module: viewed.module ?? '',
                    attributes: {...(viewed.attributes ?? {})},
                };
                const tracker = result?.data?.tracker ?? null;
                if (tracker === null) {
                    return;
                }

                saved.attributes.item_summary = tracker.item_summary;
                const newItemId = saved?.attributes?.item_id ?? '';

                const metadata = this.metadata.getModuleMeta(module);

                const current = metadata?.recentlyViewed ?? null;
                if (current) {
                    let cleared = current.filter(item => ((item?.attributes?.item_id ?? '') !== newItemId));
                    cleared.unshift(saved);
                    metadata.recentlyViewed = cleared;
                }

                this.globalRecentlyViewedStore.addToState(saved);

                this.metadata.setModuleMetadata(module, metadata);
            });
        }, 500);
    }

    conditionalGlobalRefresh(view: string = ''): void {

        const reloadActions = this.systemConfigs.getUi('global_recently_viewed_reload_actions') ?? null;
        const previousModule = this.getModule();

        if (!view) {
            view = this.getView();
        }


        if (!reloadActions || !previousModule) {
            return;
        }

        const actions: string[] = reloadActions[previousModule] ?? reloadActions['any'] ?? [];

        if (!actions || !actions.length) {
            return;
        }

        const reload = actions.some(action => {
            return action === 'any' || action === view;
        });

        if (reload) {
            this.appMetadataStore.load(this.getModule(), ['globalRecentlyViewed'], false).pipe(take(1)).subscribe();

        }
    }

    public getModule(): string {
        return this.appStateStore.getModule();
    }

    public getView(): string {
        return this.appStateStore.getView();
    }

}
