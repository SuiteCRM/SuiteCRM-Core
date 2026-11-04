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
import {ActivatedRoute, Router} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

export interface SavedSearchCriterion {
    field: string;
    label: string;
    operator: string;
    value: string;
}

export interface SavedSearchViewData {
    id: string;
    name: string;
    module: string;
    moduleLabel: string;
    userId: string;
    userName: string;
    quickFilter: boolean;
    order: string;
    sortOrder: string;
    dateEntered: string;
    dateModified: string;
    criteria: SavedSearchCriterion[];
    users: { id: string; name: string }[];
    canChangeUser: boolean;
    labels: { [key: string]: string };
}

/**
 * View of a saved filter (route saved-search/record/:record, the name link of the saved filters
 * list): its name, user (an admin gives it to another user) and Quick Filter are changed and saved;
 * its module, order and criteria are shown read-only (they are made in the filter panel of the
 * list of the module, which uses the saved-search record layout: that layout is kept for it);
 * Delete removes it.
 *
 * Data from the `saved-search` process (SavedSearchProcessHandler).
 */
@Component({
    selector: 'scrm-saved-search',
    templateUrl: './saved-search.component.html',
    styleUrls: []
})
export class SavedSearchComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'saved-search';

    data: SavedSearchViewData = null;
    name = '';
    userId = '';
    quickFilter = false;
    loading = false;

    protected id = '';
    protected subs: Subscription[] = [];
    protected processSub: Subscription;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected datetime: DatetimeFormatter,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.subs.push(this.route.paramMap.subscribe(params => {
            this.id = params.get('record') ?? '';
            this.data = null;
            this.run({action: 'load'});
        }));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.processSub?.unsubscribe();
    }

    /**
     * A label of the view (sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    trackUser(index: number, user: { id: string }): string {
        return user.id;
    }

    date(value: string): string {
        return value ? this.datetime.toUserFormat(value) : '';
    }

    changed(): boolean {
        return !!this.data && (this.name !== this.data.name || this.userId !== this.data.userId
            || this.quickFilter !== this.data.quickFilter);
    }

    save(): void {
        if (!this.name.trim()) {
            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
            return;
        }
        this.run({action: 'save', name: this.name, userId: this.userId, quickFilter: this.quickFilter});
    }

    cancel(): void {
        this.reset();
    }

    delete(): void {
        this.confirmation.showModal(['NTC_DELETE_CONFIRMATION'], () => this.run({action: 'delete'}));
    }

    protected reset(): void {
        this.name = this.data?.name ?? '';
        this.userId = this.data?.userId ?? '';
        this.quickFilter = !!this.data?.quickFilter;
    }

    protected run(options: { [key: string]: any }): void {
        this.processSub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.processSub = this.processService.submit(SavedSearchComponent.PROCESS, {...options, id: this.id})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.loading = false;
                    const data = process?.data as any;
                    if (process?.status !== 'success') {
                        const key = process?.messages?.[0] || 'LBL_ACTION_ERROR';
                        this.message.addDangerMessage(this.language.getAppString(key) || key);
                    } else if (options.action === 'delete') {
                        this.message.addSuccessMessageByKey('LBL_RECORD_DELETE_SUCCESS');
                        this.router.navigate(['/saved-search']).then();
                    } else if (data?.id) {
                        this.data = data as SavedSearchViewData;
                        this.reset();
                        if (options.action === 'save') {
                            this.message.addSuccessMessageByKey('LBL_SAVED');
                        }
                    }
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                    this.cdr.detectChanges();
                }
            });
    }
}
