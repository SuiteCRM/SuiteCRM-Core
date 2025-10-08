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

import {Action} from '../../../../common/actions/action.model';
import {AttributeMap} from '../../../../common/record/record.model';
import {FieldDefinitionMap} from '../../../../common/record/field.model';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {RecordThreadItemConfig} from '../record-thread-item/record-thread-item.model';
import {Observable} from 'rxjs';
import {RecordThreadStore} from '../../store/record-thread/record-thread.store';
import {FieldFlexbox} from '../../../../components/record-flexbox/record-flexbox.model';

export interface RecordThreadConfig {

    module: string;
    klass?: string;
    maxListHeight?: number;
    autoRefreshFrequency?: number,
    autoRefreshDeviationMin?: number,
    autoRefreshDeviationMax?: number,

    onRefresh?(): void,

    onLoadMore?(): void,

    loadMorePosition?: string;
    create?: boolean;
    direction?: 'asc' | 'desc';
    itemConfig: RecordThreadItemConfig;
    pageSize?: number;
    listActions?: Action[];
    collapseListActions?: boolean;
    listActionsClass?: string;
    listActionsButtonClass?: string;
    listActionsButtonGroupClass?: string;
    createConfig?: RecordThreadItemConfig;
    filters$: Observable<SearchCriteria>;
    presetFields$?: Observable<AttributeMap>;
    store?: RecordThreadStore;
    showNoDataMessage?: boolean;
    noDataLabel?: string;

}

export interface ThreadItemMetadataConfig {
    header?: FieldFlexbox;
    body?: FieldFlexbox;
    actions?: Action[];
    fields?: FieldDefinitionMap;
    collapseActions?: boolean;
}
