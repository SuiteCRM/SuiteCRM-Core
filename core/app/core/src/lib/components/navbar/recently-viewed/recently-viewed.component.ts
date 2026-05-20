/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

import {ChangeDetectionStrategy, Component, computed, Input, signal} from '@angular/core';
import {CommonModule} from "@angular/common";
import {ImageModule} from "../../image/image.module";
import {RouterLink} from "@angular/router";
import {ModuleNameMapper} from "../../../services/navigation/module-name-mapper/module-name-mapper.service";
import {ModuleNavigation} from "../../../services/navigation/module-navigation/module-navigation.service";
import {RecentlyViewed} from '../../../common/record/recently-viewed.model';
import {LabelModule} from "../../label/label.module";

@Component({
    selector: 'scrm-recently-viewed',
    templateUrl: './recently-viewed.component.html',
    styleUrls: [],
    standalone: true,
    imports: [CommonModule, ImageModule, RouterLink, LabelModule],
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecentlyViewedComponent {
    _menuItems = signal<RecentlyViewed[]>([]);
    @Input() set menuItems(value: RecentlyViewed[]) {
        this._menuItems.set(value);
    }

    itemWithRoutes = computed(() => this._menuItems().map( item => {
        if(item.attributes?.route) {
            return item;
        }
        return {
            ...item,
            attributes: {
                ...item.attributes,
                route: this.buildRoute(item)
            }
        };
    }));

    constructor(
        protected nameMapper: ModuleNameMapper,
        protected navigation: ModuleNavigation
    ) {}

    /**
     * Build route from recently viewed item
     * @param item
     */
    buildRoute(item: any): string {
        const legacyName = item.attributes.module_name ?? '';
        const module = this.nameMapper.toFrontend(legacyName) ?? '';
        const id = item.attributes.item_id ?? '';
        return this.navigation.getRecordRouterLink(module, id);
    }

}
