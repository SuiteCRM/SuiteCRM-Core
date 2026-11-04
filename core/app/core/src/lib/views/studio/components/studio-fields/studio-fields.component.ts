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

import {ChangeDetectorRef, Component, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioField, StudioLabels, StudioView} from '../../studio.model';
import {StudioFieldEditorComponent} from '../studio-field-editor/studio-field-editor.component';

type FieldColumn = 'name' | 'label' | 'type';

/**
 * The fields of a module in Studio (legacy fields list): name (custom fields marked),
 * display label and type, sorted by a column. A field, or a new one, opens in the field editor
 * next to the list (legacy east panel); Edit Labels the labels of the module.
 */
@Component({
    selector: 'scrm-studio-fields',
    standalone: true,
    imports: [CommonModule, FormsModule, StudioFieldEditorComponent],
    templateUrl: './studio-fields.component.html'
})
export class StudioFieldsComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    /** The Module Builder package of the module ('' for Studio). */
    @Input() package = '';
    @Input() labels: StudioLabels = {};
    /** A field to open in the editor (an earlier link to the legacy field editor), '' for a new one. */
    @Input() field: string = null;
    @Output() open = new EventEmitter<StudioView>();
    /** The field editor open (the field, '' for a new one) or closed (null): the help of the editor. */
    @Output() editing = new EventEmitter<string | null>();

    /** The field in the editor, '' for a new one; null without editor. */
    edited: string = null;

    readonly columns: { key: FieldColumn; label: string }[] = [
        {key: 'name', label: 'LBL_NAME'},
        {key: 'label', label: 'LBL_DROPDOWN_ITEM_LABEL'},
        {key: 'type', label: 'LBL_DATA_TYPE'},
    ];

    fields: StudioField[] = null;
    search = '';
    sort: FieldColumn = 'name';
    descending = false;
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
     * The fields of the module, again for another module (the page kept from a module to another).
     */
    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module) {
            this.edited = null;
            this.load();
        }
        if (changes.field && this.field !== null) {
            this.edit(this.field);
        }
    }

    load(): void {
        this.sub?.unsubscribe();
        this.fields = null;
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, {action: 'fields', module: this.module, package: this.package}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    this.fields = process.data?.fields ?? [];
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

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    get shown(): StudioField[] {
        const search = this.search.trim().toLowerCase();
        const direction = this.descending ? -1 : 1;

        return (this.fields ?? [])
            .filter(field => !search || [field.name, field.label, field.type].some(text => text.toLowerCase().includes(search)))
            .sort((a, b) => direction * a[this.sort].localeCompare(b[this.sort], undefined, {sensitivity: 'base'}));
    }

    sortBy(column: FieldColumn): void {
        this.descending = this.sort === column ? !this.descending : false;
        this.sort = column;
    }

    sortState(column: FieldColumn): string {
        return this.sort !== column ? 'none' : this.descending ? 'descending' : 'ascending';
    }

    /**
     * The field editor of a field (a new one without name).
     */
    edit(name: string): void {
        this.edited = name;
        this.editing.emit(name);
    }

    /**
     * The editor closed, the fields again when they changed.
     */
    closeEditor(changed: boolean): void {
        this.edited = null;
        this.editing.emit(null);
        if (changed) {
            this.load();
        }
    }

    editLabels(): void {
        this.open.emit({view: 'labels'});
    }

    trackName(index: number, field: StudioField): string {
        return field.name;
    }
}
