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
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {MessageService} from '../../../../services/message/message.service';
import {DROPDOWN_EDITOR_PROCESS, DropdownEditorLists} from './dropdown-editor.model';
import {DropdownListEditorComponent} from './dropdown-list-editor.component';

/**
 * Dropdown Editor view of the administration (legacy ModuleBuilder dropdowns): the dropdown lists,
 * searched by name; a list opens in the list editor (DropdownListEditorComponent), Add Dropdown
 * creates one.
 *
 * Route module-builder/index?type=dropdowns (the admin panel link); the other ModuleBuilder pages,
 * Studio included, keep their route. Data from the `dropdown-editor` process
 * (DropdownEditorProcessHandler).
 */
@Component({
    selector: 'scrm-dropdown-editor',
    templateUrl: './dropdown-editor.component.html',
    styleUrls: []
})
export class DropdownEditorComponent implements OnInit, OnDestroy {

    data: DropdownEditorLists = null;
    search = '';
    loading = false;
    error = false;

    protected sub: Subscription = null;

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected modals: NgbModal,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.load();
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of the page (ModuleBuilder strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    /**
     * The lists matching the search.
     */
    shown(): string[] {
        const search = this.search.trim().toLowerCase();
        const names = this.data?.lists ?? [];

        return search ? names.filter(name => name.toLowerCase().includes(search)) : names;
    }

    open(name: string): void {
        const modal = this.modals.open(DropdownListEditorComponent, {size: 'lg', scrollable: true, backdrop: 'static'});
        modal.componentInstance.name = name;
        modal.componentInstance.lists = this.data;
        modal.closed.pipe(take(1)).subscribe((saved: string) => {
            if (saved) {
                this.message.addSuccessMessage(this.label('LBL_DROPDOWN_EDITOR_SAVED').replace('{name}', saved));
                this.load();
            }
        });
    }

    trackName(index: number, name: string): string {
        return name;
    }

    protected load(): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(DROPDOWN_EDITOR_PROCESS, {action: 'lists'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as DropdownEditorLists;
                this.error = process?.status !== 'success' || !data?.lists;
                if (!this.error) {
                    this.data = data;
                } else {
                    this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.error = true;
                this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                this.cdr.detectChanges();
            }
        });
    }
}
