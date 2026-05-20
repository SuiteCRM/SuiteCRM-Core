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

import {StatisticsFetchGQL} from '../statistics/graphql/api.statistics.get';
import {StatisticsMap, StatisticsQueryMap} from '../../common/statistics/statistics.model';
import {Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {ChartDataStoreFactory} from './chart-data.store.factory';
import {dataTypeFormatterMock} from '../../services/formatters/data-type.formatter.spec.mock';

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
        return of({
            'accounts-past-years-closed-opportunity-amounts': {
                id: 'accounts-past-years-closed-opportunity-amounts',
                data: {
                    multiSeries: [
                        {
                            name: 'Closed Won',
                            series: [
                                {
                                    name: '2016',
                                    value: '0',
                                    extra: null,
                                    min: null,
                                    max: null,
                                    label: null
                                },
                                {
                                    name: '2017',
                                    value: '0',
                                    extra: null,
                                    min: null,
                                    max: null,
                                    label: null
                                },
                                {
                                    name: '2018',
                                    value: '0',
                                    extra: null,
                                    min: null,
                                    max: null,
                                    label: null
                                },
                                {
                                    name: '2019',
                                    value: '0',
                                    extra: null,
                                    min: null,
                                    max: null,
                                    label: null
                                },
                                {
                                    name: '2020',
                                    value: '400',
                                    extra: null,
                                    min: null,
                                    max: null,
                                    label: null
                                }
                            ]
                        }
                    ]
                },
                metadata: {
                    type: 'series-statistic',
                    dataType: 'currency',
                    chartOptions: {
                        height: null,
                        scheme: null,
                        gradient: null,
                        xAxis: null,
                        yAxis: null,
                        legend: true,
                        showXAxisLabel: null,
                        showYAxisLabel: null,
                        xAxisLabel: null,
                        yAxisLabel: null,
                        xScaleMin: '2016',
                        xScaleMax: '2020',
                        xAxisTicks: [
                            '2016',
                            '2017',
                            '2018',
                            '2019',
                            '2020'
                        ],
                        yAxisTickFormatting: true,
                        xAxisTickFormatting: null,
                        tooltipDisabled: true
                    }
                }
            }
        }).pipe(shareReplay(1));
    }
}

export const chartDataStoreFactoryMock = new ChartDataStoreFactory(
    new StatisticsFetchGQLSpy(),
    dataTypeFormatterMock
);
