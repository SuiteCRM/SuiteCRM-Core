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
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {FieldModule} from '../../../../fields/field.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {DashletColumn, DashletData} from '../../dashboard.model';

interface DashletRow {
    record: Record;
    // "Close" action of a planned call or meeting the user may edit.
    close: boolean;
    // Invitation of the user: answer buttons, or the answer given.
    acceptButtons: boolean;
    acceptLabel: string;
}

/**
 * A list dashlet of the dashboard (My Accounts, My Calls...).
 *
 * The records come from the legacy dashlet query (filters, "my items",
 * invitees, sort and pages of the dashlet, see DashboardHandler) in the list
 * format, and are shown with the dashlet's columns and the standard field
 * components, as the list views. A new sort is kept for the dashlet, as the
 * legacy dashboard keeps it. Calls and meetings keep their "Close" and
 * "Accept / Tentative / Decline" columns.
 */
@Component({
    selector: 'scrm-list-dashlet',
    standalone: true,
    imports: [CommonModule, FieldModule],
    template: `
        <p *ngIf="error" class="dashboard-dashlet-message dashboard-error">{{ label('LBL_DASHBOARD_DASHLET_ERROR') }}</p>
        <ng-container *ngIf="!error">
            <div class="dashboard-table-wrap" [class.is-loading]="loading">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th *ngFor="let column of columns" scope="col" [attr.aria-sort]="ariaSort(column)"
                            [class.dashboard-action-cell]="isAction(column)">
                            <button *ngIf="column.sortable && !isAction(column); else plainHeader" type="button"
                                    class="dashboard-sort" [class.is-sorted]="isSorted(column)"
                                    [attr.aria-label]="label('LBL_DASHBOARD_SORT', column.labelText)"
                                    (click)="sortBy(column)">
                                {{ column.labelText }}<span class="dashboard-sort-icon" aria-hidden="true">{{ sortIcon(column) }}</span>
                            </button>
                            <ng-template #plainHeader><span>{{ column.labelText }}</span></ng-template>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr *ngFor="let row of rows">
                        <td *ngFor="let column of columns" [class.dashboard-action-cell]="isAction(column)"
                            [class.dashboard-name-cell]="column.link">
                            <ng-container [ngSwitch]="column.type">
                                <ng-container *ngSwitchCase="'dashlet-close'">
                                    <button *ngIf="row.close" type="button" class="dashboard-icon-btn"
                                            [title]="label('LBL_DASHBOARD_CLOSE')"
                                            [attr.aria-label]="label('LBL_DASHBOARD_CLOSE')"
                                            [disabled]="loading" (click)="close(row.record)">
                                        <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                            <path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </ng-container>
                                <ng-container *ngSwitchCase="'dashlet-accept'">
                                    <span *ngIf="row.acceptButtons" class="dashboard-accept">
                                        <button *ngFor="let answer of answers" type="button" class="dashboard-chip"
                                                [disabled]="loading" (click)="accept(row.record, answer.key)">{{ answer.label }}</button>
                                    </span>
                                    <span *ngIf="!row.acceptButtons && row.acceptLabel" class="dashboard-status">{{ row.acceptLabel }}</span>
                                </ng-container>
                                <scrm-field *ngSwitchDefault
                                            [mode]="'list'"
                                            [type]="column.type"
                                            [field]="row.record.fields[column.name]"
                                            [record]="row.record">
                                </scrm-field>
                            </ng-container>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p *ngIf="!rows.length" class="dashboard-dashlet-message">{{ label('LBL_DASHBOARD_NO_DATA') }}</p>
            </div>
            <div *ngIf="pager" class="dashboard-pager">
                <span>{{ pager.range }}</span>
                <button type="button" class="dashboard-page-btn" [disabled]="!pager.prev || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_PREV')" (click)="page(dashlet.offsets.prev)">‹</button>
                <button type="button" class="dashboard-page-btn" [disabled]="!pager.next || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_NEXT')" (click)="page(dashlet.offsets.next)">›</button>
            </div>
        </ng-container>
    `
})
export class ListDashletComponent implements OnChanges, OnDestroy {

    @Input() dashlet: DashletData;

    columns: DashletColumn[] = [];
    rows: DashletRow[] = [];
    pager: { range: string, prev: boolean, next: boolean } | null = null;
    answers: { key: string, label: string }[] = [];
    loading = false;
    error = false;

    protected static readonly ACTION_TYPES = ['dashlet-close', 'dashlet-accept'];

    protected sub: Subscription | null = null;

    constructor(
        protected processService: ProcessService,
        protected fieldManager: FieldManager,
        protected languageStore: LanguageStore,
        protected confirmation: ConfirmationModalService,
        protected messages: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(): void {
        this.render(this.dashlet);
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /** Label of the Home module strings (LBL_DASHBOARD_*), %s filled in order. */
    label(key: string, ...args: (string | number)[]): string {
        const text = this.languageStore.getFieldLabel(key, 'home') || key;
        let index = 0;

        return text.replace(/%[sd]/g, () => String(args[index++] ?? ''));
    }

    isAction(column: DashletColumn): boolean {
        return ListDashletComponent.ACTION_TYPES.includes(column.type);
    }

    isSorted(column: DashletColumn): boolean {
        return !!this.dashlet.sort?.orderBy && this.dashlet.sort.orderBy === (column.orderBy || column.name);
    }

    sortIcon(column: DashletColumn): string {
        if (!this.isSorted(column)) {
            return ' ↕';
        }

        return this.dashlet.sort.sortOrder === 'DESC' ? ' ▼' : ' ▲';
    }

    ariaSort(column: DashletColumn): string | null {
        if (!this.isSorted(column)) {
            return null;
        }

        return this.dashlet.sort.sortOrder === 'DESC' ? 'descending' : 'ascending';
    }

    /** Same column: reverse order; another column: ascending. */
    sortBy(column: DashletColumn): void {
        const orderBy = column.orderBy || column.name;
        const sortOrder = this.isSorted(column) && this.dashlet.sort.sortOrder !== 'DESC' ? 'DESC' : 'ASC';

        this.reload(0, {orderBy, sortOrder});
    }

    page(offset: number): void {
        if (offset >= 0) {
            this.reload(offset);
        }
    }

    /** Reloads the current page (refresh button of the dashlet). */
    refresh(): void {
        this.reload(this.dashlet.offsets?.current ?? 0);
    }

    close(record: Record): void {
        this.confirmation.showModal(['LBL_DASHBOARD_CLOSE_CONFIRM'], () => {
            this.runAction({action: 'close', module: this.dashlet.legacyModule, id: record.id});
        });
    }

    accept(record: Record, status: string): void {
        this.runAction({action: 'accept', module: this.dashlet.legacyModule, id: record.id, status});
    }

    protected runAction(options: { [key: string]: string }): void {
        this.loading = true;
        this.processService.submit('dashboard', options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status !== 'success' || !process.data?.done) {
                    this.messages.addDangerMessageByKey('LBL_ACTION_ERROR');
                }
                this.refresh();
            },
            error: () => {
                this.messages.addDangerMessageByKey('LBL_ACTION_ERROR');
                this.refresh();
            }
        });
    }

    protected reload(offset: number, sort: { orderBy: string, sortOrder: string } = null): void {
        this.sub?.unsubscribe();
        this.loading = true;

        const options: { [key: string]: any } = {action: 'dashlet', id: this.dashlet.id, offset};
        if (sort) {
            options.sort = sort;
        }

        this.sub = this.processService.submit('dashboard', options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as DashletData;
                this.loading = false;
                if (process?.status !== 'success' || !data || (data as any).error === true) {
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

    protected render(dashlet: DashletData): void {
        this.loading = false;
        this.error = !!dashlet?.error;
        this.columns = dashlet?.columns ?? [];

        const fieldColumns = this.columns.filter(column => !this.isAction(column));
        const statusLabels = this.languageStore.getAppListString('dom_meeting_accept_status') as { [key: string]: string } ?? {};
        const options = this.languageStore.getAppListString('dom_meeting_accept_options') as { [key: string]: string } ?? {};
        this.answers = ['accept', 'tentative', 'decline'].map(key => ({key, label: options[key] ?? key}));

        this.rows = (dashlet?.records ?? []).map(item => {
            const record: Record = {
                id: item.id,
                module: item.module,
                type: item.type,
                attributes: item.attributes,
                acls: item.acls
            } as Record;
            fieldColumns.forEach(column => this.fieldManager.addField(record, column, fieldColumns, this.languageStore));

            const planned = item.attributes?.status === 'Planned';
            const acceptStatus = String(item.attributes?.dashlet_accept_status ?? '');

            return {
                record,
                close: planned && (item.acls ?? []).includes('edit'),
                acceptButtons: planned && acceptStatus === 'none',
                acceptLabel: planned && acceptStatus && acceptStatus !== 'none' ? (statusLabels[acceptStatus] ?? acceptStatus) : ''
            };
        });

        const offsets = dashlet?.offsets;
        this.pager = offsets && offsets.total > this.rows.length ? {
            range: this.label(
                'LBL_DASHBOARD_RANGE',
                (offsets.current + 1).toLocaleString('en-US'),
                (offsets.current + this.rows.length).toLocaleString('en-US'),
                offsets.total.toLocaleString('en-US')
            ),
            prev: offsets.prev >= 0 && offsets.current > 0,
            next: offsets.next > 0
        } : null;

        this.cdr.detectChanges();
    }
}
