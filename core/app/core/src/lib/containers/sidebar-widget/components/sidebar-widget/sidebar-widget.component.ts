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
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {SidebarWidgetRegistry} from './sidebar-widget.registry';

@Component({
    selector: 'scrm-sidebar-widget',
    templateUrl: './sidebar-widget.component.html',
    styles: []
})
export class SidebarWidgetComponent extends BaseWidgetComponent {

    @Input('type') type: string;

    constructor(protected registry: SidebarWidgetRegistry) {
        super();
    }

    get componentType(): any {
        const module = this.context.module ?? 'default';
        return this.registry.get(module, this.type);
    }

    getErrorMessage(): string {
        if (!this.type || !this.config) {
            return 'LBL_CONFIG_NO_CONFIG';
        }

        if (!this.componentType) {
            return 'LBL_WIDGET_NOT_FOUND';
        }

        return 'LBL_BAD_CONFIG';
    }
}
