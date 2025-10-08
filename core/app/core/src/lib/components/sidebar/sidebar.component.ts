/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

import {Component, OnDestroy, OnInit, signal, ViewChild, WritableSignal} from '@angular/core';
import {AppStateStore} from "../../store/app-state/app-state.store";
import {combineLatestWith, Subscription} from "rxjs";
import {NavbarModuleMap, Navigation, NavigationStore} from "../../store/navigation/navigation.store";
import {MenuItem} from '../../common/menu/menu.model';
import {ModuleNameMapper} from "../../services/navigation/module-name-mapper/module-name-mapper.service";
import {ModuleNavigation} from "../../services/navigation/module-navigation/module-navigation.service";
import {LanguageListStringMap, LanguageStore, LanguageStrings} from "../../store/language/language.store";
import {map} from "rxjs/operators";
import {CommonModule} from "@angular/common";
import {SidebarModule} from "primeng/sidebar";
import {ImageModule} from "../image/image.module";
import {MobileMenuComponent} from "./mobile-menu/mobile-menu.component";
import {SearchBarModule} from "../search-bar/search-bar.module";
import {SearchBarComponent} from "../search-bar/search-bar.component";

@Component({
    selector: 'scrm-sidebar',
    templateUrl: 'sidebar.component.html',
    standalone: true,
    imports: [CommonModule, SidebarModule, ImageModule, MobileMenuComponent, SearchBarModule],
})

export class SidebarComponent implements OnInit, OnDestroy {

    @ViewChild('searchBarComponent') searchBarComponent: SearchBarComponent;
    isSidebarVisible: boolean = false;
    menuItems: MenuItem[] = [];
    displayedItems: WritableSignal<MenuItem[]> = signal<MenuItem[]>([]);

    protected subs: Subscription[] = [];

    constructor(
        protected appStateStore: AppStateStore,
        protected navigationStore: NavigationStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected moduleNavigation: ModuleNavigation,
        protected language: LanguageStore
    ) {
    }

    ngOnInit(): void {
        this.subs.push(this.navigationStore.vm$.pipe(
            combineLatestWith(this.language.vm$),
            map(([navigation, language]: [Navigation, LanguageStrings]) => {
                this.setMenuItems(navigation.modules, navigation.tabs, language.appListStrings);
            })
        ).subscribe());

        this.subs.push(this.appStateStore.isSidebarVisible$.subscribe(
            (isSidebarVisible: boolean) => {
                this.isSidebarVisible = isSidebarVisible;
                if (!this.isSidebarVisible) {
                    this.clearFilter();
                }
            }
        ));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    setMenuItems(modules: NavbarModuleMap, tabs: string[], appListStrings: LanguageListStringMap): void {
        const menuItems = [];
        tabs.forEach((tab: string) => {
            const moduleInfo = modules[tab];
            const moduleRoute = this.moduleNavigation.getModuleRoute(moduleInfo);

            const menuItem: MenuItem = {
                link: {
                    label: this.moduleNavigation.getModuleLabel(moduleInfo, appListStrings),
                    url: moduleRoute.url,
                    route: moduleRoute.route,
                    params: null
                },
                icon: this.moduleNameMapper.toLegacy(moduleInfo?.name) ?? null,
                submenu: [],
                module: moduleInfo?.name ?? null
            };
            menuItems.push(menuItem);
        });

        this.menuItems = [...menuItems];

        this.displayedItems.set([...menuItems]);
    }

    toggleSidebar(): void {
        this.appStateStore.toggleSidebar();
    }

    closeSidebar(): void {
        this.clearFilter();
        this.appStateStore.closeSidebar();
    }

    search(searchTerm: string): void {
        this.displayedItems.set([]);
        if (searchTerm.length && searchTerm.trim() !== '') {
            this.displayedItems.set(this.menuItems.filter(item => {
                return item?.link?.label.toLowerCase().includes(searchTerm.toLowerCase());
            }) ?? []);
        } else {
            this.resetMenuItems();
        }
    }

    protected resetMenuItems(): void {
        this.displayedItems.set([...this.menuItems]);
    }

    protected clearFilter(): void {
        this.resetMenuItems();
        this?.searchBarComponent?.clearSearchTerm();
    }
}
