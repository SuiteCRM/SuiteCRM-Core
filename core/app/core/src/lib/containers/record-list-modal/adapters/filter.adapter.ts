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

import {Injectable} from '@angular/core';
import {SearchMeta, SearchMetaFieldMap} from '../../../common/metadata/list.metadata.model';
import {map} from 'rxjs/operators';
import {RecordListModalStore} from '../store/record-list-modal/record-list-modal.store';
import {RecordListModalFilterAdapterInterface} from './adapter.model';
import {FilterConfig} from '../../list-filter/components/list-filter/list-filter.model';
import {SavedFilter} from '../../../store/saved-filters/saved-filter.model';
import {of} from 'rxjs';

@Injectable()
export class ModalRecordFilterAdapter implements RecordListModalFilterAdapterInterface {

    getConfig(store: RecordListModalStore): FilterConfig {
        return {
            klass: 'light-filter',
            panelMode: 'collapsible',
            isCollapsed: true,
            collapseOnSearch: true,
            savedFilterEdit: false,
            displayHeader: true,
            module: store.recordList.getModule(),
            filter$: store.recordList.criteria$.pipe(
                map(criteria => {
                    return {
                        key: 'default',
                        criteria
                    } as SavedFilter
                })
            ),
            savedFilters$: of([]),
            searchFields$: store.searchMetadata$.pipe(
                map((searchMeta: SearchMeta) => {

                    if (!searchMeta) {
                        return {} as SearchMetaFieldMap;
                    }

                    let type = 'advanced';
                    if (!searchMeta.layout.advanced) {
                        type = 'basic';
                    }

                    return searchMeta.layout[type];
                })
            ),
            listFields: [],

            onClose: (): void => {
            },

            onSearch: (): void => {
            },

            updateFilter: (filter: SavedFilter, reload = true): void => {
                store.recordList.updateSearchCriteria(filter.criteria, reload);
            },

            resetFilter: (reload?: boolean): void => {
                store.recordList.resetSearchCriteria(reload);
            },

            addSavedFilter: (filter: SavedFilter): void => {
            },

            removeSavedFilter: (filter: SavedFilter): void => {
            },

            setOpenFilter: (filter: SavedFilter): void => {
            },
        } as FilterConfig;
    }
}
