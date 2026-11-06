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
import {ActivatedRoute, ParamMap, Router} from '@angular/router';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {Observable, Subscription} from 'rxjs';
import {filter, take} from 'rxjs/operators';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {RecordModalOptions} from '../../../../services/modals/record-modal.model';
import {CalendarGridComponent} from './calendar-grid.component';
import {CalendarEventModalComponent} from './calendar-event-modal.component';
import {CalendarCreateModalComponent} from './calendar-create-modal.component';
import {CalendarSettingsModalComponent} from './calendar-settings-modal.component';
import {CalendarUsersModalComponent} from './calendar-users-modal.component';
import {
    CalendarData,
    CalendarEvent,
    CalendarMove,
    CalendarResize,
    CalendarSettings,
    CalendarSlot,
    CalendarView
} from './calendar.types';
import {addMinutes, diffMinutes} from './calendar-dates';

/**
 * The activities calendar (legacy Calendar module) as a SuiteCRM 8 page:
 * day, week, month, shared week and shared month views of the meetings, calls,
 * tasks and events of the user (or of the users of the shared calendar), with the
 * legacy navigation (previous, today, next, go to date, a day to its day view) and
 * settings (time slots, day start and end, activities shown, colours).
 *
 * From the calendar: a free time or day creates a meeting, a call or a task there
 * (SuiteCRM 8 create modal); an activity opens its details, with its record, edit
 * view and delete; meetings and calls are moved and resized.
 *
 * Routes calendar and calendar/index (Calendar tab and menu), query params view
 * and date (Y-m-d), or the legacy ones (view=agendaDay..., year, month, day).
 * Data from the `calendar-activities` process (CalendarProcessHandler / CalendarHandler).
 */
@Component({
    selector: 'scrm-calendar-view',
    templateUrl: './calendar.component.html',
})
export class CalendarViewComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'calendar-activities';
    static readonly VIEW_STORAGE = 'calendarView';

    readonly views: { view: CalendarView; label: string }[] = [
        {view: 'day', label: 'LBL_AGENDADAY'},
        {view: 'week', label: 'LBL_AGENDAWEEK'},
        {view: 'month', label: 'LBL_MONTH'},
        {view: 'sharedWeek', label: 'LBL_SHAREDWEEK'},
        {view: 'sharedMonth', label: 'LBL_SHAREDMONTH'},
    ];

    // Legacy views (Calendar menu and URLs).
    readonly legacyViews: { [view: string]: CalendarView } = {
        agendaDay: 'day',
        basicDay: 'day',
        agendaWeek: 'week',
        basicWeek: 'week',
        month: 'month',
        sharedWeek: 'sharedWeek',
        sharedMonth: 'sharedMonth',
    };

    data: CalendarData = null;
    labels: { [key: string]: string } = null;
    gotoDate = '';
    loading = false;
    error = '';

    protected view: CalendarView = null;
    protected date = '';
    protected subs: Subscription[] = [];
    protected loadSub: Subscription;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected datetimeFormatter: DatetimeFormatter,
        protected appState: AppStateStore,
        protected navigation: ModuleNavigation,
        protected modals: NgbModal,
        protected confirmation: ConfirmationModalService,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get shared(): boolean {
        return this.data?.view === 'sharedWeek' || this.data?.view === 'sharedMonth';
    }

    get canCreate(): boolean {
        return Object.values(this.data?.create ?? {}).some(Boolean);
    }

    ngOnInit(): void {
        this.subs.push(this.route.queryParamMap.subscribe(params => {
            this.view = this.readView(params);
            this.date = this.readDate(params);
            this.load();
        }));
    }

    ngOnDestroy(): void {
        this.loadSub?.unsubscribe();
        this.subs.forEach(sub => sub.unsubscribe());
    }

    /**
     * A label of the Calendar module (sent by the process: the front end only has the
     * strings of the modules in the navigation), or of the application, without the legacy
     * final colon. Nothing until they are loaded.
     */
    label(key: string): string {
        const label = this.labels?.[key] || this.language.getAppString(key) || (this.labels ? key : '');

        return label.replace(/:\s*$/, '');
    }

    navLabel(direction: 'PREVIOUS' | 'NEXT'): string {
        const period = {day: 'DAY', week: 'WEEK', sharedWeek: 'WEEK'}[this.data?.view] ?? 'MONTH';

        return this.label('LBL_' + direction + '_' + period);
    }

    show(view: CalendarView, date = this.data?.date ?? ''): void {
        this.storeView(view);
        this.router.navigate([], {relativeTo: this.route, queryParams: {view, date}});
    }

    previous(): void {
        this.show(this.data.view, this.data.previous);
    }

    next(): void {
        this.show(this.data.view, this.data.next);
    }

    today(): void {
        this.show(this.data.view, this.data.today);
    }

    goTo(): void {
        if (/^\d{4}-\d{2}-\d{2}$/.test(this.gotoDate)) {
            this.show(this.data.view, this.gotoDate);
        }
    }

    showDay(date: string): void {
        this.show('day', date);
    }

    // Activities.

    open(event: CalendarEvent): void {
        const modal = this.modals.open(CalendarEventModalComponent, {windowClass: 'calendar-view-modal', scrollable: true});
        modal.componentInstance.event = event;
        modal.componentInstance.color = this.data.settings.colors.find(color => color.frontModule === event.module) ?? null;
        modal.componentInstance.label = (key: string): string => this.label(key);
        modal.componentInstance.period = this.period(event);
        this.showModal();
        modal.closed.pipe(take(1)).subscribe((action: string) => {
            if (action === 'view') {
                this.router.navigateByUrl(this.navigation.getRecordRouterLink(event.module, event.id));
            } else if (action === 'edit') {
                this.router.navigateByUrl('/' + event.module + '/edit/' + event.id);
            } else if (action === 'delete') {
                this.remove(event);
            }
        });
    }

    create(slot: CalendarSlot): void {
        const modules = ['meetings', 'calls', 'tasks'].filter(module => this.data?.create?.[module]);
        if (!modules.length) {
            return;
        }

        const modal = this.modals.open(CalendarCreateModalComponent, {windowClass: 'calendar-view-modal'});
        modal.componentInstance.modules = modules.map(module => ({
            module,
            label: this.label({meetings: 'LNK_NEW_MEETING', calls: 'LNK_NEW_CALL', tasks: 'LNK_NEW_TASK'}[module])
        }));
        modal.componentInstance.label = (key: string): string => this.label(key);
        modal.componentInstance.when = this.slotLabel(slot);
        this.showModal();
        modal.closed.pipe(take(1)).subscribe((module: string) => this.openCreate(module, slot));
    }

    moveEvent(change: CalendarMove): void {
        this.change({action: 'move', module: change.event.legacyModule, id: change.event.id, start: change.start});
    }

    resizeEvent(change: CalendarResize): void {
        this.change({action: 'resize', module: change.event.legacyModule, id: change.event.id, minutes: change.minutes});
    }

    // Settings and shared users.

    openSettings(): void {
        const modal = this.modals.open(CalendarSettingsModalComponent, {windowClass: 'calendar-view-modal', size: 'lg', scrollable: true});
        modal.componentInstance.settings = JSON.parse(JSON.stringify(this.data.settings));
        modal.componentInstance.label = (key: string): string => this.label(key);
        this.showModal();
        modal.closed.pipe(take(1)).subscribe((settings: CalendarSettings) => this.change({action: 'settings', settings}));
    }

    openUsers(): void {
        const modal = this.modals.open(CalendarUsersModalComponent, {windowClass: 'calendar-view-modal', scrollable: true});
        modal.componentInstance.users = this.data.users;
        modal.componentInstance.value = [...this.data.sharedIds];
        modal.componentInstance.label = (key: string): string => this.label(key);
        this.showModal();
        modal.closed.pipe(take(1)).subscribe((ids: string[]) => this.change({action: 'users', ids}));
    }

    /**
     * Shows a modal with the inputs just set on it: the application runs without zone.js and a
     * slot selected with the mouse ends in a pointerup listener of the window, which runs no
     * change detection, so the window stayed empty until the next click in it.
     */
    protected showModal(): void {
        this.cdr.markForCheck();
    }

    protected remove(event: CalendarEvent): void {
        this.confirmation.showModal(['NTC_DELETE_CONFIRMATION'], () => {
            this.change({action: 'remove', module: event.legacyModule, id: event.id});
        });
    }

    /**
     * The SuiteCRM 8 create modal of the module, with the time chosen (start, and duration
     * of a time range) and the user of the calendar; the calendar reloads once it is saved.
     */
    protected openCreate(module: string, slot: CalendarSlot): void {
        const internal = (datetime: string): string => this.datetimeFormatter.toInternalFormat(datetime, {fromFormat: 'yyyy-MM-dd HH:mm:ss'});
        const values: { [field: string]: string } = {};
        const minutes = slot.end ? diffMinutes(slot.start, slot.end) : 0;

        if (module === 'tasks') {
            values.date_due = internal(slot.end ? addMinutes(slot.start, minutes) : slot.start);
            if (slot.end) {
                values.date_start = internal(slot.start);
            }
        } else {
            values.date_start = internal(slot.start);
            if (minutes > 0) {
                values.duration_hours = String(Math.floor(minutes / 60));
                values.duration_minutes = String(minutes % 60);
            }
        }

        const user = slot.userId ? {id: slot.userId, name: slot.userName} : this.data.currentUser;
        values.assigned_user_id = user.id;
        values.assigned_user_name = user.name;

        this.appState.openRecordModal({
            module,
            mode: 'create',
            record: {id: '', module: 'calendar', attributes: {}},
            mapFields: {default: values},
            headerLabelKey: {meetings: 'LBL_SCHEDULE_MEETING', calls: 'LBL_SCHEDULE_CALL', tasks: 'LBL_CREATE_TASK'}[module],
            modalOptions: {size: 'lg', scrollable: true},
        } as RecordModalOptions);

        const modal = this.appState.getActiveModals().slice(-1)[0];
        modal?.dismissed.pipe(take(1)).subscribe(reason => {
            if (reason !== 'save') {
                return;
            }
            // The modal closes while it saves: reload once the requests are done.
            this.appState.activeRequests$.pipe(filter(count => !count), take(1)).subscribe(() => this.load());
        });
    }

    protected change(options: { [key: string]: any }): void {
        this.loading = true;
        this.cdr.detectChanges();
        this.run(options).subscribe({
            next: (process: Process) => {
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(this.label(process?.messages?.[0] || 'LBL_ERROR_SAVING'));
                }
                this.load();
            },
            error: () => {
                this.message.addDangerMessage(this.label('LBL_ERROR_SAVING'));
                this.load();
            }
        });
    }

    protected load(): void {
        this.loadSub?.unsubscribe();
        this.loading = true;
        this.error = '';
        this.cdr.detectChanges();

        this.loadSub = this.run({action: 'load', view: this.view ?? this.storedView() ?? '', date: this.date}).subscribe({
            next: (process: Process) => {
                const data = process?.data as CalendarData;
                this.loading = false;
                this.labels = data?.labels ?? this.labels ?? {};
                if (process?.status !== 'success' || !data?.days) {
                    this.error = process?.messages?.[0] || 'LBL_ERROR_LOADING';
                    this.cdr.detectChanges();
                    return;
                }
                this.data = data;
                this.gotoDate = data.date;
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.labels = this.labels ?? {};
                this.error = 'LBL_ERROR_LOADING';
                this.cdr.detectChanges();
            }
        });
    }

    protected run(options: { [key: string]: any }): Observable<Process> {
        return this.processService.submit(CalendarViewComponent.PROCESS, options).pipe(take(1));
    }

    protected readView(params: ParamMap): CalendarView {
        return this.toView(params.get('view') ?? '');
    }

    protected toView(view: string): CalendarView {
        return this.views.some(item => item.view === view) ? view as CalendarView : this.legacyViews[view] ?? null;
    }

    protected readDate(params: ParamMap): string {
        const date = params.get('date') ?? '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            return date;
        }

        const [year, month, day] = ['year', 'month', 'day'].map(key => parseInt(params.get(key) ?? '', 10));
        if (year && month && day) {
            return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        return '';
    }

    /** The last view shown in the browser tab, as the legacy calendar keeps it in the session. */
    protected storedView(): CalendarView | null {
        try {
            return this.toView(window.sessionStorage.getItem(CalendarViewComponent.VIEW_STORAGE) ?? '');
        } catch (e) {
            return null;
        }
    }

    protected storeView(view: CalendarView): void {
        try {
            window.sessionStorage.setItem(CalendarViewComponent.VIEW_STORAGE, view);
        } catch (e) {
            // ignore
        }
    }

    /**
     * A date and time of the calendar ('yyyy-MM-dd HH:mm:ss', user time zone) in the user
     * format: date and time, date or time.
     */
    protected format(datetime: string, part: 'datetime' | 'date' | 'time' = 'datetime'): string {
        const value = this.datetimeFormatter.toDateTime(datetime, 'yyyy-MM-dd HH:mm:ss');
        if (!value.isValid) {
            return datetime;
        }

        const format = {
            datetime: this.datetimeFormatter.getDateTimeFormat(),
            date: this.datetimeFormatter.getDateFormat(),
            time: this.datetimeFormatter.getTimeFormat(),
        }[part];
        let text = value.toFormat(format);
        if (part !== 'date' && this.datetimeFormatter.getTimeFormat(false).includes('aaaaaa')) {
            text = text.replace('AM', 'am').replace('PM', 'pm');
        }

        return text;
    }

    protected period(event: CalendarEvent): string {
        if (event.allDay) {
            const last = addMinutes(event.end, -1);

            return event.end > event.start && last.slice(0, 10) !== event.start.slice(0, 10)
                ? this.format(event.start, 'date') + ' – ' + this.format(last, 'date')
                : this.format(event.start, 'date');
        }

        if (event.end <= event.start) {
            return this.format(event.start);
        }

        return event.end.slice(0, 10) === event.start.slice(0, 10)
            ? this.format(event.start) + ' – ' + event.endTime
            : this.format(event.start) + ' – ' + this.format(event.end);
    }

    protected slotLabel(slot: CalendarSlot): string {
        const when = slot.end
            ? this.format(slot.start) + ' – ' + this.format(slot.end, 'time')
            : this.format(slot.start, 'date');

        return slot.userName && this.shared ? when + ' — ' + slot.userName : when;
    }
}
