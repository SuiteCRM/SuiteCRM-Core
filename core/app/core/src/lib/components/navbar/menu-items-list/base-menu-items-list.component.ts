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

import {Component, Input, signal} from '@angular/core';
import {MenuItem} from '../../../common/menu/menu.model';
import {Subscription} from "rxjs";
import {AppStateStore} from "../../../store/app-state/app-state.store";

@Component({
    selector: 'scrm-base-menu-items-list',
    templateUrl: './base-menu-items-list.component.html',
    styleUrls: []
})
export class BaseMenuItemsListComponent {
    @Input() items: MenuItem[];
    @Input() labelKey: string;
    @Input() index: number;

    showDropdown = signal<boolean>(true);
    hoverEnabled = signal<boolean>(true);
    allowHover = signal<boolean>(true);
    isTouchDevice = signal<boolean>(false);

    subs: Subscription[] = [];

    constructor(protected appStateStore: AppStateStore) {
    }

    ngOnInit(): void {
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
    }

    hideDropdown() {
        this.showDropdown.set(false);
        this.hoverEnabled.set(true);
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
}
