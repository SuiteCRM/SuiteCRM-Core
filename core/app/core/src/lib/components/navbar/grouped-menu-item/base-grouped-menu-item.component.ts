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

import {Component, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {MenuItem} from '../../../common/menu/menu.model';
import {Subject, Subscription} from "rxjs";
import {AppStateStore} from "../../../store/app-state/app-state.store";
import {MenuItemLinkConfig} from "../menu-item-link/menu-item-link-config.model";
import {ModuleNavigation} from "../../../services/navigation/module-navigation/module-navigation.service";
import {SubMenuRecentlyViewedConfig} from "../sub-menu-recently-viewed/sub-menu-recently-viewed-config.model";
import {SubMenuFavoritesConfig} from "../sub-menu-favorites/sub-menu-favorites-config.model";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";

@Component({
    selector: 'scrm-base-grouped-menu-item',
    templateUrl: './base-grouped-menu-item.component.html',
    styleUrls: []
})
export class BaseGroupedMenuItemComponent implements OnInit, OnDestroy {
    @Input() item: MenuItem;
    @Input() subNavCollapse: boolean;
    @Input() index: number = 0;

    showDropdown = signal<boolean>(false);
    showSubDropdown: WritableSignal<boolean>[] = [];
    hoverEnabled = signal<boolean>(true);
    recentlyViewedConfig: SubMenuRecentlyViewedConfig;
    favoritesConfig: SubMenuFavoritesConfig;
    showRecentlyViewed: Subject<boolean>;
    showFavorites: Subject<boolean>;

    subs: Subscription[] = [];
    clickType: string = 'click';
    private openSubDropdown?: number = null;

    charSize = {
        minLength: 20,
        mediumLength: 20,
        maxLength: 20
    }

    constructor(
        protected appStateStore: AppStateStore,
        protected moduleNavigation: ModuleNavigation,
        protected systemConfigStore: SystemConfigStore
    ) {}

    ngOnInit(): void {
        this.showRecentlyViewed = new Subject<boolean>();
        this.showFavorites = new Subject<boolean>();

        const characterSizes = this.systemConfigStore.getUi('navbar_truncate_character_sizes');
        this.charSize = {...characterSizes}

        this.subs.push(this.appStateStore.activeNavbarDropdown$.subscribe(
            (activeDropdown: number) => {
                if (this.index !== activeDropdown) {
                    this.hideDropdown();
                }
            }
        ));

        const submenuItems = this?.item?.submenu ?? [];
        submenuItems.forEach(() => {
            this.showSubDropdown.push(signal<boolean>(false));
        });

        this.recentlyViewedConfig = {
            onItemClick: (event) => {
                if (this.clickType === 'touch') {
                    this.hideDropdown();
                    this.clickType = 'click';
                }
            },
            onItemTouchStart: (event): void => {
                this.clickType = 'touch';
            },
            onToggleDropdown: (showDropdown): void => {
                if (showDropdown) {
                    this.showFavorites.next(false);
                }
            },
            showDropdown$: this.showRecentlyViewed.asObservable()
        } as SubMenuRecentlyViewedConfig

        this.favoritesConfig = {
            onItemClick: (event) => {
                if (this.clickType === 'touch') {
                    this.hideDropdown();
                    this.clickType = 'click';
                }
            },
            onItemTouchStart: (event): void => {
                this.clickType = 'touch';
            },
            onToggleDropdown: (showDropdown): void => {
                if (showDropdown) {
                    this.showRecentlyViewed.next(false);
                }
            },
            showDropdown$: this.showFavorites.asObservable()
        } as SubMenuFavoritesConfig
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.showRecentlyViewed.unsubscribe();
        this.showFavorites.unsubscribe();
    }

    hideDropdown() {
        this.showDropdown.set(false);
        this.hoverEnabled.set(true);
        this.showSubDropdown.forEach(subDropdown => {
            subDropdown.set(false);
        })
    }

    toggleDropdown() {
        this.showDropdown.set(!this.showDropdown());
        if (this.showDropdown()) {
            this.appStateStore.setActiveDropdown(this.index);
            this.hoverEnabled.set(false);
        } else {
            this.appStateStore.resetActiveDropdown();
            this.hideDropdown();
        }
    }

    navigate(): void {
        this.moduleNavigation.navigateUsingMenuItem(this.item);
    }

    onSubItemClick($event: PointerEvent, item: MenuItem, index: number): void {
        if (this.clickType === 'click') {
            this.navigate();
            return;
        }

        this.toggleSubDropdown(index);
        this.clickType = 'click';
    }

    toggleSubDropdown(index: number): void {

        const openSubDropdownIndex = this.openSubDropdown ?? -1;

        if (index !== openSubDropdownIndex && openSubDropdownIndex >= 0) {
            this?.showSubDropdown[openSubDropdownIndex]?.set(false);
        }

        this.showSubDropdown[index]?.set(!this.showSubDropdown[index]());

        this.openSubDropdown = index;
        if (!this.showSubDropdown[index]()) {
            this.openSubDropdown = null;
        }
    }

    getConfig(sub: MenuItem, index: number): MenuItemLinkConfig {
        return {
            onClick: (event) => {
                this.onSubItemClick(event, sub, index)
            },
            onTouchStart: (event) => {
                this.clickType = 'touch';
            }
        } as MenuItemLinkConfig;
    }
}
