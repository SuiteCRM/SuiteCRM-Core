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

import {Component, EventEmitter, Input, Output} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {DashletOptionChoice} from '../../dashboard.model';

/**
 * A multiple choice of the dashlet options (users, countries,
 * sales stages...): checkboxes, filtered by a search box when the list is long.
 * No choice means no filter ("All").
 */
@Component({
    selector: 'scrm-dashlet-multi-select',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <div class="dashboard-multi" role="group" [attr.aria-labelledby]="labelId">
            <div class="dashboard-multi-head">
                <input *ngIf="options.length > 8" type="search" class="form-control dashboard-multi-search"
                       [placeholder]="searchLabel" [attr.aria-label]="searchLabel" [(ngModel)]="search">
                <span class="dashboard-multi-count">{{ value.length ? selectedLabel.replace('%s', '' + value.length) : allLabel }}</span>
                <button *ngIf="value.length" type="button" class="btn btn-link dashboard-multi-clear" (click)="clear()">{{ clearLabel }}</button>
            </div>
            <ul class="dashboard-multi-list">
                <li *ngFor="let option of shown()">
                    <label>
                        <input type="checkbox" [checked]="value.includes(option.value)" (change)="toggle(option.value)">
                        <span>{{ option.label || option.value }}</span>
                    </label>
                </li>
            </ul>
        </div>
    `
})
export class DashletMultiSelectComponent {

    @Input() options: DashletOptionChoice[] = [];
    @Input() value: string[] = [];
    @Input() labelId = '';
    @Input() searchLabel = 'Search';
    @Input() allLabel = 'All';
    @Input() selectedLabel = '%s selected';
    @Input() clearLabel = 'Clear';
    @Output() valueChange = new EventEmitter<string[]>();

    search = '';

    shown(): DashletOptionChoice[] {
        const search = this.search.trim().toLowerCase();

        return search ? this.options.filter(option => (option.label || option.value).toLowerCase().includes(search)) : this.options;
    }

    toggle(value: string): void {
        this.value = this.value.includes(value) ? this.value.filter(item => item !== value) : [...this.value, value];
        this.valueChange.emit(this.value);
    }

    clear(): void {
        this.value = [];
        this.valueChange.emit(this.value);
    }
}
