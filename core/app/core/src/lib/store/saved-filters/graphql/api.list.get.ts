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
import {Apollo} from 'apollo-angular';
import {Observable} from 'rxjs';
import {Pagination, SortingSelection} from '../../../common/views/list/list-navigation.model';
import {SearchCriteria} from '../../../common/views/list/search-criteria.model';
import {ListGQL} from '../../record-list/graphql/api.list.get';
import {SavedFilter, SavedFilterList} from '../saved-filter.model';

@Injectable({
    providedIn: 'root'
})
export class FiltersListGQL extends ListGQL {

    constructor(protected apollo: Apollo) {
        super(apollo);
    }

    /**
     * Fetch the list of filters from the backend
     *
     * @param {string} module to use
     * @param {object} criteria to use
     * @param {object} sort to use
     * @param {object} pagination to use
     * @returns {object} Observable<any>
     */
    public get(module: string, criteria: SearchCriteria, sort: SortingSelection, pagination: Pagination): Observable<SavedFilterList> {
        return super.get(module, criteria, sort, pagination);
    }

    /**
     * Map record. Allow for extensions
     * @param record
     */
    protected mapRecord(record: any): SavedFilter {
        if (!record) {
            return record;
        }

        record.key = record.id || (record.attributes && record.attributes.id) || '';

        const contents = (record.attributes && record.attributes && record.attributes.contents) || null;
        if (contents) {
            const savedFilter = {...record} as SavedFilter;
            savedFilter.criteria = contents;
            return savedFilter;
        }

        return record;
    }
}
