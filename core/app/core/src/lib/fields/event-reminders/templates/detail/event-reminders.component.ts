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


import {Component, inject, OnInit} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {EventReminder, EventRemindersValue} from '../../../event-invitees/event-invitees.model';

/**
 * Reminders of a call or a meeting: popup and email, their timer and the
 * invitees they remind.
 */
@Component({
    selector: 'scrm-event-reminders-detail',
    templateUrl: './event-reminders.component.html',
    styleUrls: []
})
export class EventRemindersDetailFieldComponent extends BaseFieldComponent implements OnInit {

    reminders: EventReminder[] = [];

    protected language = inject(LanguageStore);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        const value: EventRemindersValue = this.field?.valueObject ?? this.record?.attributes?.[this.field?.name] ?? null;
        this.reminders = (Array.isArray(value?.reminders) ? value.reminders : []).filter(reminder => reminder.popup || reminder.email);
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.record?.module ?? '') || this.language.getAppString(key) || key;
    }

    timer(seconds: string): string {
        return this.language.getListLabel('reminder_time_options', seconds) || seconds;
    }

    inviteeNames(reminder: EventReminder): string {
        return (reminder.invitees ?? []).map(invitee => invitee.value).filter(name => !!name).join(', ');
    }
}
