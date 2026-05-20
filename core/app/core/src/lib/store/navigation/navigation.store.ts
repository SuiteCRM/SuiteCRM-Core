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
import {BehaviorSubject, combineLatestWith, Observable, of} from 'rxjs';
import {distinctUntilChanged, map, shareReplay, tap} from 'rxjs/operators';

import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {StateStore} from '../state';
import {deepClone} from '../../common/utils/object-utils';
import {ObjectMap} from '../../common/types/object-map';
import {Params} from '@angular/router';
import {isEmpty} from "lodash-es";

export interface Navigation {
    tabs: string[];
    groupedTabs: GroupedTab[];
    modules: NavbarModuleMap;
    quickActions: ModuleAction[];
    userActionMenu: UserActionMenu[];
    maxTabs: number;
}

export interface NavbarModuleMap {
    [key: string]: NavbarModule;
}

export interface NavbarModule {
    name: string;
    path: string;
    defaultRoute: string;
    labelKey: string;
    menu: ModuleAction[];
}

export interface GroupedTab {
    name: string;
    labelKey: string;
    modules: string[];
}

export interface UserActionMenu {
    name: string;
    labelKey: string;
    url: string;
    icon: string;
}

export interface ModuleAction {
    name: string;
    labelKey: string;
    actionLabelKey?: string;
    label?: string;
    url: string;
    params?: string | Params;
    icon: string;
    module?: string;
    sublinks?: ObjectMap;
    quickAction?: boolean;
    type?: string;
    process?: string;
    processParams?: { [key: string]: string };
}

const initialState: Navigation = {
    tabs: [],
    groupedTabs: [],
    modules: {},
    userActionMenu: [],
    quickActions: [],
    maxTabs: 0
};

let internalState: Navigation = deepClone(initialState);

let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class NavigationStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    tabs$: Observable<string[]>;
    groupedTabs$: Observable<GroupedTab[]>;
    modules$: Observable<NavbarModuleMap>;
    userActionMenu$: Observable<UserActionMenu[]>;
    maxTabs$: Observable<number>;
    quickActions$: Observable<ModuleAction[]>;

    /**
     * ViewModel that resolves once all the data is ready (or updated)...
     */
    vm$: Observable<Navigation>;

    protected store = new BehaviorSubject<Navigation>(internalState);
    protected state$ = this.store.asObservable();
    protected resourceName = 'navbar';
    protected fieldsMetadata = {
        fields: [
            'tabs',
            'groupedTabs',
            'modules',
            'userActionMenu',
            'maxTabs'
        ]
    };

    constructor(private recordGQL: EntityGQL) {

        this.tabs$ = this.state$.pipe(map(state => state.tabs), distinctUntilChanged());
        this.groupedTabs$ = this.state$.pipe(map(state => state.groupedTabs), distinctUntilChanged());
        this.modules$ = this.state$.pipe(map(state => state.modules), distinctUntilChanged());
        this.userActionMenu$ = this.state$.pipe(map(state => state.userActionMenu), distinctUntilChanged());
        this.maxTabs$ = this.state$.pipe(map(state => state.maxTabs), distinctUntilChanged());
        this.quickActions$ = this.state$.pipe(map(state => state.quickActions), distinctUntilChanged());


        this.vm$ = this.tabs$.pipe(
            combineLatestWith(this.groupedTabs$, this.modules$, this.userActionMenu$, this.maxTabs$, this.quickActions$),
            map(([tabs, groupedTabs, modules, userActionMenu, maxTabs, quickActions]) => ({
                    tabs, groupedTabs, modules, userActionMenu, maxTabs, quickActions
                })
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
        cache$ = null;
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Initial Navigation load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @returns {{}} Observable<any>
     */
    public load(): Observable<any> {

        return this.getNavigation().pipe(
            tap(navigation => {
                this.updateState({
                    ...internalState,
                    tabs: navigation.tabs,
                    groupedTabs: navigation.groupedTabs,
                    userActionMenu: navigation.userActionMenu,
                    modules: navigation.modules,
                    maxTabs: navigation.maxTabs,
                    quickActions: navigation?.quickActions ?? []
                });
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
     * Set pre-loaded navigation and cache
     */
    public set(navigation: Navigation): void {
        cache$ = of(navigation).pipe(shareReplay(1));
        this.updateState({
            ...internalState,
            tabs: navigation.tabs,
            groupedTabs: navigation.groupedTabs,
            userActionMenu: navigation.userActionMenu,
            modules: navigation.modules,
            maxTabs: navigation.maxTabs,
            quickActions: navigation?.quickActions ?? []
        });
    }

    /**
     * Check if the user has access to a module
     * @param module
     */
    public hasAccessToModule(module: string): boolean {
        if (!internalState.modules || isEmpty(internalState.modules)) {
            return true;
        }
        return !!(internalState.modules[module] ?? false);
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {{}} state to set
     */
    protected updateState(state: Navigation): void {
        this.store.next(internalState = state);
    }

    /**
     * Get Navigation cached Observable or call the backend
     *
     * @returns {{}} Observable<any>
     */
    protected getNavigation(): Observable<any> {

        const user = '1';

        if (cache$ === null) {
            cache$ = this.fetch(user).pipe(
                shareReplay(1)
            );
        }

        return cache$;
    }

    /**
     * Fetch the Navigation from the backend
     *
     * @param {string} userId to use
     * @returns {{}} Observable<any>
     */
    protected fetch(userId: string): Observable<any> {
        return this.recordGQL
            .fetch(this.resourceName, `/api/navbars/${userId}`, this.fieldsMetadata)
            .pipe(
                map(({data}) => {
                    let navigation: Navigation = null;

                    if (data && data.navbar) {
                        navigation = {
                            tabs: data.navbar.tabs,
                            groupedTabs: data.navbar.groupedTabs,
                            userActionMenu: data.navbar.userActionMenu,
                            modules: data.navbar.modules,
                            maxTabs: data.navbar.maxTabs,
                            quickActions: data?.navbar?.quickActions ?? [],
                        };

                    }

                    return navigation;
                })
            );
    }
}
