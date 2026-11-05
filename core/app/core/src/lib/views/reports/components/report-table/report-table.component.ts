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

import {Component, EventEmitter, Input, OnChanges, Output} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FieldModule} from '../../../../fields/field.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {ReportTable} from '../../report.model';
import {ReportRowsService, ReportCells} from '../../services/report-rows.service';

/**
 * A table of the report page (the report, or a group of a grouped report) as the
 * legacy report shows it: columns, rows, totals and pages ("(1 - 20 of 54)", first,
 * previous, next and last page). The values are shown with the SuiteCRM 8 field components
 * (ReportRowsService); the linked columns open their record.
 */
@Component({
    selector: 'scrm-report-table',
    standalone: true,
    imports: [CommonModule, FieldModule],
    template: `
        <div *ngIf="table.pager && table.pager.total > table.rows.length" class="report-pager">
            <button type="button" class="report-page-btn" [disabled]="table.pager.first === null || loading"
                    [attr.aria-label]="label('LBL_REPORT_VIEWER_FIRST')" (click)="go(table.pager.first)">«</button>
            <button type="button" class="report-page-btn" [disabled]="table.pager.prev === null || loading"
                    [attr.aria-label]="label('LBL_REPORT_VIEWER_PREVIOUS')" (click)="go(table.pager.prev)">‹</button>
            <span class="report-range">{{ range() }}</span>
            <button type="button" class="report-page-btn" [disabled]="table.pager.next === null || loading"
                    [attr.aria-label]="label('LBL_REPORT_VIEWER_NEXT')" (click)="go(table.pager.next)">›</button>
            <button type="button" class="report-page-btn" [disabled]="table.pager.last === null || loading"
                    [attr.aria-label]="label('LBL_REPORT_VIEWER_LAST')" (click)="go(table.pager.last)">»</button>
        </div>
        <div class="report-table-wrap" [class.is-loading]="loading">
            <table class="report-table">
                <thead>
                <tr>
                    <th *ngFor="let column of table.columns" scope="col">{{ column.label }}</th>
                </tr>
                </thead>
                <tbody>
                <tr *ngFor="let row of cells.rows">
                    <td *ngFor="let cell of row">
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
            <p *ngIf="!table.rows.length" class="report-message">{{ label('LBL_REPORT_VIEWER_NO_DATA') }}</p>
        </div>
    `
})
export class ReportTableComponent implements OnChanges {

    @Input() table: ReportTable;
    @Input() loading = false;
    // Offset of the page to show.
    @Output() page = new EventEmitter<number>();

    cells: ReportCells = {rows: [], totals: []};

    constructor(protected language: LanguageStore, protected reportRows: ReportRowsService) {
    }

    ngOnChanges(): void {
        this.cells = this.reportRows.build(this.table);
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }

    range(): string {
        const pager = this.table.pager;

        return this.label('LBL_REPORT_VIEWER_RANGE')
            .replace('%s', String(pager.start))
            .replace('%s', String(pager.end))
            .replace('%s', String(pager.total));
    }

    go(offset: number | null): void {
        if (offset !== null && offset >= 0) {
            this.page.emit(offset);
        }
    }
}
