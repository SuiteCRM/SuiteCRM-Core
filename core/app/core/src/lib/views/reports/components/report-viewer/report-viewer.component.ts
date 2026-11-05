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

import {ChangeDetectorRef, Component, OnDestroy, OnInit, QueryList, ViewChildren} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subject, Subscription} from 'rxjs';
import {take, takeUntil} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../../containers/widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {ReportChartComponent} from '../report-chart/report-chart.component';
import {ReportTableComponent} from '../report-table/report-table.component';
import {ReportViewerState, ShownReport} from '../../services/report-viewer.state';
import {ReportGroup, ReportPage, ReportParameter, ReportParameterValue, ReportTable} from '../../report.model';

/**
 * The report of a report page (record view of a report, bottom widget
 * `report-viewer`), as the legacy report page shows it: the charts (graphs_per_row a
 * row), the parameters of the report with "Update Parameters", and the report: its table,
 * or a table per value of the grouping field, each with its pages. The report runs on the
 * backend from the data of the report: legacy query, parameters, pages and totals
 * (ReportViewerHandler).
 *
 * The parameter values the report was run with, and its charts as images, are kept for the
 * Export, Download PDF and Add To Target List actions of the page (ReportViewerState).
 */
@Component({
    selector: 'scrm-report-viewer',
    standalone: true,
    imports: [CommonModule, FormsModule, ReportChartComponent, ReportTableComponent],
    templateUrl: './report-viewer.component.html'
})
export class ReportViewerComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    @ViewChildren(ReportChartComponent) charts: QueryList<ReportChartComponent>;

    report: ReportPage | null = null;
    // Values of the parameter inputs, by parameter key.
    values: { [key: string]: ReportParameterValue } = {};
    loading = false;
    error = false;
    showParameters = true;
    showReport = true;
    // Collapsed groups, by index; the table being paged.
    collapsed: { [index: number]: boolean } = {};
    paging: ReportTable | null = null;

    protected recordId = '';
    protected shown: ShownReport | null = null;
    protected load$: Subscription | null = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected state: ReportViewerState,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    ngOnInit(): void {
        super.ngOnInit();

        this.open(this.context?.id);
        this.context$?.pipe(takeUntil(this.destroy$)).subscribe(context => this.open(context?.id));
    }

    ngOnDestroy(): void {
        this.destroy$.next();
        this.destroy$.complete();
        this.load$?.unsubscribe();
        this.state.clear(this.recordId, this.shown);
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }

    trackParameter(index: number, parameter: ReportParameter): string {
        return parameter.key;
    }

    /** Runs the report again with the values of the parameter inputs. */
    update(): void {
        this.load({...this.values});
    }

    toggleGroup(index: number): void {
        this.collapsed[index] = !this.collapsed[index];
    }

    /** Another page of a table (of the report, or of a group). */
    page(table: ReportTable, offset: number): void {
        if (!this.report || this.paging) {
            return;
        }

        this.paging = table;
        this.processService.submit('report-viewer', {
            action: 'page',
            id: this.recordId,
            parameters: this.shown?.parameters ?? {},
            group: table.group ?? '',
            offset
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as ReportTable;
                if (process?.status === 'success' && data?.columns) {
                    // A new table object, so that its cells are built again; a group keeps its label.
                    this.replaceTable(table, {...table, ...data});
                }
                this.paging = null;
                this.cdr.detectChanges();
            },
            error: () => {
                this.paging = null;
                this.cdr.detectChanges();
            }
        });
    }

    protected replaceTable(table: ReportTable, page: ReportTable): void {
        if (this.report.table === table) {
            this.report.table = page;
            return;
        }

        const index = this.report.groups.indexOf(table as ReportGroup);
        if (index >= 0) {
            this.report.groups[index] = page as ReportGroup;
        }
    }

    protected open(id?: string): void {
        if (!id || id === this.recordId) {
            return;
        }

        this.state.clear(this.recordId, this.shown);
        this.recordId = id;
        this.report = null;
        this.values = {};
        this.load({});
    }

    protected load(parameters: { [key: string]: ReportParameterValue }): void {
        this.load$?.unsubscribe();
        this.loading = true;
        this.error = false;
        const id = this.recordId;
        this.load$ = this.processService.submit('report-viewer', {action: 'load', id, parameters})
            .pipe(take(1))
            .subscribe({
                next: (process: Process) => {
                    const report = process?.data as ReportPage;
                    this.loading = false;
                    if (process?.status !== 'success' || !report?.id) {
                        this.error = true;
                        this.cdr.detectChanges();
                        return;
                    }

                    this.report = report;
                    this.collapsed = {};
                    this.values = {};
                    report.parameters.forEach(parameter => {
                        this.values[parameter.key] = Array.isArray(parameter.value) ? [...parameter.value] : parameter.value;
                    });
                    this.keep(id, parameters, report);
                    // The response can come outside the app zone.
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.cdr.detectChanges();
                }
            });
    }

    /** The values the report was run with, and its charts, for the actions of the page. */
    protected keep(id: string, parameters: { [key: string]: ReportParameterValue }, report: ReportPage): void {
        this.state.clear(id, this.shown);
        this.shown = {
            parameters,
            chartImages: () => this.chartsImage(report.graphsPerRow)
        };
        this.state.set(id, this.shown);
    }

    /**
     * The charts of the page as one PNG image, graphs_per_row charts a row as on the page (the
     * PDF engine does not lay out images side by side); none without charts.
     */
    protected async chartsImage(perRow: number): Promise<string[]> {
        const urls = (await Promise.all((this.charts?.toArray() ?? []).map(chart => chart.toImage()))).filter(url => !!url);
        if (!urls.length) {
            return [];
        }

        const images = await Promise.all(urls.map(url => new Promise<HTMLImageElement>((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = url;
        })));
        const columns = Math.max(1, Math.min(perRow, images.length));
        const width = Math.max(...images.map(image => image.width));
        const height = Math.max(...images.map(image => image.height));
        const canvas = document.createElement('canvas');
        canvas.width = width * columns;
        canvas.height = height * Math.ceil(images.length / columns);
        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        images.forEach((image, index) => {
            context.drawImage(image, (index % columns) * width, Math.floor(index / columns) * height, image.width, image.height);
        });

        return [canvas.toDataURL('image/png')];
    }
}
