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
import {
    LegacyEntrypointLinkBuilder
} from '../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service';

export interface TemplateAttachment {
    id: string;
    name: string;
    mimeType: string;
    size: number;
}

/**
 * Attachments of an email template (record view, bottom widget `email-template-attachments`), as
 * the legacy template editor: the files of the template, downloaded from their note; a file is
 * added or removed at once. They stay notes of the template, where the email senders look for
 * them (EmailTemplateAttachmentsHandler). Shown in the detail and edit views (files added while
 * editing the template, as in the legacy editor) and in the create view, where a new template has
 * no id for its notes yet: a hint to save it first.
 */
@Component({
    selector: 'scrm-email-template-attachments-widget',
    templateUrl: './email-template-attachments-widget.component.html',
    styleUrls: []
})
export class EmailTemplateAttachmentsWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'email-template-attachments';

    attachments: TemplateAttachment[] = [];
    maxSize = 0;
    loading = false;
    error = false;

    protected recordId = '';
    protected run$: Subscription = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected links: LegacyEntrypointLinkBuilder,
        protected confirmation: ConfirmationModalService,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    /** The template exists (not the create view): its files can be listed and changed. */
    get saved(): boolean {
        return !!this.recordId;
    }

    /** The user can change the template (edit access of the record view). */
    get editable(): boolean {
        return !!this.recordId && this.context?.record?.acls?.includes('edit') !== false;
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
        return this.language.getFieldLabel(key, 'email-templates') || key;
    }

    link(attachment: TemplateAttachment): string {
        return this.links.getDownloadEntrypointLink(attachment.id, 'Notes');
    }

    size(bytes: number): string {
        if (!bytes) {
            return '';
        }

        return bytes < 1024 * 1024 ? Math.max(1, Math.round(bytes / 1024)) + ' KB' : (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    add(event: Event): void {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0];
        input.value = '';
        if (!file) {
            return;
        }
        if (this.maxSize && file.size > this.maxSize) {
            this.message.addDangerMessage(this.label('LBL_TEMPLATE_ATTACHMENTS_TOO_BIG'));
            return;
        }

        const reader = new FileReader();
        reader.onload = (): void => {
            const content = String(reader.result ?? '').split(',')[1] ?? '';
            this.run({action: 'add', filename: file.name, content});
        };
        reader.readAsDataURL(file);
    }

    remove(attachment: TemplateAttachment): void {
        this.confirmation.showModal(['LBL_TEMPLATE_ATTACHMENTS_REMOVE_CONFIRM'], () => {
            this.run({action: 'remove', attachment: attachment.id});
        }, () => {}, {}, {module: 'email-templates'});
    }

    protected open(id: string): void {
        if (!id || id === this.recordId) {
            return;
        }
        this.recordId = id;
        this.attachments = [];
        this.run({action: 'list'});
    }

    protected run(options: { [key: string]: string }): void {
        this.run$?.unsubscribe();
        this.loading = true;
        this.error = false;
        this.cdr.detectChanges();

        this.run$ = this.processService.submit(EmailTemplateAttachmentsWidgetComponent.PROCESS, {...options, id: this.recordId})
            .pipe(take(1))
            .subscribe({
                next: (process: Process) => {
                    this.loading = false;
                    this.error = process?.status !== 'success';
                    this.attachments = (process?.data?.attachments as TemplateAttachment[]) ?? this.attachments;
                    this.maxSize = (process?.data?.maxSize as number) ?? 0;
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.cdr.detectChanges();
                }
            });
    }
}
