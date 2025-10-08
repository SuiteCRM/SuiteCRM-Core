/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {Component, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {Observable, Subscription} from 'rxjs';
import {BulkActionsMap} from '../../common/actions/bulk-action.model';
import {DropdownButtonInterface} from '../../common/components/button/dropdown-button.model';
import {SelectionStatus} from '../../common/views/list/record-selection.model';
import {SelectionDataSource} from '../../common/views/list/selection.model';
import {LanguageStore} from '../../store/language/language.store';

export interface BulkActionDataSource {
    getBulkActions(): Observable<BulkActionsMap>;

    executeBulkAction(action: string): void;
}

export interface BulkActionViewModel {
    status: SelectionStatus;
    count: number;
    actions: BulkActionsMap;
}

@Component({
    selector: 'scrm-bulk-action-menu',
    templateUrl: 'bulk-action-menu.component.html'
})
export class BulkActionMenuComponent implements OnInit, OnDestroy {

    @Input() selectionSource: SelectionDataSource;
    @Input() actionSource: BulkActionDataSource;

    dropdownConfig: DropdownButtonInterface;
    dropdownSmallConfig: DropdownButtonInterface;
    subs: Subscription[] = [];
    status: SelectionStatus = SelectionStatus.NONE;
    count: WritableSignal<number> = signal(0);


    constructor(protected languageStore: LanguageStore) {
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
        this.count = signal(0);
        this.status = SelectionStatus.NONE;
    }

    ngOnInit(): void {
        this.subs = [];

        this.subs.push(this.selectionSource.getSelectionStatus().subscribe(status => this.status = status));
        this.subs.push(this.selectionSource.getSelectedCount().subscribe(count => this.count.set(count)));

        this.subs.push(this.actionSource.getBulkActions().subscribe(actions => {
            const dropdownConfig = {
                labelKey: 'LBL_BULK_ACTION_BUTTON_LABEL',
                klass: ['bulk-action-button', 'btn', 'btn-sm'],
                wrapperKlass: ['bulk-action-group', 'float-left'],
                items: []
            } as DropdownButtonInterface;

            const dropdownSmallConfig = {
                labelKey: 'LBL_ACTION',
                klass: ['bulk-action-button', 'btn', 'btn-sm'],
                wrapperKlass: ['bulk-action-group', 'float-left'],
                items: []
            } as DropdownButtonInterface;

            Object.keys(actions).forEach(actionKey => {
                const action = actions[actionKey];
                dropdownConfig.items.push({
                    labelKey: action.labelKey ?? '',
                    klass: [`${actionKey}-bulk-action`],
                    onClick: (): void => {
                        this.actionSource.executeBulkAction(action.key);
                    }
                });
                dropdownSmallConfig.items.push({
                    labelKey: action.labelKey ?? '',
                    klass: [`${actionKey}-bulk-action`],
                    onClick: (): void => {
                        this.actionSource.executeBulkAction(action.key);
                    }
                });
            });

            this.dropdownConfig = dropdownConfig;
            this.dropdownSmallConfig = dropdownSmallConfig;
        }));
    }

    selectPage(): void {
        this.selectionSource.updateSelection(SelectionStatus.PAGE);
    }

    selectAll(): void {
        this.selectionSource.updateSelection(SelectionStatus.ALL);
    }

    deselectAll(): void {
        this.selectionSource.updateSelection(SelectionStatus.NONE);
    }

    toggleSelection(status: SelectionStatus): void {
        if (status === SelectionStatus.ALL) {
            this.selectionSource.updateSelection(SelectionStatus.NONE);
            return;
        }

        this.selectionSource.updateSelection(SelectionStatus.ALL);
    }

    protected readonly SelectionStatus = SelectionStatus;
}
