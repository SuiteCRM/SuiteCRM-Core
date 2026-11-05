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

import {Component, OnInit} from '@angular/core';
import {BaseFieldComponent} from '../base/base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../store/language/language.store';

type CronType = 'daily' | 'weekly' | 'monthly';

/**
 * Schedule of a scheduled report (legacy CronSchedule field: a cron expression
 * "minutes hours day month weekday"), as the legacy field:
 *  - detail: "Daily at 08:00", "Weekly on Mon, Fri at 08:00", "Monthly on the 1 at 08:00", or
 *    the expression when it does not fit;
 *  - edit: daily, weekly (weekdays) or monthly (days) at a time, or the five parts of the
 *    expression (Advanced).
 *
 * Field type `cron-schedule`, detail and edit modes (the schedule of the scheduled reports,
 * ScheduledReportFieldDefinitionMapper).
 */
@Component({
    selector: 'scrm-cron-schedule-field',
    templateUrl: './cron-schedule.component.html',
    styleUrls: []
})
export class CronScheduleFieldComponent extends BaseFieldComponent implements OnInit {

    readonly types: CronType[] = ['daily', 'weekly', 'monthly'];
    readonly weekdays = [0, 1, 2, 3, 4, 5, 6];
    readonly days = Array.from({length: 31}, (_, index) => `${index + 1}`);
    readonly hours = Array.from({length: 24}, (_, index) => this.pad(index));
    readonly minutes = Array.from({length: 60}, (_, index) => this.pad(index));
    readonly rawParts = ['LBL_CRON_MIN', 'LBL_CRON_HOUR', 'LBL_CRON_DAY', 'LBL_CRON_MONTH', 'LBL_CRON_DOW'];

    advanced = false;
    type: CronType = 'daily';
    selectedWeekdays: string[] = [];
    selectedDays: string[] = [];
    hour = '';
    minute = '';
    raw: string[] = ['', '', '', '', ''];

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    get editing(): boolean {
        return this.mode !== 'detail' && this.mode !== 'list';
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.read(`${this.field?.value ?? ''}`);
    }

    appString(key: string): string {
        return this.language.getAppString(key) || key;
    }

    typeLabel(type: CronType): string {
        return `${this.language.getListLabel('aor_scheduled_report_schedule_types', type) || type}`;
    }

    weekdayLabel(weekday: number | string): string {
        return `${this.language.getListLabel('dom_cal_day_short', `${Number(weekday) + 1}`) || weekday}`;
    }

    /**
     * The schedule in words, as the legacy detail view, or the expression when it is not a
     * daily, weekly or monthly schedule at a time (steps, ranges...).
     */
    readable(): string {
        const schedule = `${this.field?.value ?? ''}`.trim();
        const [minutes = '', hours = '', day = '', month = '', weekday = ''] = schedule.split(/\s+/);
        const list = /^(\*|\d+(,\d+)*)$/;
        if (!schedule || month !== '*' || !/^\d+$/.test(minutes) || !/^\d+$/.test(hours)
            || !list.test(day) || !list.test(weekday) || (weekday !== '*' && day !== '*')) {
            return schedule;
        }

        const time = `${this.appString('LBL_CRON_AT')} ${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
        if (weekday !== '*') {
            const names = weekday.split(',').map(value => this.weekdayLabel(value)).join(', ');

            return `${this.appString('LBL_CRON_WEEKLY')} ${this.appString('LBL_CRON_ON_THE_WEEKDAY')} ${names} ${time}`;
        }
        if (day !== '*') {
            return `${this.appString('LBL_CRON_MONTHLY')} ${this.appString('LBL_CRON_ON_THE_MONTHDAY')} ${day} ${time}`;
        }

        return `${this.appString('LBL_CRON_DAILY')} ${time}`;
    }

    toggle(list: string[], value: string): void {
        const index = list.indexOf(value);
        if (index === -1) {
            list.push(value);
            list.sort((a, b) => Number(a) - Number(b));
        } else {
            list.splice(index, 1);
        }
        this.update();
    }

    setAdvanced(advanced: boolean): void {
        this.advanced = advanced;
        if (advanced) {
            this.raw = this.basicParts();
        } else {
            this.read(this.raw.join(' '), false);
        }
        this.update();
    }

    /**
     * The expression of the editor, set on the field.
     */
    update(): void {
        const parts = this.advanced ? this.raw.map(part => part.trim() || '*') : this.basicParts();
        const value = this.advanced || (this.hour !== '' && this.minute !== '') ? parts.join(' ') : '';

        this.field.value = value;
        this.field.formControl?.setValue(value);
        this.field.formControl?.markAsDirty();
    }

    protected basicParts(): string[] {
        return [
            this.minute !== '' ? `${Number(this.minute)}` : '*',
            this.hour !== '' ? `${Number(this.hour)}` : '*',
            this.type === 'monthly' && this.selectedDays.length ? this.selectedDays.join(',') : '*',
            '*',
            this.type === 'weekly' && this.selectedWeekdays.length ? this.selectedWeekdays.join(',') : '*',
        ];
    }

    /**
     * Editor state of an expression: basic when it fits, as the legacy field, else advanced.
     */
    protected read(schedule: string, switchMode = true): void {
        const parts = schedule.trim() ? schedule.trim().split(/\s+/) : [];
        const [minutes = '', hours = '', day = '*', month = '*', weekday = '*'] = parts;
        this.raw = [minutes, hours, day, month, weekday];

        const basic = !parts.length || (parts.length === 5 && /^\d+$/.test(minutes) && /^\d+$/.test(hours)
            && month === '*' && (weekday === '*' || day === '*')
            && /^(\*|\d+(,\d+)*)$/.test(day) && /^(\*|\d+(,\d+)*)$/.test(weekday));
        if (switchMode) {
            this.advanced = !basic;
        }
        if (!basic) {
            return;
        }

        this.minute = minutes !== '' ? this.pad(Number(minutes)) : '';
        this.hour = hours !== '' ? this.pad(Number(hours)) : '';
        this.selectedWeekdays = weekday !== '*' ? weekday.split(',') : [];
        this.selectedDays = day !== '*' ? day.split(',') : [];
        this.type = weekday !== '*' ? 'weekly' : (day !== '*' ? 'monthly' : 'daily');
    }

    protected pad(value: number): string {
        return `${value}`.padStart(2, '0');
    }
}
