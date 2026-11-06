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

/**
 * Data of the calendar page (backend CalendarHandler, process
 * `calendar-activities`). Dates and times are in the user's time zone:
 * 'yyyy-MM-dd' and 'yyyy-MM-dd HH:mm:ss'.
 */

export type CalendarView = 'day' | 'week' | 'month' | 'sharedWeek' | 'sharedMonth';

export interface CalendarDetail {
    label: string;
    value: string;
    multiline?: boolean;
}

export interface CalendarEvent {
    id: string;
    // Front end module (meetings, calls, tasks, events) and legacy module.
    module: string;
    legacyModule: string;
    name: string;
    assignedUserId: string;
    assignedUserName: string;
    start: string;
    end: string;
    // Start and end times in the user's time format.
    time: string;
    endTime: string;
    allDay: boolean;
    view: boolean;
    edit: boolean;
    delete: boolean;
    // Meetings and calls the user can edit: moved and resized on the calendar.
    movable: boolean;
    parent: { module: string; id: string; name: string; label: string; view: boolean } | null;
    details: CalendarDetail[];
}

export interface CalendarDay {
    date: string;
    number: string;
    label: string;
    today: boolean;
    weekend: boolean;
    // Day of another month (month views).
    outside: boolean;
}

export interface CalendarColor {
    module: string;
    frontModule: string;
    label: string;
    body: string;
    border: string;
    text: string;
}

export interface CalendarSettings {
    firstDay: number;
    dayStart: string;
    dayEnd: string;
    timeslots: boolean;
    sharedSeparate: boolean;
    showCalls: boolean;
    showTasks: boolean;
    showCompleted: boolean;
    draggable: boolean;
    resizable: boolean;
    colors: CalendarColor[];
}

export interface CalendarOption {
    value: string;
    label: string;
}

export interface UserCalendar {
    // Empty for the shared calendar showing all the users together.
    userId: string;
    userName: string;
    events: CalendarEvent[];
}

export interface CalendarData {
    view: CalendarView;
    date: string;
    today: string;
    start: string;
    end: string;
    title: string;
    previous: string;
    next: string;
    days: CalendarDay[];
    weekdays: string[];
    hours: string[];
    calendars: UserCalendar[];
    settings: CalendarSettings;
    // Front end module => whether the user can create it.
    create: { [module: string]: boolean };
    users: CalendarOption[];
    sharedIds: string[];
    currentUser: { id: string; name: string };
    labels?: { [key: string]: string };
}

/** A period chosen on the calendar to create an activity. */
export interface CalendarSlot {
    start: string;
    // End of a chosen time range, empty for a day.
    end: string;
    // User of the calendar (shared calendars).
    userId: string;
    userName: string;
}

export interface CalendarMove {
    event: CalendarEvent;
    start: string;
}

export interface CalendarResize {
    event: CalendarEvent;
    minutes: number;
}
