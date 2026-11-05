/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {ChangeDetectorRef, Component, Input, OnChanges, OnDestroy} from '@angular/core';
import {CommonModule} from '@angular/common';
import {Router} from '@angular/router';
import {ColorHelper, NgxChartsModule, ScaleType} from '@swimlane/ngx-charts';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DashletChart, DashletChartItem, DashletData} from '../../dashboard.model';

/**
 * A chart dashlet of the dashboard, drawn with the
 * charts of SuiteCRM 8 (ngx-charts, "picnic" colours):
 * - All Opportunities by Lead Source: pie;
 * - All Opportunities By Lead Source By Outcome: stacked horizontal bars;
 * - Outcome by Month: stacked vertical bars, a bar per closing month;
 * - Pipeline By Sales Stage and My Pipeline By Sales Stage: horizontal bars, in
 *   sales stage order (the legacy funnel);
 * - Campaign ROI: vertical bars.
 *
 * The figures come from the legacy dashlet query (see DashboardHandler::loadChart).
 * As on the legacy charts, a click on a slice or a bar opens the opportunities
 * list filtered on it.
 */
@Component({
    selector: 'scrm-chart-dashlet',
    standalone: true,
    imports: [CommonModule, NgxChartsModule],
    template: `
        <p *ngIf="error" class="dashboard-dashlet-message dashboard-error">{{ label('LBL_DASHBOARD_DASHLET_ERROR') }}</p>
        <p *ngIf="!error && isEmpty" class="dashboard-dashlet-message">{{ chart?.empty || label('LBL_DASHBOARD_NO_DATA') }}</p>
        <ng-container *ngIf="!error && !isEmpty">
            <div class="dashboard-chart-body" [class.is-loading]="loading" [class.is-clickable]="!!chart.link"
                 [class.dashboard-chart-pie]="chart.type === 'pie'">
                <!-- ngx-charts sizes the chart on this box: no padding on it. -->
                <div class="dashboard-chart" [style.height.px]="height">
                    <ngx-charts-pie-chart *ngIf="chart.type === 'pie'"
                                          [results]="series"
                                          [scheme]="scheme"
                                          [animations]="false"
                                          [legend]="false"
                                          [labels]="false"
                                          [tooltipText]="pieTooltip"
                                          (select)="open($event)">
                    </ngx-charts-pie-chart>
                    <ngx-charts-bar-horizontal-stacked *ngIf="chart.type === 'stacked'"
                                                       [results]="groups"
                                                       [scheme]="scheme"
                                                       [animations]="false"
                                                       [xAxis]="true"
                                                       [yAxis]="true"
                                                       [legend]="false"
                                                       [trimYAxisTicks]="true"
                                                       [maxYAxisTickLength]="22"
                                                       [xAxisTickFormatting]="format"
                                                       [noBarWhenZero]="true"
                                                       (select)="open($event)">
                        <ng-template #tooltipTemplate let-model="model">
                            <div>{{ model.series }} · {{ model.name }}</div>
                            <div>{{ format(model.value) }}</div>
                        </ng-template>
                    </ngx-charts-bar-horizontal-stacked>
                    <ngx-charts-bar-vertical-stacked *ngIf="chart.type === 'stacked-vertical'"
                                                     [results]="groups"
                                                     [scheme]="scheme"
                                                     [animations]="false"
                                                     [xAxis]="true"
                                                     [yAxis]="true"
                                                     [legend]="false"
                                                     [yAxisTickFormatting]="format"
                                                     [showDataLabel]="true"
                                                     [dataLabelFormatting]="format"
                                                     [noBarWhenZero]="true"
                                                     (select)="open($event)">
                        <ng-template #tooltipTemplate let-model="model">
                            <div>{{ model.series }} · {{ model.name }}</div>
                            <div>{{ format(model.value) }}</div>
                        </ng-template>
                    </ngx-charts-bar-vertical-stacked>
                    <ngx-charts-bar-horizontal *ngIf="chart.type === 'funnel'"
                                               [results]="series"
                                               [scheme]="scheme"
                                               [animations]="false"
                                               [xAxis]="true"
                                               [yAxis]="true"
                                               [trimYAxisTicks]="true"
                                               [maxYAxisTickLength]="22"
                                               [xAxisTickFormatting]="format"
                                               [showDataLabel]="true"
                                               [dataLabelFormatting]="format"
                                               [noBarWhenZero]="true"
                                               (select)="open($event)">
                        <ng-template #tooltipTemplate let-model="model">
                            <div>{{ model.name }}</div>
                            <div>{{ format(model.value) }}</div>
                        </ng-template>
                    </ngx-charts-bar-horizontal>
                    <ngx-charts-bar-vertical *ngIf="chart.type === 'bar'"
                                             [results]="series"
                                             [scheme]="scheme"
                                             [animations]="false"
                                             [xAxis]="true"
                                             [yAxis]="true"
                                             [yAxisTickFormatting]="format"
                                             [showDataLabel]="true"
                                             [dataLabelFormatting]="format"
                                             [noBarWhenZero]="true">
                        <ng-template #tooltipTemplate let-model="model">
                            <div>{{ model.name }}</div>
                            <div>{{ format(model.value) }}</div>
                        </ng-template>
                    </ngx-charts-bar-vertical>
                </div>
                <!-- Legend with the full names (and the amounts of the pie, as the legacy key). -->
                <ul *ngIf="legend.length" class="dashboard-legend">
                    <li *ngFor="let item of legend">
                        <button *ngIf="chart.type === 'pie'; else legendText" type="button" class="dashboard-legend-item"
                                (click)="open({name: item.name})">
                            <span class="dashboard-legend-color" [style.background-color]="item.color"></span>
                            <span class="dashboard-legend-name">{{ item.name }}</span>
                            <span class="dashboard-legend-value">{{ format(item.value) }}</span>
                        </button>
                        <ng-template #legendText>
                            <span class="dashboard-legend-item">
                                <span class="dashboard-legend-color" [style.background-color]="item.color"></span>
                                <span class="dashboard-legend-name">{{ item.name }}</span>
                            </span>
                        </ng-template>
                    </li>
                </ul>
            </div>
            <p *ngIf="chart.subtitle" class="dashboard-chart-caption">{{ chart.subtitle }}</p>
        </ng-container>
    `
})
export class ChartDashletComponent implements OnChanges, OnDestroy {

    @Input() dashlet: DashletData;

    chart: DashletChart | null = null;
    series: { name: string, value: number }[] = [];
    groups: { name: string, series: { name: string, value: number }[] }[] = [];
    legend: { name: string, value: number, color: string }[] = [];
    loading = false;
    error = false;

    readonly scheme = 'picnic';

    protected sub: Subscription | null = null;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected router: Router,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(): void {
        this.render(this.dashlet);
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    get isEmpty(): boolean {
        return !this.chart || (!this.series.length && !this.groups.length);
    }

    /** Groups of bars, by sales stage: lead sources or closing months. */
    get isStacked(): boolean {
        return this.chart?.type === 'stacked' || this.chart?.type === 'stacked-vertical';
    }

    /** Taller charts for the long lists (lead sources by outcome, sales stages). */
    get height(): number {
        const rows = this.chart?.type === 'stacked' ? this.groups.length : this.series.length;
        if (this.chart?.type === 'stacked' || this.chart?.type === 'funnel') {
            return Math.max(260, Math.min(560, 120 + rows * 30));
        }

        return 300;
    }

    /** Amount as on the legacy charts: currency symbol, thousands unit ($7,757K). */
    format = (value: number): string => {
        const amount = Math.round(Number(value) || 0).toLocaleString('en-US');

        return `${this.chart?.currency ?? ''}${amount}${this.chart?.unit ?? ''}`;
    };

    pieTooltip = (entry: { data: { name: string, value: number } }): string => {
        const name = this.escape(entry?.data?.name ?? '');

        return `<span class="tooltip-label">${name}</span><span class="tooltip-val">${this.escape(this.format(entry?.data?.value))}</span>`;
    };

    /** Reloads the chart (refresh button of the dashlet). */
    refresh(): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.sub = this.processService.submit('dashboard', {action: 'dashlet', id: this.dashlet.id, offset: 0})
            .pipe(take(1))
            .subscribe({
                next: (process: Process) => {
                    const data = process?.data as DashletData;
                    if (process?.status !== 'success' || !data || (data as any).error === true) {
                        this.loading = false;
                        this.error = true;
                        this.cdr.detectChanges();
                        return;
                    }
                    // The error of a previous load does not stay.
                    this.dashlet = {...this.dashlet, ...data, error: data.error};
                    this.render(this.dashlet);
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.cdr.detectChanges();
                }
            });
    }

    /** The opportunities list filtered on the clicked slice or bar, as the legacy charts. */
    open(event: any): void {
        const link = this.chart?.link;
        if (!link) {
            return;
        }

        const name = typeof event === 'string' ? event : event?.name;
        const groupName = typeof event === 'object' ? event?.series : '';
        const params: { [key: string]: string | string[] } = {query: 'true', searchFormTab: 'advanced_search', ...link.params};

        if (this.isStacked) {
            const group = this.chart.groups.find(item => item.name === groupName);
            const item = group?.series.find(entry => entry.name === name);
            if (!group || !item) {
                return;
            }
            params[link.group] = group.key;
            params[link.item] = item.key;
        } else {
            const item = this.chart.series.find(entry => entry.name === name);
            if (!item) {
                return;
            }
            params[link.item] = item.key;
        }

        this.router.navigate(['/opportunities'], {queryParams: params});
    }

    protected render(dashlet: DashletData): void {
        this.loading = false;
        this.error = !!dashlet?.error;
        this.chart = dashlet?.chart ?? null;
        this.series = (this.chart?.series ?? []).map((item: DashletChartItem) => ({name: item.name, value: item.value}));
        this.groups = (this.chart?.groups ?? []).map(group => ({
            name: group.name,
            series: group.series.map(item => ({name: item.name, value: item.value}))
        }));
        this.legend = this.buildLegend();
        this.cdr.detectChanges();
    }

    /**
     * Legend of the pie (lead sources and amounts) and of the stacked bars (sales
     * stages), with the colours ngx-charts gives them: same scheme and domain
     * (the names, in order of appearance).
     */
    protected buildLegend(): { name: string, value: number, color: string }[] {
        let items: { name: string, value: number }[] = [];
        if (this.chart?.type === 'pie') {
            items = this.series;
        } else if (this.isStacked) {
            const names: string[] = [];
            this.groups.forEach(group => group.series.forEach(item => {
                if (!names.includes(item.name)) {
                    names.push(item.name);
                }
            }));
            items = names.map(name => ({name, value: 0}));
        }

        const colors = new ColorHelper(this.scheme, ScaleType.Ordinal, items.map(item => item.name), null);

        return items.map(item => ({...item, color: colors.getColor(item.name)}));
    }

    protected escape(text: string): string {
        return String(text).replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', '\'': '&#39;'
        }[char]));
    }
}
