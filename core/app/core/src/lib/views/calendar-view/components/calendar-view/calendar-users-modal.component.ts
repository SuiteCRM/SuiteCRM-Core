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

import {Component, Input} from '@angular/core';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {CalendarUsersSelectComponent} from './calendar-users-select.component';
import {CalendarOption} from './calendar.types';

/**
 * Users of the shared calendar (legacy "User List"). The modal closes with
 * their ids.
 */
@Component({
    selector: 'scrm-calendar-users-modal',
    template: `
        <div class="modal-header">
            <h4 class="modal-title" id="calendar-view-users-title">{{ label('LBL_SELECT_USERS') }}</h4>
            <button type="button" class="btn-close close" [attr.aria-label]="label('LBL_CLOSE_BUTTON_TITLE')"
                    (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <scrm-calendar-users-select labelId="calendar-view-users-title" [options]="users" [value]="value" (valueChange)="value = $event"
                              [allLabel]="label('LBL_USERS')" [searchLabel]="label('LBL_SEARCH')"
                              [clearLabel]="label('LBL_CLEAR_BUTTON_LABEL')"></scrm-calendar-users-select>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" (click)="modal.dismiss()">{{ label('LBL_CANCEL_BUTTON') }}</button>
            <button type="button" class="btn btn-sm btn-main" [disabled]="!value.length"
                    (click)="modal.close(value)">{{ label('LBL_APPLY_BUTTON') }}</button>
        </div>
    `
})
export class CalendarUsersModalComponent {

    @Input() users: CalendarOption[] = [];
    @Input() value: string[] = [];
    @Input() label: (key: string) => string = key => key;

    constructor(public modal: NgbActiveModal) {
    }
}
