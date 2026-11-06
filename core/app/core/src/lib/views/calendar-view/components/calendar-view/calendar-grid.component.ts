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

import {
    ChangeDetectorRef,
    Component,
    ElementRef,
    EventEmitter,
    Input,
    OnChanges,
    OnDestroy,
    Output
} from '@angular/core';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {
    CalendarColor,
    CalendarDay,
    CalendarEvent,
    CalendarMove,
    CalendarResize,
    CalendarSettings,
    CalendarSlot,
    CalendarView
} from './calendar.types';
import {atMinutes, datePart, diffMinutes, lastDay, minutesOf, timeToMinutes} from './calendar-dates';

/** A timed activity in a day column of the time grid. */
interface TimedItem {
    event: CalendarEvent;
    start: number;
    end: number;
    top: number;
    height: number;
    left: number;
    width: number;
}

interface DayColumn {
    day: CalendarDay;
    allDay: CalendarEvent[];
    timed: TimedItem[];
}

interface MonthCell {
    day: CalendarDay;
    events: CalendarEvent[];
    more: number;
}

/** What the pointer is doing: moving or resizing an activity, choosing a time range. */
interface PointerAction {
    kind: 'move' | 'resize' | 'select';
    pointerId: number;
    x: number;
    y: number;
    started: boolean;
    event?: CalendarEvent;
    // Time grid: minutes between the start of the activity and the pointer.
    grab?: number;
    date: string;
    minutes: number;
    // Select: first slot chosen.
    anchor?: number;
}

/**
 * One calendar of the calendar page (the user's, or one user of the shared
 * calendar): the days of the period with their activities, as the legacy calendar
 * (FullCalendar) shows them:
 *  - time grid (day and week views with time slots, shared week): the all-day and
 *    multi-day activities and the tasks above, the others at their time, side by side
 *    when they overlap, between the day start and end of the settings (wider when
 *    activities are outside);
 *  - day grid (month views, day and week views without time slots): the activities of
 *    each day, with "+n" for the others of a month day.
 * Meetings and calls the user can edit are moved (drag and drop) and, on the time
 * grid, resized; a click on a free time or day creates an activity there.
 */
@Component({
    selector: 'scrm-calendar-grid',
    templateUrl: './calendar-grid.component.html',
})
export class CalendarGridComponent implements OnChanges, OnDestroy {

    // Height of an hour of the time grid, in pixels.
    static readonly HOUR = 48;
    // Moves and resizes snap to 15 minutes, a click chooses a 30 minute slot.
    static readonly SNAP = 15;
    static readonly SLOT = 30;
    // Activities shown in a day of a month before "+n".
    static readonly MONTH_EVENTS = 4;

    @Input() view: CalendarView = 'week';
    @Input() days: CalendarDay[] = [];
    @Input() weekdays: string[] = [];
    @Input() hours: string[] = [];
    @Input() events: CalendarEvent[] = [];
    @Input() settings: CalendarSettings = null;
    // Shared calendar of all the users together: the activities show their user.
    @Input() showUser = false;
    @Input() canCreate = false;
    @Input() userId = '';
    @Input() userName = '';
    @Input() labels: { more: string; allDay: string } = {more: '+%s', allDay: ''};

    @Output() create = new EventEmitter<CalendarSlot>();
    @Output() open = new EventEmitter<CalendarEvent>();
    @Output() move = new EventEmitter<CalendarMove>();
    @Output() resize = new EventEmitter<CalendarResize>();
    @Output() showDay = new EventEmitter<string>();

    readonly halfHour = CalendarGridComponent.HOUR / 2;

    timeGrid = false;
    columns: DayColumn[] = [];
    rows: MonthCell[][] = [];
    headNames: string[] = [];
    rangeStart = 0;
    rangeEnd = 1440;
    hourRows: { label: string; top: number }[] = [];
    action: PointerAction = null;

    protected colors: { [module: string]: CalendarColor } = {};
    protected suppressClick = false;
    protected moveListener = (event: PointerEvent): void => this.onPointerMove(event);
    protected upListener = (event: PointerEvent): void => this.onPointerUp(event);

    constructor(
        protected host: ElementRef<HTMLElement>,
        protected cdr: ChangeDetectorRef,
        protected datetimeFormatter: DatetimeFormatter
    ) {
    }

    get height(): number {
        return (this.rangeEnd - this.rangeStart) / 60 * CalendarGridComponent.HOUR;
    }

    ngOnChanges(): void {
        this.colors = {};
        (this.settings?.colors ?? []).forEach(color => this.colors[color.frontModule] = color);
        this.timeGrid = this.view === 'sharedWeek' || ((this.view === 'day' || this.view === 'week') && !!this.settings?.timeslots);

        if (this.timeGrid) {
            this.buildTimeGrid();
        } else {
            this.buildDayGrid();
        }
    }

    ngOnDestroy(): void {
        this.stopListening();
    }

    eventStyle(event: CalendarEvent): { [key: string]: string } {
        const color = this.colors[event.module];

        return color ? {
            'background-color': '#' + color.body,
            'border-color': '#' + color.border,
            'color': '#' + color.text
        } : {};
    }

    eventTitle(event: CalendarEvent): string {
        const time = event.allDay ? '' : event.time + (event.endTime && event.end !== event.start ? ' – ' + event.endTime : '') + ' ';
        const user = event.assignedUserName ? ' (' + event.assignedUserName + ')' : '';
        const parent = event.parent?.name ? '\n' + event.parent.name : '';

        return time + event.name + user + parent;
    }

    eventName(event: CalendarEvent): string {
        return this.showUser && event.assignedUserName ? event.name + ' — ' + event.assignedUserName : event.name;
    }

    /** Whether the activity starts on the day (its time is shown there). */
    startsOn(event: CalendarEvent, date: string): boolean {
        return datePart(event.start) === date;
    }

    moreLabel(count: number): string {
        return (this.labels.more || '+%s').replace('%s', String(count));
    }

    isDropTarget(date: string): boolean {
        return !!this.action?.started && this.action.kind === 'move' && !this.timeGrid && this.action.date === date
            && this.action.date !== datePart(this.action.event.start);
    }

    /** Preview of the activity moved or resized, or of the time range chosen, in a day column. */
    preview(date: string): { top: number; height: number; event: CalendarEvent | null; time: string } | null {
        const action = this.action;
        if (!this.timeGrid || !action?.started || action.date !== date) {
            return null;
        }

        if (action.kind === 'select') {
            const start = Math.min(action.anchor, action.minutes);
            const end = Math.max(action.anchor, action.minutes) + CalendarGridComponent.SLOT;

            return {
                top: this.toPixels(start),
                height: this.toPixels(end) - this.toPixels(start),
                event: null,
                time: this.timeLabel(start) + ' – ' + this.timeLabel(end)
            };
        }

        const duration = this.duration(action.event);
        if (action.kind === 'resize') {
            const start = minutesOf(action.event.start);

            return {
                top: this.toPixels(start),
                height: Math.max(this.toPixels(action.minutes) - this.toPixels(start), 12),
                event: action.event,
                time: this.timeLabel(start) + ' – ' + this.timeLabel(action.minutes)
            };
        }

        return {
            top: this.toPixels(action.minutes),
            height: Math.max(this.toPixels(action.minutes + Math.max(duration, CalendarGridComponent.SLOT)) - this.toPixels(action.minutes), 12),
            event: action.event,
            time: this.timeLabel(action.minutes)
        };
    }

    /** A time of the day (minutes) in the user's time format. */
    protected timeLabel(minutes: number): string {
        const time = this.datetimeFormatter.toDateTime(atMinutes('2000-01-01', minutes), 'yyyy-MM-dd HH:mm:ss');
        let label = time.isValid ? time.toFormat(this.datetimeFormatter.getTimeFormat()) : '';
        if (this.datetimeFormatter.getTimeFormat(false).includes('aaaaaa')) {
            label = label.replace('AM', 'am').replace('PM', 'pm');
        }

        return label;
    }

    isMoving(event: CalendarEvent): boolean {
        return !!this.action?.started && this.action.event === event;
    }

    // Clicks.

    onEventClick(event: CalendarEvent, domEvent: MouseEvent): void {
        domEvent.stopPropagation();
        if (this.suppressClick) {
            this.suppressClick = false;
            return;
        }
        this.open.emit(event);
    }

    onDayClick(date: string, domEvent: MouseEvent): void {
        if (domEvent.target !== domEvent.currentTarget || !this.canCreate) {
            return;
        }
        this.create.emit(this.slot(atMinutes(date, timeToMinutes(this.settings?.dayStart ?? '08:00')), ''));
    }

    onShowDay(date: string, domEvent: Event): void {
        domEvent.stopPropagation();
        this.showDay.emit(date);
    }

    // Pointer: move, resize, choose a time range.

    onEventPointerDown(event: CalendarEvent, domEvent: PointerEvent, date: string): void {
        // On touch screens the finger scrolls the page: a tap opens the activity.
        if (domEvent.button !== 0 || domEvent.pointerType === 'touch' || !event.movable || !this.settings?.draggable) {
            return;
        }
        domEvent.stopPropagation();

        const pointer = this.timeGrid ? this.pointerMinutes(domEvent.clientY, date) : 0;
        this.startAction({
            kind: 'move',
            pointerId: domEvent.pointerId,
            x: domEvent.clientX,
            y: domEvent.clientY,
            started: false,
            event,
            grab: this.timeGrid ? pointer - minutesOf(event.start) : 0,
            date: datePart(event.start),
            minutes: minutesOf(event.start)
        });
    }

    /** A tap on a free time of a day column (touch screens): its 30 minute slot. */
    onColumnClick(date: string, domEvent: PointerEvent): void {
        if (domEvent.pointerType !== 'touch' || !this.canCreate || domEvent.target !== domEvent.currentTarget) {
            return;
        }
        const start = this.slotAt(domEvent.clientY, date);
        this.create.emit(this.slot(atMinutes(date, start), atMinutes(date, start + CalendarGridComponent.SLOT)));
    }

    onResizePointerDown(item: TimedItem, domEvent: PointerEvent): void {
        if (domEvent.button !== 0 || domEvent.pointerType === 'touch' || !this.settings?.resizable) {
            return;
        }
        domEvent.stopPropagation();
        domEvent.preventDefault();

        this.startAction({
            kind: 'resize',
            pointerId: domEvent.pointerId,
            x: domEvent.clientX,
            y: domEvent.clientY,
            started: true,
            event: item.event,
            date: datePart(item.event.start),
            minutes: item.end
        });
    }

    onColumnPointerDown(date: string, domEvent: PointerEvent): void {
        if (domEvent.button !== 0 || domEvent.pointerType === 'touch' || !this.canCreate || domEvent.target !== domEvent.currentTarget) {
            return;
        }
        domEvent.preventDefault();

        const minutes = this.slotAt(domEvent.clientY, date);
        this.startAction({
            kind: 'select',
            pointerId: domEvent.pointerId,
            x: domEvent.clientX,
            y: domEvent.clientY,
            started: true,
            date,
            minutes,
            anchor: minutes
        });
    }

    protected startAction(action: PointerAction): void {
        this.stopListening();
        this.action = action;
        window.addEventListener('pointermove', this.moveListener);
        window.addEventListener('pointerup', this.upListener);
        window.addEventListener('pointercancel', this.upListener);
        this.cdr.detectChanges();
    }

    protected onPointerMove(domEvent: PointerEvent): void {
        const action = this.action;
        if (!action || domEvent.pointerId !== action.pointerId) {
            return;
        }

        if (!action.started) {
            if (Math.abs(domEvent.clientX - action.x) + Math.abs(domEvent.clientY - action.y) < 5) {
                return;
            }
            action.started = true;
        }
        domEvent.preventDefault();

        if (action.kind === 'select') {
            action.minutes = this.slotAt(domEvent.clientY, action.date);
        } else if (action.kind === 'resize') {
            const start = minutesOf(action.event.start);
            const end = this.snap(this.pointerMinutes(domEvent.clientY, action.date), CalendarGridComponent.SNAP, Math.round);
            action.minutes = Math.min(Math.max(end, start + CalendarGridComponent.SNAP), 1440);
        } else {
            const date = this.dateAt(domEvent.clientX, domEvent.clientY);
            if (date) {
                action.date = date;
            }
            if (this.timeGrid) {
                const start = this.snap(this.pointerMinutes(domEvent.clientY, action.date) - action.grab, CalendarGridComponent.SNAP, Math.round);
                action.minutes = Math.min(Math.max(start, 0), 1440 - CalendarGridComponent.SNAP);
            }
        }

        this.cdr.detectChanges();
    }

    protected onPointerUp(domEvent: PointerEvent): void {
        const action = this.action;
        if (!action || domEvent.pointerId !== action.pointerId) {
            return;
        }
        this.stopListening();
        this.action = null;

        if (domEvent.type === 'pointercancel' || !action.started) {
            this.cdr.detectChanges();
            return;
        }

        if (action.kind === 'select') {
            const start = Math.min(action.anchor, action.minutes);
            const end = Math.max(action.anchor, action.minutes) + CalendarGridComponent.SLOT;
            this.create.emit(this.slot(atMinutes(action.date, start), atMinutes(action.date, end)));
        } else if (action.kind === 'resize') {
            const minutes = action.minutes - minutesOf(action.event.start);
            if (minutes !== this.duration(action.event)) {
                this.resize.emit({event: action.event, minutes});
            }
        } else {
            // The click following the drop must not open the activity.
            this.suppressClick = true;
            setTimeout(() => this.suppressClick = false);
            const start = atMinutes(action.date, this.timeGrid ? action.minutes : minutesOf(action.event.start));
            if (start !== action.event.start) {
                this.move.emit({event: action.event, start});
            }
        }

        this.cdr.detectChanges();
    }

    protected stopListening(): void {
        window.removeEventListener('pointermove', this.moveListener);
        window.removeEventListener('pointerup', this.upListener);
        window.removeEventListener('pointercancel', this.upListener);
    }

    protected slot(start: string, end: string): CalendarSlot {
        return {start, end, userId: this.userId, userName: this.userName};
    }

    /** Day under the pointer (day columns of the time grid, days of the day grid). */
    protected dateAt(x: number, y: number): string {
        const element = document.elementFromPoint(x, y) as HTMLElement | null;
        const day = element?.closest('[data-date]') as HTMLElement | null;
        if (day && this.host.nativeElement.contains(day)) {
            return day.dataset.date ?? '';
        }

        if (!this.timeGrid) {
            return '';
        }

        // Beside the columns: the nearest one.
        const columns = Array.from(this.host.nativeElement.querySelectorAll<HTMLElement>('.calendar-view-col[data-date]'));
        let nearest = '';
        let distance = Infinity;
        columns.forEach(column => {
            const rect = column.getBoundingClientRect();
            const gap = x < rect.left ? rect.left - x : x > rect.right ? x - rect.right : 0;
            if (gap < distance) {
                distance = gap;
                nearest = column.dataset.date ?? '';
            }
        });

        return nearest;
    }

    /** Minutes of the day at a height of a day column. */
    protected pointerMinutes(y: number, date: string): number {
        const column = this.host.nativeElement.querySelector<HTMLElement>('.calendar-view-col[data-date="' + date + '"]');
        if (!column) {
            return this.rangeStart;
        }
        const top = column.getBoundingClientRect().top;

        return this.rangeStart + (y - top) / CalendarGridComponent.HOUR * 60;
    }

    /** Start of the 30 minute slot at a height of a day column. */
    protected slotAt(y: number, date: string): number {
        const slot = CalendarGridComponent.SLOT;

        return Math.min(this.snap(this.pointerMinutes(y, date), slot, Math.floor), this.rangeEnd - slot);
    }

    protected snap(minutes: number, step: number, round: (value: number) => number): number {
        return Math.min(Math.max(round(minutes / step) * step, this.rangeStart), this.rangeEnd);
    }

    protected toPixels(minutes: number): number {
        return (minutes - this.rangeStart) / 60 * CalendarGridComponent.HOUR;
    }

    protected duration(event: CalendarEvent): number {
        return Math.max(diffMinutes(event.start, event.end), 0);
    }

    // Layout.

    protected buildTimeGrid(): void {
        const dates = new Set(this.days.map(day => day.date));
        const allDay: { [date: string]: CalendarEvent[] } = {};
        const timed: { [date: string]: CalendarEvent[] } = {};

        this.events.forEach(event => {
            const first = datePart(event.start);
            const last = lastDay(event.start, event.end);
            // Tasks are due at a time, they take no time: above the hours, with the
            // all-day and multi-day activities (many are due at midnight UTC).
            if (event.allDay || first !== last || event.legacyModule === 'Tasks') {
                this.days.forEach(day => {
                    if (day.date >= first && day.date <= last) {
                        (allDay[day.date] = allDay[day.date] ?? []).push(event);
                    }
                });
                return;
            }
            if (dates.has(first)) {
                (timed[first] = timed[first] ?? []).push(event);
            }
        });

        // From the day start to the day end of the settings, wider for the activities outside.
        let start = timeToMinutes(this.settings?.dayStart ?? '08:00');
        let end = timeToMinutes(this.settings?.dayEnd ?? '19:00');
        Object.values(timed).forEach(events => events.forEach(event => {
            start = Math.min(start, minutesOf(event.start));
            end = Math.max(end, minutesOf(event.start) + Math.max(this.duration(event), CalendarGridComponent.SLOT));
        }));
        this.rangeStart = Math.max(0, Math.floor(start / 60) * 60);
        this.rangeEnd = Math.min(1440, Math.max(Math.ceil(end / 60) * 60, this.rangeStart + 60));

        this.hourRows = [];
        for (let minutes = this.rangeStart; minutes < this.rangeEnd; minutes += 60) {
            this.hourRows.push({label: this.hours[minutes / 60] ?? '', top: this.toPixels(minutes)});
        }

        this.columns = this.days.map(day => ({
            day,
            allDay: allDay[day.date] ?? [],
            timed: this.layoutDay(timed[day.date] ?? [])
        }));
        this.rows = [];
    }

    /**
     * Positions of the activities of a day: overlapping activities share the width of the
     * column, each in the first free sub-column.
     */
    protected layoutDay(events: CalendarEvent[]): TimedItem[] {
        const items: TimedItem[] = events.map(event => {
            const start = minutesOf(event.start);
            const end = Math.min(start + Math.max(this.duration(event), CalendarGridComponent.SLOT), 1440);

            return {event, start, end, top: 0, height: 0, left: 0, width: 100};
        }).sort((a, b) => a.start - b.start || b.end - a.end);

        let cluster: TimedItem[][] = [];
        let clusterEnd = -1;
        const close = (): void => {
            cluster.forEach((column, index) => column.forEach(item => {
                item.left = index / cluster.length * 100;
                item.width = 100 / cluster.length;
            }));
            cluster = [];
        };

        items.forEach(item => {
            if (item.start >= clusterEnd) {
                close();
            }
            const column = cluster.find(items => items[items.length - 1].end <= item.start);
            if (column) {
                column.push(item);
            } else {
                cluster.push([item]);
            }
            clusterEnd = Math.max(clusterEnd, item.end);
            item.top = this.toPixels(item.start);
            item.height = Math.max(this.toPixels(item.end) - item.top, 18);
        });
        close();

        return items;
    }

    protected buildDayGrid(): void {
        const month = this.view === 'month' || this.view === 'sharedMonth';
        const cells = this.days.map(day => {
            const events = this.events.filter(event => day.date >= datePart(event.start) && day.date <= lastDay(event.start, event.end));
            const visible = month && events.length > CalendarGridComponent.MONTH_EVENTS
                ? CalendarGridComponent.MONTH_EVENTS - 1
                : events.length;

            return {day, events: events.slice(0, visible), more: events.length - visible};
        });

        this.rows = [];
        const size = this.days.length >= 7 ? 7 : this.days.length;
        this.headNames = size === 7 ? this.weekdays : this.days.map(day => day.label);
        for (let index = 0; index < cells.length; index += size) {
            this.rows.push(cells.slice(index, index + size));
        }
        this.columns = [];
    }
}
