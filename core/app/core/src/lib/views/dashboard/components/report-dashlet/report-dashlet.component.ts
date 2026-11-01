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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {FieldModule} from '../../../../fields/field.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DashletData, DashletReport} from '../../dashboard.model';
import {ReportRowsService, ReportCells} from '../../../reports/services/report-rows.service';

/**
 * A report dashlet (AOR Reports) of the dashboard: the table
 * of its report as the legacy dashlet shows it (same query, parameters and
 * totals, see DashboardHandler::loadReport), with its pages. The values are
 * shown with the SuiteCRM 8 field components, as on the report page
 * (ReportRowsService). Grouped reports and reports with charts stay in a
 * legacy frame.
 */
@Component({
    selector: 'scrm-report-dashlet',
    standalone: true,
    imports: [CommonModule, FieldModule],
    template: `
        <p *ngIf="error" class="dashboard-dashlet-message dashboard-error">{{ label('LBL_DASHBOARD_DASHLET_ERROR') }}</p>
        <p *ngIf="!error && !report && loading" class="dashboard-dashlet-message">{{ label('LBL_LOADING') }}</p>
        <p *ngIf="!error && !report && !loading" class="dashboard-dashlet-message">{{ label('LBL_DASHBOARD_CHOOSE_REPORT') }}</p>
        <ng-container *ngIf="!error && report">
            <div class="dashboard-table-wrap" [class.is-loading]="loading">
                <table class="dashboard-table dashboard-report">
                    <thead>
                    <tr>
                        <th *ngFor="let column of report.columns" scope="col">{{ column.label }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr *ngFor="let row of cells.rows">
                        <td *ngFor="let cell of row" [class.dashboard-name-cell]="cell.field.metadata?.link">
                            <scrm-field [mode]="'list'" [type]="cell.field.type" [field]="cell.field" [record]="cell.record"></scrm-field>
                        </td>
                    </tr>
                    </tbody>
                    <tfoot *ngIf="cells.totals.length">
                    <tr>
                        <td *ngFor="let total of cells.totals">
                            <scrm-field *ngIf="total" [mode]="'list'" [type]="total.field.type" [field]="total.field"
                                        [record]="total.record"></scrm-field>
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <p *ngIf="!report.rows.length" class="dashboard-dashlet-message">{{ label('LBL_DASHBOARD_NO_DATA') }}</p>
            </div>
            <div *ngIf="report.pager && report.pager.total > report.rows.length" class="dashboard-pager">
                <span>{{ range() }}</span>
                <button type="button" class="dashboard-page-btn" [disabled]="report.pager.prev === null || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_PREV')" (click)="page(report.pager.prev)">‹</button>
                <button type="button" class="dashboard-page-btn" [disabled]="report.pager.next === null || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_NEXT')" (click)="page(report.pager.next)">›</button>
            </div>
        </ng-container>
    `
})
export class ReportDashletComponent implements OnChanges, OnDestroy {

    @Input() dashlet: DashletData;

    report: DashletReport | null = null;
    cells: ReportCells = {rows: [], totals: []};
    loading = false;
    error = false;

    protected offset = 0;
    protected sub: Subscription | null = null;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected reportRows: ReportRowsService,
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

    range(): string {
        const pager = this.report.pager;

        return this.label('LBL_DASHBOARD_RANGE')
            .replace('%s', pager.start.toLocaleString('en-US'))
            .replace('%s', pager.end.toLocaleString('en-US'))
            .replace('%s', pager.total.toLocaleString('en-US'));
    }

    page(offset: number | null): void {
        if (offset !== null && offset >= 0) {
            this.load(offset);
        }
    }

    /** Reloads the current page (refresh button of the dashlet). */
    refresh(): void {
        this.load(this.offset);
    }

    protected load(offset: number): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.sub = this.processService.submit('dashboard', {action: 'dashlet', id: this.dashlet.id, offset})
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
                    this.offset = offset;
                    // The error of a previous load does not stay.
                    this.dashlet = {...this.dashlet, ...data, error: data.error, deferred: false};
                    this.render(this.dashlet);
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.cdr.detectChanges();
                }
            });
    }

    protected render(dashlet: DashletData): void {
        this.loading = false;
        this.error = !!dashlet?.error;
        this.report = dashlet?.report ?? null;
        this.cells = this.reportRows.build(this.report);
        if (dashlet?.deferred && !this.error) {
            // The content is loaded here, after the dashboard.
            this.load(0);
            return;
        }
        this.cdr.detectChanges();
    }
}
