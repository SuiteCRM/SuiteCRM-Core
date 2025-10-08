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
import {BehaviorSubject, Observable, of} from 'rxjs';
import {deepClone} from '../../common/utils/object-utils';
import {SeriesStatistic, Statistic, StatisticsQuery} from '../../common/statistics/statistics.model';
import {SeriesResult} from '../../common/containers/chart/chart.model';
import {ChartOptions} from '../../common/metadata/charts-widget.metadata';
import {ChartDataSource, ChartOptionMap} from '../../common/containers/chart/chart.model';
import {distinctUntilChanged, map, shareReplay} from 'rxjs/operators';
import {SeriesStatisticsState, SeriesStatisticsStore} from '../series-statistics/series-statistics.store';
import {StatisticsFetchGQL} from '../statistics/graphql/api.statistics.get';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {SeriesMapper} from '../../services/statistics/series/mapper/series-mapper.service';

const initialState = {
    module: '',
    query: {} as StatisticsQuery,
    statistic: {
        id: '',
        data: {} as SeriesResult
    } as SeriesStatistic,
    loading: false
} as ChartDataState;

export interface ChartDataState extends SeriesStatisticsState {
    dataSource?: ChartDataSource;
}

@Injectable()
export class ChartDataStore extends SeriesStatisticsStore {

    state$: Observable<ChartDataState>;
    statistic$: Observable<SeriesStatistic>;
    loading$: Observable<boolean>;
    protected internalState: ChartDataState = deepClone(initialState);
    protected store = new BehaviorSubject<ChartDataState>(this.internalState);
    protected defaultOptions: ChartOptions = {};

    constructor(
        protected fetchGQL: StatisticsFetchGQL,
        protected formatter: DataTypeFormatter,
        protected seriesMapper: SeriesMapper
    ) {
        super(fetchGQL);
        this.state$ = this.store.asObservable();
        this.statistic$ = this.state$.pipe(map(state => state.statistic), distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading), distinctUntilChanged());
    }

    public setDefaultOptions(chartOptions: ChartOptions): void {
        this.defaultOptions = chartOptions;
    }

    public getDataSource(): ChartDataSource {
        return this.internalState.dataSource;
    }

    protected addNewState(statistic: Statistic): void {

        if (!statistic.metadata || !statistic.metadata.dataType) {
            return;
        }
        this.injectDefaultValues(statistic);

        const dataSource = this.buildCharDataSource(statistic);

        this.updateState({
            ...this.internalState,
            statistic,
            dataSource,
            loading: false
        });
    }

    protected injectDefaultValues(statistic: Statistic): void {
        if (!statistic.metadata.chartOptions) {
            statistic.metadata.chartOptions = deepClone(this.defaultOptions);
            return;
        }

        Object.keys(this.defaultOptions).forEach(optionKey => {
            if (!(optionKey in statistic.metadata.chartOptions)) {
                statistic.metadata.chartOptions[optionKey] = this.defaultOptions[optionKey];
            }
        });
    }

    protected buildCharDataSource(statistic: Statistic): ChartDataSource {
        const dataType = statistic.metadata.dataType || '';

        let formatOptions = null;
        const digits = (statistic.metadata && statistic.metadata.digits) || null;

        if (digits !== null) {
            formatOptions = {
                digits
            };
        }

        return {
            options: statistic.metadata.chartOptions || {} as ChartOptionMap,

            getResults: (): Observable<SeriesResult> => of(this.buildSeriesResult(statistic)).pipe(shareReplay(1)),
            tickFormatting: (value: any): any => this.formatter.toUserFormat(dataType, value, formatOptions),
            tooltipFormatting: (value: any): any => this.formatter.toUserFormat(dataType, value, formatOptions)
        } as ChartDataSource;
    }

    protected buildSeriesResult(statistic: Statistic): SeriesResult {

        const dataType = statistic.metadata.dataType || '';

        const result = {} as SeriesResult;

        const singleSeries = statistic.data.singleSeries || null;
        if (singleSeries) {
            result.singleSeries = singleSeries;
        }

        const multiSeries = statistic.data.multiSeries || null;
        if (multiSeries) {
            result.multiSeries = multiSeries;
        }

        this.seriesMapper.map(result, 'data-type-unit-converter', {dataType});

        return result;
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: ChartDataState): void {
        super.updateState(state);
    }
}
