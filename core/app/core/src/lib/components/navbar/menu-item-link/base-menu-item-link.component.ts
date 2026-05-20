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

import {Component, Input, OnInit} from '@angular/core';
import {MenuItemLink} from '../../../common/menu/menu.model';
import {take} from 'rxjs/operators';
import {AsyncActionInput, AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {AppStateStore} from '../../../store/app-state/app-state.store';
import {MenuItemLinkConfig} from "./menu-item-link-config.model";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";
import {Params} from "@angular/router";

@Component({
    selector: 'scrm-base-menu-item-link',
    templateUrl: './base-menu-item-link.component.html',
    styleUrls: []
})
export class BaseMenuItemLinkComponent implements OnInit{
    @Input() link: MenuItemLink;
    @Input() icon: string;
    @Input() class: string;
    @Input() config: MenuItemLinkConfig;

    charSize = {
        minLength: 20,
        mediumLength: 20,
        maxLength: 20
    }

    constructor(
        protected asyncActionService: AsyncActionService,
        protected systemConfigStore: SystemConfigStore,
        protected appState: AppStateStore
    ) {}

    ngOnInit() {
        const characterSizes = this.systemConfigStore.getUi('navbar_truncate_character_sizes');
        this.charSize = {...characterSizes}
    }

    handleProcess(process: string, params: Params = {}) {

        if (!process) {
            return;
        }

        const processType = process;

        const options = {
            action: processType,
            module: this.appState.getModule(),
            params: params
        } as AsyncActionInput;

        this.asyncActionService.run(processType, options).pipe(take(1)).subscribe();
    }
}
