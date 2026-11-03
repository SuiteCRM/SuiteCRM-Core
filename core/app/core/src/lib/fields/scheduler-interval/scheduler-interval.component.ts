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

export type IntervalPeriod = 'min' | 'hour';

/**
 * Interval of a scheduler (legacy job_interval: "minutes::hours::day of month::
 * months::day of week"), as the legacy pages:
 *  - detail: the interval in words (SchedulerJobEntityMapper), or the interval;
 *  - edit: every 1 to 30 minutes or hours on some days of the week (basic setup), or the five
 *    parts in crontab notation (Advanced Options), saved as the legacy Save.php.
 *
 * Field type `scheduler-interval`, detail and edit modes (the interval of the schedulers,
 * SchedulerFieldDefinitionMapper).
 */
@Component({
    selector: 'scrm-scheduler-interval-field',
    templateUrl: './scheduler-interval.component.html',
    styleUrls: []
})
export class SchedulerIntervalFieldComponent extends BaseFieldComponent implements OnInit {

    static readonly READABLE = 'interval_readable';

    readonly intervals = Array.from({length: 30}, (_, index) => index + 1);
    readonly periods: IntervalPeriod[] = ['min', 'hour'];
    // Days of the legacy edit view, Monday first (crontab day of the week: 0 is Sunday).
    readonly days = [
        {value: '1', label: 'LBL_MON'}, {value: '2', label: 'LBL_TUE'}, {value: '3', label: 'LBL_WED'},
        {value: '4', label: 'LBL_THU'}, {value: '5', label: 'LBL_FRI'}, {value: '6', label: 'LBL_SAT'},
        {value: '0', label: 'LBL_SUN'},
    ];
    readonly partLabels = ['LBL_MINS', 'LBL_HOURS', 'LBL_DAY_OF_MONTH', 'LBL_MONTHS', 'LBL_DAY_OF_WEEK'];

    advanced = false;
    interval = 1;
    period: IntervalPeriod = 'min';
    selectedDays: { [day: string]: boolean } = {};
    parts: string[] = ['*', '*', '*', '*', '*'];

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

    get allDays(): boolean {
        return this.days.every(day => !!this.selectedDays[day.value]);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.read(`${this.field?.value ?? ''}`);
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, 'schedulers') || key;
    }

    periodLabel(period: IntervalPeriod): string {
        return `${this.language.getListLabel('scheduler_period_dom', period) || period}`;
    }

    /**
     * The interval in words, while it is the saved one, or the interval.
     */
    readable(): string {
        const value = `${this.field?.value ?? ''}`;
        const readable = this.record?.attributes?.[SchedulerIntervalFieldComponent.READABLE];
        if (readable && value === `${this.record?.attributes?.[this.field?.name] ?? ''}`) {
            return readable;
        }

        return value;
    }

    setAllDays(all: boolean): void {
        this.days.forEach(day => this.selectedDays[day.value] = all);
        this.update();
    }

    /**
     * Switching between the basic setup and the advanced options keeps the interval when it fits.
     */
    setAdvanced(advanced: boolean): void {
        if (advanced) {
            this.parts = this.compute().split('::');
        } else if (!this.readBasic(this.parts)) {
            // The basic setup of the legacy edit view for an interval it cannot show.
            this.interval = 12;
            this.period = 'hour';
            this.setDays('*');
        }
        this.advanced = advanced;
        this.update();
    }

    update(): void {
        const value = this.compute();
        this.field.value = value;
        this.field.formControl?.setValue(value);
        this.field.formControl?.markAsDirty();
    }

    trackIndex(index: number): number {
        return index;
    }

    /**
     * The interval of the editor, as the legacy Save.php: the basic setup runs on every day of
     * the month and every month (no day of the week selected: every day).
     */
    protected compute(): string {
        if (this.advanced) {
            return this.parts.map(part => `${part ?? ''}`.trim() || '*').join('::');
        }

        const days = this.days.filter(day => this.selectedDays[day.value]).map(day => day.value);
        const dayOfWeek = days.length && days.length < this.days.length ? days.join(',') : '*';
        const interval = Number(this.interval) || 1;

        if (this.period === 'min') {
            return `*/${interval}::*::*::*::${dayOfWeek}`;
        }

        return `0::${interval < 24 ? `*/${interval}` : '0'}::*::*::${dayOfWeek}`;
    }

    protected read(value: string): void {
        const parts = value.trim() ? value.trim().split('::') : [];
        this.parts = [0, 1, 2, 3, 4].map(index => (parts[index] ?? '').trim() || '*');
        this.advanced = !this.readBasic(this.parts);
        if (this.advanced) {
            this.setDays(this.parts[4]);
        }
    }

    /**
     * The basic setup of an interval, as the legacy edit view, false when it does not fit (every
     * minute, every N minutes, every N hours on the hour, every day of the month and month).
     */
    protected readBasic(parts: string[]): boolean {
        const [mins, hours, dayOfMonth, months, dayOfWeek] = parts;
        if (dayOfMonth !== '*' || months !== '*') {
            return false;
        }

        if (mins === '*' && hours === '*') {
            this.interval = 1;
            this.period = 'min';
        } else if (mins === '0' && /^\*\/\d+$/.test(hours)) {
            this.interval = Number(hours.slice(2));
            this.period = 'hour';
        } else if (/^\*\/\d+$/.test(mins) && hours === '*') {
            this.interval = Number(mins.slice(2));
            this.period = 'min';
        } else {
            return false;
        }

        if (!this.intervals.includes(this.interval)) {
            return false;
        }

        return this.setDays(dayOfWeek);
    }

    /**
     * The days of the week of an interval part (*, 1,3,5, 1-5), false when it does not fit.
     */
    protected setDays(dayOfWeek: string): boolean {
        this.selectedDays = {};
        if (dayOfWeek === '*') {
            this.days.forEach(day => this.selectedDays[day.value] = true);
            return true;
        }

        for (const item of dayOfWeek.split(',')) {
            const range = item.split('-').map(day => Number(day));
            if (range.some(day => !Number.isInteger(day) || day < 0 || day > 7) || range.length > 2) {
                return false;
            }
            for (let day = range[0]; day <= (range[1] ?? range[0]); day++) {
                this.selectedDays[`${day % 7}`] = true;
            }
        }

        return true;
    }
}
