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
import {
    ActivatedRoute,
    DefaultUrlSerializer,
    Params, PRIMARY_OUTLET,
    UrlSegment, UrlSegmentGroup,
    UrlTree
} from '@angular/router';
import {HttpParams} from '@angular/common/http';
import {ModuleNameMapper} from '../module-name-mapper/module-name-mapper.service';
import {ActionNameMapper} from '../action-name-mapper/action-name-mapper.service';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';

export interface RouteInfo {
    module?: string;
    action?: string;
    record?: string;
    params?: Params;
}

@Injectable({providedIn: 'root'})
export class RouteConverter {

    constructor(
        private moduleNameMapper: ModuleNameMapper,
        private actionNameMapper: ActionNameMapper,
        private systemConfigStore: SystemConfigStore
    ) {
    }

    /**
     * Public Api
     */

    /**
     * Converts legacyLink to front end link. Includes the /#/.
     *
     * @param {string} legacyLink legacy url
     * @returns {string} frontend path
     */
    public toFrontEndLink(legacyLink: string): string {
        return '#/' + this.toFrontEndRoute(legacyLink);
    }

    /**
     * Converts legacyLink to front end route
     *
     * @param {string} legacyLink legacy url
     * @returns {string} frontend path
     */
    public toFrontEndRoute(legacyLink: string): string {

        if (legacyLink && legacyLink.includes('/#/')) {
            const anchorParts = legacyLink.split('/#/');

            if (anchorParts.length < 2) {
                return '/';
            }

            return anchorParts[1];
        }

        const info = this.parse(legacyLink);
        if (!info) {
            return '/';
        }

        let route = this.buildRoute(info.module, info.action, info.record);

        route += this.buildQueryString(info.params, ['module', 'action', 'record']);

        return route;
    }

    /**
     * Build legacy link from router information
     *
     * @param {object} params route params
     * @param {object} queryParams route query params
     * @returns {string} legacy url
     */
    public toLegacy(params: Params, queryParams: Params): string {
        let path = './legacy/index.php';

        const queryObject = {
            ...queryParams,
        };

        if (params.module) {
            queryObject.module = this.moduleNameMapper.toLegacy(params.module);
        }
        if (params.action) {
            queryObject.action = this.actionNameMapper.toLegacy(params.action);
        }

        if (params.record) {
            queryObject.record = params.record;
        }

        path += this.buildQueryString(queryObject);

        return path;
    }

    /**
     * Parse legacy link
     *
     * @param {string} legacyLink to parse
     * @returns {object} route info
     */
    public parse(legacyLink: string): RouteInfo {

        const parser = new DefaultUrlSerializer();

        const replacedString = legacyLink.replace('/legacy', '');
        const parts = replacedString.split('?');

        if (parts.length < 2) {
            return null;
        }

        const tree: UrlTree = parser.parse('/?' + parts[1]);

        const params = tree.queryParamMap;

        const module = params.get('module') || '';
        const action = params.get('action') || '';
        const record = params.get('record') || '';

        return {
            module,
            action,
            record,
            params: tree.queryParams
        };
    }

    /**
     * Map route url to RouteInfo
     *
     * @returns {object} RouteInfo of the current URL
     * @description Parses route information from ActivatedRouteSnapshot to RouteInfo object
     * @param {UrlSegment[]} urlSegment from the Router object
     */
    public parseRouteURL(urlSegment: UrlSegment[]): RouteInfo {

        return {
            module: urlSegment[0]?.path ?? '',
            action: urlSegment[1]?.path ?? 'index',
            record: urlSegment[2]?.path ?? ''
        } as RouteInfo;
    }

    /**
     * check if the current route is a classic view route
     *
     * @returns {object} RouteInfo
     * @description if the current url is a classic view url; so redirect back to the same view
     * @param {UrlTree} urlTree of current route
     */
    public parseRouteInfoFromUrlTree(urlTree: UrlTree): RouteInfo {
        const urlSegmentGroup: UrlSegmentGroup = urlTree.root.children[PRIMARY_OUTLET];
        const urlSegment: UrlSegment[] = urlSegmentGroup.segments;
        return this.parseRouteURL(urlSegment);
    }

    /**
     * check if the current route is a classic view route
     *
     * @returns {boolean} true/false
     * @param {UrlTree} urlTree of the route
     * @description if the current url is a classic view url; so redirect back to the same view
     */
    public isClassicViewRoute(urlTree: UrlTree): boolean {

        const configRoutes = this.systemConfigStore.getConfigValue('module_routing');

        const currentRouteInfo = this.parseRouteInfoFromUrlTree(urlTree);

        const module = currentRouteInfo.module;
        const action = currentRouteInfo.action;

        return !configRoutes[module] || !configRoutes[module][action];
    }

    /**
     * Check if given routeInfo matches the provided activated route
     *
     * @param {object} route to check
     * @param {object} routeInfo to check
     * @returns {boolean} is match
     */
    public matchesActiveRoute(route: ActivatedRoute, routeInfo: RouteInfo): boolean {
        const toCheck = [
            {
                name: 'module',
                map: (value): any => {

                    if (!value) {
                        return value;
                    }

                    return this.mapModuleToFrontend(value);
                }
            },
            {
                name: 'action',
                map: (value): any => {

                    if (!value) {
                        return value;
                    }

                    return this.mapActionToFrontEnd(value);
                }
            },
            {
                name: 'record',
                map: (value): any => value
            }
        ];

        let match = true;

        toCheck.forEach((param) => {
            if (!route.snapshot.params[param.name] && !routeInfo[param.name]) {
                return;
            }

            match = match && (route.snapshot.params[param.name] === param.map(routeInfo[param.name]));
        });

        return match;
    }

    /**
     * Internal API
     */

    /**
     * Build front end route
     *
     * @param {string} module name
     * @param {string} action name
     * @param {string} record id
     * @returns {string} route
     */
    protected buildRoute(module: string, action: string, record: string): string {
        const moduleName = this.mapModuleToFrontend(module);
        let route = `${moduleName}`;

        if (action) {
            const actionName = this.mapActionToFrontEnd(action);
            route += `/${actionName}`;
        }

        if (record) {
            route += `/${record}`;
        }

        return route;
    }

    /**
     * Build query string
     *
     * @param {object} queryParams query parameters
     * @param {string[]} exclude parameters to exclude
     * @returns {string} query string
     */
    protected buildQueryString(queryParams: Params, exclude: string[] = []): string {

        let params = new HttpParams();


        Object.keys(queryParams).forEach((param) => {

            if (exclude.includes(param)) {
                return;
            }

            const value = queryParams[param];
            params = params.set(param, value);
        });

        if (params.keys().length > 0) {
            return '?' + params.toString();
        }

        return '';
    }

    /**
     * Map legacy module name to frontend
     *
     * @param {string} module legacy name
     * @returns {string} frontend name
     */
    protected mapModuleToFrontend(module: string): string {
        return this.moduleNameMapper.toFrontend(module);
    }

    /**
     * Map legacy action name to frontend
     *
     * @param {string} action legacy name
     * @returns {string} frontend name
     */
    protected mapActionToFrontEnd(action: string): string {
        return this.actionNameMapper.toFrontend(action);
    }

}
