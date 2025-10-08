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

import {Component, OnInit} from '@angular/core';
import {ChartDataSource} from '../../../../common/containers/chart/chart.model';
import {ChartMetadata, ChartsWidgetOptions} from '../../../../common/metadata/charts-widget.metadata';
import {StatisticsQuery} from '../../../../common/statistics/statistics.model';
import {ViewContext} from '../../../../common/views/view.model';
import {combineLatestWith, Observable, of, Subscription} from 'rxjs';
import {map, take, tap} from 'rxjs/operators';
import {ChartDataStoreFactory} from '../../../../store/chart-data/chart-data.store.factory';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore, LanguageStringMap} from '../../../../store/language/language.store';
import {ChartDataState, ChartDataStore} from '../../../../store/chart-data/chart-data.store';

interface ChartStatistic {
    key: string;
    chartType: string;
    statisticsType: string;
    labelKey: string;
    store: ChartDataStore;
    reload: boolean;
}

interface CharDataMap {
    [key: string]: ChartDataState;
}

interface ChartStatisticMap {
    [key: string]: ChartStatistic;
}

interface ChartSidebarWidgetState {
    statistics: { [key: string]: ChartDataState };
    appStrings: LanguageStringMap;
}

@Component({
    selector: 'chart-sidebar-widget',
    templateUrl: './chart-sidebar-widget.component.html',
    styleUrls: []
})
export class ChartSidebarWidgetComponent extends BaseWidgetComponent implements OnInit {
    charts: ChartStatisticMap = {};
    titleLabelKey = 'LBL_INSIGHTS';
    title = '';
    messageLabelKey = 'LBL_CHART_NOT_FOUND';
    selectedChart = '';
    chartType = '';
    dataSource: ChartDataSource;
    vm$: Observable<ChartSidebarWidgetState>;
    loading$: Observable<boolean>;
    appStrings$: Observable<LanguageStringMap>;
    loading = true;
    protected subs: Subscription[] = [];


    constructor(public language: LanguageStore, protected factory: ChartDataStoreFactory) {
        super();
    }

    ngOnInit(): void {
        this.appStrings$ = this.language.appStrings$;

        super.ngOnInit();

        if (this.validateConfig() === false) {
            return;
        }

        if (this.context$) {
            this.subs.push(this.context$.subscribe((context: ViewContext) => {
                this.context = context;

                Object.keys(this.charts).forEach(key => {
                    const chart = this.charts[key];
                    chart.store.context = context;
                });
            }));
        }

        const options: ChartsWidgetOptions = this.config.options;
        const charts: ChartMetadata[] = options.charts;

        if (options.defaultChart) {
            this.selectedChart = options.defaultChart || '';
        }

        this.setupCharts(charts);
        this.setHeaderTitle(options);
        this.reloadSelectedChart();
        this.setupLoading();
        this.setupVM();
        this.setupReload();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    get configuredCharts(): ChartMetadata[] {
        return this.chartOptions.charts || [];
    }

    get chartOptions(): ChartsWidgetOptions {
        return this.config.options || {};
    }

    isConfigured(): boolean {
        return !!(this.config.options.charts && this.config.options.charts.length);
    }

    getLabelKey(stat: ChartDataState): string {
        const labelKey = stat.statistic.metadata && stat.statistic.metadata.labelKey;
        if (labelKey) {
            return labelKey;
        }

        return this.charts[stat.query.key].labelKey;
    }

    getKey(chart: ChartMetadata): string {
        return chart.chartKey || chart.statisticsType || '';
    }

    protected validateConfig(): boolean {
        if (!this.context || !this.context.module) {
            this.messageLabelKey = 'LBL_BAD_CONFIG_BAD_CONTEXT';
            return false;
        }

        if (!this.config) {
            this.messageLabelKey = 'LBL_BAD_CONFIG_NO_CONFIG';
            return false;
        }

        const options: ChartsWidgetOptions = this.config.options;

        if (!options || !options.charts || !options.charts.length) {
            this.messageLabelKey = 'LBL_BAD_CONFIG_NO_STATISTICS_KEY';
            return false;
        }

        return true;
    }

    protected setupCharts(charts: ChartMetadata[]): void {

        this.selectedChart = '';

        charts.forEach((chart: ChartMetadata) => {

            const key = this.getKey(chart);

            if (!key) {
                return;
            }

            if (!this.selectedChart) {
                this.selectedChart = key || '';
                this.chartType = chart.chartType;
            }

            this.buildChartInfo(key, chart);
            this.initChartStore(key, chart);
        });
    }

    protected buildChartInfo(key: string, chart: ChartMetadata): void {
        this.charts[key] = {
            key,
            labelKey: chart.labelKey || '',
            chartType: chart.chartType,
            statisticsType: chart.statisticsType,
            store: this.factory.create(),
            reload: true
        };
    }

    protected initChartStore(key: string, chart: ChartMetadata): void {
        this.charts[key].store.init(
            this.context.module,
            {
                key: chart.statisticsType,
                context: {...this.context}
            } as StatisticsQuery,
            false,
        );

        this.charts[key].store.setDefaultOptions(chart.chartOptions);
    }


    protected setHeaderTitle(options: ChartsWidgetOptions): void {

        if (this.config.labelKey) {
            this.titleLabelKey = this.config.labelKey;
        }

        if (options.headerTitle) {
            if (!this.charts || !this.charts[this.selectedChart] || !this.charts[this.selectedChart].labelKey) {
                return;
            }

            this.titleLabelKey = this.charts[this.selectedChart].labelKey;
        }

        this.title = this.language.getFieldLabel(this.titleLabelKey);
    }

    onChartSelect(event): void {
        this.dataSource = null;
        this.selectedChart = event;
        this.chartType = this.charts[this.selectedChart].chartType;
        this.reloadSelectedChart(false);
    }

    protected setupVM(): void {

        const statistics$: Observable<ChartDataState>[] = [];

        Object.keys(this.charts).forEach(key => statistics$.push(this.charts[key].store.state$));

        let statisticObs: Observable<ChartDataState[]> = of([]);

        if(statistics$.length < 1) {
            statisticObs = of([]);
        } else if(statistics$.length === 1){
            statisticObs = statistics$[0].pipe(
                map(value => [value])
            );
        } else {
            let firsObs = null;
            let others;
            [firsObs, ...others] = statistics$;
            statisticObs = firsObs.pipe(
                combineLatestWith(others)
            );
        }

        this.vm$ = statisticObs.pipe(
            combineLatestWith(this.language.appStrings$),
            map(([statistics, appStrings]) => {
                const statsMap = this.mapChartData(statistics);

                return {
                    statistics: statsMap,
                    appStrings
                };
            })
        );
    }

    protected mapChartData(statistics: ChartDataState[]): CharDataMap {
        const statsMap: CharDataMap = {};

        statistics.forEach((statistic: ChartDataState) => {

            if (!statistic.query.key) {
                return;
            }

            statsMap[statistic.query.key] = statistic;

            this.charts[statistic.query.key].labelKey = this.getLabelKey(statistic);
        });

        return statsMap;
    }

    protected setupReload(): void {
        if (!this.config.reload$) {
            return;
        }

        this.subs.push(this.config.reload$.subscribe(() => {
            if (this.loading === true) {
                return;
            }

            this.loading = true;

            Object.keys(this.charts).forEach(chartKey => {
                this.charts[chartKey].reload = true;
            });

            this.reloadSelectedChart();
        }));

    }

    protected setupLoading(): void {

        const loadings$: Observable<boolean>[] = [];

        Object.keys(this.charts).forEach(key => loadings$.push(this.charts[key].store.loading$));

        let statisticObs: Observable<boolean[]> = of([]);

        if(loadings$.length < 1) {
            statisticObs = of([]);
        } else if(loadings$.length === 1){
            statisticObs = loadings$[0].pipe(
                map(value => [value])
            );
        } else {
            let firsObs = null;
            let others;
            [firsObs, ...others] = loadings$;
            statisticObs = firsObs.pipe(
                combineLatestWith(others)
            );
        }

        this.loading$ = statisticObs.pipe(
            map((loadings) => {

            if (!loadings || loadings.length < 1) {
                this.loading = false;
                return false;
            }

            let loading = false;

            loadings.forEach(value => {
                loading = loading || value;
            });

            this.loading = loading;

            return loading;
        }));

        this.subs.push(this.loading$.subscribe());
    }

    protected reloadSelectedChart(useCache = false): void {

        if (!this.charts || !this.charts[this.selectedChart] || !this.charts[this.selectedChart].store) {
            return;
        }

        useCache = useCache && !this.charts[this.selectedChart].reload;

        this.charts[this.selectedChart].store.load(useCache).pipe(
            take(1),
            tap(() => {
                this.dataSource = this.charts[this.selectedChart]?.store?.getDataSource() ?? null;
                this.charts[this.selectedChart].reload = false;
            })
        ).subscribe();
    }
}
