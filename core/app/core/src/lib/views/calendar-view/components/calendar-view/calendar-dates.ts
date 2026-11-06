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
 * Wall clock dates of the calendar ('yyyy-MM-dd HH:mm:ss' in the user's
 * time zone), computed as UTC dates so that the browser time zone and its daylight
 * saving changes play no part.
 */

const pad = (value: number): string => String(value).padStart(2, '0');

function toTime(datetime: string): number {
    const [date, time = '00:00:00'] = datetime.split(' ');
    const [year, month, day] = date.split('-').map(Number);
    const [hours, minutes] = time.split(':').map(Number);

    return Date.UTC(year, month - 1, day, hours || 0, minutes || 0);
}

function fromTime(time: number): string {
    const date = new Date(time);

    return date.getUTCFullYear() + '-' + pad(date.getUTCMonth() + 1) + '-' + pad(date.getUTCDate())
        + ' ' + pad(date.getUTCHours()) + ':' + pad(date.getUTCMinutes()) + ':00';
}

export function datePart(datetime: string): string {
    return datetime.slice(0, 10);
}

/** Minutes since the start of the day. */
export function minutesOf(datetime: string): number {
    const [hours, minutes] = (datetime.split(' ')[1] ?? '00:00').split(':').map(Number);

    return (hours || 0) * 60 + (minutes || 0);
}

/** 'HH:mm' (24:00 for the end of the day) as minutes. */
export function timeToMinutes(time: string): number {
    const [hours, minutes] = (time || '00:00').split(':').map(Number);

    return (hours || 0) * 60 + (minutes || 0);
}

export function atMinutes(date: string, minutes: number): string {
    return fromTime(toTime(date) + minutes * 60000);
}

export function addMinutes(datetime: string, minutes: number): string {
    return fromTime(toTime(datetime) + minutes * 60000);
}

export function diffMinutes(from: string, to: string): number {
    return Math.round((toTime(to) - toTime(from)) / 60000);
}

/**
 * Last day of an activity: its end date, or the day before when it ends at midnight
 * (an all-day activity ends at 00:00 the next day).
 */
export function lastDay(start: string, end: string): string {
    if (end > start && minutesOf(end) === 0) {
        return datePart(addMinutes(end, -1));
    }

    return datePart(end > start ? end : start);
}
