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
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface GanttTask {
    id: string;
    number: number;
    order: number;
    name: string;
    predecessor: string;
    relationType: string;
    start: string;
    finish: string;
    duration: number;
    unit: string;
    resource: string;
    percent: number;
    milestone: boolean;
    actualDuration: string;
    note: string;
}

interface GanttData {
    project: { id: string; start: string; end: string };
    /** A project template: tasks without dates, placed by day from their predecessors. */
    template?: boolean;
    tasks: GanttTask[];
    resources: { id: string; name: string; type: string }[];
    relationTypes: { value: string; label: string }[];
    canEdit: boolean;
    labels: { [key: string]: string };
}

interface TaskForm {
    taskId: string;
    name: string;
    milestone: boolean;
    predecessor: string;
    relationType: string;
    lag: number;
    start: string;
    duration: number;
    unit: string;
    resource: string;
    percent: number;
    actualDuration: string;
    note: string;
}

interface GanttDay {
    date: Date;
    day: number;
    letter: string;
    weekend: boolean;
}

/**
 * The Gantt chart of a project under its details (record view, bottom widget
 * `project-gantt`), as the legacy Project Gantt chart page, with the `project-gantt` process
 * (ProjectGantt):
 *  - the task table (number, name, predecessor, start, finish, duration, resource, % complete,
 *    milestone, actual duration), its rows dragged to change the order;
 *  - the chart: the days from the first task (30 days at least), a bar by task with its % complete,
 *    a diamond by milestone, and the arrows from a predecessor (finish to start, start to start);
 *  - Add New Task and the task name open the task form of the legacy chart (task or milestone,
 *    predecessor with its relation type and lag, start, duration and unit, resource, % complete,
 *    actual duration, notes); the end date is computed by the legacy save (business days).
 *
 * Also the Gantt chart of a project template (ProjectTemplateGanttWidgetComponent,
 * process `project-template-gantt`): the task templates have no dates, the chart counts the
 * days from the start of the project to create (a task after its predecessor, or with it for start
 * to start) and the task form has no start, lag nor actual duration.
 */
@Component({
    selector: 'scrm-project-gantt-widget',
    templateUrl: './project-gantt-widget.component.html',
    styleUrls: []
})
export class ProjectGanttWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'project-gantt';
    static readonly DAY_WIDTH = 24;
    static readonly ROW_HEIGHT = 34;

    data: GanttData = null;
    days: GanttDay[] = [];
    months: { label: string; span: number }[] = [];
    form: TaskForm = null;
    loading = false;
    dragging: GanttTask = null;
    /** The first and last day (from 0) of each task of a template, from the predecessors. */
    schedule: { [id: string]: { start: number; finish: number } } = {};

    protected processType = ProjectGanttWidgetComponent.PROCESS;
    protected projectId = '';
    protected subs: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected formatter: DataTypeFormatter,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    get dayWidth(): number {
        return ProjectGanttWidgetComponent.DAY_WIDTH;
    }

    get rowHeight(): number {
        return ProjectGanttWidgetComponent.ROW_HEIGHT;
    }

    get template(): boolean {
        return !!this.data?.template;
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.open(this.context?.id);
        if (this.context$) {
            this.subs.push(this.context$.subscribe(context => this.open(context?.id)));
        }
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || key;
    }

    date(value: string): string {
        return value ? this.formatter.toUserFormat('date', value) : '';
    }

    resourceName(id: string): string {
        return this.data?.resources?.find(resource => resource.id === id)?.name ?? '';
    }

    predecessorName(number: string): string {
        return this.data?.tasks?.find(task => `${task.number}` === `${number}`)?.name ?? number;
    }

    /** The start of a task in the table: its date, or its day for a template. */
    startText(task: GanttTask): string {
        return this.template ? `${this.startIndex(task) + 1}` : this.date(task.start);
    }

    finishText(task: GanttTask): string {
        return this.template ? `${this.finishIndex(task) + 1}` : this.date(task.finish);
    }

    /** The left offset of a task in the chart. */
    offset(task: GanttTask): number {
        const index = this.startIndex(task);
        return index < 0 ? 0 : index * this.dayWidth;
    }

    /** The width of the bar of a task (its days). */
    width(task: GanttTask): number {
        return Math.max(1, this.finishIndex(task) - this.startIndex(task) + 1) * this.dayWidth;
    }

    hasBar(task: GanttTask): boolean {
        return this.startIndex(task) >= 0;
    }

    /** The arrows from the predecessors (SVG paths), as the legacy chart draws them. */
    arrows(): string[] {
        const tasks = this.data?.tasks ?? [];
        const paths: string[] = [];
        tasks.forEach((task, row) => {
            if (!task.predecessor || !this.hasBar(task)) {
                return;
            }
            const fromRow = tasks.findIndex(other => `${other.number}` === `${task.predecessor}`);
            const from = tasks[fromRow];
            if (!from || !this.hasBar(from)) {
                return;
            }
            const y1 = fromRow * this.rowHeight + this.rowHeight / 2;
            const y2 = row * this.rowHeight + this.rowHeight / 2;
            const x2 = this.offset(task);
            const x1 = task.relationType === 'SS' ? this.offset(from) : this.offset(from) + this.width(from);
            const bend = (task.relationType === 'SS' ? Math.min(x1, x2) : x1) - 6;
            paths.push(`M ${x1} ${y1} H ${task.relationType === 'SS' ? bend : x1 + 6} V ${y2} H ${x2 - 2}`);
        });
        return paths;
    }

    add(): void {
        const today = new Date().toISOString().slice(0, 10);
        this.form = {
            taskId: '', name: '', milestone: false, predecessor: '', relationType: 'FS', lag: 0,
            start: this.data?.project?.start || today, duration: 1, unit: 'Days', resource: '', percent: 0,
            actualDuration: '', note: ''
        };
    }

    edit(task: GanttTask): void {
        if (!this.data?.canEdit) {
            return;
        }
        this.form = {
            taskId: task.id, name: task.name, milestone: task.milestone, predecessor: task.predecessor || '',
            relationType: task.relationType || 'FS', lag: 0, start: task.start, duration: task.duration || 1,
            unit: task.unit || 'Days', resource: task.resource, percent: task.percent, actualDuration: task.actualDuration,
            note: task.note
        };
    }

    /**
     * A predecessor sets the start: the day after its finish plus the lag (finish to start, legacy
     * get_end_date), or its start plus the lag (start to start).
     */
    predecessorChanged(): void {
        if (this.template) {
            return;
        }
        const from = this.data?.tasks?.find(task => `${task.number}` === `${this.form.predecessor}`);
        if (!from?.start) {
            return;
        }
        const base = new Date(((this.form.relationType === 'SS' ? from.start : (from.finish || from.start))) + 'T00:00:00Z');
        base.setUTCDate(base.getUTCDate() + (this.form.relationType === 'SS' ? 0 : 1) + (Number(this.form.lag) || 0));
        this.form.start = base.toISOString().slice(0, 10);
    }

    save(): void {
        if (!this.form?.name?.trim() || (!this.template && !this.form.start)) {
            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
            return;
        }
        const {lag, ...task} = this.form;
        this.run({action: 'save', task: {...task, duration: task.milestone ? 1 : task.duration}}, () => this.form = null);
    }

    delete(): void {
        const taskId = this.form?.taskId;
        if (!taskId) {
            return;
        }
        this.confirmation.showModal([this.label('LBL_GANTT_DELETE_TASK_CONFIRM')], () => {
            this.run({action: 'delete', taskId}, () => this.form = null);
        });
    }

    dragStart(task: GanttTask): void {
        this.dragging = task;
    }

    /** A row dropped on another one takes its place; the order of all the tasks is saved. */
    drop(target: GanttTask): void {
        const moved = this.dragging;
        this.dragging = null;
        if (!moved || moved === target || !this.data) {
            return;
        }
        const tasks = this.data.tasks.filter(task => task !== moved);
        tasks.splice(tasks.indexOf(target), 0, moved);
        this.data.tasks = tasks;
        const order: { [id: string]: number } = {};
        tasks.forEach((task, index) => order[task.id] = index + 1);
        this.run({action: 'order', order});
    }

    trackTask(index: number, task: GanttTask): string {
        return task.id;
    }

    protected open(id: string): void {
        if (id && id !== this.projectId) {
            this.projectId = id;
            this.run({action: 'load'});
        }
    }

    protected run(options: { [key: string]: any }, done: () => void = null): void {
        this.loading = true;
        this.cdr.detectChanges();
        this.subs.push(this.processService.submit(this.processType, {...options, project: this.projectId})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.loading = false;
                    if (process?.status !== 'success' || !process.data) {
                        const key = process?.messages?.[0] || 'LBL_ACTION_ERROR';
                        this.message.addDangerMessage(this.language.getAppString(key) || key);
                    } else {
                        this.data = process.data as GanttData;
                        this.buildDays();
                        done?.();
                    }
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                    this.cdr.detectChanges();
                }
            }));
    }

    /**
     * The days of the chart: from the first task start (or the project start) to the last task
     * finish, 30 days at least (legacy generate_chart), with their months.
     */
    protected buildDays(): void {
        if (this.template) {
            this.buildTemplateDays();
            return;
        }
        const starts = this.data.tasks.map(task => task.start).filter(Boolean);
        const finishes = this.data.tasks.map(task => task.finish || task.start).filter(Boolean);
        const first = (starts.length ? starts.sort()[0] : (this.data.project.start || new Date().toISOString().slice(0, 10)));
        const last = finishes.length ? finishes.sort()[finishes.length - 1] : first;
        const start = new Date(first + 'T00:00:00Z');
        let count = Math.round((new Date(last + 'T00:00:00Z').getTime() - start.getTime()) / 86400000) + 1;
        count = Math.max(count, 30);

        // getCurrentLanguage() returns the local storage value, which is {} (not a string) for a
        // user who never chose a language: the days would not be built and the task window would
        // stay open after a save.
        const language: unknown = this.language.getCurrentLanguage();
        const locale = typeof language === 'string' && language ? language.replace('_', '-') : 'en';
        this.days = [];
        this.months = [];
        for (let i = 0; i < count; i++) {
            const date = new Date(start.getTime() + i * 86400000);
            const weekday = date.getUTCDay();
            this.days.push({
                date,
                day: date.getUTCDate(),
                letter: date.toLocaleDateString(locale, {weekday: 'narrow', timeZone: 'UTC'}),
                weekend: weekday === 0 || weekday === 6
            });
            const month = date.toLocaleDateString(locale, {month: 'long', year: 'numeric', timeZone: 'UTC'});
            const last = this.months[this.months.length - 1];
            if (last?.label === month) {
                last.span++;
            } else {
                this.months.push({label: month, span: 1});
            }
        }
    }

    /**
     * The days of the chart of a template, numbered from 1 (30 days at least, legacy chart), and
     * the place of each task: day 1, or after its predecessor (finish to start) or with it (start
     * to start).
     */
    protected buildTemplateDays(): void {
        const byNumber = new Map(this.data.tasks.map(task => [`${task.number}`, task]));
        this.schedule = {};
        const place = (task: GanttTask, chain: string[]): { start: number; finish: number } => {
            if (this.schedule[task.id]) {
                return this.schedule[task.id];
            }
            const from = task.predecessor ? byNumber.get(`${task.predecessor}`) : null;
            let start = 0;
            if (from && !chain.includes(from.id)) {
                const span = place(from, [...chain, from.id]);
                start = task.relationType === 'SS' ? span.start : span.finish + 1;
            }
            const length = task.milestone ? 1 : Math.max(1, Number(task.duration) || 1);
            return this.schedule[task.id] = {start, finish: start + length - 1};
        };
        this.data.tasks.forEach(task => place(task, [task.id]));

        const last = Math.max(0, ...Object.values(this.schedule).map(span => span.finish));
        const count = Math.max(last + 1, 30);
        this.days = [];
        for (let i = 0; i < count; i++) {
            this.days.push({date: null, day: i + 1, letter: '', weekend: false});
        }
        this.months = [{label: this.label('LBL_DAYS'), span: count}];
    }

    protected startIndex(task: GanttTask): number {
        if (this.template) {
            return this.schedule[task.id]?.start ?? -1;
        }
        return task.start ? this.dayIndex(task.start) : -1;
    }

    protected finishIndex(task: GanttTask): number {
        if (this.template) {
            return this.schedule[task.id]?.finish ?? -1;
        }
        return this.dayIndex(task.finish || task.start);
    }

    protected dayIndex(value: string): number {
        if (!value || !this.days.length) {
            return -1;
        }
        return Math.round((new Date(value + 'T00:00:00Z').getTime() - this.days[0].date.getTime()) / 86400000);
    }
}

/**
 * The Gantt chart of a project template under its details (record view, bottom widget
 * `project-template-gantt`), as the legacy project template Gantt chart page.
 */
@Component({
    selector: 'scrm-project-template-gantt-widget',
    templateUrl: './project-gantt-widget.component.html',
    styleUrls: []
})
export class ProjectTemplateGanttWidgetComponent extends ProjectGanttWidgetComponent {

    static readonly TEMPLATE_PROCESS = 'project-template-gantt';

    protected processType = ProjectTemplateGanttWidgetComponent.TEMPLATE_PROCESS;
}
