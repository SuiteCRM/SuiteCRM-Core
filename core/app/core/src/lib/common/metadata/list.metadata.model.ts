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

import {ViewFieldDefinition} from './metadata.model';
import {WidgetMetadata} from './widget.metadata';
import {DisplayType,FieldDefinition} from '../record/field.model';
import {BulkActionsMap} from '../actions/bulk-action.model';
import {ChartTypesMap} from '../containers/chart/chart.model';
import {Action} from '../actions/action.model';

export interface RecordListMeta {
    fields: ColumnDefinition[];
    bulkActions: BulkActionsMap;
    lineActions: Action[];
    tableActions?: Action[];
    filters: Filter[];
    maxHeight?: number;
}

export interface ListViewMeta extends RecordListMeta {
    chartTypes: ChartTypesMap;
    sidebarWidgets?: WidgetMetadata[];
    orderBy?: string;
    sortOrder?: string;
    paginationType?: string;
    maxHeight?: number;
}

export interface Filter {
    id: string;
    name: string;
    contents: { [key: string]: any };
}

export interface ColumnDefinition extends ViewFieldDefinition {
    width: string;
    default: boolean;
    module: string;
    id: string;
    sortable: boolean;
    sortReadOnly: boolean;
}

export interface SearchMetaField {
    name?: string;
    vardefBased?: boolean;
    readonly?: boolean;
    display?: DisplayType;
    type?: string;
    label?: string;
    default?: boolean;
    options?: string;
    fieldDefinition?: FieldDefinition;
}

export interface SearchMetaFieldMap {
    [key: string]: SearchMetaField;
}

export interface SearchMeta {
    layout: {
        basic?: SearchMetaFieldMap;
        advanced?: SearchMetaFieldMap;
    };
}

export interface MassUpdateMeta {
    fields?: ColumnDefinition[];
}


