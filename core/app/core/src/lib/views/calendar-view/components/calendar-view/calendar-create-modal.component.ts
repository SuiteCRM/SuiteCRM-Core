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

/**
 * The activity to create at the time or day chosen on the calendar (the
 * legacy calendar offers a meeting or a call in its dialog). The modal closes with the
 * module.
 */
@Component({
    selector: 'scrm-calendar-create-modal',
    template: `
        <div class="modal-header">
            <h4 class="modal-title">{{ label('LBL_CREATE_NEW_RECORD') }}</h4>
            <button type="button" class="btn-close close" [attr.aria-label]="label('LBL_CLOSE_BUTTON_TITLE')"
                    (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <p class="calendar-view-modal-period">{{ when }}</p>
            <div class="calendar-view-create">
                <button *ngFor="let item of modules" type="button" class="calendar-view-create-item"
                        (click)="modal.close(item.module)">{{ item.label }}</button>
            </div>
        </div>
    `
})
export class CalendarCreateModalComponent {

    @Input() modules: { module: string; label: string }[] = [];
    @Input() when = '';
    @Input() label: (key: string) => string = key => key;

    constructor(public modal: NgbActiveModal) {
    }
}
