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

import {Component, Input, OnInit} from '@angular/core';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {CalendarSettings} from './calendar.types';

/**
 * Settings of the calendar, as the legacy settings dialog: time slots in the
 * day and week views, day start and end, shared calendars separate, calls, tasks and
 * completed activities shown, colours of the activities. The modal closes with the
 * settings.
 */
@Component({
    selector: 'scrm-calendar-settings-modal',
    template: `
        <form (ngSubmit)="submit()">
            <div class="modal-header">
                <h4 class="modal-title">{{ label('LBL_SETTINGS_TITLE') }}</h4>
                <button type="button" class="btn-close close" [attr.aria-label]="label('LBL_CLOSE_BUTTON_TITLE')"
                        (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body calendar-view-settings">
                <fieldset>
                    <legend>{{ label('LBL_GENERAL_SETTINGS') }}</legend>
                    <div class="calendar-view-check">
                        <input id="calendar-view-timeslots" name="timeslots" type="checkbox" [(ngModel)]="settings.timeslots">
                        <label for="calendar-view-timeslots">{{ label('LBL_SETTINGS_DISPLAY_TIMESLOTS') }}</label>
                    </div>
                    <div *ngIf="settings.timeslots" class="calendar-view-times">
                        <div *ngFor="let part of ['dayStart', 'dayEnd']" class="calendar-view-time-field" role="group"
                             [attr.aria-labelledby]="'calendar-view-' + part">
                            <span [id]="'calendar-view-' + part">{{ label(part === 'dayStart' ? 'LBL_SETTINGS_TIME_STARTS' : 'LBL_SETTINGS_TIME_ENDS') }}</span>
                            <select class="form-control form-control-sm" [name]="part + 'Hours'" [attr.aria-label]="label(part === 'dayStart' ? 'LBL_SETTINGS_TIME_STARTS' : 'LBL_SETTINGS_TIME_ENDS')"
                                    [ngModel]="hours(part)" (ngModelChange)="setTime(part, $event, minutes(part))">
                                <option *ngFor="let hour of hourOptions" [value]="hour">{{ hour }}</option>
                            </select>
                            :
                            <select class="form-control form-control-sm" [name]="part + 'Minutes'" [attr.aria-label]="label(part === 'dayStart' ? 'LBL_SETTINGS_TIME_STARTS' : 'LBL_SETTINGS_TIME_ENDS')"
                                    [ngModel]="minutes(part)" (ngModelChange)="setTime(part, hours(part), $event)">
                                <option *ngFor="let minute of minuteOptions" [value]="minute">{{ minute }}</option>
                            </select>
                        </div>
                    </div>
                    <p *ngIf="!validTimes()" class="calendar-view-modal-error" role="alert">{{ label('LBL_DATE_END_ERROR') }}</p>
                    <div class="calendar-view-check">
                        <input id="calendar-view-separate" name="sharedSeparate" type="checkbox" [(ngModel)]="settings.sharedSeparate">
                        <label for="calendar-view-separate">{{ label('LBL_SETTINGS_DISPLAY_SHARED_CALENDAR_SEPARATE') }}</label>
                    </div>
                    <div class="calendar-view-check">
                        <input id="calendar-view-calls" name="showCalls" type="checkbox" [(ngModel)]="settings.showCalls">
                        <label for="calendar-view-calls">{{ label('LBL_SETTINGS_CALLS_SHOW') }}</label>
                    </div>
                    <div class="calendar-view-check">
                        <input id="calendar-view-tasks" name="showTasks" type="checkbox" [(ngModel)]="settings.showTasks">
                        <label for="calendar-view-tasks">{{ label('LBL_SETTINGS_TASKS_SHOW') }}</label>
                    </div>
                    <div class="calendar-view-check">
                        <input id="calendar-view-completed" name="showCompleted" type="checkbox" [(ngModel)]="settings.showCompleted">
                        <label for="calendar-view-completed">{{ label('LBL_SETTINGS_COMPLETED_SHOW') }}</label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>{{ label('LBL_COLOR_SETTINGS') }}</legend>
                    <table class="calendar-view-colors">
                        <thead>
                        <tr>
                            <th scope="col">{{ label('LBL_MODULE') }}</th>
                            <th scope="col">{{ label('LBL_BODY') }}</th>
                            <th scope="col">{{ label('LBL_BORDER') }}</th>
                            <th scope="col">{{ label('LBL_TEXT') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr *ngFor="let color of settings.colors; let index = index">
                            <th scope="row">
                                <span class="calendar-view-sample" [style.background-color]="'#' + color.body"
                                      [style.border-color]="'#' + color.border" [style.color]="'#' + color.text">{{ color.label }}</span>
                            </th>
                            <td *ngFor="let part of parts">
                                <input type="color" [name]="part + index" [attr.aria-label]="color.label + ' — ' + label(partLabels[part])"
                                       [ngModel]="'#' + color[part]" (ngModelChange)="color[part] = $event.replace('#', '').toUpperCase()">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" (click)="modal.dismiss()">{{ label('LBL_CANCEL_BUTTON') }}</button>
                <button type="submit" class="btn btn-sm btn-main" [disabled]="!validTimes()">{{ label('LBL_APPLY_BUTTON') }}</button>
            </div>
        </form>
    `
})
export class CalendarSettingsModalComponent implements OnInit {

    @Input() settings: CalendarSettings;
    @Input() label: (key: string) => string = key => key;

    readonly parts: ('body' | 'border' | 'text')[] = ['body', 'border', 'text'];
    readonly partLabels = {body: 'LBL_BODY', border: 'LBL_BORDER', text: 'LBL_TEXT'};
    // As the legacy settings: hours 00 to 24, quarters of an hour.
    readonly hourOptions = Array.from({length: 25}, (value, hour) => String(hour).padStart(2, '0'));
    readonly minuteOptions = ['00', '15', '30', '45'];

    constructor(public modal: NgbActiveModal) {
    }

    ngOnInit(): void {
        // Times of the legacy preferences out of the options (e.g. 08:10) go to the quarter before.
        (['dayStart', 'dayEnd'] as const).forEach(part => this.setTime(part, this.hours(part), this.minutes(part)));
    }

    hours(part: 'dayStart' | 'dayEnd'): string {
        return (this.settings[part] || '00:00').split(':')[0];
    }

    minutes(part: 'dayStart' | 'dayEnd'): string {
        const minutes = parseInt((this.settings[part] || '00:00').split(':')[1], 10) || 0;

        return String(Math.floor(minutes / 15) * 15).padStart(2, '0');
    }

    setTime(part: 'dayStart' | 'dayEnd', hours: string, minutes: string): void {
        this.settings[part] = hours === '24' ? '24:00' : hours + ':' + minutes;
    }

    validTimes(): boolean {
        return this.settings.dayStart < this.settings.dayEnd;
    }

    submit(): void {
        if (this.validTimes()) {
            this.modal.close(this.settings);
        }
    }
}
