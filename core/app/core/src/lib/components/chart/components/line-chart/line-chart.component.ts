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

import {Component, ElementRef, OnDestroy, OnInit} from '@angular/core';
import {MultiSeries} from '../../../../common/containers/chart/chart.model';
import {isFalse} from '../../../../common/utils/value-utils';
import {BaseChartComponent} from '../base-chart/base-chart.component';
import {ScreenSizeObserverService} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";
import {LanguageStore} from "../../../../store/language/language.store";

@Component({
    selector: 'scrm-line-chart',
    templateUrl: './line-chart.component.html',
    styleUrls: []
})
export class LineChartComponent extends BaseChartComponent implements OnInit, OnDestroy {

    results: MultiSeries;
    scheme: string;
    gradient: boolean;
    xAxis: boolean;
    yAxis: boolean;
    legend: boolean;
    xScaleMin: number | string;
    xScaleMax: number | string;
    xAxisTicks: any;
    showXAxisLabel: boolean;
    showYAxisLabel: boolean;
    xAxisLabel: string;
    legendTitle: string;
    yAxisLabel: string;
    yAxisTickFormatting: Function;
    xAxisTickFormatting: Function;
    tooltipDisabled: boolean;

    constructor(
        protected elementRef: ElementRef,
        protected screenSize: ScreenSizeObserverService,
        protected language: LanguageStore
    ) {
        super(elementRef, screenSize);
    }

    ngOnInit(): void {
        if (this.dataSource.options.height) {
            this.height = this.dataSource.options.height;
        }

        this.initResizeListener();

        this.subs.push(this.dataSource.getResults().subscribe(value => {
            this.results = value.multiSeries;
            this.calculateView();
        }));

        this.scheme = this.getScheme();
        this.gradient = this.getGradient();
        this.xAxis = this.getXAxis();
        this.yAxis = this.getYAxis();
        this.legend = this.getLegend();
        this.xScaleMin = this.getXScaleMin();
        this.xScaleMax = this.getXScaleMax();
        this.xAxisTicks = this.getXAxisTicks();
        this.showXAxisLabel = this.getShowXAxisLabel();
        this.showYAxisLabel = this.getShowYAxisLabel();
        this.legendTitle = this.getLegendTitle();
        this.xAxisLabel = this.getXAxisLabel();
        this.yAxisLabel = this.getYAxisLabel();
        this.yAxisTickFormatting = this.getYAxisTickFormatting();
        this.xAxisTickFormatting = this.getXAxisTickFormatting();
        this.tooltipDisabled = this.getTooltipDisabled();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getScheme(): string {
        return this.dataSource.options.scheme || 'picnic';
    }

    getGradient(): boolean {
        return this.dataSource.options.gradient || false;
    }

    getXAxis(): boolean {
        return !isFalse(this.dataSource.options.xAxis);
    }

    getYAxis(): boolean {
        return !isFalse(this.dataSource.options.yAxis);
    }

    getLegend(): boolean {
        return !isFalse(this.dataSource.options.legend);
    }

    getLegendTitle(): string {
        return this.language.getFieldLabel(this.dataSource.options.legendTitle) || this.language.getFieldLabel('LBL_LEGEND');
    }

    getXScaleMin(): number | string {
        return this.dataSource.options.xScaleMin || null;
    }

    getXScaleMax(): number | string {
        return this.dataSource.options.xScaleMax || null;
    }

    getXAxisTicks(): any {
        return this.dataSource.options.xAxisTicks || null;
    }

    getShowXAxisLabel(): boolean {
        return !isFalse(this.dataSource.options.showXAxisLabel);
    }

    getShowYAxisLabel(): boolean {
        return this.dataSource.options.showYAxisLabel || false;
    }

    getXAxisLabel(): string {
        return this.dataSource.options.xAxisLabel || '';
    }

    getYAxisLabel(): string {
        return this.dataSource.options.yAxisLabel || '';
    }

    getYAxisTickFormatting(): Function {
        if (!this.dataSource.options.yAxisTickFormatting) {
            return null;
        }
        return this.dataSource.tickFormatting || null;
    }

    getXAxisTickFormatting(): Function {
        if (!this.dataSource.options.xAxisTickFormatting) {
            return null;
        }
        return this.dataSource.tickFormatting || null;
    }

    getTooltipDisabled(): boolean {
        return this.dataSource.options.tooltipDisabled || false;
    }
}
