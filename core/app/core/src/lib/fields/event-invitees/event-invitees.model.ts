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
 * Value of an event-invitees field (calls and meetings): the users, contacts and
 * leads invited, with their accept status (backend EventInviteesHandler).
 * `sendInvites` asks the save to send the invitations ("Save and Send Invites").
 */
export interface EventInvitee {
    module: string;
    id: string;
    name: string;
    email: string;
    accept_status: string;
}

export interface EventInviteesValue {
    invitees: EventInvitee[];
    sendInvites?: boolean;
}

/**
 * Value of an event-reminders field (backend EventRemindersHandler): popup and
 * email reminders, with their timer (seconds before the start) and the invitees
 * they remind.
 */
export interface EventReminderInvitee {
    id: string;
    module: string;
    module_id: string;
    value: string;
}

export interface EventReminder {
    id: string;
    popup: boolean;
    email: boolean;
    timer_popup: string;
    timer_email: string;
    invitees: EventReminderInvitee[];
}

export interface EventRemindersValue {
    reminders: EventReminder[];
}

export const EVENT_INVITEES_FIELD = 'invitees';

export function eventInviteeKey(module: string, id: string): string {
    return module + ':' + id;
}

/** The invitees of the record (event-invitees field), for its reminders. */
export function getRecordInvitees(record: any): EventInvitee[] {
    const field = record?.fields?.[EVENT_INVITEES_FIELD];
    const value = field?.valueObject ?? record?.attributes?.[EVENT_INVITEES_FIELD] ?? null;

    return Array.isArray(value?.invitees) ? value.invitees : [];
}
