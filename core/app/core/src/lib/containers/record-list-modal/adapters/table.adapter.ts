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

import {of} from 'rxjs';
import {ColumnDefinition} from '../../../common/metadata/list.metadata.model';
import {Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {SortDirection} from '../../../common/views/list/list-navigation.model';
import {map} from 'rxjs/operators';
import {RecordListModalTableAdapterInterface} from './adapter.model';
import {RecordListModalStore} from '../store/record-list-modal/record-list-modal.store';
import {TableConfig} from '../../../components/table/table.model';
import {UserPreferenceStore} from "../../../store/user-preference/user-preference.store";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";

export class ModalRecordListTableAdapter implements RecordListModalTableAdapterInterface {

    constructor(
        protected systemConfigs: SystemConfigStore,
        protected preferences: UserPreferenceStore
    ){
    }

    /**
     * Get table config
     *
     * @param {object} store to use
     * @param {boolean} multiSelect
     * @returns {object} TableConfig
     */
    getTable(store: RecordListModalStore, multiSelect: boolean = false): TableConfig {
        const config = {
            showHeader: true,
            showFooter: true,
            klass: 'light-table',
            module: store.recordList.getModule(),

            columns: store.columns$.pipe(map(columns => this.mapColumns(store, columns))),
            sort$: store.recordList.sort$,
            maxColumns$: of(5),
            loading$: store.recordList.loading$,

            dataSource: store.recordList,
            pagination: store.recordList,

            toggleRecordSelection: (id: string): void => {
                store.recordList.toggleSelection(id);
            },

            updateSorting: (orderBy: string, sortOrder: SortDirection): void => {
                store.recordList.updateSorting(orderBy, sortOrder);
                store.saveCurrentSort();
            },

            maxListHeight: this.preferences.getUserPreference('record_modal_max_height') ?? this.systemConfigs.getConfigValue('record_modal_max_height'),

            paginationType: this.preferences.getUserPreference('record_modal_pagination_type') ?? this.systemConfigs.getConfigValue('record_modal_pagination_type'),

            loadMore: (): void => {
                const jump = this.preferences.getUserPreference('list_max_entries_per_modal') ?? this.systemConfigs.getConfigValue('list_max_entries_per_modal');
                const pagination = store.recordList.getPagination();
                const currentPageSize = pagination.pageSize || 0;
                const newPageSize = Number(currentPageSize) + Number(jump);


                store.recordList.setPageSize(newPageSize);
                store.recordList.updatePagination(pagination.current);
            },

            allLoaded: (): boolean => {
                const pagination = store.recordList.getPagination();

                if (!pagination) {
                    return false;
                }

                if (Number(pagination.pageLast) >= Number(pagination.total)) {
                    return true;
                }

                return Number(pagination.pageSize) >= Number(pagination.total);
            }

        } as TableConfig;


        if (multiSelect){
            config.selection$ = store.recordList.selection$;
            config.selectedCount$ = store.recordList.selectedCount$;
            config.selectedStatus$ = store.recordList.selectedStatus$;
        }

        return config;
    }

    /**
     * Parse and override column definitions
     *
     * @param {object} store to use
     * @param {[]} columns to map
     * @returns {[]} ColumnDefinition[]
     */
    protected mapColumns(store: RecordListModalStore, columns: ColumnDefinition[]): ColumnDefinition[] {
        const mappedColumns = [];

        columns.forEach(column => {
            const mapped = {...column};
            const metadata = column.metadata || {};
            mapped.metadata = {...metadata};

            this.disableRelateFieldsLink(mapped);
            this.addLinkSelectHandler(store, mapped);

            mappedColumns.push(mapped);
        });

        return mappedColumns;
    }

    /**
     * Disable link for relate fields
     *
     * @param {object} definition to update
     */
    protected disableRelateFieldsLink(definition: ColumnDefinition): void {
        if (definition.type !== 'relate') {
            return;
        }
        definition.link = false;
        definition.metadata.link = false;
    }

    /**
     * Add onClick handler for link fields
     *
     * @param {object} store to use
     * @param {object} definition to update
     */
    protected addLinkSelectHandler(store: RecordListModalStore, definition: ColumnDefinition): void {
        if (!definition.link) {
            return;
        }

        definition.metadata.onClick = (field: Field, record: Record): void => {
            store.recordList.clearSelection();
            store.recordList.toggleSelection(record.id);
            store.emitLinkClicked();
        };
    }
}
