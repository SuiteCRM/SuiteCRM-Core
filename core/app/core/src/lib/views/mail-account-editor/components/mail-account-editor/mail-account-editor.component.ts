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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {ActivatedRoute, Router} from '@angular/router';
import {combineLatest, Observable, of, Subscription} from 'rxjs';
import {catchError, map, take} from 'rxjs/operators';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {SelectModalService} from '../../../../services/modals/select-modal.service';
import {MailAccount, MailAccountEntry, MailAccountField, MailAccountFolderChoice, MailAccountPanel, MailAccountRule, MailAccountTestResult} from '../../mail-account-editor.types';

/**
 * The account editor of SuiteCRM 8 (create and edit an account), as the legacy
 * edit view of the account module: the backend gives the form (panels of fields with the rules of
 * the legacy page that show them) and the values, and saves the account with the legacy code.
 *
 * The fields of a mail server: the connection test (or a test email to an address), and the
 * folders of the server to choose the monitored, trash and sent folders (and the primary one among
 * the monitored).
 *
 * Also the external OAuth providers (key-value lists) and connections (authorization by the
 * provider) of the OAuth mail accounts, and the calendar accounts.
 *
 * Route data: module (frontend name) and process (backend editor), e.g. inbound-email/edit
 * (?type=personal|group|bounce for a new account) and inbound-email/edit/<id>, or
 * outbound-email-accounts/edit (?type=user|group).
 */
@Component({
    selector: 'scrm-mail-account-editor',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './mail-account-editor.component.html',
    styleUrls: []
})
export class MailAccountEditorComponent implements OnInit, OnDestroy {

    account: MailAccount | null = null;
    loading = false;
    saving = false;
    testing = false;
    error = false;
    testResult: MailAccountTestResult | null = null;
    folderChoice: MailAccountFolderChoice | null = null;

    protected module = '';
    protected process = '';
    protected id = '';
    protected type = '';
    protected subscriptions: Subscription[] = [];
    // The token sent back by the authorization window of an OAuth connection.
    protected authChannel: BroadcastChannel | null = null;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected messages: MessageService,
        protected selectModal: SelectModalService,
        protected formatter: DataTypeFormatter,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get values(): { [field: string]: any } {
        return this.account?.values ?? {};
    }

    ngOnInit(): void {
        this.module = this.route.snapshot.data?.module ?? '';
        this.process = this.route.snapshot.data?.process ?? '';
        this.subscriptions.push(combineLatest([this.route.paramMap, this.route.queryParamMap]).subscribe(([params, query]) => {
            this.id = params.get('record') || query.get('record') || '';
            this.type = query.get('type') || 'personal';
            this.load();
        }));
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
        this.authChannel?.close();
    }

    /** A label of the page (given by the backend), else of the application. */
    l(key: string): string {
        return this.account?.labels?.[key] || this.app(key);
    }

    app(key: string): string {
        return this.language.getAppString(key) || key;
    }

    /** Shown by the rule of the legacy page: each field of the rule has one of its values. */
    isShown(item: { show?: MailAccountRule }): boolean {
        return Object.entries(item.show ?? {}).every(([field, values]) => values.includes(String(this.values[field] ?? '')));
    }

    visibleFields(panel: MailAccountPanel): MailAccountField[] {
        return panel.fields.filter(field => this.isShown(field));
    }

    /** The input of a field (one for the folder fields, one or several folders). */
    kind(field: MailAccountField): string {
        return field.type === 'folder' ? 'folders' : field.type;
    }

    optionLabel(field: MailAccountField): string {
        const value = String(this.values[field.name] ?? '');
        if (field.format === 'datetime') {
            return value ? this.formatter.toUserFormat('datetime', value) : '';
        }

        return field.options?.find(option => option.value === value)?.label ?? value;
    }

    /** A field that sets other fields (the port of the SSL setting). */
    changeValue(field: MailAccountField, value: any): void {
        this.values[field.name] = value;
        Object.entries(field.sets ?? {}).forEach(([target, values]) => {
            if (values[String(value)] !== undefined) {
                this.values[target] = values[String(value)];
            }
        });
    }

    // Related records.

    selectRecord(field: MailAccountField): void {
        this.selectModal.showSelectModal(field.module, (record: Record) => {
            if (!record?.id) {
                return;
            }
            this.values[field.idField] = record.id;
            this.values[field.name] = this.recordLabel(record);
            if (field.fill) {
                this.call({action: 'fill', field: field.name, record: record.id}).subscribe(values => {
                    Object.assign(this.values, values ?? {});
                    this.cdr.detectChanges();
                });
            }
            this.cdr.detectChanges();
        });
    }

    clearRecord(field: MailAccountField): void {
        this.values[field.idField] = '';
        this.values[field.name] = '';
    }

    // Key-value lists.

    entries(field: MailAccountField): MailAccountEntry[] {
        if (!Array.isArray(this.values[field.name])) {
            this.values[field.name] = [];
        }

        return this.values[field.name];
    }

    addEntry(field: MailAccountField): void {
        this.entries(field).push({key: '', value: ''});
    }

    removeEntry(field: MailAccountField, index: number): void {
        this.entries(field).splice(index, 1);
    }

    // Authorization of an OAuth connection.

    /**
     * Opens the authorization of the provider (legacy entry point redirectToExternalOAuth, as the
     * Authenticate button of the legacy edit view); its window sends the token back.
     */
    authorize(panel: MailAccountPanel): void {
        const providerId = String(this.values[panel.authorize.provider] ?? '');
        if (!providerId) {
            this.messages.addWarningMessage(panel.authorize.missing);
            return;
        }
        this.listenToAuthorization();
        window.open(`${panel.authorize.url}&provider=${encodeURIComponent(providerId)}`, '_blank', 'toolbar=yes,scrollbars=yes');
    }

    // Folders of the mail server.

    /** The monitored folders, from which the primary folder is chosen (the first by default). */
    folderList(field: MailAccountField): string[] {
        return String(this.values[field.of] ?? '').split(',').map(folder => folder.trim()).filter(folder => folder);
    }

    chooseFolders(field: MailAccountField): void {
        this.folderChoice = null;
        this.call({action: 'folders', id: this.id, values: this.connectionValues()}).subscribe(result => {
            if (!result?.success) {
                this.messages.addDangerMessage(result?.message || this.app('LBL_ACTION_ERROR'));
            } else {
                const current = String(this.values[field.name] ?? '').split(',').map(folder => folder.trim()).filter(folder => folder);
                this.folderChoice = {field, folders: result.folders, selected: new Set(current), message: ''};
            }
            this.cdr.detectChanges();
        });
    }

    toggleFolder(folder: string): void {
        const choice = this.folderChoice;
        if (choice.field.type === 'folder') {
            choice.selected = new Set([folder]);
        } else if (choice.selected.has(folder)) {
            choice.selected.delete(folder);
        } else {
            choice.selected.add(folder);
        }
    }

    applyFolders(): void {
        const choice = this.folderChoice;
        // In the order of the server, as the legacy folder list.
        this.values[choice.field.name] = choice.folders.filter(folder => choice.selected.has(folder)).join(',');
        this.account.panels.forEach(panel => panel.fields
            .filter(field => field.type === 'primary' && field.of === choice.field.name)
            .forEach(field => this.resetPrimary(field)));
        this.folderChoice = null;
    }

    // Connection test, save.

    /** The connection test, or the test email to the address of the panel. */
    test(panel: MailAccountPanel): void {
        this.testing = true;
        this.testResult = null;
        const options: { [key: string]: any } = {action: 'test', id: this.id, values: this.connectionValues()};
        if (panel.test?.address) {
            options.address = this.values.testAddress ?? '';
        }
        this.call(options).subscribe(result => {
            this.testing = false;
            this.testResult = result ?? {success: false, message: this.app('LBL_ACTION_ERROR')};
            Object.assign(this.values, result?.values ?? {});
            this.cdr.detectChanges();
        });
    }

    save(): void {
        if (!this.account || this.saving) {
            return;
        }
        const values = this.formValues();
        const missing = this.account.panels
            .filter(panel => this.isShown(panel))
            .some(panel => this.visibleFields(panel).some(field => field.required && this.isEmpty(values[field.idField ?? field.name])));
        if (missing) {
            this.messages.addWarningMessage(this.l('LBL_ACCOUNT_EDITOR_REQUIRED'));
            return;
        }

        this.saving = true;
        this.processService.submit(this.process, {action: 'save', id: this.id, values}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.saving = false;
                const data = process?.data ?? {};
                if (process?.status !== 'success' || !data.id) {
                    this.messages.addDangerMessage(data.error || this.l('LBL_ACCOUNT_EDITOR_SAVE_ERROR'));
                    this.cdr.detectChanges();
                    return;
                }
                if (data.warning) {
                    this.messages.addWarningMessage(data.warning);
                }
                this.router.navigate([`/${this.module}/record`, data.id]).then();
            },
            error: () => {
                this.saving = false;
                this.messages.addDangerMessage(this.l('LBL_ACCOUNT_EDITOR_SAVE_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }

    cancel(): void {
        this.router.navigate(this.id ? [`/${this.module}/record`, this.id] : [`/${this.module}`]).then();
    }

    protected load(): void {
        this.loading = true;
        this.error = false;
        this.account = null;
        this.testResult = null;
        this.folderChoice = null;
        this.call({action: 'load', id: this.id, type: this.type}).subscribe((account: MailAccount) => {
            this.loading = false;
            this.account = account?.panels ? account : null;
            this.error = !this.account;
            this.account?.panels.forEach(panel => panel.fields.forEach(field => {
                if (field.type === 'primary') {
                    this.resetPrimary(field);
                }
                // A list posts its first option when its value is not one of them, as a legacy select.
                if (field.type === 'enum' && field.options?.length
                    && !field.options.some(option => option.value === String(this.values[field.name] ?? ''))) {
                    this.values[field.name] = field.options[0].value;
                }
            }));
            this.cdr.detectChanges();
        });
    }

    /** The token of the authorization window (legacy setExternalOAuthToken: [field, value] messages). */
    protected listenToAuthorization(): void {
        if (this.authChannel || typeof BroadcastChannel === 'undefined') {
            return;
        }
        this.authChannel = new BroadcastChannel('auth');
        this.authChannel.onmessage = (event: MessageEvent) => {
            const [field, value] = Array.isArray(event.data) ? event.data : [];
            if (this.account && typeof field === 'string' && field in this.values) {
                this.values[field] = value ?? '';
                this.cdr.detectChanges();
            }
        };
    }

    /** The primary folder stays one of the monitored folders (the first by default). */
    protected resetPrimary(field: MailAccountField): void {
        const folders = this.folderList(field);
        if (!folders.includes(String(this.values[field.name] ?? ''))) {
            this.values[field.name] = folders[0] ?? '';
        }
    }

    /** The values to save: the fields hidden by the rules of the legacy page are emptied there. */
    protected formValues(): { [field: string]: any } {
        const values = {...this.values};
        this.account.panels.forEach(panel => panel.fields
            .filter(field => field.clear && !this.isShown(field))
            .forEach(field => {
                values[field.name] = '';
                if (field.idField) {
                    values[field.idField] = '';
                }
            }));

        return values;
    }

    /** The settings to connect to the mail server (the type, for a new account). */
    protected connectionValues(): { [field: string]: any } {
        return {...this.formValues(), type: this.values.type ?? this.type};
    }

    protected isEmpty(value: any): boolean {
        return value === undefined || value === null || String(value).trim() === '';
    }

    protected recordLabel(record: Record): string {
        const attributes = record.attributes ?? {};

        return attributes.name || attributes.full_name || attributes.user_name || record.id;
    }

    protected call(options: { [key: string]: any }): Observable<any> {
        return this.processService.submit(this.process, options).pipe(
            take(1),
            map((process: Process) => process?.status === 'success' ? process.data : (process?.data?.error ? process.data : null)),
            catchError(() => of(null))
        );
    }
}
