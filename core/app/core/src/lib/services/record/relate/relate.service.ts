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
import {RecordListStoreFactory} from '../../../store/record-list/record-list.store.factory';
import {RecordListStore} from '../../../store/record-list/record-list.store';
import {map, shareReplay, take} from 'rxjs/operators';
import {Record} from '../../../common/record/record.model';
import {Observable} from 'rxjs';
import {SearchCriteria} from "../../../common/views/list/search-criteria.model";

@Injectable()
export class RelateService {
    recordList: RecordListStore;

    constructor(recordListStoreFactory: RecordListStoreFactory) {
        this.recordList = recordListStoreFactory.create();
    }

    init(module: string): void {
        this.recordList.init(module, false);
    }

    search(term: string, field: string, criteria: SearchCriteria): Observable<Record[]> {

        const recordListCriteria = this.recordList.criteria;

        criteria = {
            ...recordListCriteria,
            ...criteria
        }

        if (!term.startsWith('%')) {
            term = '%' + term;
        }

        criteria.filters[field] = {
            field,
            operator: '=',
            values: [term]
        };

        this.recordList.updateSearchCriteria(criteria, false);
        return this.recordList.load(false).pipe(
            map(value => value.records),
            take(1),
            shareReplay(1)
        );
    }

}
