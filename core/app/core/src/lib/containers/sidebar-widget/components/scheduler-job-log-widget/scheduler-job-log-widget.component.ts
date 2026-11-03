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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {Subject, Subscription} from 'rxjs';
import {take, takeUntil} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';

export interface JobLogRow {
    id: string;
    name: string;
    status: string;
    resolution: string;
    resolutionKey: string;
    executeTime: string;
    dateModified: string;
    message: string;
}

export interface JobLogPage {
    rows: JobLogRow[];
    total: number;
    offset: number;
    pageSize: number;
    sort: string;
    direction: 'asc' | 'desc';
    labels: { [key: string]: string };
}

/**
 * The job log of a scheduler (record view, bottom widget `scheduler-job-log`), as the Job Log
 * subpanel of the legacy page: its jobs (name, status, result, execute time, date modified),
 * newest first, sorted by a column and paged; the message of a job is shown on its line. Data
 * from the `scheduler-job-log` process (SchedulerJobLogProcessHandler).
 */
@Component({
    selector: 'scrm-scheduler-job-log-widget',
    templateUrl: './scheduler-job-log-widget.component.html',
    styleUrls: []
})
export class SchedulerJobLogWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'scheduler-job-log';

    readonly columns = [
        {key: 'name', label: 'LBL_NAME', sortable: false},
        {key: 'status', label: 'LBL_STATUS', sortable: true},
        {key: 'resolution', label: 'LBL_RESOLUTION', sortable: true},
        {key: 'execute_time', label: 'LBL_EXECUTE_TIME', sortable: true},
        {key: 'date_modified', label: 'LBL_DATE_MODIFIED', sortable: true},
    ];

    data: JobLogPage = null;
    loading = false;
    error = false;

    protected schedulerId = '';
    protected run$: Subscription = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected processService: ProcessService,
        public language: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    get pageEnd(): number {
        return Math.min((this.data?.offset ?? 0) + (this.data?.pageSize ?? 0), this.data?.total ?? 0);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.open(this.context?.id);
        this.context$?.pipe(takeUntil(this.destroy$)).subscribe(context => this.open(context?.id));
    }

    ngOnDestroy(): void {
        this.destroy$.next();
        this.destroy$.complete();
        this.run$?.unsubscribe();
    }

    /**
     * A label of the widget (job log strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || key;
    }

    sortBy(column: string): void {
        const direction = this.data?.sort === column && this.data?.direction === 'desc' ? 'asc' : 'desc';
        this.load(0, column, direction);
    }

    page(offset: number): void {
        this.load(offset, this.data?.sort, this.data?.direction);
    }

    trackRow(index: number, row: JobLogRow): string {
        return row.id;
    }

    protected open(id: string): void {
        if (!id || id === this.schedulerId) {
            return;
        }
        this.schedulerId = id;
        this.data = null;
        this.load(0);
    }

    protected load(offset: number, sort = 'execute_time', direction = 'desc'): void {
        this.run$?.unsubscribe();
        this.loading = true;
        this.error = false;
        this.cdr.markForCheck();

        const options = {id: this.schedulerId, offset, sort, direction};
        this.run$ = this.processService.submit(SchedulerJobLogWidgetComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as JobLogPage;
                this.error = process?.status !== 'success' || !data?.rows;
                if (!this.error) {
                    this.data = data;
                }
                this.cdr.markForCheck();
            },
            error: () => {
                this.loading = false;
                this.error = true;
                this.cdr.markForCheck();
            }
        });
    }
}
