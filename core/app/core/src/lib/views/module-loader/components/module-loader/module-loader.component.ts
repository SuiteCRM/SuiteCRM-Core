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
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

export interface ModuleLoaderPackage {
    name: string;
    version: string;
    type: string;
    published: string;
    description: string;
    // The hash of its file (legacy session), for the legacy actions.
    file: string;
    uninstallable?: boolean;
    enabled?: boolean;
    canDisable?: boolean;
}

export interface ModuleLoaderData {
    staged: ModuleLoaderPackage[];
    installed: ModuleLoaderPackage[];
    labels: { [key: string]: string };
    uploadWarning: string;
}

export interface LegacyChoice {
    name: string;
    options: { value: string; label: string }[];
    value: string;
}

/**
 * The form of the legacy prepare action, read to be shown and posted by the page.
 */
export interface PreparedAction {
    mode: string;
    name: string;
    action: string;
    hidden: [string, string][];
    license: string;
    readme: string[];
    choices: LegacyChoice[];
    files: { name: string; value: string; label: string; checked: boolean; disabled: boolean }[];
    requireLicense: boolean;
}

/**
 * The Module Loader of the administration, as a SuiteCRM 8 view: the packages ready to
 * install (install, delete) and the installed ones (uninstall, disable, enable), the upload of a
 * package. The packages come from the `module-loader` process (ModuleLoaderProcessHandler); the
 * upload, the prepare (license, readme, options) and the commit are the legacy actions of the
 * Module Loader, posted as its forms (they run outside Symfony, as in the legacy page), their
 * forms and output shown by the page.
 *
 * Route administration/UpgradeWizard?view=module (admin panel link).
 */
@Component({
    selector: 'scrm-module-loader',
    templateUrl: './module-loader.component.html',
    styleUrls: []
})
export class ModuleLoaderComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'module-loader';
    static readonly LEGACY = 'legacy/index.php';

    data: ModuleLoaderData = null;
    upload: File = null;
    prepared: PreparedAction = null;
    licenseAccepted = false;
    output = '';
    error = '';
    loading = false;

    protected sub: Subscription;
    protected destroyed = false;

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.run({action: 'load'});
    }

    ngOnDestroy(): void {
        this.destroyed = true;
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || key;
    }

    selectFile(input: HTMLInputElement): void {
        this.upload = input.files?.[0] ?? null;
    }

    /**
     * Uploads the package as the legacy form (run=upload): its manifest checked, the package
     * then ready to install.
     */
    async uploadPackage(input: HTMLInputElement): Promise<void> {
        if (!this.upload) {
            this.message.addWarningMessage(this.label('ERR_UW_NO_UPLOAD_FILE'));
            return;
        }

        const form = new FormData();
        Object.entries({module: 'Administration', action: 'UpgradeWizard', view: 'module', run: 'upload', to_pdf: '1'})
            .forEach(([name, value]) => form.append(name, value));
        form.append('upgrade_zip_escaped', this.upload.name);
        form.append('upgrade_zip', this.upload, this.upload.name);

        const html = await this.legacy(ModuleLoaderComponent.LEGACY, form);
        if (html === null || this.destroyed) {
            return;
        }
        // The legacy page shows its status message, empty when the server got no file (as a file
        // above its upload limit).
        const status = html.match(/showStatusMessages\('((?:[^'\\]|\\.)*)'\)/);
        const message = status ? this.text(status[1]).trim() : '';
        const uploaded = this.text(this.label('LBL_UW_UPLOAD_SUCCESS')).trim();
        if (message && uploaded && message.includes(uploaded)) {
            this.message.addSuccessMessage(message);
        } else if (status) {
            this.message.addDangerMessage(message || this.text(this.label('ERR_UW_NO_UPLOAD_FILE')).trim());
        } else {
            // The legacy action stopped on an error (die): its message.
            this.showError(html);
        }
        this.upload = null;
        input.value = '';
        this.run({action: 'load'});
    }

    remove(item: ModuleLoaderPackage): void {
        this.confirmation.showModal(['REMOVE_QUESTION'], () => this.run({action: 'remove', file: item.file}), () => {}, {}, {module: 'administration'});
    }

    /**
     * The legacy prepare of an action on a package (Install, Uninstall, Disable, Enable): its form
     * read to be shown (license, readme, options) and committed.
     */
    async prepare(item: ModuleLoaderPackage, mode: string): Promise<void> {
        this.output = '';
        this.error = '';
        const html = await this.legacy(ModuleLoaderComponent.LEGACY, new URLSearchParams({
            module: 'Administration', action: 'UpgradeWizard_prepare', view: 'module', install_file: item.file, mode, to_pdf: '1'
        }));
        if (html === null || this.destroyed) {
            return;
        }

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const form = Array.from(doc.forms).find(candidate => (candidate.getAttribute('action') ?? '').includes('UpgradeWizard_commit'));
        if (!form) {
            this.showError(html);
            return;
        }

        const prepared: PreparedAction = {
            mode,
            name: item.name,
            action: form.getAttribute('action'),
            hidden: [],
            license: '',
            readme: [],
            choices: [],
            files: [],
            requireLicense: !!form.querySelector('input[name="radio_license_agreement"]'),
        };
        form.querySelectorAll('input[type="hidden"]').forEach((input: HTMLInputElement) => prepared.hidden.push([input.name, input.value]));
        form.querySelectorAll('textarea').forEach((area: HTMLTextAreaElement, index) => {
            if (prepared.requireLicense && index === 0) {
                prepared.license = area.value;
            } else {
                prepared.readme.push(area.value);
            }
        });
        form.querySelectorAll('input[type="radio"]').forEach((input: HTMLInputElement) => {
            if (input.name === 'radio_license_agreement') {
                return;
            }
            let choice = prepared.choices.find(existing => existing.name === input.name);
            if (!choice) {
                choice = {name: input.name, options: [], value: ''};
                prepared.choices.push(choice);
            }
            choice.options.push({value: input.value, label: this.nextText(input)});
            if (input.checked) {
                choice.value = input.value;
            }
        });
        form.querySelectorAll('input[type="checkbox"][name^="copy_"]').forEach((input: HTMLInputElement) => prepared.files.push({
            name: input.name, value: input.value, label: this.nextText(input), checked: input.checked, disabled: input.disabled
        }));

        this.prepared = prepared;
        this.licenseAccepted = false;
        this.refresh();
    }

    /**
     * Commits the prepared action as the legacy form (the license accepted first), then rebuilds
     * the relationships as the legacy commit page does (its script).
     */
    async commit(): Promise<void> {
        const prepared = this.prepared;
        if (prepared.requireLicense && !this.licenseAccepted) {
            this.message.addWarningMessage(this.label('ERR_UW_ACCEPT_LICENSE'));
            return;
        }

        const body = new URLSearchParams();
        prepared.hidden.forEach(([name, value]) => body.append(name, value));
        if (prepared.requireLicense) {
            body.append('radio_license_agreement', 'accept');
        }
        prepared.choices.forEach(choice => body.append(choice.name, choice.value));
        prepared.files.filter(file => file.checked && !file.disabled).forEach(file => body.append(file.name, file.value));
        body.append('to_pdf', '1');

        const html = await this.legacy('legacy/' + prepared.action, body);
        if (html === null || this.destroyed) {
            return;
        }
        this.prepared = null;
        this.output = this.clean(html);

        if (html.includes('action=RebuildRelationship')) {
            const rebuild = await this.legacy(ModuleLoaderComponent.LEGACY + '?module=Administration&action=RebuildRelationship&to_pdf=true', null);
            if (rebuild !== null) {
                this.output += this.clean(rebuild);
            }
        }
        this.refresh();
        if (!this.destroyed) {
            this.run({action: 'load'});
        }
    }

    cancel(): void {
        this.prepared = null;
    }

    trackPackage(index: number, item: ModuleLoaderPackage): string {
        return item.file;
    }

    protected showError(html: string): void {
        this.error = this.clean(html) || this.label('LBL_ACTION_ERROR');
        this.refresh();
    }

    /**
     * The output of a legacy action as plain HTML: no scripts, styles, forms, page title, progress
     * bar (updated by its scripts) and link showing its log (shown).
     */
    protected clean(html: string): string {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        doc.querySelectorAll('script, style, link, form input, form button, select, textarea, .moduleTitle, img, [id$="_progress_bar"], #displayLoglink')
            .forEach(node => node.remove());
        doc.querySelectorAll('form').forEach(form => form.replaceWith(...Array.from(form.childNodes)));

        return doc.body.innerHTML.replace(/<br\s*\/?>\s*(<br\s*\/?>\s*)+/gi, '<br>').trim();
    }

    protected text(html: string): string {
        const doc = new DOMParser().parseFromString(html.replace(/\\'/g, '\''), 'text/html');

        return doc.body.textContent ?? '';
    }

    /**
     * The label of a legacy radio or checkbox: the text right after it.
     */
    protected nextText(input: Element): string {
        let node = input.nextSibling;
        let text = '';
        while (node && !(node instanceof HTMLInputElement) && !(node.nodeName === 'BR')) {
            text += node.textContent ?? '';
            node = node.nextSibling;
        }

        return text.replace(/ /g, ' ').trim();
    }

    /**
     * Posts to a legacy action (its session, as its forms), its HTML output.
     */
    protected async legacy(url: string, body: FormData | URLSearchParams | null): Promise<string | null> {
        this.loading = true;
        this.refresh();
        try {
            const response = await fetch(url, body ? {method: 'POST', body, credentials: 'same-origin'} : {credentials: 'same-origin'});
            if (!response.ok) {
                throw new Error(`${response.status}`);
            }

            return await response.text();
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));

            return null;
        } finally {
            this.loading = false;
            this.refresh();
        }
    }

    /**
     * Shows the changes, the page not left (a legacy action answers after it).
     */
    protected refresh(): void {
        if (!this.destroyed) {
            this.cdr.detectChanges();
        }
    }

    protected run(options: { [key: string]: any }): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.refresh();

        this.sub = this.processService.submit(ModuleLoaderComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.data?.staged) {
                    this.data = process.data as ModuleLoaderData;
                }
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.refresh();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.refresh();
            }
        });
    }
}
