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
import {LanguageStore} from '../../../../store/language/language.store';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {IMPORT_PROCESS} from '../../import-pages';

/**
 * The largest vCard the legacy form takes (its max_file_size).
 */
const VCARD_MAX_SIZE = 30000;

/**
 * Create From vCard of Contacts and Leads, in the page instead of the legacy form in an
 * iframe: the vCard chosen is imported as a record as the legacy form imports it
 * (view.importvcardsave), then the record is opened; the legacy messages otherwise.
 */
@Component({
    selector: 'scrm-import-vcard',
    templateUrl: './import-vcard.component.html',
    styleUrls: []
})
export class ImportVCardComponent implements OnInit, OnDestroy {

    /** The module of the route (contacts, leads). */
    module = '';
    file: File | null = null;
    error = '';
    loading = false;

    protected subs = new Subscription();
    protected destroyed = false;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.module = this.route.snapshot.data?.module ?? '';
    }

    ngOnDestroy(): void {
        this.destroyed = true;
        this.subs.unsubscribe();
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.module) || this.language.getAppString(key) || key;
    }

    get moduleName(): string {
        return (this.language.getAppListString('moduleList') as any)?.[this.moduleNameMapper.toLegacy(this.module)] ?? '';
    }

    fileChosen(event: Event): void {
        this.file = (event.target as HTMLInputElement).files?.[0] ?? null;
        this.error = '';
    }

    importVCard(): void {
        if (this.loading) {
            return;
        }
        if (!this.file) {
            this.error = this.label('LBL_EMPTY_VCARD');
            return;
        }
        if (this.file.size > VCARD_MAX_SIZE) {
            this.error = this.label('LBL_VCARD_ERROR_FILESIZE');
            return;
        }
        this.loading = true;
        this.error = '';
        const reader = new FileReader();
        reader.onload = () => {
            const content = String(reader.result ?? '').replace(/^data:[^,]*,/, '');
            this.subs.add(this.processService.submit(IMPORT_PROCESS, {
                action: 'vcard', module: this.moduleNameMapper.toLegacy(this.module), content
            }).pipe(take(1)).subscribe({
                next: (process: Process) => {
                    const id = process?.status === 'success' ? process.data?.id ?? '' : null;
                    if (id) {
                        this.router.navigate(['/', this.module, 'record', id]).then();
                        return;
                    }
                    this.done(this.label(id === '' ? 'LBL_EMPTY_REQUIRED_VCARD' : 'LBL_VCARD_ERROR_DEFAULT'));
                },
                error: () => this.done(this.label('LBL_VCARD_ERROR_DEFAULT'))
            }));
        };
        reader.onerror = () => this.done(this.label('LBL_VCARD_ERROR_DEFAULT'));
        reader.readAsDataURL(this.file);
    }

    cancel(): void {
        this.router.navigate(['/', this.module]).then();
    }

    protected done(error: string): void {
        this.loading = false;
        this.error = error;
        if (!this.destroyed) {
            this.cdr.detectChanges();
        }
    }
}
