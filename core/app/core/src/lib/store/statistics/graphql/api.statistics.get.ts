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
import {Statistic, StatisticsMap, StatisticsQueryMap} from '../../../common/statistics/statistics.model';
import {map} from 'rxjs/operators';
import {ApolloQueryResult} from '@apollo/client/core';

@Injectable({
    providedIn: 'root'
})
export class StatisticsFetchGQL {

    constructor(private apollo: Apollo) {
    }

    /**
     * Fetch statistics data from backend
     *
     * @param {string} module name
     * @param {object} queries to use
     * @returns {object} Observable<ApolloQueryResult<any>>
     */
    public fetch(
        module: string,
        queries: StatisticsQueryMap,
    ): Observable<StatisticsMap> {

        const queryOptions = {
            query: gql`
            query batchedStatistics($module: String!, $queries: Iterable!){
              batchedStatistics(module: $module, queries: $queries) {
                  _id
                  id
                  items
              }
            }
        `,
            variables: {
                module,
                queries,
            },
        };

        return this.apollo.query(queryOptions).pipe(map((result: ApolloQueryResult<any>) => {

            const statistics: StatisticsMap = {};
            const response = (result.data && result.data.batchedStatistics) || {} as any;
            const items = response.items || {} as any;
            const itemKeys = Object.keys(items);

            if (itemKeys && itemKeys.length) {
                itemKeys.forEach((itemKey: string) => {
                    const item = items[itemKey];
                    // eslint-disable-next-line no-underscore-dangle
                    const key = itemKey || item._id;
                    statistics[key] = {
                        // eslint-disable-next-line no-underscore-dangle
                        id: item._id,
                        data: item.data,
                        metadata: item.metadata
                    } as Statistic;
                });
            }
            return statistics;
        }));
    }
}
