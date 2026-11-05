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

import {ChangeDetectorRef, Component, Input, OnDestroy, OnInit} from '@angular/core';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ButtonInterface} from '../../common/components/button/button.model';
import {ModalCloseFeedBack} from '../../common/components/modal/modal.model';
import {LanguageStore} from '../../store/language/language.store';
import {Process, ProcessService} from '../../services/process/process.service';

export interface ChangeLogChange {
    id: string;
    field: string;
    before: string;
    after: string;
    user: string;
    date: string;
}

export interface ChangeLogData {
    module: string;
    name: string;
    audited: boolean;
    fields: string[];
    changes: ChangeLogChange[];
    labels: { [key: string]: string };
}

/**
 * Change log of a record (legacy Audit popup): the fields audited in its module and its changes,
 * newest first (field, old value, new value, user, date), with a search on them. Data from the
 * `record-change-log` process.
 */
@Component({
    selector: 'scrm-change-log-modal',
    templateUrl: './change-log-modal.component.html',
    styleUrls: [],
})
export class ChangeLogModalComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'record-change-log';

    @Input() module = '';
    @Input() id = '';

    data: ChangeLogData = null;
    error = false;
    search = '';
    closeButton: ButtonInterface;

    protected shownSearch: string = null;
    protected shown: ChangeLogChange[] = [];
    protected sub: Subscription = null;

    constructor(
        public activeModal: NgbActiveModal,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.closeButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.activeModal.close({type: 'close-button'} as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.sub = this.processService.submit(ChangeLogModalComponent.PROCESS, {module: this.module, id: this.id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as ChangeLogData;
                this.error = process?.status !== 'success' || !data?.changes;
                this.data = this.error ? null : data;
                this.shownSearch = null;
                this.cdr.markForCheck();
            },
            error: () => {
                this.error = true;
                this.cdr.markForCheck();
            }
        });
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of the modal (Audit strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || key;
    }

    /**
     * The changes shown: all, or those matching the search (field, values or user).
     */
    changes(): ChangeLogChange[] {
        const search = this.search.trim().toLowerCase();
        if (this.shownSearch !== search) {
            this.shownSearch = search;
            this.shown = (this.data?.changes ?? []).filter(change => !search
                || [change.field, change.before, change.after, change.user].some(value => (value ?? '').toLowerCase().includes(search)));
        }

        return this.shown;
    }

    trackChange(index: number, change: ChangeLogChange): string {
        return change.id || `${index}`;
    }
}
