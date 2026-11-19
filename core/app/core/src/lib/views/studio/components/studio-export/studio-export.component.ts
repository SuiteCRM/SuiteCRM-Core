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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioExportModule, StudioLabels} from '../../studio.model';

/**
 * Export Customizations of Studio (legacy view.exportcustomizations): a package name,
 * author and description, the customized modules to export (what is customized in each), then
 * the package made as the legacy export makes it, downloaded.
 */
@Component({
    selector: 'scrm-studio-export',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './studio-export.component.html',
    styleUrls: []
})
export class StudioExportComponent implements OnInit, OnDestroy {

    @Input() labels: StudioLabels = {};

    modules: StudioExportModule[] = null;
    checked: { [module: string]: boolean } = {};
    name = '';
    author = '';
    description = '';
    loading = false;

    /** The package name rule of the backend (a folder of the package). */
    protected readonly namePattern = /^[A-Za-z0-9][A-Za-z0-9_ -]{0,49}$/;
    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.submit({action: 'exportModules'}, data => this.modules = data.modules ?? []);
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    get selected(): string[] {
        return (this.modules ?? []).filter(item => item.exportable && this.checked[item.module]).map(item => item.module);
    }

    get validName(): boolean {
        return this.namePattern.test(this.name.trim());
    }

    export(): void {
        if (!this.validName || !this.selected.length || this.loading) {
            return;
        }
        this.submit({
            action: 'exportCustomizations',
            name: this.name.trim(),
            author: this.author,
            description: this.description,
            modules: this.selected
        }, data => this.download(data.fileName, data.content));
    }

    trackModule(index: number, item: StudioExportModule): string {
        return item.module;
    }

    protected download(fileName: string, content: string): void {
        if (!fileName || !content) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
            return;
        }
        const bytes = Uint8Array.from(atob(content), char => char.charCodeAt(0));
        const url = URL.createObjectURL(new Blob([bytes], {type: 'application/zip'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    protected submit(options: { [key: string]: any }, next: (data: any) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
