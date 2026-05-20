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

import {Component, Input} from '@angular/core';
import {ModuleNavigation} from '../../../services/navigation/module-navigation/module-navigation.service';
import {ModuleNameMapper} from '../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {BaseFavoritesComponent} from './base-favorites.component';

@Component({
    selector: 'scrm-base-menu-favorites',
    templateUrl: './base-menu-favorites.component.html',
    styleUrls: []
})
export class BaseMenuFavoritesComponent extends BaseFavoritesComponent {

    @Input() onClick: Function;
    @Input() collapsible = false;
    collapsed = false;

    constructor(
        protected navigation: ModuleNavigation,
        protected nameMapper: ModuleNameMapper,
        protected configs: SystemConfigStore,
        protected metadata: MetadataStore
    ) {
        super(navigation, nameMapper, configs, metadata)
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.collapsed = !!this.collapsible;
    }

    /**
     * toggle collapse
     */
    toggleCollapse() {
        if (!this.collapsible) {
            return;
        }

        this.collapsed = !this.collapsed;
    }
}
