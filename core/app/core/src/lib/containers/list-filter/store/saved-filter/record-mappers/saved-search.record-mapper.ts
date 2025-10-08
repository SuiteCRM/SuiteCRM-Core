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

import {deepClone} from '../../../../../common/utils/object-utils';
import {Record} from '../../../../../common/record/record.model';
import {RecordMapper} from '../../../../../common/record/record-mappers/record-mapper.model';
import {Injectable} from '@angular/core';
import {SavedFilter} from '../../../../../store/saved-filters/saved-filter.model';

@Injectable({
    providedIn: 'root'
})
export class SavedSearchRecordMapper implements RecordMapper {

    getKey(): string {
        return 'criteria';
    }

    map(record: Record): void {
        const savedFilter: SavedFilter = record;
        if (savedFilter.criteria) {
            const contents = savedFilter?.attributes?.contents ?? {};
            const filters = savedFilter?.criteria?.filters ?? {};
            contents.filters = deepClone(filters);

            if (record.fields.name) {
                contents.name = record.fields.name.value;
                savedFilter.criteria.name = contents.name;
            }

            if (record.fields.orderBy) {
                contents.orderBy = record.fields.orderBy.value;
                savedFilter.criteria.orderBy = contents.orderBy;
            }

            if (record.fields.sortOrder) {
                contents.sortOrder = record.fields.sortOrder.value;
                savedFilter.criteria.sortOrder = contents.sortOrder;
            }

            if (record.attributes.search_module) {
                contents.searchModule = record.attributes.search_module;
                savedFilter.criteria.searchModule = contents.searchModule;
            }

            savedFilter.attributes.contents = contents;
        }

        let key = savedFilter.key || '';
        if (key === 'default') {
            key = '';
        }

        savedFilter.id = key;
        savedFilter.attributes.id = key;
    }
}
