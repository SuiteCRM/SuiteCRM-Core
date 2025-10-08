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
import {StateStore} from '../../../../store/state';
import {RecordList, RecordListStore} from '../../../../store/record-list/record-list.store';
import {Observable} from 'rxjs';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {ViewContext} from '../../../../common/views/view.model';

@Injectable()
export class HistoryTimelineStore implements StateStore {
    private recordList: RecordListStore;
    private viewContext: ViewContext;

    constructor(
        protected listStoreFactory: RecordListStoreFactory
    ) {
        this.recordList = listStoreFactory.create();
    }

    clear(): void {
        this.recordList.clear();
        this.recordList = null;
    }

    clearAuthBased(): void {
        this.recordList.clearAuthBased();
    }

    /**
     * Initial list records load if not cached and update state.
     *
     * @param {ViewContext} context of parent
     * @description initialize the Record List Store
     * returns {void}
     */
    public init(context): void {
        this.recordList.init('history', false, 'list_max_entries_per_subpanel');
        this.initViewContext(context);
    }

    /**
     * Load / reload records using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordList>
     */
    public load(useCache = true): Observable<RecordList> {

        return this.recordList.load(useCache);
    }

    /**
     * Init search criteria
     *
     * @param {number} offset of the recordset
     * @param {number} limit of the recordset
     * @description initialize the search module/criteria(offset/limit) for the record set
     */
    public initSearchCriteria(offset: number, limit: number): void {
        this.recordList.criteria = {
            preset: {
                type: 'history-timeline',
                params: {
                    parentModule: this.viewContext.module,
                    parentId: this.viewContext.id,
                    offset,
                    limit
                }
            }
        };
    }

    protected initViewContext(viewContext: ViewContext): void {
        this.viewContext = viewContext;
    }
}
