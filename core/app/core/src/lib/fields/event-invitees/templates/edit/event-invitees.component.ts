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


import {ChangeDetectorRef, Component, inject, OnDestroy, OnInit} from '@angular/core';
import {Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, switchMap, take} from 'rxjs/operators';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {EventInvitee, eventInviteeKey, EventInviteesValue} from '../../event-invitees.model';

/**
 * Invitees of a call or a meeting edited as in the legacy edit view (scheduler):
 * users, contacts and leads searched by name or email address and added, or
 * removed. A new event invites the current user.
 */
@Component({
    selector: 'scrm-event-invitees-edit',
    templateUrl: './event-invitees.component.html',
    styleUrls: []
})
export class EventInviteesEditFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    invitees: EventInvitee[] = [];
    query = '';
    results: EventInvitee[] = [];
    searching = false;
    open = false;

    protected static readonly PROCESS_TYPE = 'event-invitees';

    protected searchQuery = new Subject<string>();
    protected subscriptions: Subscription[] = [];

    protected language = inject(LanguageStore);
    protected processService = inject(ProcessService);
    protected changeDetector = inject(ChangeDetectorRef);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        const value: EventInviteesValue = this.field?.valueObject ?? this.record?.attributes?.[this.field?.name] ?? null;
        if (Array.isArray(value?.invitees)) {
            this.invitees = value.invitees.map(invitee => ({...invitee}));
            this.update(false);
        } else {
            this.loadDefaults();
        }

        this.subscriptions.push(this.searchQuery.pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap(query => this.processService.submit(
                EventInviteesEditFieldComponent.PROCESS_TYPE,
                {action: 'search', query}
            ).pipe(take(1)))
        ).subscribe({
            next: (process: Process) => {
                const found: EventInvitee[] = (process?.data as any)?.invitees ?? [];
                const invited = new Set(this.invitees.map(invitee => eventInviteeKey(invitee.module, invitee.id)));
                this.results = found.filter(invitee => !invited.has(eventInviteeKey(invitee.module, invitee.id)));
                this.searching = false;
                this.changeDetector.markForCheck();
            },
            error: () => {
                this.results = [];
                this.searching = false;
            }
        }));
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
        super.ngOnDestroy();
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.record?.module ?? '') || this.language.getAppString(key) || key;
    }

    moduleLabel(module: string): string {
        return this.language.getListLabel('moduleListSingular', module) || module;
    }

    acceptStatus(status: string): string {
        return this.language.getListLabel('dom_meeting_accept_status', status || 'none') || status;
    }

    search(query: string): void {
        this.query = query;
        this.results = [];
        this.open = true;
        this.searching = query.trim().length > 0;
        if (this.searching) {
            this.searchQuery.next(query.trim());
        }
    }

    closeResults(): void {
        // After a click in the result list (picked on mousedown).
        setTimeout(() => {
            this.open = false;
            this.changeDetector.markForCheck();
        }, 150);
    }

    add(invitee: EventInvitee): void {
        this.invitees.push({...invitee, accept_status: invitee.accept_status || 'none'});
        this.results = this.results.filter(item => item !== invitee);
        this.query = '';
        this.open = false;
        this.update(true);
    }

    remove(invitee: EventInvitee): void {
        this.invitees = this.invitees.filter(item => item !== invitee);
        this.update(true);
    }

    trackByKey(index: number, invitee: EventInvitee): string {
        return eventInviteeKey(invitee.module, invitee.id);
    }

    /**
     * The invitees given to the field (saved with the record).
     */
    protected update(dirty: boolean): void {
        const value: EventInviteesValue = {invitees: this.invitees.map(invitee => ({...invitee}))};

        this.field.valueObject = value;
        this.field.formControl?.setValue(value);
        if (dirty) {
            this.field.formControl?.markAsDirty();
        }
        if (this.record?.attributes) {
            this.record.attributes[this.field.name] = value;
        }
    }

    /**
     * Invitees of a new event (no value yet): the current user. The value is only
     * set once they are loaded, as the create view may build the field again meanwhile.
     */
    protected loadDefaults(): void {
        this.subscriptions.push(this.processService.submit(
            EventInviteesEditFieldComponent.PROCESS_TYPE,
            {action: 'defaults'}
        ).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const defaults: EventInvitee[] = (process?.data as any)?.invitees ?? [];
                const invited = new Set(this.invitees.map(invitee => eventInviteeKey(invitee.module, invitee.id)));
                this.invitees = [
                    ...this.invitees,
                    ...defaults.filter(invitee => !invited.has(eventInviteeKey(invitee.module, invitee.id)))
                ];
                this.update(false);
                this.changeDetector.markForCheck();
            },
            error: () => {
                this.update(false);
            }
        }));
    }
}
