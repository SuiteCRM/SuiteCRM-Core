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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {BaseFieldComponent} from '../base/base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../store/language/language.store';
import {Process, ProcessService} from '../../services/process/process.service';

export type RecipientType = 'Email Address' | 'Specify User' | 'Users';

export interface ReportRecipient {
    type: RecipientType;
    email?: string;
    userId?: string;
    userName?: string;
    scope?: 'all' | 'role' | 'security_group';
    groupId?: string;
    groupName?: string;
    roleId?: string;
    roleName?: string;
}

export interface RecipientChoice {
    id: string;
    name: string;
}

/**
 * Recipients of a scheduled report (legacy email_recipients function field), as the
 * legacy field: one line per recipient, an email address, a user, or the users (all, of a role,
 * or of a security group with an optional role). The value is the list of recipients
 * (RecipientsEntityMapper, saved by RecipientsSaveHandler); the users, roles and groups to
 * choose from come from the `scheduled-report-recipients` process.
 *
 * Field type `report-recipients`, detail and edit modes (the recipients of the scheduled
 * reports, ScheduledReportFieldDefinitionMapper).
 */
@Component({
    selector: 'scrm-report-recipients-field',
    templateUrl: './report-recipients.component.html',
    styleUrls: []
})
export class ReportRecipientsFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'scheduled-report-recipients';

    readonly types: RecipientType[] = ['Email Address', 'Specify User', 'Users'];
    readonly scopes = ['all', 'role', 'security_group'];

    recipients: ReportRecipient[] = [];
    users: RecipientChoice[] = [];
    roles: RecipientChoice[] = [];
    groups: RecipientChoice[] = [];

    protected options$: Subscription = null;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore,
        protected processService: ProcessService,
        protected cdr: ChangeDetectorRef
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    get editing(): boolean {
        return this.mode !== 'detail' && this.mode !== 'list';
    }

    ngOnInit(): void {
        super.ngOnInit();
        const value = this.field?.valueObject ?? this.record?.attributes?.email_recipients ?? [];
        this.recipients = Array.isArray(value) ? value.map((recipient: ReportRecipient) => ({...recipient})) : [];

        if (this.editing) {
            this.options$ = this.processService.submit(ReportRecipientsFieldComponent.PROCESS, {}).pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.users = (process?.data?.users ?? []) as RecipientChoice[];
                    this.roles = (process?.data?.roles ?? []) as RecipientChoice[];
                    this.groups = (process?.data?.groups ?? []) as RecipientChoice[];
                    this.cdr.markForCheck();
                },
                error: () => this.cdr.markForCheck()
            });
        }
    }

    ngOnDestroy(): void {
        this.options$?.unsubscribe();
        super.ngOnDestroy();
    }

    listLabel(list: string, key: string): string {
        return `${this.language.getListLabel(list, key) || key}`;
    }

    appString(key: string): string {
        return this.language.getAppString(key) || key;
    }

    /**
     * A recipient in words: "Email: a@b.org", "User: Jane Doe", "Users: ALL Users in Role: Sales".
     */
    describe(recipient: ReportRecipient): string {
        let text = recipient.email ?? '';
        if (recipient.type === 'Specify User') {
            text = recipient.userName || recipient.userId || '';
        } else if (recipient.type === 'Users') {
            text = this.listLabel('aor_assign_options', recipient.scope || 'all');
            const names = [
                recipient.scope === 'security_group' ? recipient.groupName : '',
                recipient.scope !== 'all' ? recipient.roleName : '',
            ].filter(name => !!name);
            text += names.length ? `: ${names.join(', ')}` : '';
        }

        return `${this.listLabel('aor_email_type_list', recipient.type)}: ${text}`;
    }

    add(): void {
        this.recipients.push({type: 'Email Address', email: ''});
        this.update();
    }

    remove(index: number): void {
        this.recipients.splice(index, 1);
        this.update();
    }

    /**
     * The recipients of the editor, set on the field; the names follow the choices.
     */
    update(): void {
        this.recipients.forEach(recipient => {
            recipient.userName = this.users.find(user => user.id === recipient.userId)?.name ?? recipient.userName ?? '';
            recipient.groupName = this.groups.find(group => group.id === recipient.groupId)?.name ?? recipient.groupName ?? '';
            recipient.roleName = this.roles.find(role => role.id === recipient.roleId)?.name ?? recipient.roleName ?? '';
            if (recipient.type === 'Users' && !recipient.scope) {
                recipient.scope = 'all';
            }
        });

        const value = this.recipients.map(recipient => ({...recipient}));
        this.field.valueObject = value;
        this.field.value = value as any;
        this.field.formControl?.setValue(value);
        this.field.formControl?.markAsDirty();
    }
}
