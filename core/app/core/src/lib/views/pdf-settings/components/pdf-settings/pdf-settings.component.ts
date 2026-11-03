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
import {Router} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';

export interface PdfSettingsData {
    engines: { value: string; label: string }[];
    engine: string;
    labels?: { [key: string]: string };
}

/**
 * PDF settings view of the administration (legacy Administration PDFSettings): the PDF engine
 * used to print the PDF templates. Save goes back to the administration, as the legacy page.
 *
 * Route administration/PDFSettings (admin panel link). Data from the `pdf-settings` process
 * (PdfSettingsProcessHandler).
 */
@Component({
    selector: 'scrm-pdf-settings',
    templateUrl: './pdf-settings.component.html',
    styleUrls: []
})
export class PdfSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'pdf-settings';

    data: PdfSettingsData = null;
    labels: { [key: string]: string } = null;
    engine = '';
    loading = false;
    error = false;

    protected sub: Subscription;

    constructor(
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.run({action: 'load'});
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of the page (Administration strings sent by the process: the front end only
     * has the strings of the modules in the navigation), or of the application.
     */
    label(key: string): string {
        return this.labels?.[key] || this.language.getAppString(key) || (this.labels ? key : '');
    }

    save(): void {
        this.run({action: 'save', engine: this.engine}, true);
    }

    protected run(options: { [key: string]: string }, leave = false): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.error = false;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(PdfSettingsComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as PdfSettingsData;
                this.loading = false;
                this.labels = data?.labels ?? this.labels ?? {};
                if (process?.status !== 'success' || !data?.engines) {
                    this.error = true;
                    this.cdr.detectChanges();
                    return;
                }

                this.data = data;
                this.engine = data.engine;
                this.cdr.detectChanges();
                if (leave) {
                    this.router.navigate(['/administration']);
                }
            },
            error: () => {
                this.loading = false;
                this.labels = this.labels ?? {};
                this.error = true;
                this.cdr.detectChanges();
            }
        });
    }
}
