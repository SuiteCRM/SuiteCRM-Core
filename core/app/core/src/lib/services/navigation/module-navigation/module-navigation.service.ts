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
import {Router} from '@angular/router';
import {ModuleAction, NavbarModule, Navigation, NavigationStore} from '../../../store/navigation/navigation.store';
import {LanguageListStringMap, LanguageStrings} from '../../../store/language/language.store';
import {MenuItem} from '../../../common/menu/menu.model';
import {Record} from '../../../common/record/record.model';
import {ModuleNameMapper} from '../module-name-mapper/module-name-mapper.service';
import {ActionNameMapper} from '../action-name-mapper/action-name-mapper.service';

export interface NavigationRoute {
    route: string;
    url: string;
    params: { [key: string]: string };
    process?: string;
    processParams?: { [key: string]: string };
}

const ROUTE_PREFIX = './#';

@Injectable({providedIn: 'root'})
export class ModuleNavigation {

    constructor(
        protected router: Router,
        protected moduleNameMapper: ModuleNameMapper,
        protected actionNameMapper: ActionNameMapper,
        protected navigation: NavigationStore
    ) {
    }

    /**
     * Public Api
     */

    /**
     * Get module info
     *
     * @param {string} module name
     * @param {object} navigation info
     * @returns {object} module info
     */
    public getModuleInfo(module: string, navigation: Navigation): NavbarModule {
        if (!navigation || !navigation.modules) {
            return null;
        }

        return navigation.modules[module];
    }

    /**
     * Get module label
     *
     * @param {object} module info
     * @param {object} appListStrings map
     * @returns {string} the module label
     */
    public getModuleLabel(module: NavbarModule, appListStrings: LanguageListStringMap): string {
        if (!appListStrings || !appListStrings.moduleList || !module) {
            return '';
        }
        const labelKey = (module && module.labelKey) || '';

        return appListStrings.moduleList[labelKey] || labelKey;
    }

    /**
     * Get module route
     *
     * @param {object} module NavbarModule
     * @returns {object} NavigationRoute
     */
    public getModuleRoute(module: NavbarModule): NavigationRoute {
        let url = (module && module.defaultRoute) || '';
        let route = null;
        const params = null;

        if (url.startsWith(ROUTE_PREFIX)) {
            route = url.replace(ROUTE_PREFIX, '');
            url = null;
        }

        return {route, url, params};
    }

    /**
     * Navigate using action information
     *
     * @param {object} item ModuleAction
     * @returns {object} Promise<boolean>
     */
    public navigate(item: ModuleAction): Promise<boolean> {
        const route = this.getActionRoute(item);

        return this.router.navigate([route.route], {
            queryParams: route.params
        });
    }

    /**
     * Navigate using menu item information
     *
     * @param {object} item MenuItem
     */
    public navigateUsingMenuItem(item: MenuItem): void {

        if(item.link.route) {

            this.router.navigate([item.link.route ?? ''], {
                queryParams: item.link.params ?? {}
            }).then();

            return;
        }

        if(item.link.url) {

            this.router.navigateByUrl(item.link.url ?? '').then();

            return;
        }
    }

    /**
     * Get action route info
     *
     * @param {object} action ModuleAction
     * @returns {object} NavigationRoute
     */
    public getActionRoute(action: ModuleAction): NavigationRoute {
        let url = action.url;
        let route = null;
        let params = {};
        let process = action?.process
        let processParams = action?.processParams ?? {};

        if (url.startsWith(ROUTE_PREFIX)) {
            route = url.replace(ROUTE_PREFIX, '');
            url = null;

            if (action.params) {
                params = action.params;
            } else {
                const routeParts = route.split('?');
                route = routeParts[0];
                const queryParamsStr = routeParts[1];
                const queryParamsObj = {};

                if (queryParamsStr) {
                    queryParamsStr.split('&').forEach(param => {
                        const keyValue = param.split('=');
                        queryParamsObj[keyValue[0]] = keyValue[1];
                    });
                }
                params = queryParamsObj;
            }
        }

        return {route, url, params, process, processParams};
    }

    /**
     * Get label for module action item
     *
     * @param {string} module name
     * @param {object} item action
     * @param {object} languages map
     * @param {string} labelKey to use
     * @returns {string} label
     */
    public getActionLabel(module: string, item: ModuleAction, languages: LanguageStrings, labelKey = ''): string {
        if (!languages || !languages.modStrings || !item || !module) {
            return '';
        }

        let key = labelKey;
        if (!key) {
            key = item.labelKey;
        }

        let label = languages.modStrings[module] && languages.modStrings[module][key];

        if (!label) {
            label = languages.appStrings && languages.appStrings[key];
        }

        if (!label && item.module) {
            label = languages.modStrings[item.module] && languages.modStrings[item.module][key];
        }

        if (!label) {
            label = languages.modStrings.administration && languages.modStrings.administration[key];
        }

        return label || '';
    }

    /**
     * Get record router link route info
     *
     * @param {string} module name
     * @param {string} id fo the record
     * @returns {string} router link
     */
    public getRecordRouterLink(module: string, id: string, isEdit?: boolean): string {

        if(isEdit) {
            return `/${module}/edit/${id}`;
        }

        return `/${module}/record/${id}`;
    }

    /**
     * Navigate back using return params
     * @param record
     * @param moduleName
     * @param params
     */
    public navigateBack(
        record: Record,
        moduleName: string,
        params: { [key: string]: string }
    ) {

        let returnModule = this.getReturnModule(params);
        let returnAction = this.getReturnAction(params);
        const returnId = this.getReturnId(params);

        let route = '';
        if (returnModule) {
            route += '/' + returnModule;
        }

        if (returnAction) {
            route += '/' + returnAction;
        }

        if (returnId) {
            route += '/' + returnId;
        }

        if (returnModule === moduleName && returnAction === 'record') {
            const rid = !returnId ? record.id : returnId;
            route = '/' + moduleName + '/record/' + rid;
        }

        if (!route && record && record.id) {
            route = '/' + moduleName + '/record/' + record.id;
        }

        if (!route && record && record.id) {
            route = '/' + moduleName;
        }

        this.router.navigate([route]).then();
    }

    /**
     * Extract return id
     * @param params
     */
    public getReturnId(params: { [p: string]: string }) {
        return params.return_id || '';
    }

    /**
     * Extract and map return action
     * @param params
     */
    public getReturnAction(params: { [p: string]: string }) {
        let returnAction = '';

        if (params.return_action) {
            returnAction = this.actionNameMapper.toFrontend(params.return_action);
        }
        return returnAction;
    }

    /**
     * Extract and map return action
     * @param params
     */
    public getReturnModule(params: { [p: string]: string }) {
        let returnModule = '';

        if (params.return_module) {
            returnModule = this.moduleNameMapper.toFrontend(params.return_module);
        }

        return returnModule;
    }

    /**
     * Check if the user has access to a module
     * @param module
     */
    public hasAccessToModule(module: string): boolean {
       return this.navigation.hasAccessToModule(module);
    }
}
