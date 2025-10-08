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
import {ActionContext} from '../../../../common/actions/action.model';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../../../common/components/button/button-group.model';
import {Observable} from 'rxjs';
import {TableConfig} from '../../../../components/table/table.model';
import {SubpanelTableAdapter} from '../../adapters/table.adapter';
import {LanguageStore} from '../../../../store/language/language.store';
import {SubpanelStore} from '../../store/subpanel/subpanel.store';
import {SubpanelActionManager} from './action-manager.service';
import {SubpanelTableAdapterFactory} from '../../adapters/table.adapter.factory';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {FilterConfig} from "../../../list-filter/components/list-filter/list-filter.model";
import {SubpanelFilterAdapterFactory} from "../../adapters/filter.adapter.factory";
import {SubpanelFilterAdapter} from "../../adapters/filter.adapter";
import {SubpanelActionAdapterFactory} from "../../adapters/actions.adapter.factory";
import {SubpanelActionsAdapter} from "../../adapters/actions.adapter";

@Component({
    selector: 'scrm-subpanel',
    templateUrl: 'subpanel.component.html',
    providers: [
        SubpanelTableAdapter
    ]
})
export class SubpanelComponent implements OnInit {
    @Input() store: SubpanelStore;
    @Input() maxColumns$: Observable<number>;
    @Input() onClose: Function;
    @Input() filterConfig: FilterConfig;
    @Input() panelHeaderButtonClass: string = 'btn btn-sm btn-outline-light';

    closeButton: ButtonInterface;
    adapter: SubpanelTableAdapter;
    config$: Observable<ButtonGroupInterface>;
    tableConfig: TableConfig;
    filterAdapter: SubpanelFilterAdapter;
    actionsAdapter: SubpanelActionsAdapter;

    constructor(
        protected actionManager: SubpanelActionManager,
        protected languages: LanguageStore,
        protected tableAdapterFactory: SubpanelTableAdapterFactory,
        protected preferences: UserPreferenceStore,
        protected systemConfigs: SystemConfigStore,
        protected filterAdapterFactory: SubpanelFilterAdapterFactory,
        protected actionAdapterFactory: SubpanelActionAdapterFactory
    ) {
    }

    ngOnInit(): void {

        this.buildAdapters();

        if (this.maxColumns$) {
            this.tableConfig.maxColumns$ = this.maxColumns$;
        }

        if (this.store?.metadata?.max_height) {
            this.tableConfig.maxListHeight = this.store.metadata.max_height;
        }

        if (!this.tableConfig?.maxListHeight) {
            const ui = this.systemConfigs.getConfigValue('ui') ?? {};
            this.tableConfig.maxListHeight = ui.subpanel_max_height;
        }

        this.tableConfig.paginationType = this?.store?.metadata?.pagination_type ?? this.tableConfig.paginationType;

        const parentModule = this.store.parentModule;
        const module = this.store.recordList.getModule();

        const sort = this.preferences.getUi(parentModule, module + '-subpanel-sort');

        if (sort) {
            this.store.recordList.updateSorting(sort.orderBy, sort.sortOrder);
        }

        this.closeButton = {
            onClick: (): void => {
                this.onClose && this.onClose();
            }
        } as ButtonInterface;
    }

    getActionContext(): ActionContext {
        const module = this.store?.metadata?.module ?? '';
        return {module} as ActionContext;
    }

    buildAdapters(): void {
        this.adapter = this.tableAdapterFactory.create(this.store);
        this.tableConfig = this.adapter.getTable();
        this.filterAdapter = this.filterAdapterFactory.create(this.store);
        this.filterConfig = this.filterAdapter.getConfig();
        this.actionsAdapter = this.actionAdapterFactory.create(this.store);
    }
}
