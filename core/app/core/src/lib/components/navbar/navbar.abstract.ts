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

import {NavbarModel} from './navbar-model';
import {LogoAbstract} from '../logo/logo-abstract';
import {CurrentUserModel} from './current-user-model';
import {ActionLinkModel} from './action-link-model';
import {ready} from '../../common/utils/object-utils';
import {MenuItem} from '../../common/menu/menu.model';
import {User} from '../../common/types/user';
import {LanguageStore} from '../../store/language/language.store';
import {
    GroupedTab,
    NavbarModule,
    NavbarModuleMap,
    Navigation,
    UserActionMenu
} from '../../store/navigation/navigation.store';
import {LinkTarget} from './link-target';
import {RouteConverter} from '../../services/navigation/route-converter/route-converter.service';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {ModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {ModuleNameMapper} from "../../services/navigation/module-name-mapper/module-name-mapper.service";

export class NavbarAbstract implements NavbarModel {
    authenticated = true;
    logo = new LogoAbstract();
    useGroupTabs = false;
    globalActions: ActionLinkModel[] = [];
    currentUser: CurrentUserModel = {
        id: '',
        firstName: '',
        lastName: '',
    };
    all = {
        modules: [],
        extra: [],
    };
    menu: MenuItem[] = [];
    current?: MenuItem;

    /**
     * Public API
     */

    constructor(
        private routeConverter: RouteConverter,
        protected moduleNavigation: ModuleNavigation,
        protected preferences: UserPreferenceStore,
        protected language: LanguageStore,
        protected appState: AppStateStore,
        protected moduleNameMapper: ModuleNameMapper
    ) {
    }

    /**
     * Reset menus
     */
    public resetMenu(): void {
        this.menu = [];
        this.globalActions = [];
        this.all.modules = [];
        this.all.extra = [];
        this.current = null;
        this.currentUser = {} as User;
    }

    /**
     * Build user action menu
     *
     * @param {[]} userActionMenu info
     * @param {object} currentUser info
     */
    public buildUserActionMenu(
        userActionMenu: UserActionMenu[],
        currentUser: CurrentUserModel
    ): void {
        this.currentUser.id = currentUser.id;
        this.currentUser.firstName = currentUser.firstName;
        this.currentUser.lastName = currentUser.lastName;

        if (userActionMenu) {
            userActionMenu.forEach((subMenu) => {
                const name = subMenu.name;
                let url = subMenu.url;

                if (name === 'logout') {
                    return;
                }

                let target = LinkTarget.none;

                if (name === 'training') {
                    target = LinkTarget.blank;
                } else {
                    url = this.routeConverter.toFrontEndLink(url);
                }

                const label = this.language.getAppString(subMenu.labelKey) ?? '';

                this.globalActions.push({
                    link: {
                        url,
                        label,
                        target
                    },
                });
            });
        }
        return;
    }

    /**
     * Build navbar
     *
     * @param {object} navigation info
     * @param {object} currentUser info
     * @param {number} maxTabs to display
     */
    public build(
        navigation: Navigation,
        currentUser: CurrentUserModel,
        maxTabs: number,
    ): void {

        this.buildUserActionMenu(navigation.userActionMenu, currentUser);

        const navigationParadigm = this.preferences.getUserPreference('navigation_paradigm');
        const sort = this.preferences.getUserPreference('sort_modules_by_name') === 'on';

        if (navigationParadigm === 'm') {
            this.buildModuleNavigation(navigation, maxTabs, sort);
            return;
        }

        if (navigationParadigm === 'gm') {
            this.buildGroupedNavigation(navigation, maxTabs, sort);
            return;
        }
    }

    /**
     * Build Group tab menu
     *
     * @param {[]} items list
     * @param {object} modules info
     * @param {number} threshold limit
     * @param {object} groupedTabs info
     * @param {boolean} sort flag
     */
    public buildGroupTabMenu(
        items: string[],
        modules: NavbarModuleMap,
        threshold: number,
        groupedTabs: GroupedTab[],
        sort: boolean
    ): void {

        const navItems = [];
        const moreItems = [];

        if (items && items.length > 0) {
            items.forEach((module) => {
                moreItems.push(this.buildTabMenuItem(module, modules[module]));
            });

            if (sort) {
                this.sortMenuItems(moreItems);
            }
        }

        let count = 0;
        groupedTabs.forEach((groupedTab: any) => {

            if (count < threshold) {
                navItems.push(this.buildTabGroupedMenuItem(
                    groupedTab.labelKey,
                    groupedTab.modules,
                    modules,
                    sort
                ));
            }

            count++;
        });

        this.menu = navItems;
        this.all.modules = moreItems;
    }

    /**
     *
     * Internal API
     *
     */

    /**
     * Build module navigation
     *
     * @param {object} navigation info
     * @param {number} maxTabs to use
     * @param {boolean} sort flag
     */
    protected buildModuleNavigation(
        navigation: Navigation,
        maxTabs: number,
        sort: boolean,
    ): void {

        if (!ready([navigation.tabs, navigation.modules])) {
            return;
        }

        this.buildTabMenu(navigation.tabs, navigation.modules, maxTabs, sort);
        this.buildSelectedModule(navigation);
    }

    /**
     * Build grouped navigation
     *
     * @param {object} navigation info
     * @param {number} maxTabs to use
     * @param {boolean} sort flag
     */
    protected buildGroupedNavigation(
        navigation: Navigation,
        maxTabs: number,
        sort: boolean,
    ): void {

        if (!ready([navigation.tabs, navigation.modules, navigation.groupedTabs])) {
            return;
        }

        this.buildGroupTabMenu(navigation.tabs, navigation.modules, maxTabs, navigation.groupedTabs, sort);
        this.buildSelectedModule(navigation);
    }

    /**
     * Build selected module
     *
     * @param {object} navigation info
     */
    protected buildSelectedModule(
        navigation: Navigation,
    ): void {
        const module = this.appState.getModule() ?? '';

        if (module === '' || module === 'home') {
            return;
        }

        if (!navigation.modules[module]) {
            return;
        }

        this.current = this.buildTabMenuItem(module, navigation.modules[module]);
    }

    /**
     * Build tab / module menu
     *
     * @param {[]} items list
     * @param {object} modules info
     * @param {number} threshold limit
     * @param {boolean} sort flag
     */
    protected buildTabMenu(
        items: string[],
        modules: NavbarModuleMap,
        threshold: number,
        sort: boolean,
    ): void {

        const navItems = [];
        const moreItems = [];

        if (!items || items.length === 0) {
            this.menu = navItems;
            this.all.modules = moreItems;
            return;
        }

        let count = 0;
        items.forEach((module: string) => {

            const item = this.buildTabMenuItem(module, modules[module]);

            if (module === 'home' || this.appState.getModule() === module || count > threshold) {
                moreItems.push(item);
            } else {
                navItems.push(item);
            }

            count++;
        });

        if (sort) {
            this.sortMenuItems(navItems);
            this.sortMenuItems(moreItems);
        }


        this.menu = navItems;
        this.all.modules = moreItems;
    }

    /**
     * Build Grouped Tab menu item
     *
     * @param {string} moduleLabel to display
     * @param {object} groupedModules list
     * @param {object} modules list
     * @param {boolean} sort flag
     *
     * @returns {object} group tab menu item
     */
    protected buildTabGroupedMenuItem(
        moduleLabel: string,
        groupedModules: any[],
        modules: NavbarModuleMap,
        sort: boolean
    ): any {

        return {
            link: {
                label: this.language.getAppString(moduleLabel) || moduleLabel,
                url: '',
                route: null,
                params: null
            },
            icon: '',
            submenu: this.buildGroupedMenu(groupedModules, modules, sort)
        };
    }

    /**
     * Build Grouped menu
     *
     * @param {object} groupedModules info
     * @param {object} modules map
     * @param {boolean} sort flag
     *
     * @returns {[]} menu item array
     */
    protected buildGroupedMenu(
        groupedModules: any[],
        modules: NavbarModuleMap,
        sort: boolean
    ): MenuItem[] {

        const groupedItems = [];
        let homeMenuItem = null;

        groupedModules.forEach((groupedModule) => {

            const module = modules[groupedModule];

            if (!module) {
                return;
            }

            const moduleMenuItem = this.buildTabMenuItem(groupedModule, module);

            if (groupedModule === 'home') {
                homeMenuItem = moduleMenuItem;
                return;
            }

            groupedItems.push(moduleMenuItem);
        });

        if (sort) {
            this.sortMenuItems(groupedItems);
        }

        if (homeMenuItem) {
            groupedItems.unshift(homeMenuItem);
        }

        return groupedItems;
    }

    /**
     * Build module menu items
     *
     * @param {string} module name
     * @param {object} moduleInfo info
     *
     * @returns {object} menuItem
     */
    protected buildTabMenuItem(
        module: string,
        moduleInfo: NavbarModule,
    ): MenuItem {
        if (moduleInfo.name) {
            module = moduleInfo.name;
        }
        const moduleRoute = this.moduleNavigation.getModuleRoute(moduleInfo);
        const appListStrings = this.language.getLanguageStrings()?.appListStrings ?? {};
        const menuItem: MenuItem = {
            link: {
                label: this.moduleNavigation.getModuleLabel(moduleInfo, appListStrings),
                url: moduleRoute.url,
                route: moduleRoute.route,
                params: null
            },
            icon: this.moduleNameMapper.toLegacy(module) ?? null,
            submenu: [],
            module: module ?? null,
            isGroupedMenu: false
        };
        let hasSubmenu = false;
        if (moduleInfo) {
            moduleInfo.menu.forEach((subMenu) => {
                const sublinks = subMenu.sublinks || [];
                const subMenuItem = this.buildSubMenuItem(module, subMenu, sublinks);
                menuItem.submenu.push(subMenuItem);
                if (sublinks.length > 0) {
                    hasSubmenu = true;
                }
            });
        }
        menuItem.isGroupedMenu = hasSubmenu;
        return menuItem;
    }

    protected buildSubMenuItem(module: string, subMenu: any, sublinks: any): MenuItem {
        const moduleActionRoute = this.moduleNavigation.getActionRoute(subMenu);
        const subMenuItem: MenuItem = {
            link: {
                label: this.moduleNavigation.getActionLabel(module, subMenu, this.language.getLanguageStrings()),
                url: moduleActionRoute.url,
                route: moduleActionRoute.route,
                params: moduleActionRoute.params,
                process: moduleActionRoute.process,
                processParams: moduleActionRoute.processParams
            },
            icon: subMenu.icon || '',
            submenu: sublinks.map((item) => this.buildSubMenuItem(module, item, [])),

        };
        return subMenuItem;
    }


    /**
     * Sort menu items by label
     *
     * @param {object} navItems to sort
     */
    protected sortMenuItems(navItems: any[]): void {
        navItems.sort((a: MenuItem, b: MenuItem) => {

            const nameA = a.link.label.toUpperCase(); // ignore upper and lowercase
            const nameB = b.link.label.toUpperCase(); // ignore upper and lowercase

            if (nameA < nameB) {
                return -1;
            }
            if (nameA > nameB) {
                return 1;
            }

            // names must be equal
            return 0;
        });
    }
}
