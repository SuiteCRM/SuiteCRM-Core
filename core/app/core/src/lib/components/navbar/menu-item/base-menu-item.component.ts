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

import {Component, ElementRef, Input, OnDestroy, OnInit, signal, ViewChild} from '@angular/core';
import {MenuItem} from '../../../common/menu/menu.model';
import {Subject, Subscription} from "rxjs";
import {AppStateStore} from "../../../store/app-state/app-state.store";
import {ModuleNavigation} from "../../../services/navigation/module-navigation/module-navigation.service";
import {MenuItemLinkConfig} from "../menu-item-link/menu-item-link-config.model";
import {SubMenuRecentlyViewedConfig} from "../sub-menu-recently-viewed/sub-menu-recently-viewed-config.model";
import {SubMenuFavoritesConfig} from "../sub-menu-favorites/sub-menu-favorites-config.model";

@Component({
    selector: 'scrm-base-menu-item',
    templateUrl: './base-menu-item.component.html',
    styleUrls: []
})
export class BaseMenuItemComponent implements OnInit, OnDestroy {
    @Input() item: MenuItem;
    @Input() index: number = 0;
    @ViewChild('topLink') topLink: ElementRef;

    showDropdown = signal<boolean>(false);
    hoverEnabled = signal<boolean>(true);
    topLinkConfig: MenuItemLinkConfig;
    recentlyViewedConfig: SubMenuRecentlyViewedConfig;
    favoritesConfig: SubMenuFavoritesConfig;
    showRecentlyViewed: Subject<boolean>;
    showFavorites: Subject<boolean>;
    clickType: string = 'click';

    subs: Subscription[] = [];

    constructor(protected appStateStore: AppStateStore, protected moduleNavigation: ModuleNavigation) {
    }

    ngOnInit(): void {

        this.showRecentlyViewed = new Subject<boolean>();
        this.showFavorites = new Subject<boolean>();

        this.topLinkConfig = {
            onClick: (event) => {
            },
            onTouchStart: (event) => {
            }
        } as MenuItemLinkConfig

        this.recentlyViewedConfig = {
            onItemClick: (event) => {
                if (this.clickType === 'touch') {
                    this.hideDropdown();
                    this.clickType = 'click';
                }
            },
            onItemTouchStart: (event) => {
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
            onItemTouchStart: (event) => {
                this.clickType = 'touch';
            },
            onToggleDropdown: (showDropdown): void => {
                if (showDropdown) {
                    this.showRecentlyViewed.next(false);
                }
            },
            showDropdown$: this.showFavorites.asObservable()
        } as SubMenuFavoritesConfig

        this.subs.push(this.appStateStore.activeNavbarDropdown$.subscribe(
            (activeDropdown: number) => {
                if (this.index !== activeDropdown) {
                    this.hideDropdown();
                }
            }
        ));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.showRecentlyViewed.unsubscribe();
        this.showFavorites.unsubscribe();
    }

    hideDropdown() {
        this.showDropdown.set(false);
        this.hoverEnabled.set(true);
    }

    navigate(): void {
        this.moduleNavigation.navigateUsingMenuItem(this.item);
    }

    onTopItemClick($event: PointerEvent): void {

        if (this.clickType === 'click') {
            this.appStateStore.resetActiveDropdown();
            this.navigate();
            return;
        }

        this.toggleDropdown();
        this.clickType = 'click';
    }

    toggleDropdown(): void {
        this.showDropdown.set(!this.showDropdown());
        if (this.showDropdown()) {
            this.appStateStore.setActiveDropdown(this.index);
            this.hoverEnabled.set(false);
        } else {
            this.appStateStore.resetActiveDropdown();
            this.hoverEnabled.set(true);
        }
    }

    onTouchStart(): void {
        this.clickType = 'touch';
    }

    onTouchEnd(): void {
        this.clickType = 'touch';
    }

    onClick(event): void {
        event.stopImmediatePropagation();
        event.stopPropagation();
        this.onTopItemClick(event)
    }
}
