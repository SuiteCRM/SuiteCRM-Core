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

import {Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {StatisticsMap, StatisticsQueryMap} from '../../../../common/statistics/statistics.model';
import {SingleValueStatisticsStoreFactory} from '../../../../store/single-value-statistics/single-value-statistics.store.factory';
import {fieldManagerMock} from '../../../../services/record/field/field.manager.spec.mock';
import {StatisticsFetchGQL} from '../../../../store/statistics/graphql/api.statistics.get';

class StatisticsFetchGQLSpy extends StatisticsFetchGQL {
    constructor() {
        super(null);
    }

    public fetch(
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        module: string,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        queries: StatisticsQueryMap,
    ): Observable<StatisticsMap> {

        if (queries.opportunities) {
            return of({
                history: {
                    id: 'opportunities',
                    data: {
                        value: '5400'
                    },
                    metadata: {
                        dataType: 'currency',
                        type: 'single-value-statistic'
                    }
                }
            }).pipe(shareReplay(1));
        }

        if (queries['accounts-won-opportunity-amount-by-year']) {
            return of({
                history: {
                    id: 'accounts-won-opportunity-amount-by-year',
                    data: {
                        value: '1466.6666666666667'
                    },
                    metadata: {
                        dataType: 'currency',
                        type: 'single-value-statistic'
                    }
                }
            }).pipe(shareReplay(1));
        }

    }
}

export const topWidgetStatisticsFactoryMock = new SingleValueStatisticsStoreFactory(
    new StatisticsFetchGQLSpy(),
    fieldManagerMock
);
