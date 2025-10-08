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
import {Apollo, gql} from 'apollo-angular';
import {Observable} from 'rxjs';
import {ApolloQueryResult} from '@apollo/client/core';
import {Pagination, SortingSelection} from '../../../common/views/list/list-navigation.model';
import {Record} from '../../../common/record/record.model';
import {SearchCriteria} from '../../../common/views/list/search-criteria.model';
import {map} from 'rxjs/operators';
import {RecordList} from '../record-list.store';
import {isInteger, toInteger} from "lodash-es";

@Injectable({
    providedIn: 'root'
})
export class ListGQL {

    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
            'meta',
            'records'
        ]
    };

    constructor(protected apollo: Apollo) {
    }

    /**
     * Fetch data either from backend
     *
     * @param {string} module to get from
     * @param {number} limit  page limit
     * @param {number} offset  current offset
     * @param {object} criteria filter criteria
     * @param {object} sort selection
     * @param {object} metadata with the fields to ask for
     * @returns {object} Observable<ApolloQueryResult<any>>
     */
    public fetch(
        module: string,
        limit: number,
        offset: number,
        criteria: { [key: string]: any },
        sort: { [key: string]: any },
        metadata: { fields: string[] }
    ): Observable<ApolloQueryResult<any>> {

        const fields = metadata.fields;

        const queryOptions = {
            query: gql`
              query recordList($module: String!, $limit: Int, $offset: Int, $criteria: Iterable, $sort: Iterable) {
                recordList(module: $module, limit: $limit, offset: $offset, criteria: $criteria, sort: $sort) {
                  ${fields.join('\n')}
                }
              }
            `,
            variables: {
                module,
                limit,
                offset,
                criteria,
                sort
            },
        };

        return this.apollo.query(queryOptions);
    }

    /**
     * Fetch the List records from the backend
     *
     * @param {string} module to use
     * @param {object} criteria to use
     * @param {object} sort to use
     * @param {object} pagination to use
     * @returns {object} Observable<any>
     */
    public get(module: string, criteria: SearchCriteria, sort: SortingSelection, pagination: Pagination): Observable<RecordList> {
        const mappedSort = this.mapSort(sort);

        return this.fetch(module, toInteger(pagination.pageSize), toInteger(pagination.current), criteria, mappedSort, this.fieldsMetadata)
            .pipe(map(({data}) => {
                const recordsList: RecordList = {
                    records: [],
                    pagination: {...pagination} as Pagination
                };

                if (!data || !data.recordList) {
                    return recordsList;
                }

                const listData = data.recordList;

                if (listData.records) {
                    listData.records.forEach((record: any) => {
                        recordsList.records.push(
                            this.mapRecord(record)
                        );
                    });
                }

                if (!listData.meta) {
                    return recordsList;
                }

                if (listData.meta.offsets) {

                    const paginationFieldMap = {
                        current: 'current',
                        next: 'next',
                        prev: 'previous',
                        total: 'total',
                        end: 'last',
                    };

                    Object.keys(paginationFieldMap).forEach((key) => {
                        if (key in listData.meta.offsets) {
                            const paginationField = paginationFieldMap[key];
                            recordsList.pagination[paginationField] = listData.meta.offsets[key];
                        }
                    });
                }

                recordsList.meta = listData.meta;

                return recordsList;
            }));
    }

    /**
     * Map sort.
     * @param {object} sort to map
     */
    protected mapSort(sort: SortingSelection): { [key: string]: string } {
        const sortOrderMap = {
            NONE: '',
            ASC: 'ASC',
            DESC: 'DESC'
        };

        return {
            sortOrder: sortOrderMap[sort.sortOrder],
            orderBy: sort.orderBy
        };
    }

    /**
     * Map record. Allow for extensions
     * @param record
     */
    protected mapRecord(record: any): Record {
        return record;
    }
}
