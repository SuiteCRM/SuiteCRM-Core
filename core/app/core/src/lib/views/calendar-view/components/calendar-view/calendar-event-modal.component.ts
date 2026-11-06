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
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {CalendarColor, CalendarEvent} from './calendar.types';

/**
 * Details of an activity of the calendar (the legacy calendar shows them on
 * hover and in its task and event dialogs): period, related record and the main fields,
 * with its record, its edit view and its deletion. The modal closes with 'view', 'edit'
 * or 'delete'.
 */
@Component({
    selector: 'scrm-calendar-event-modal',
    // Shared modal styles, scoped under .calendar-view-modal (the modal window class).
    template: `
        <div class="modal-header">
            <h4 class="modal-title">{{ event.name }}</h4>
            <button type="button" class="btn-close close" [attr.aria-label]="label('LBL_CLOSE_BUTTON_TITLE')"
                    (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <p class="calendar-view-modal-period">
                <span *ngIf="color" class="calendar-view-swatch" [style.background-color]="'#' + color.body"
                      [style.border-color]="'#' + color.border"></span>
                <span *ngIf="color">{{ color.label }} · </span>{{ period }}
            </p>
            <dl class="calendar-view-details">
                <ng-container *ngIf="event.parent">
                    <dt>{{ event.parent.label }}</dt>
                    <dd>
                        <a *ngIf="event.parent.view; else parentName" [routerLink]="parentLink()"
                           (click)="modal.dismiss()">{{ event.parent.name }}</a>
                        <ng-template #parentName>{{ event.parent.name }}</ng-template>
                    </dd>
                </ng-container>
                <ng-container *ngFor="let detail of event.details">
                    <dt>{{ detail.label }}</dt>
                    <dd [class.is-multiline]="detail.multiline">{{ detail.value }}</dd>
                </ng-container>
            </dl>
        </div>
        <div class="modal-footer">
            <button *ngIf="event.delete" type="button" class="btn btn-sm btn-outline-danger"
                    (click)="modal.close('delete')">{{ label('LBL_DELETE_BUTTON') }}</button>
            <button *ngIf="event.edit" type="button" class="btn btn-sm btn-outline-secondary"
                    (click)="modal.close('edit')">{{ label('LBL_EDIT_BUTTON') }}</button>
            <button *ngIf="event.view" type="button" class="btn btn-sm btn-main"
                    (click)="modal.close('view')">{{ label('LBL_VIEW_BUTTON') }}</button>
        </div>
    `
})
export class CalendarEventModalComponent {

    @Input() event: CalendarEvent;
    @Input() color: CalendarColor = null;
    @Input() period = '';
    @Input() label: (key: string) => string = key => key;

    constructor(public modal: NgbActiveModal, protected navigation: ModuleNavigation) {
    }

    parentLink(): string {
        return this.navigation.getRecordRouterLink(this.event.parent.module, this.event.parent.id);
    }
}
