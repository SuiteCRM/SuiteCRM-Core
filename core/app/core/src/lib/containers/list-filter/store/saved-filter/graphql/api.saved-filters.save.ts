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
import {Record} from '../../../../../common/record/record.model';
import {ApolloQueryResult} from '@apollo/client/core';
import {RecordSaveGQL} from '../../../../../store/record/graphql/api.record.save';
import {SavedFilter} from '../../../../../store/saved-filters/saved-filter.model';
import {Observable} from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class SavedFilterSaveGQL extends RecordSaveGQL {

    constructor(protected apollo: Apollo) {
        super(apollo);
    }

    public save(record: Record): Observable<SavedFilter> {
        return super.save(record);
    }

    protected mapToRecord(response: ApolloQueryResult<any>): SavedFilter {
        if (!response.data || !response.data.saveRecord || !response.data.saveRecord.record) {
            return null;
        }

        const savedFilter = {
            // eslint-disable-next-line no-underscore-dangle
            id: response.data.saveRecord.record._id,
            type: response.data.saveRecord.record.type || '',
            module: response.data.saveRecord.record.module || '',
            attributes: response.data.saveRecord.record.attributes || null,
        } as SavedFilter;

        savedFilter.key = savedFilter.id || (savedFilter.attributes && savedFilter.attributes.id) || '';

        const contents = (savedFilter.attributes && savedFilter.attributes.contents) || null;
        if (contents) {
            savedFilter.criteria = contents;
        }

        return savedFilter;
    }
}
