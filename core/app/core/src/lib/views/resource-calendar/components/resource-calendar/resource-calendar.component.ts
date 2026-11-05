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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';

export type ResourceCalendarType = 'weekly' | 'monthly' | 'quarterly';

export interface ResourceCalendarOption {
    value: string;
    label: string;
}

export interface ResourceCalendarColumn {
    start: string;
    end: string;
    label: string;
    sub: string;
    weekend: boolean;
    today: boolean;
}

export interface ResourceCalendarTask {
    id: string;
    name: string;
    status: string;
    percentComplete: number;
    start: string;
    end: string;
    duration: string;
    projectId: string;
    projectName: string;
}

export interface ResourceCalendarResource {
    id: string;
    module: 'users' | 'contacts';
    name: string;
    tasks: ResourceCalendarTask[];
}

export interface ResourceCalendarData {
    type: ResourceCalendarType;
    start: string;
    end: string;
    previous: string;
    next: string;
    headers: { label: string; span: number }[][];
    columns: ResourceCalendarColumn[];
    resources: ResourceCalendarResource[];
    filters: { projects: string[]; users: string[]; contacts: string[] };
    options: { projects: ResourceCalendarOption[]; users: ResourceCalendarOption[]; contacts: ResourceCalendarOption[] };
    labels?: { [module: string]: { [key: string]: string } };
}

/**
 * Resource calendar of Projects (legacy Project ResourceList): the users and
 * contacts that are resources of the projects, with their project tasks per
 * day (weekly), week (monthly) or month (quarterly) from the week of the start
 * date. A busy period is marked, and darker when tasks overlap; its tasks are
 * listed on hover and when it is clicked. The filters are the legacy ones:
 * projects, users and contacts ("None" leaves the users or the contacts out),
 * chart type and start date.
 *
 * Routes resource-calendar (Resource Calendar tab) and project/resource-list
 * (Projects menu), data from the `project-resource-calendar` process.
 */
@Component({
    selector: 'scrm-resource-calendar',
    templateUrl: './resource-calendar.component.html',
    styleUrls: [],
})
export class ResourceCalendarComponent implements OnInit, OnDestroy {

    static readonly NONE = 'none';

    readonly types: { value: ResourceCalendarType; label: string }[] = [
        {value: 'weekly', label: 'LBL_CHART_WEEKLY'},
        {value: 'monthly', label: 'LBL_CHART_MONTHLY'},
        {value: 'quarterly', label: 'LBL_CHART_QUARTERLY'},
    ];

    type: ResourceCalendarType = 'weekly';
    start = '';
    projects: string[] = [];
    users: string[] = [];
    contacts: string[] = [];

    data: ResourceCalendarData = null;
    labels: { [module: string]: { [key: string]: string } } = null;
    cells: ResourceCalendarTask[][][] = [];
    selected: { resource: ResourceCalendarResource; column: ResourceCalendarColumn; tasks: ResourceCalendarTask[] } = null;
    loading = false;
    error = '';

    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected dateFormatter: DateFormatter,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.load('');
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of Projects (or of the application), or of another module, without the legacy
     * final colon. The process sends the module labels: the front end only has the labels of
     * the modules shown in the navigation. Nothing until they are loaded.
     */
    label(key: string, module = 'project'): string {
        const label = this.labels?.[module]?.[key] || this.language.getFieldLabel(key, module) || (this.labels ? key : '');

        return label.replace(/:\s*$/, '');
    }

    /** The users or contacts of the filter, after "None". */
    resourceOptions(module: 'users' | 'contacts'): ResourceCalendarOption[] {
        const none = {value: ResourceCalendarComponent.NONE, label: this.label('LBL_NONE')};

        return [none, ...(this.data?.options?.[module] ?? [])];
    }

    /** First column of a header row: what the row counts. */
    rowLabel(row: number): string {
        const labels = {
            weekly: ['LBL_RESOURCE_CHART_WEEK', ''],
            monthly: ['LBL_RESOURCE_CHART_MONTH', ''],
            quarterly: ['LBL_RESOURCE_CHART_QUARTER', ''],
        };
        const key = labels[this.data?.type ?? 'weekly'][row] ?? '';

        return key ? this.label(key) : '';
    }

    columnLabel(): string {
        const keys = {weekly: 'LBL_RESOURCE_CHART_DAY', monthly: 'LBL_RESOURCE_CHART_WEEK', quarterly: 'LBL_RESOURCE_CHART_MONTH'};

        return this.label(keys[this.data?.type ?? 'weekly']);
    }

    search(): void {
        this.load(this.start);
    }

    previous(): void {
        this.load(this.data?.previous ?? '');
    }

    next(): void {
        this.load(this.data?.next ?? '');
    }

    cellClass(resource: number, column: number): string {
        const count = this.cells[resource]?.[column]?.length ?? 0;
        if (!count) {
            return '';
        }

        return count > 1 ? 'resource-calendar-busy resource-calendar-overlap' : 'resource-calendar-busy';
    }

    /** Hover text of a busy period: its tasks, one per line. */
    cellTitle(resource: number, column: number): string {
        return (this.cells[resource]?.[column] ?? [])
            .map(task => task.projectName + ' — ' + task.name + (task.duration ? ' (' + task.duration + ')' : ''))
            .join('\n');
    }

    select(resource: number, column: number): void {
        const tasks = this.cells[resource]?.[column] ?? [];
        this.selected = tasks.length
            ? {resource: this.data.resources[resource], column: this.data.columns[column], tasks}
            : null;
    }

    isSelected(resource: number, column: number): boolean {
        return !!this.selected
            && this.selected.resource === this.data.resources[resource]
            && this.selected.column === this.data.columns[column];
    }

    /** A date (Y-m-d) in the user format. */
    date(value: string): string {
        return this.dateFormatter.toUserFormat(value);
    }

    period(column: ResourceCalendarColumn): string {
        return column.start === column.end ? this.date(column.start) : this.date(column.start) + ' – ' + this.date(column.end);
    }

    protected load(start: string): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.error = '';
        this.selected = null;

        this.sub = this.processService.submit('project-resource-calendar', {
            type: this.type,
            start,
            projects: this.projects,
            users: this.users,
            contacts: this.contacts,
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as ResourceCalendarData;
                this.loading = false;
                this.labels = data?.labels ?? this.labels ?? {};
                if (process?.status !== 'success' || !data?.columns) {
                    this.error = process?.messages?.[0] || 'LBL_GET_RECORD_LIST_ERROR';
                    this.cdr.detectChanges();
                    return;
                }
                this.data = data;
                this.start = data.start;
                this.cells = data.resources.map(resource => data.columns.map(column =>
                    resource.tasks.filter(task => task.start <= column.end && task.end >= column.start)
                ));
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.labels = this.labels ?? {};
                this.error = 'LBL_GET_RECORD_LIST_ERROR';
                this.cdr.detectChanges();
            }
        });
    }
}
