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

import {ChangeDetectorRef, Component, ElementRef, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {CdkDragDrop, DragDropModule, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {
    STUDIO_EMPTY,
    STUDIO_FILLER,
    STUDIO_PROCESS,
    StudioGridLayout,
    StudioGridPanel,
    StudioGridSlot,
    StudioHelp,
    StudioHistoryEntry,
    StudioLabels
} from '../../studio.model';

/**
 * The height of the fixed navbar, over the top of the page.
 */
const NAVBAR_HEIGHT = 64;

/**
 * A slot of the layout: its panel, row and column.
 */
export interface StudioSlotRef {
    panel: number;
    row: number;
    col: number;
}

/**
 * What is dragged: a field of the layout, a field of the toolbox, or a filler.
 */
export type StudioGridDrag = { kind: 'slot'; ref: StudioSlotRef } | { kind: 'available'; field: StudioGridSlot } | { kind: 'filler' };

/**
 * A grid layout of a module in Studio (legacy layout editor: edit view, detail view,
 * quick create): its panels (label, panel or tab, collapsed) and rows of fields, ordered by drag
 * and drop from the toolbox (fields not on the layout, filler, new panel, new row) or between
 * slots, a field on a whole row, the label and tab order of a field, the label of a panel; Save
 * (a draft) and Save & Deploy as the legacy editor, View History, Restore Default, Copy from
 * EditView and Sync to Detail View.
 *
 * The new panel and the new row of the toolbox are dragged to their place in the layout, and
 * the panels and rows of the layout are moved by drag and drop, as in the legacy editor.
 */
@Component({
    selector: 'scrm-studio-grid-layout',
    standalone: true,
    imports: [CommonModule, FormsModule, DragDropModule],
    templateUrl: './studio-grid-layout.component.html'
})
export class StudioGridLayoutComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    /** The legacy view of the layout (editview, detailview, quickcreate). */
    @Input() view = '';
    @Input() labels: StudioLabels = {};
    @Input() help: StudioHelp = {};
    @Output() hover = new EventEmitter<string>();

    readonly filler = STUDIO_FILLER;
    /** Nothing is dropped on the filler of the toolbox. */
    readonly never = (): boolean => false;

    data: StudioGridLayout = null;
    panels: StudioGridPanel[] = [];
    available: StudioGridSlot[] = [];
    nextPanelId = 1;
    sync = false;
    /** The labels changed (label key => text), saved with the layout. */
    changes: { [key: string]: string } = {};
    /** The layout of the history shown (restored, not saved yet). */
    restored: StudioHistoryEntry = null;
    copied = false;
    editingSlot: StudioGridSlot = null;
    editingPanel: StudioGridPanel = null;
    historyOpen = false;
    error = '';
    loading = false;
    /** The ids of the drop lists (toolbox and slots), connected to one another. */
    dropIds: string[] = [];
    /** The ids of the rows of the panels, connected to one another and to the new row of the toolbox. */
    rowListIds: string[] = [];
    /** The panel of the new row of the toolbox: none. */
    readonly noPanel = -1;
    /** The row or panel just added, highlighted for a moment. */
    added: StudioGridSlot[] | StudioGridPanel = null;

    protected sub: Subscription;
    protected addedTimer: ReturnType<typeof setTimeout>;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef,
        protected host: ElementRef<HTMLElement>
    ) {
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module || changes.view) {
            this.load();
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
        clearTimeout(this.addedTimer);
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    helpText(key: string): string {
        return this.help?.layoutEditor?.[key] ?? '';
    }

    get maxColumns(): number {
        return Math.max(1, this.data?.maxColumns ?? 2);
    }

    get disabled(): boolean {
        return !this.data || this.data.disabled || this.loading;
    }

    load(sid = '', copyFromEditView = false): void {
        this.submit({action: 'gridLayout', sid, copyFromEditView}, data => {
            this.show(data);
            this.restored = sid ? (data.history.find(entry => entry.sid === sid) ?? {sid, date: sid}) : null;
            this.copied = copyFromEditView;
        });
    }

    /**
     * A layout of the history in the editor, saved by Save or Save & Deploy (legacy View
     * History, Restore, Restore Default).
     */
    restore(sid: string): void {
        this.historyOpen = false;
        this.load(sid);
    }

    copyFromEditView(): void {
        this.load('', true);
    }

    isField(slot: StudioGridSlot): boolean {
        return !!slot && slot.name !== STUDIO_FILLER && slot.name !== STUDIO_EMPTY;
    }

    slotId(ref: StudioSlotRef): string {
        return `studio-slot-${ref.panel}-${ref.row}-${ref.col}`;
    }

    rowsId(panel: number): string {
        return `studio-rows-${panel}`;
    }

    /**
     * The fields of a row, to name it while it is dragged.
     */
    rowLabel(slots: StudioGridSlot[]): string {
        return slots.filter(slot => this.isField(slot)).map(slot => slot.label || slot.name).join(' · ') || this.label('LBL_FILLER');
    }

    /**
     * Whether a slot is taken by the field before it (an empty slot after a field).
     */
    isSpanned(panel: number, row: number, col: number): boolean {
        return col > 0 && this.panels[panel].rows[row][col].name === STUDIO_EMPTY;
    }

    /**
     * The columns of a field: its own and the empty slots after it.
     */
    span(panel: number, row: number, col: number): number {
        const slots = this.panels[panel].rows[row];
        let span = 1;
        while (col + span < slots.length && slots[col + span].name === STUDIO_EMPTY) {
            span++;
        }

        return span;
    }

    /**
     * A field takes a whole row when the other slots of the row are fillers (legacy expand).
     */
    canExpand(panel: number, row: number, col: number): boolean {
        const slots = this.panels[panel].rows[row];

        return this.maxColumns > 1 && this.isField(slots[col])
            && slots.every((slot, index) => index === col || slot.name === STUDIO_FILLER || slot.name === STUDIO_EMPTY);
    }

    isExpanded(panel: number, row: number, col: number): boolean {
        return this.span(panel, row, col) > 1;
    }

    toggleExpand(panel: number, row: number, col: number): void {
        const slots = this.panels[panel].rows[row];
        if (this.isExpanded(panel, row, col)) {
            this.unspan({panel, row, col});
        } else {
            const field = slots[col];
            slots.forEach((slot, index) => slots[index] = index === 0 ? field : this.emptySlot());
        }
    }

    /**
     * A field dropped on a slot: from the layout, the fields of the two slots swapped; from the
     * toolbox, the field (or a filler) in the slot, its field back to the toolbox.
     */
    dropOnSlot(event: CdkDragDrop<StudioSlotRef, unknown, StudioGridDrag>): void {
        const target = event.container.data;
        const source = event.item.data;
        if (this.disabled || (source.kind === 'slot' && this.slotId(source.ref) === this.slotId(target))) {
            return;
        }
        const replaced = this.slot(target);
        this.unspan(target);
        if (source.kind === 'slot') {
            const moved = this.slot(source.ref);
            this.unspan(source.ref);
            this.setSlot(source.ref, replaced.name === STUDIO_EMPTY ? this.fillerSlot() : replaced);
            this.setSlot(target, moved);
        } else {
            if (this.isField(replaced)) {
                this.toAvailable(replaced);
            }
            if (source.kind === 'available') {
                this.available = this.available.filter(field => field !== source.field);
            }
            this.setSlot(target, source.kind === 'available' ? source.field : this.fillerSlot());
        }
        this.error = '';
    }

    /**
     * A field of the layout dropped on the toolbox: removed (legacy recycle bin).
     */
    dropOnAvailable(event: CdkDragDrop<unknown, unknown, StudioGridDrag>): void {
        const source = event.item.data;
        if (!this.disabled && source.kind === 'slot') {
            this.removeField(source.ref);
        }
    }

    removeField(ref: StudioSlotRef): void {
        const field = this.slot(ref);
        if (this.isField(field)) {
            this.unspan(ref);
            this.setSlot(ref, this.fillerSlot());
            this.toAvailable(field);
        }
    }

    /**
     * A field of the toolbox added without the mouse: in the first filler of the first panel,
     * else on a new row of it (legacy addField).
     */
    addField(field: StudioGridSlot): void {
        if (!this.panels.length) {
            this.addPanel();
        }
        const rows = this.panels[0].rows;
        let row = rows.findIndex(slots => slots.some(slot => slot.name === STUDIO_FILLER));
        if (row < 0) {
            rows.push(this.newRow());
            row = rows.length - 1;
            this.refreshIds();
        }
        const col = rows[row].findIndex(slot => slot.name === STUDIO_FILLER);
        this.available = this.available.filter(item => item !== field);
        this.setSlot({panel: 0, row, col}, field);
    }

    /**
     * A new panel at the end of the layout, or before the panel given (legacy new panel:
     * lbl_<view>_panel<n>).
     */
    addPanel(index = this.panels.length): void {
        const panel: StudioGridPanel = {
            id: `lbl_${this.view.toLowerCase()}_panel${this.nextPanelId++}`,
            label: this.label('LBL_NEW_PANEL'),
            labelEditable: true,
            newTab: false,
            panelDefault: 'expanded',
            rows: [this.newRow()]
        };
        this.panels.splice(index, 0, panel);
        this.firstPanelTab();
        this.refreshIds();
        this.reveal(panel);
    }

    /**
     * The new panel of the toolbox clicked, not dragged: after the panel on screen.
     */
    addPanelOnScreen(): void {
        this.addPanel(this.panelOnScreen() + 1);
    }

    /**
     * A panel dropped on the layout: the new panel of the toolbox created there, a panel of the
     * layout moved there (legacy drag and drop of the panels).
     */
    dropPanel(event: CdkDragDrop<unknown>): void {
        if (this.disabled) {
            return;
        }
        if (event.previousContainer !== event.container) {
            this.addPanel(event.currentIndex);
            return;
        }
        moveItemInArray(this.panels, event.previousIndex, event.currentIndex);
        this.firstPanelTab();
        this.refreshIds();
    }

    removePanel(panel: number): void {
        this.panels[panel].rows.forEach(slots => slots.filter(slot => this.isField(slot)).forEach(slot => this.toAvailable(slot)));
        this.panels.splice(panel, 1);
        this.editingPanel = null;
        this.refreshIds();
    }

    movePanel(panel: number, offset: number): void {
        const target = panel + offset;
        if (target >= 0 && target < this.panels.length) {
            [this.panels[panel], this.panels[target]] = [this.panels[target], this.panels[panel]];
            this.firstPanelTab();
            this.refreshIds();
        }
    }

    /**
     * A new row at the end of a panel, or before the row given.
     */
    addRow(panel: number, index = this.panels[panel].rows.length): void {
        const row = this.newRow();
        this.panels[panel].rows.splice(index, 0, row);
        this.refreshIds();
        this.reveal(row);
    }

    /**
     * The new row of the toolbox clicked, not dragged: at the end of the panel on screen.
     */
    addRowOnScreen(): void {
        this.addRow(this.panelOnScreen());
    }

    /**
     * A row dropped on the rows of a panel: the new row of the toolbox created there, a row of
     * the layout moved there (legacy drag and drop of the rows).
     */
    dropRow(event: CdkDragDrop<number>): void {
        if (this.disabled) {
            return;
        }
        const from = event.previousContainer.data;
        const to = event.container.data;
        if (from === this.noPanel) {
            this.addRow(to, event.currentIndex);
            return;
        }
        if (from === to) {
            moveItemInArray(this.panels[to].rows, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(this.panels[from].rows, this.panels[to].rows, event.previousIndex, event.currentIndex);
        }
        this.refreshIds();
    }

    removeRow(panel: number, row: number): void {
        this.panels[panel].rows[row].filter(slot => this.isField(slot)).forEach(slot => this.toAvailable(slot));
        this.panels[panel].rows.splice(row, 1);
        this.refreshIds();
    }

    /**
     * A row up or down, into the panel before or after at the ends of its panel.
     */
    moveRow(panel: number, row: number, offset: number): void {
        const rows = this.panels[panel].rows;
        const target = row + offset;
        if (target >= 0 && target < rows.length) {
            [rows[row], rows[target]] = [rows[target], rows[row]];
        } else if (this.panels[panel + offset]) {
            const [moved] = rows.splice(row, 1);
            const other = this.panels[panel + offset].rows;
            offset < 0 ? other.push(moved) : other.unshift(moved);
        }
        this.refreshIds();
    }

    /**
     * Whether the first panel is a tab because another panel is (legacy Display Type).
     */
    firstLocked(): boolean {
        return this.panels.slice(1).some(panel => panel.newTab);
    }

    setTab(panel: number, newTab: boolean): void {
        this.panels[panel].newTab = newTab;
        this.firstPanelTab();
    }

    toggleCollapsed(panel: StudioGridPanel): void {
        panel.panelDefault = panel.panelDefault === 'collapsed' ? 'expanded' : 'collapsed';
    }

    editSlot(slot: StudioGridSlot): void {
        this.editingPanel = null;
        this.editingSlot = this.editingSlot === slot ? null : slot;
    }

    editPanel(panel: StudioGridPanel): void {
        this.editingSlot = null;
        this.editingPanel = this.editingPanel === panel ? null : panel;
    }

    changeSlotLabel(slot: StudioGridSlot, text: string): void {
        slot.label = text;
        if (/^\w+$/.test(slot.labelKey.trim())) {
            this.changes[slot.labelKey.trim()] = text;
        }
    }

    changePanelLabel(panel: StudioGridPanel, text: string): void {
        panel.label = text;
        this.changes[panel.id.toUpperCase()] = text;
    }

    changeTabindex(slot: StudioGridSlot, value: string): void {
        slot.tabindex = String(value ?? '').replace(/\D/g, '').slice(0, 4);
    }

    /**
     * Saves the layout as the legacy Save (a draft) or Save & Deploy: a field at least, and the
     * required fields on the layout unless confirmed (not for the detail view).
     */
    save(deploy: boolean): void {
        if (!this.panels.some(panel => panel.rows.some(slots => slots.some(slot => this.isField(slot))))) {
            this.error = this.label('ERROR_MINIMUM_FIELDS');
            return;
        }
        const missing = this.data.type === 'detailview' ? [] : this.data.required.filter(name => this.available.some(field => field.name === name));
        if (missing.length && !window.confirm(`${this.label('ERROR_REQUIRED_FIELDS')}: ${missing.map(name => `"${name}"`).join(',')}`)) {
            return;
        }

        this.submit({
            action: 'saveGridLayout',
            sid: this.restored?.sid ?? '',
            deploy,
            panels: this.panels.map(panel => ({
                id: panel.id,
                label: panel.label,
                newTab: panel.newTab,
                panelDefault: panel.panelDefault,
                rows: panel.rows.map(slots => slots.map(slot => ({name: slot.name, labelKey: slot.labelKey, tabindex: slot.tabindex})))
            })),
            sync: this.data.type === 'editview' ? this.sync : null,
            labels: this.changes
        }, data => {
            this.show(data);
            this.restored = null;
            this.copied = false;
            this.message.addSuccessMessage(this.label('LBL_SAVED'));
        });
    }

    trackIndex(index: number): number {
        return index;
    }

    trackField(index: number, field: StudioGridSlot): string {
        return field.name;
    }

    protected show(data: StudioGridLayout): void {
        this.data = data;
        const copy = (slot: StudioGridSlot): StudioGridSlot => ({...slot});
        this.panels = (data.panels ?? []).map(panel => ({...panel, rows: panel.rows.map(slots => slots.map(copy))}));
        this.available = (data.available ?? []).map(copy);
        this.nextPanelId = data.nextPanelId ?? 1;
        this.sync = !!data.sync;
        this.changes = {};
        this.editingSlot = null;
        this.editingPanel = null;
        this.added = null;
        this.error = '';
        this.refreshIds();
    }

    protected slot(ref: StudioSlotRef): StudioGridSlot {
        return this.panels[ref.panel].rows[ref.row][ref.col];
    }

    protected setSlot(ref: StudioSlotRef, slot: StudioGridSlot): void {
        this.panels[ref.panel].rows[ref.row][ref.col] = slot;
    }

    /**
     * The empty slots after a field back to fillers (the field on its column only).
     */
    protected unspan(ref: StudioSlotRef): void {
        const slots = this.panels[ref.panel].rows[ref.row];
        for (let col = ref.col + 1; col < slots.length && slots[col].name === STUDIO_EMPTY; col++) {
            slots[col] = this.fillerSlot();
        }
    }

    /**
     * A field back to the toolbox, in the order of its labels (legacy getAvailableFields).
     */
    protected toAvailable(field: StudioGridSlot): void {
        if (this.editingSlot === field) {
            this.editingSlot = null;
        }
        this.available = [...this.available, field].sort((a, b) => a.label.localeCompare(b.label));
    }

    /**
     * The first panel a tab when another panel is (legacy showHideBox).
     */
    protected firstPanelTab(): void {
        if (this.firstLocked() && this.panels[0]) {
            this.panels[0].newTab = true;
        }
    }

    protected newRow(): StudioGridSlot[] {
        return Array.from({length: this.maxColumns}, () => this.fillerSlot());
    }

    protected fillerSlot(): StudioGridSlot {
        return {name: STUDIO_FILLER, label: '', labelKey: '', tabindex: ''};
    }

    protected emptySlot(): StudioGridSlot {
        return {name: STUDIO_EMPTY, label: '', labelKey: '', tabindex: ''};
    }

    protected refreshIds(): void {
        const ids = ['studio-available', 'studio-filler'];
        this.panels.forEach((panel, p) => panel.rows.forEach((slots, r) => slots.forEach((slot, c) => ids.push(this.slotId({panel: p, row: r, col: c})))));
        this.dropIds = ids;
        this.rowListIds = this.panels.map((panel, p) => this.rowsId(p));
    }

    /**
     * The panel taking most of the screen (the last panel when none is on screen).
     */
    protected panelOnScreen(): number {
        let panel = this.panels.length - 1;
        let most = 0;
        this.host.nativeElement.querySelectorAll('.studio-panel-box').forEach((box, index) => {
            const rect = box.getBoundingClientRect();
            const visible = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, NAVBAR_HEIGHT);
            if (visible > most) {
                most = visible;
                panel = index;
            }
        });

        return panel;
    }

    /**
     * A new row or panel highlighted for a moment, and scrolled to when it is out of the screen.
     */
    protected reveal(added: StudioGridSlot[] | StudioGridPanel): void {
        this.added = added;
        this.cdr.detectChanges();
        const element = this.host.nativeElement.querySelector('.is-added');
        const rect = element?.getBoundingClientRect();
        if (rect && (rect.top < NAVBAR_HEIGHT || rect.bottom > window.innerHeight)) {
            element.scrollIntoView({block: 'center', behavior: 'smooth'});
        }
        clearTimeout(this.addedTimer);
        this.addedTimer = setTimeout(() => {
            this.added = null;
            this.cdr.markForCheck();
        }, 1500);
    }

    protected submit(options: { [key: string]: any }, next: (data: StudioGridLayout) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, {module: this.module, view: this.view, ...options}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    next(process.data as StudioGridLayout);
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
