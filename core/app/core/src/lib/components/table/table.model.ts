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

import {Observable} from 'rxjs';
import {DataSource} from '@angular/cdk/collections';
import {ActionDataSource} from '../../common/actions/action.model';
import {ColumnDefinition} from '../../common/metadata/list.metadata.model';
import {PaginationDataSource} from '../../common/components/pagination/pagination.model';
import {Record} from '../../common/record/record.model';
import {RecordSelection, SelectionStatus} from '../../common/views/list/record-selection.model';
import {SelectionDataSource} from '../../common/views/list/selection.model';
import {SortDirection, SortingSelection} from '../../common/views/list/list-navigation.model';
import {BulkActionDataSource} from '../bulk-action-menu/bulk-action-menu.component';

export interface TableConfig {
    showHeader: boolean;
    showFooter: boolean;
    klass?: string;
    dataSource: DataSource<Record>;

    columns: Observable<ColumnDefinition[]>;
    maxColumns$: Observable<number>;
    lineActions?: ActionDataSource;
    maxListHeight?: number;
    selection$?: Observable<RecordSelection>;
    selectedCount$?: Observable<number>;
    selectedStatus$?: Observable<SelectionStatus>;
    sort$?: Observable<SortingSelection>;
    loading$?: Observable<boolean>;

    selection?: SelectionDataSource;

    bulkActions?: BulkActionDataSource;
    pagination?: PaginationDataSource;
    tableActions?: ActionDataSource;

    paginationType?: string;

    loadMore?(): void;

    allLoaded?(): boolean;

    module?: string;

    toggleRecordSelection(id: string): void;

    updateSorting(orderBy: string, sortOrder: SortDirection): void;
}
