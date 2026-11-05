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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {LanguageStore} from '../../../../store/language/language.store';

/**
 * Name (and, for a new tab, number of columns) of a dashboard tab.
 * The modal closes with {title, columns}.
 */
@Component({
    selector: 'scrm-dashboard-tab-modal',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <form (ngSubmit)="submit()">
            <div class="modal-header">
                <h4 class="modal-title">{{ label(isNew ? 'LBL_DASHBOARD_ADD_TAB' : 'LBL_DASHBOARD_RENAME_TAB') }}</h4>
                <button type="button" class="btn-close close" [attr.aria-label]="appLabel('LBL_CLOSE_BUTTON_TITLE')"
                        (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body dashboard-options">
                <div class="dashboard-field">
                    <label for="dashboard-tab-title">{{ label('LBL_DASHBOARD_TAB_NAME') }}</label>
                    <input id="dashboard-tab-title" name="title" class="form-control" maxlength="100" required [(ngModel)]="title">
                </div>
                <div *ngIf="isNew" class="dashboard-field">
                    <label for="dashboard-tab-columns">{{ label('LBL_DASHBOARD_COLUMNS') }}</label>
                    <select id="dashboard-tab-columns" name="columns" class="form-control" [(ngModel)]="columns">
                        <option *ngFor="let count of [1, 2, 3]" [ngValue]="count">{{ count }}</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" (click)="modal.dismiss()">{{ appLabel('LBL_CANCEL_BUTTON_LABEL') }}</button>
                <button type="submit" class="btn btn-sm btn-main" [disabled]="!title.trim()">{{ appLabel('LBL_SAVE_BUTTON_LABEL') }}</button>
            </div>
        </form>
    `
})
export class DashboardTabModalComponent {

    @Input() isNew = true;
    @Input() title = '';
    @Input() columns = 2;

    constructor(public modal: NgbActiveModal, protected languageStore: LanguageStore) {
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    appLabel(key: string): string {
        return this.languageStore.getAppString(key) || key;
    }

    submit(): void {
        if (this.title.trim()) {
            this.modal.close({title: this.title.trim(), columns: this.columns});
        }
    }
}
