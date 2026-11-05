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


import {ChangeDetectorRef, Component, inject, OnDestroy, OnInit} from '@angular/core';
import {Subscription} from 'rxjs';
import {debounceTime} from 'rxjs/operators';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {
    EventInvitee,
    eventInviteeKey,
    EventReminder,
    EventReminderInvitee,
    EventRemindersValue,
    getRecordInvitees
} from '../../../event-invitees/event-invitees.model';

/**
 * Reminders of a call or a meeting edited as in the legacy edit view
 * (modules/Reminders/tpls/reminders.tpl): reminders added and removed, popup and
 * email reminders with their timer, and the invitees they remind, picked among
 * the invitees of the event. A new event gets the reminder of the user
 * preferences. The reminders follow the invitees: a removed invitee is no longer
 * reminded, and a reminder for all the invitees gets the new ones.
 */
@Component({
    selector: 'scrm-event-reminders-edit',
    templateUrl: './event-reminders.component.html',
    styleUrls: []
})
export class EventRemindersEditFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    reminders: EventReminder[] = [];
    invitees: EventInvitee[] = [];
    timerOptions: { value: string; label: string }[] = [];

    protected subscriptions: Subscription[] = [];

    protected language = inject(LanguageStore);
    protected preferences = inject(UserPreferenceStore);
    protected changeDetector = inject(ChangeDetectorRef);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        const options = this.language.getAppListString('reminder_time_options') as { [key: string]: string } || {};
        this.timerOptions = Object.keys(options).map(key => ({value: key, label: options[key]}));
        this.invitees = getRecordInvitees(this.record);

        const value: EventRemindersValue = this.field?.valueObject ?? this.record?.attributes?.[this.field?.name] ?? null;
        if (Array.isArray(value?.reminders)) {
            this.reminders = value.reminders.map(reminder => ({...reminder, invitees: [...(reminder.invitees ?? [])]}));
            this.update(false);
        } else {
            this.addDefaultReminder();
        }

        // The invitees field has its own row: its changes are followed through the record form.
        if (this.record?.formGroup) {
            this.subscriptions.push(this.record.formGroup.valueChanges.pipe(
                debounceTime(200)
            ).subscribe(() => this.followInvitees()));
        }
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
        super.ngOnDestroy();
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.record?.module ?? '') || this.language.getAppString(key) || key;
    }

    addReminder(): void {
        this.reminders.push(this.newReminder(true, true));
        this.update(true);
    }

    removeReminder(reminder: EventReminder): void {
        this.reminders = this.reminders.filter(item => item !== reminder);
        this.update(true);
    }

    setAction(reminder: EventReminder, action: 'popup' | 'email', checked: boolean): void {
        reminder[action] = checked;
        this.update(true);
    }

    setTimer(reminder: EventReminder, timer: 'timer_popup' | 'timer_email', value: string): void {
        reminder[timer] = value;
        this.update(true);
    }

    addAllInvitees(reminder: EventReminder): void {
        const reminded = new Set(reminder.invitees.map(invitee => eventInviteeKey(invitee.module, invitee.module_id)));
        this.invitees.forEach(invitee => {
            if (!reminded.has(eventInviteeKey(invitee.module, invitee.id))) {
                reminder.invitees.push(this.toReminderInvitee(invitee));
            }
        });
        this.update(true);
    }

    removeInvitee(reminder: EventReminder, invitee: EventReminderInvitee): void {
        reminder.invitees = reminder.invitees.filter(item => item !== invitee);
        this.update(true);
    }

    trackByIndex(index: number): number {
        return index;
    }

    /**
     * The reminders given to the field (saved with the record).
     */
    protected update(dirty: boolean): void {
        const value: EventRemindersValue = {
            reminders: this.reminders.map(reminder => ({...reminder, invitees: reminder.invitees.map(invitee => ({...invitee}))}))
        };

        this.field.valueObject = value;
        this.field.formControl?.setValue(value);
        if (dirty) {
            this.field.formControl?.markAsDirty();
        }
        if (this.record?.attributes) {
            this.record.attributes[this.field.name] = value;
        }
    }

    /**
     * The reminder of the user preferences, for a new event (no value yet).
     */
    protected addDefaultReminder(): void {
        const popup = this.isChecked('reminder_checked');
        const email = this.isChecked('email_reminder_checked');

        if (popup || email) {
            this.reminders.push(this.newReminder(popup, email));
        }

        this.update(false);
    }

    protected newReminder(popup: boolean, email: boolean): EventReminder {
        return {
            id: '',
            popup,
            email,
            timer_popup: this.preferredTimer('reminder_time'),
            timer_email: this.preferredTimer('email_reminder_time'),
            invitees: this.invitees.map(invitee => this.toReminderInvitee(invitee)),
        };
    }

    protected followInvitees(): void {
        const previous = this.invitees;
        const current = getRecordInvitees(this.record);
        const previousKeys = previous.map(invitee => eventInviteeKey(invitee.module, invitee.id)).sort().join(',');
        const currentKeys = current.map(invitee => eventInviteeKey(invitee.module, invitee.id)).sort().join(',');
        if (previousKeys === currentKeys) {
            return;
        }

        const invited = new Set(current.map(invitee => eventInviteeKey(invitee.module, invitee.id)));
        const previouslyInvited = new Set(previous.map(invitee => eventInviteeKey(invitee.module, invitee.id)));

        this.reminders.forEach(reminder => {
            const remindsAll = previous.length > 0 && previous.every(invitee =>
                reminder.invitees.some(item => eventInviteeKey(item.module, item.module_id) === eventInviteeKey(invitee.module, invitee.id))
            );

            reminder.invitees = reminder.invitees.filter(item => invited.has(eventInviteeKey(item.module, item.module_id)));

            if (remindsAll || previous.length === 0) {
                current
                    .filter(invitee => !previouslyInvited.has(eventInviteeKey(invitee.module, invitee.id)))
                    .forEach(invitee => reminder.invitees.push(this.toReminderInvitee(invitee)));
            }
        });

        this.invitees = current;
        this.update(this.field.formControl?.dirty ?? false);
        this.changeDetector.markForCheck();
    }

    protected toReminderInvitee(invitee: EventInvitee): EventReminderInvitee {
        return {id: '', module: invitee.module, module_id: invitee.id, value: invitee.name};
    }

    protected isChecked(preference: string): boolean {
        const value = this.preferences.getUserPreference(preference);
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    protected preferredTimer(preference: string): string {
        const value = String(this.preferences.getUserPreference(preference) ?? '');
        if (value !== '' && value !== '-1' && this.timerOptions.some(option => option.value === value)) {
            return value;
        }

        return this.timerOptions[0]?.value ?? '';
    }
}
