/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

import {Injectable} from "@angular/core";
import {FilterConfig} from "../../list-filter/components/list-filter/list-filter.model";
import {map} from "rxjs/operators";
import {SavedFilter, SavedFilterMap} from "../../../store/saved-filters/saved-filter.model";
import {SearchMeta, SearchMetaFieldMap} from '../../../common/metadata/list.metadata.model';
import {SubpanelStore} from "../store/subpanel/subpanel.store";

@Injectable()
export class SubpanelFilterAdapter {

    constructor(protected store: SubpanelStore) {
    }

    getConfig(): FilterConfig {
        return {
            panelMode: 'collapsible',
            collapseOnSearch: true,
            savedFilterEdit: false,
            module: this.store.recordList.getModule(),
            displayHeader: false,
            filter$: this.store.recordList.openFilter$,
            savedFilters$: this.store.filterList.records$,
            searchFields$: this.store.searchMetadata$.pipe(
                map((searchMeta: SearchMeta) => {

                    if (this.store.metadata.searchdefs){
                        return this.store.metadata.searchdefs
                    }

                    if (!searchMeta) {
                        return {} as SearchMetaFieldMap;
                    }

                    let type = 'advanced';
                    if (!searchMeta?.layout?.advanced) {
                        type = 'basic';
                    }

                    return searchMeta?.layout[type];
                })
            ),
            listFields: [],

            onClose: (): void => {
            },

            onSearch: (): void => {
                this.store.searchFilter();
            },

            updateFilter: (filter: SavedFilter, reload = true): void => {
                const filters = {} as SavedFilterMap;
                filters[filter.key] = filter;
                this.store.setFilters(filters, reload);
            },

            resetFilter: (reload?: boolean): void => {
                this.store.resetFilters(reload);
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
