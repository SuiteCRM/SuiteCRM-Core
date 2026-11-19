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

import {ChangeDetectorRef, Component, Input, OnChanges, OnDestroy, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioLabel, StudioLabels, StudioLabelsData} from '../../studio.model';

/**
 * The labels of a module in Studio (legacy label editor): the labels of its fields,
 * layouts and subpanels, or all of them, in a language; the changed ones saved and deployed.
 * Changing the language or the labels shown drops the changes, as the legacy editor.
 */
@Component({
    selector: 'scrm-studio-labels',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './studio-labels.component.html'
})
export class StudioLabelsComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    @Input() labels: StudioLabels = {};

    data: StudioLabelsData = null;
    language = '';
    type = 'frequently_used';
    search = '';
    changes: { [key: string]: string } = {};
    loading = false;

    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    /**
     * The labels of the module, again for another module (the page kept from a module to another).
     */
    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module) {
            this.data = null;
            this.changes = {};
            this.load();
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    get shown(): StudioLabel[] {
        const search = this.search.trim().toLowerCase();

        return (this.data?.labels ?? []).filter(item => !search || item.key.toLowerCase().includes(search)
            || this.value(item).toLowerCase().includes(search));
    }

    get changed(): boolean {
        return Object.keys(this.changes).length > 0;
    }

    value(item: StudioLabel): string {
        return this.changes[item.key] ?? item.value;
    }

    change(item: StudioLabel, value: string): void {
        if (value === item.value) {
            delete this.changes[item.key];
        } else {
            this.changes[item.key] = value;
        }
    }

    load(): void {
        this.submit({action: 'labels', language: this.language, all: this.type === 'all'});
    }

    save(): void {
        this.submit({action: 'saveLabels', language: this.language, all: this.type === 'all', labels: this.changes}, true);
    }

    trackKey(index: number, item: StudioLabel): string {
        return item.key;
    }

    protected submit(options: { [key: string]: any }, saving = false): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(STUDIO_PROCESS, {module: this.module, ...options}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    this.data = process.data as StudioLabelsData;
                    this.language = this.data.language;
                    this.changes = {};
                    if (saving) {
                        this.message.addSuccessMessage(this.languageStore.getAppString('LBL_SAVED') || 'Saved');
                    }
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
