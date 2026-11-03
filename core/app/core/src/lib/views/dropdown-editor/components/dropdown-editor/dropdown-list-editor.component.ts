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
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DROPDOWN_EDITOR_PROCESS, DropdownEditorLists, DropdownItem} from './dropdown-editor.model';

/**
 * The editor of a dropdown list (legacy ModuleBuilder dropdown view): its name (a new
 * list), its language, and its items in their order: moved up or down, sorted by label (the blank
 * item first), their labels changed, removed, or added. Removing items asks for the confirmation
 * of the legacy editor. Saved as the legacy editor (`dropdown-editor` process); closes with the
 * name of the saved list.
 */
@Component({
    selector: 'scrm-dropdown-list-editor',
    templateUrl: './dropdown-list-editor.component.html',
    styleUrls: []
})
export class DropdownListEditorComponent implements OnInit, OnDestroy {

    @Input() name = '';
    @Input() lists: DropdownEditorLists = null;

    newName = '';
    lang = '';
    items: DropdownItem[] = [];
    newKey = '';
    newLabel = '';
    error = '';
    confirming = false;
    loading = false;

    protected loadedKeys: string[] = [];
    protected sub: Subscription = null;

    constructor(
        public modal: NgbActiveModal,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get isNew(): boolean {
        return !this.name;
    }

    /**
     * Items of the saved list removed in the editor.
     */
    get removed(): number {
        const keys = new Set(this.items.map(item => item.key));
        return this.loadedKeys.filter(key => !keys.has(key)).length;
    }

    ngOnInit(): void {
        this.lang = this.lists?.language ?? '';
        if (!this.isNew) {
            this.load();
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of the editor (ModuleBuilder strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.lists?.labels?.[key] || this.language.getAppString(key) || key;
    }

    keyLabel(key: string): string {
        return key === '' ? this.label('LBL_BLANK') : key;
    }

    load(): void {
        this.run({action: 'load', name: this.name, language: this.lang}, (data) => {
            this.items = (data?.items ?? []).map((item: DropdownItem) => ({key: item.key, label: item.label}));
            this.loadedKeys = this.items.map(item => item.key);
            this.confirming = false;
        });
    }

    move(index: number, offset: number): void {
        const target = index + offset;
        if (target < 0 || target >= this.items.length) {
            return;
        }
        [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
        this.confirming = false;
    }

    remove(index: number): void {
        this.items.splice(index, 1);
        this.confirming = false;
    }

    /**
     * Adds an item, as the legacy editor: a key (no quotes, semicolons... and not used yet) and its
     * display label.
     */
    add(): void {
        const key = this.newKey.trim();
        if (!key || /['";$`\\\r\n]/.test(key) || this.items.some(item => item.key === key)) {
            this.error = this.label('LBL_JS_VALIDATE_KEY');
            return;
        }
        this.items.push({key, label: this.newLabel});
        this.newKey = '';
        this.newLabel = '';
        this.error = '';
    }

    /**
     * Sorts the items by label, the blank item kept first (legacy Sort Ascending / Descending).
     */
    sort(descending: boolean): void {
        const blank = this.items.filter(item => item.key === '');
        const others = this.items.filter(item => item.key !== '')
            .sort((a, b) => a.label.toLowerCase().localeCompare(b.label.toLowerCase()) * (descending ? -1 : 1));
        this.items = [...blank, ...others];
    }

    save(): void {
        this.error = '';
        const name = this.isNew ? this.newName.trim() : this.name;
        if (this.isNew && !/^[a-zA-Z][a-zA-Z0-9_]*$/.test(name)) {
            this.error = this.label('LBL_JS_VALIDATE_NAME');
            return;
        }
        if (this.removed && !this.confirming) {
            this.confirming = true;
            return;
        }

        const items = this.items.map(item => ({key: item.key, label: item.label}));
        this.run({action: 'save', name, language: this.lang, items, new: this.isNew}, (data) => {
            if (data?.saved) {
                this.modal.close(name);
                return;
            }
            this.confirming = false;
            this.error = this.label(data?.error || 'LBL_JS_VALIDATE_KEY');
        });
    }

    trackIndex(index: number): number {
        return index;
    }

    protected run(options: { [key: string]: any }, done: (data: any) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.markForCheck();

        this.sub = this.processService.submit(DROPDOWN_EDITOR_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status !== 'success') {
                    this.error = this.label('LBL_DROPDOWN_EDITOR_NOT_FOUND');
                } else {
                    done(process.data);
                }
                this.cdr.markForCheck();
            },
            error: () => {
                this.loading = false;
                this.error = this.label('LBL_DROPDOWN_EDITOR_NOT_FOUND');
                this.cdr.markForCheck();
            }
        });
    }
}
