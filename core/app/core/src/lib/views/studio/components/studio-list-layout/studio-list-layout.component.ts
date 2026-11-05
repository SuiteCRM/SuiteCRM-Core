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
import {CdkDragDrop, DragDropModule, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioHelp, StudioHistoryEntry, StudioLabels, StudioLayoutColumn, StudioLayoutField, StudioListLayout} from '../../studio.model';

/**
 * A list layout of a module in Studio (legacy list layout editor: list view, subpanel,
 * dashlet, popup, filters): its columns of fields (Default, Available, Hidden) ordered by drag
 * and drop, the label and width of a field (legacy property editor), the subpanel title, the
 * history and Restore Default. Save & Deploy saves it as the legacy editor.
 */
@Component({
    selector: 'scrm-studio-list-layout',
    standalone: true,
    imports: [CommonModule, FormsModule, DragDropModule],
    templateUrl: './studio-list-layout.component.html'
})
export class StudioListLayoutComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    /** The Module Builder package of the module ('' for Studio). */
    @Input() package = '';
    /** The legacy view of the layout (listview, dashlet, popuplist, basic_search…). */
    @Input() view = '';
    @Input() subpanel = '';
    @Input() labels: StudioLabels = {};
    @Input() help: StudioHelp = {};
    /** The help group of the legacy editor (listViewEditor, subPanelEditor…). */
    @Input() helpGroup = '';
    @Output() hover = new EventEmitter<string>();

    data: StudioListLayout = null;
    columns: StudioLayoutColumn[] = [];
    subpanelTitle = '';
    /** The labels changed (label key => text), saved with the layout. */
    changes: { [key: string]: string } = {};
    /** The layout of the history shown (restored, not saved yet). */
    restored: StudioHistoryEntry = null;
    editing: StudioLayoutField = null;
    historyOpen = false;
    error = '';
    loading = false;

    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module || changes.view || changes.subpanel) {
            this.load();
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    helpText(key: string): string {
        return this.help?.[this.helpGroup]?.[key] ?? '';
    }

    /**
     * The help of a column (legacy help keys: the English names of the columns).
     */
    columnHelp(column: StudioLayoutColumn): string {
        const keys: { [key: string]: string } = {LBL_DEFAULT: 'Default', LBL_AVAILABLE: 'Available', LBL_HIDDEN: 'Hidden'};

        return this.helpText(keys[column.key] ?? '');
    }

    load(sid = ''): void {
        this.submit({action: 'listLayout', sid}, data => {
            this.show(data);
            this.restored = sid ? (data.history.find(entry => entry.sid === sid) ?? {sid, date: sid}) : null;
        });
    }

    /**
     * A layout of the history in the editor, saved by Save & Deploy (legacy View History,
     * Restore, Restore Default).
     */
    restore(sid: string): void {
        this.historyOpen = false;
        this.load(sid);
    }

    drop(event: CdkDragDrop<StudioLayoutField[]>): void {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(event.previousContainer.data, event.container.data, event.previousIndex, event.currentIndex);
        }
        this.error = '';
    }

    /**
     * A field moved without the mouse (field properties): up or down in its column, or to the
     * end of another column.
     */
    move(field: StudioLayoutField, offset: number): void {
        const column = this.columnOf(field);
        const index = column.fields.indexOf(field);
        const target = index + offset;
        if (target >= 0 && target < column.fields.length) {
            moveItemInArray(column.fields, index, target);
        }
    }

    moveTo(field: StudioLayoutField, key: string): void {
        const from = this.columnOf(field);
        const to = this.columns.find(column => column.key === key);
        if (to && to !== from) {
            from.fields.splice(from.fields.indexOf(field), 1);
            to.fields.push(field);
            this.error = '';
        }
    }

    columnOf(field: StudioLayoutField): StudioLayoutColumn {
        return this.columns.find(column => column.fields.includes(field));
    }

    edit(field: StudioLayoutField): void {
        this.editing = this.editing === field ? null : field;
    }

    changeLabel(field: StudioLayoutField, text: string): void {
        field.label = text;
        this.changes[field.labelKey] = text;
    }

    changeWidth(field: StudioLayoutField, width: string): void {
        field.width = String(width ?? '').replace(/\D/g, '').slice(0, 3);
    }

    /**
     * Saves the layout as the legacy Save & Deploy: a list view keeps a field at least.
     */
    save(): void {
        if (this.data.minimum && !this.columns[0]?.fields.length) {
            this.error = this.label('ERROR_MINIMUM_FIELDS');
            return;
        }
        const widths: { [name: string]: string } = {};
        this.columns.forEach(column => column.fields.forEach(field => widths[field.name] = field.width));

        this.submit({
            action: 'saveListLayout',
            sid: this.restored?.sid ?? '',
            groups: this.columns.map(column => column.fields.map(field => field.name)),
            widths,
            labels: this.changes,
            subpanelTitle: this.data.subpanelTitle ? this.subpanelTitle : null
        }, data => {
            this.show(data);
            this.restored = null;
            this.message.addSuccessMessage(this.label('LBL_SAVED'));
        });
    }

    trackField(index: number, field: StudioLayoutField): string {
        return field.name;
    }

    protected show(data: StudioListLayout): void {
        this.data = data;
        this.columns = (data.columns ?? []).map(column => ({...column, fields: column.fields.map(field => ({...field}))}));
        this.subpanelTitle = data.subpanelTitle?.value ?? '';
        this.changes = {};
        this.editing = null;
        this.error = '';
    }

    protected submit(options: { [key: string]: any }, next: (data: StudioListLayout) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, {module: this.module, package: this.package, view: this.view, subpanel: this.subpanel, ...options})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.loading = false;
                    if (process?.status === 'success') {
                        next(process.data as StudioListLayout);
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
