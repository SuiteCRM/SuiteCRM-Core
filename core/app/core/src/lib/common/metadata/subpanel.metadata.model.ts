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

import {ColumnDefinition, SearchMetaField} from './list.metadata.model';
import {WidgetOptionMap} from './widget.metadata';
import {Action} from '../actions/action.model';

export interface SubPanelCollectionList {
    [key: string]: SubPanelCollectionItem;
}

/* eslint-disable camelcase */
export interface SubPanelCollectionItem {
    module: string;
    subpanel_name: string;
    get_subpanel_data: string;
}

/* eslint-enable camelcase */

export interface SubPanelMeta {
    [key: string]: SubPanelDefinition;
}

export interface SubPanelDefinition {
    subpanelWidget?: WidgetOptionMap;
    order?: 10;
    sort_order?: string;
    sort_by?: string;
    title_key?: string;
    pagination_type: string;
    type?: string;
    name: string;
    searchdefs?: SearchMetaField;
    subpanel_name?: string;
    max_height?: number;
    header_definition_from_subpanel?: string;
    module?: string;
    legacyModule?: string;
    headerModule?: string;
    top_buttons?: Action[];
    collection_list: SubPanelCollectionList;
    columns: ColumnDefinition[];
    icon?: string;
    lineActions?: Action[];
    get_subpanel_data?: string;
    showFilter?: boolean;
    topButtonsDropdownLabelKey?: string;
    parameters?: {
        [key: string]: any;
    };
}

/* eslint-enable camelcase */

