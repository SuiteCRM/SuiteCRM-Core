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

import {Observable, of} from 'rxjs';
import {Injectable} from '@angular/core';
import {map} from 'rxjs/operators';
import {ColumnDefinition} from '../../../common/metadata/list.metadata.model';
import {ActionDataSource} from '../../../common/actions/action.model';
import {SortDirection, SortingSelection} from '../../../common/views/list/list-navigation.model';
import {TableConfig} from '../../../components/table/table.model';
import {SubpanelStore} from '../store/subpanel/subpanel.store';
import {SubpanelLineActionsAdapterFactory} from './line-actions.adapter.factory';
import {UserPreferenceStore} from '../../../store/user-preference/user-preference.store';
import {SystemConfigStore} from "../../../store/system-config/system-config.store";

@Injectable()
export class SubpanelTableAdapter {

    constructor(
        protected store: SubpanelStore,
        protected lineActionsAdapterFactory: SubpanelLineActionsAdapterFactory,
        protected preferences: UserPreferenceStore,
        protected systemConfigs: SystemConfigStore
    ) {
    }

    getTable(): TableConfig {
        return {
            showHeader: false,
            showFooter: true,

            module: this.store.metadata.headerModule,

            columns: this.getColumns(),
            lineActions: this.getLineActions(),
            sort$: this.store.recordList.sort$,
            maxColumns$: of(5),
            loading$: this.store.recordList.loading$,

            dataSource: this.store.recordList,
            pagination: this.store.recordList,

            toggleRecordSelection: (id: string): void => {
                this.store.recordList.toggleSelection(id);
            },

            updateSorting: (orderBy: string, sortOrder: SortDirection): void => {
                this.store.recordList.updateSorting(orderBy, sortOrder);

                const parentModule = this.store.parentModule;
                const module = this.store.recordList.getModule();
                const sort = {orderBy, sortOrder} as SortingSelection;

                this.preferences.setUi(parentModule, module + '-subpanel-sort', sort);
            },

            maxListHeight: this.preferences.getUserPreference('subpanel_max_height') ?? this.systemConfigs.getConfigValue('subpanel_max_height'),

            paginationType: this.preferences.getUserPreference('subpanel_pagination_type') ?? this.systemConfigs.getConfigValue('subpanel_pagination_type'),

            loadMore: (): void => {
                const jump = this.preferences.getUserPreference('list_max_entries_per_subpanel') ?? this.systemConfigs.getConfigValue('list_max_entries_per_subpanel');
                const pagination = this.store.recordList.getPagination();
                const currentPageSize = pagination.pageSize || 0;
                const newPageSize = Number(currentPageSize) + Number(jump);


                this.store.recordList.setPageSize(newPageSize);
                this.store.recordList.updatePagination(pagination.current)
            },

            allLoaded: (): boolean => {
                const pagination = this.store.recordList.getPagination();

                if (!pagination) {
                    return false;
                }

                if (Number(pagination.pageLast) >= Number(pagination.total)) {
                    return true;
                }

                return Number(pagination.pageSize) >= Number(pagination.total);
            }

        } as TableConfig;
    }

    protected getColumns(): Observable<ColumnDefinition[]> {
        return this.store.metadata$.pipe(map(metadata => metadata.columns));
    }

    protected getLineActions(): ActionDataSource {
        return this.lineActionsAdapterFactory.create(this.store);
    }
}
