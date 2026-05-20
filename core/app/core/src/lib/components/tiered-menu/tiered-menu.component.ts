/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {Component, EventEmitter, Input, OnInit, signal, WritableSignal} from "@angular/core";
import {MenuItem} from "primeng/api";

@Component({
    selector: 'scrm-tiered-menu',
    templateUrl: './tiered-menu.component.html',
    styles: []
})
export class TieredMenuComponent implements OnInit {
    items: WritableSignal<MenuItem[]> = signal([]);

    @Input() showSearch: boolean = true;
    @Input() itemsName: MenuItem[] | [];
    @Input() clearSearchEventEmitter: EventEmitter<boolean>;

    isSearchBoxVisible: WritableSignal<boolean> = signal(true);

    ngOnInit() {
        this.isSearchBoxVisible.set(this.showSearch);
        this.items.set(this.itemsName || []);
    }

    closeSearchBox(isVisible: boolean) {
        this.isSearchBoxVisible.set(isVisible);
    }

    search(searchTerm: string) {
        if (!searchTerm || searchTerm.trim() === '') {
            this.items.set(this.itemsName || []);
            return;
        }

        this.items.set(this.filterItems(this.itemsName || [], searchTerm));
    }


    filterItems(items: MenuItem[], searchTerm): MenuItem[] {
        return items.reduce((filtered: MenuItem[], item) => {
            const matchesLabel = item.label?.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesBadge = item.badge?.toLowerCase().includes(searchTerm.toLowerCase());
            const filteredSubItems = item.items ? this.filterItems(item.items, searchTerm) : [];
            if (matchesLabel || matchesBadge || filteredSubItems.length > 0) {
                filtered.push({
                    ...item,
                    items: filteredSubItems.length > 0 ? filteredSubItems : item.items
                });
            }

            return filtered;
        }, []) as MenuItem[];
    }
}

