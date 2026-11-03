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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {Subject, Subscription} from 'rxjs';
import {take, takeUntil} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {MessageService} from '../../../../services/message/message.service';
import {SelectModalService} from '../../../../services/modals/select-modal.service';
import {Record} from '../../../../common/record/record.model';

export type DelegateModule = 'Contacts' | 'Leads' | 'Prospects';

export interface Delegate {
    module: DelegateModule;
    id: string;
    name: string;
    account: string;
    email: string;
    invite: string;
    accept: string;
}

export interface DelegatesPage {
    rows: Delegate[];
    total: number;
    offset: number;
    pageSize: number;
    counts: { all: number; invite: { [status: string]: number }; accept: { [status: string]: number } };
    editable: boolean;
    template: boolean;
    invitations?: { sent: number; failed: number; error?: string };
}

/**
 * The delegates of an event (record view, bottom widget `event-delegates`), as the
 * Delegates subpanel of the legacy page and its buttons: the contacts, leads and targets of the
 * event with their invitation and acceptance status, filtered and paged; the selected ones (or
 * all the matching ones) are marked as invited, not invited, attended, not attended, accepted or
 * declined; delegates are added (records, or the members of target lists) or removed; the
 * invitations are sent to the delegates not invited yet. Data from the `event-delegates`
 * process (EventDelegatesProcessHandler).
 */
@Component({
    selector: 'scrm-event-delegates-widget',
    templateUrl: './event-delegates-widget.component.html',
    styleUrls: []
})
export class EventDelegatesWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'event-delegates';

    readonly modules: DelegateModule[] = ['Contacts', 'Leads', 'Prospects'];
    readonly frontModules: { [module: string]: string } = {
        Contacts: 'contacts',
        Leads: 'leads',
        Prospects: 'prospects',
        ProspectLists: 'prospect-lists',
    };
    readonly addLabels: { [module: string]: string } = {
        Contacts: 'LBL_SELECT_DELEGATES_CONTACTS',
        Leads: 'LBL_SELECT_DELEGATES_LEADS',
        Prospects: 'LBL_SELECT_DELEGATES_TARGETS',
        ProspectLists: 'LBL_SELECT_DELEGATES_TARGET_LIST',
    };
    readonly inviteStatuses = ['Invited', 'Not Invited', 'Attended', 'Not Attended'];
    readonly acceptStatuses = ['Accepted', 'Declined'];

    data: DelegatesPage = null;
    filters = {search: '', module: '', invite: '', accept: ''};
    selected: { [key: string]: Delegate } = {};
    allMatching = false;
    loading = false;
    error = false;

    protected eventId = '';
    protected run$: Subscription = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected processService: ProcessService,
        public language: LanguageStore,
        protected confirmation: ConfirmationModalService,
        protected selectModal: SelectModalService,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    get selectedCount(): number {
        return this.allMatching ? (this.data?.total ?? 0) : Object.keys(this.selected).length;
    }

    get pageSelected(): boolean {
        return !!this.data?.rows?.length && this.data.rows.every(row => !!this.selected[this.rowKey(row)]);
    }

    get pageEnd(): number {
        return Math.min((this.data?.offset ?? 0) + (this.data?.pageSize ?? 0), this.data?.total ?? 0);
    }

    get filtered(): boolean {
        return !!(this.filters.search.trim() || this.filters.module || this.filters.invite || this.filters.accept);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.open(this.context?.id);
        this.context$?.pipe(takeUntil(this.destroy$)).subscribe(context => this.open(context?.id));
    }

    ngOnDestroy(): void {
        this.destroy$.next();
        this.destroy$.complete();
        this.run$?.unsubscribe();
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, 'events') || key;
    }

    listLabel(list: string, key: string): string {
        return `${this.language.getListLabel(list, key) || key}`;
    }

    moduleLabel(module: string): string {
        return this.listLabel('moduleListSingular', module);
    }

    count(field: 'invite' | 'accept', status: string): number {
        return this.data?.counts?.[field]?.[status] ?? 0;
    }

    statusClass(status: string): string {
        return 'is-' + status.toLowerCase().replace(/\s+/g, '-');
    }

    recordLink(row: Delegate): string[] {
        return ['/', this.frontModules[row.module], 'record', row.id];
    }

    rowKey(row: Delegate): string {
        return row.module + ':' + row.id;
    }

    toggle(row: Delegate): void {
        this.allMatching = false;
        const key = this.rowKey(row);
        if (this.selected[key]) {
            delete this.selected[key];
        } else {
            this.selected[key] = row;
        }
    }

    togglePage(): void {
        this.allMatching = false;
        const select = !this.pageSelected;
        (this.data?.rows ?? []).forEach(row => {
            if (select) {
                this.selected[this.rowKey(row)] = row;
            } else {
                delete this.selected[this.rowKey(row)];
            }
        });
    }

    selectMatching(): void {
        this.allMatching = true;
    }

    clearSelection(): void {
        this.selected = {};
        this.allMatching = false;
    }

    applyFilters(): void {
        this.clearSelection();
        this.run({action: 'list'}, 0);
    }

    clearFilters(): void {
        this.filters = {search: '', module: '', invite: '', accept: ''};
        this.applyFilters();
    }

    page(offset: number): void {
        this.run({action: 'list'}, offset);
    }

    mark(field: 'invite' | 'accept', status: string): void {
        if (!this.selectedCount) {
            this.message.addWarningMessage(this.language.getAppString('LBL_LISTVIEW_NO_SELECTED'));
            return;
        }

        const options: { [key: string]: any } = {action: 'status', field, status};
        if (this.allMatching) {
            options.all = true;
        } else {
            options.delegates = Object.values(this.selected).map(row => ({module: row.module, id: row.id}));
        }
        this.clearSelection();
        this.run(options, this.data?.offset ?? 0);
    }

    add(module: string): void {
        // The multi-select modal gives the selected records by id, despite its Record[] type.
        this.selectModal.showSelectModal(this.frontModules[module], (records: Record[] | { [id: string]: Record }) => {
            const ids = Object.values(records ?? {}).map((record: Record) => record?.id).filter(id => !!id);
            if (ids.length) {
                this.run({action: 'add', module, ids}, this.data?.offset ?? 0);
            }
        }, {multiSelect: true});
    }

    remove(row: Delegate): void {
        this.confirmation.showModal(['LBL_DELEGATES_REMOVE_CONFIRM'], () => {
            delete this.selected[this.rowKey(row)];
            this.run({action: 'remove', module: row.module, delegate: row.id}, this.data?.offset ?? 0);
        }, () => {}, {}, {module: 'events'});
    }

    invite(): void {
        this.confirmation.showModal(['LBL_DELEGATES_INVITE_CONFIRM'], () => {
            this.run({action: 'invite'}, this.data?.offset ?? 0);
        }, () => {}, {}, {module: 'events'});
    }

    protected open(id: string): void {
        if (!id || id === this.eventId) {
            return;
        }
        this.eventId = id;
        this.data = null;
        this.clearSelection();
        this.run({action: 'list'}, 0);
    }

    protected run(options: { [key: string]: any }, offset: number): void {
        this.run$?.unsubscribe();
        this.loading = true;
        this.error = false;
        this.cdr.detectChanges();

        const filters = {...this.filters, search: this.filters.search.trim()};
        this.run$ = this.processService.submit(EventDelegatesWidgetComponent.PROCESS, {...options, id: this.eventId, filters, offset})
            .pipe(take(1))
            .subscribe({
                next: (process: Process) => {
                    const data = process?.data as DelegatesPage;
                    this.loading = false;
                    if (data?.rows) {
                        this.data = data;
                    }
                    if (options.action === 'invite') {
                        this.reportInvitations(data?.invitations);
                    } else if (process?.status !== 'success') {
                        this.error = true;
                        this.message.addDangerMessage(this.label('LBL_DELEGATES_ERROR'));
                    }
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.message.addDangerMessage(this.label('LBL_DELEGATES_ERROR'));
                    this.cdr.detectChanges();
                }
            });
    }

    /**
     * Result of the invitations, with the messages of the legacy action.
     */
    protected reportInvitations(result: { sent: number; failed: number; error?: string }): void {
        if (!result) {
            this.message.addDangerMessage(this.label('LBL_DELEGATES_ERROR'));
        } else if (result.error) {
            this.message.addDangerMessage(this.label(result.error));
        } else if (!result.sent && !result.failed) {
            this.message.addWarningMessage(this.label('LBL_ERROR_MSG_1'));
        } else if (!result.sent) {
            this.message.addDangerMessage(this.label('LBL_ERROR_MSG_2'));
        } else if (result.failed) {
            this.message.addWarningMessage(`${result.sent} ${this.label('LBL_DELEGATES_SENT')} ${result.failed}${this.label('LBL_ERROR_MSG_4')}`);
        } else {
            this.message.addSuccessMessage(this.label('LBL_SUCCESS_MSG'));
        }
    }
}
