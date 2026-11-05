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

import {ChangeDetectorRef, Component, Input, NgZone, OnDestroy, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {DomSanitizer, SafeResourceUrl} from '@angular/platform-browser';
import {CdkDragDrop, DragDropModule, moveItemInArray} from '@angular/cdk/drag-drop';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {LanguageStore} from '../../../../store/language/language.store';
import {DashboardEditService} from '../../services/dashboard-edit.service';
import {DashletOptionChoice, DashletOptions} from '../../dashboard.model';
import {DashletMultiSelectComponent} from '../dashlet-multi-select/dashlet-multi-select.component';

/**
 * Options of a dashlet. List and chart dashlets get a native form (title, rows,
 * auto refresh, "my items", columns, filters or chart fields), saved by the
 * dashlet itself (see DashboardEditHandler); the other dashlets (reports,
 * Activity Stream, iFrame...) their legacy form, in a frame. The modal closes with 'saved' when the
 * options changed.
 */
@Component({
    selector: 'scrm-dashlet-options-modal',
    standalone: true,
    imports: [CommonModule, FormsModule, DragDropModule, DashletMultiSelectComponent],
    template: `
        <div class="modal-header">
            <h4 class="modal-title" id="dashboard-options-title">{{ label('LBL_DASHBOARD_OPTIONS_OF').replace('%s', dashletTitle) }}</h4>
            <button type="button" class="btn-close close" [attr.aria-label]="appLabel('LBL_CLOSE_BUTTON_TITLE')"
                    (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <p *ngIf="loading" class="dashboard-modal-message">{{ label('LBL_DASHBOARD_LOADING') }}</p>
            <p *ngIf="error" class="dashboard-modal-message dashboard-modal-error" role="alert">{{ error }}</p>
            <p *ngIf="options?.type === 'none'" class="dashboard-modal-message">{{ label('LBL_DASHBOARD_NO_OPTIONS') }}</p>

            <iframe *ngIf="legacyUrl" class="dashboard-options-frame" [src]="legacyUrl"
                    [title]="label('LBL_DASHBOARD_OPTIONS_OF').replace('%s', dashletTitle)"></iframe>

            <form *ngIf="isNative" class="dashboard-options" (ngSubmit)="save()" id="dashboard-options-form">
                <fieldset>
                    <legend>{{ label('LBL_DASHBOARD_GENERAL') }}</legend>
                    <div class="dashboard-field">
                        <label for="dashlet-option-title">{{ label('LBL_DASHBOARD_TITLE') }}</label>
                        <input id="dashlet-option-title" name="title" class="form-control" maxlength="100" required [(ngModel)]="title">
                    </div>
                    <div *ngIf="options.rows" class="dashboard-field">
                        <label for="dashlet-option-rows">{{ label('LBL_DASHBOARD_ROWS') }}</label>
                        <select id="dashlet-option-rows" name="rows" class="form-control" [(ngModel)]="rows">
                            <option *ngFor="let option of options.rows.options" [value]="option">{{ option }}</option>
                        </select>
                    </div>
                    <div *ngIf="options.autoRefresh" class="dashboard-field">
                        <label for="dashlet-option-refresh">{{ label('LBL_DASHBOARD_AUTO_REFRESH') }}</label>
                        <select id="dashlet-option-refresh" name="autoRefresh" class="form-control" [(ngModel)]="autoRefresh">
                            <option *ngFor="let option of options.autoRefresh.options" [value]="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div *ngIf="options.myItemsOnly !== null && options.myItemsOnly !== undefined" class="dashboard-field dashboard-check">
                        <label>
                            <input type="checkbox" name="myItemsOnly" [(ngModel)]="myItemsOnly">
                            {{ label('LBL_DASHBOARD_MY_ITEMS') }}
                        </label>
                    </div>
                </fieldset>

                <fieldset *ngIf="options.columns">
                    <legend>{{ label('LBL_DASHBOARD_COLUMNS_SHOWN') }}</legend>
                    <ol class="dashboard-columns-list" cdkDropList (cdkDropListDropped)="moveColumn($event)">
                        <li *ngFor="let column of columns; let index = index" cdkDrag cdkDragLockAxis="y">
                            <span class="dashboard-drag-handle" cdkDragHandle aria-hidden="true">⠿</span>
                            <span class="dashboard-columns-name">{{ columnLabel(column) }}</span>
                            <button type="button" class="dashboard-icon-btn dashboard-small-btn" [disabled]="index === 0"
                                    [attr.aria-label]="label('LBL_DASHBOARD_MOVE_UP') + ' — ' + columnLabel(column)"
                                    (click)="shiftColumn(index, -1)">↑</button>
                            <button type="button" class="dashboard-icon-btn dashboard-small-btn" [disabled]="index === columns.length - 1"
                                    [attr.aria-label]="label('LBL_DASHBOARD_MOVE_DOWN') + ' — ' + columnLabel(column)"
                                    (click)="shiftColumn(index, 1)">↓</button>
                            <button type="button" class="dashboard-icon-btn dashboard-small-btn" [disabled]="columns.length === 1"
                                    [attr.aria-label]="label('LBL_DASHBOARD_REMOVE') + ' — ' + columnLabel(column)"
                                    (click)="removeColumn(index)">×</button>
                        </li>
                    </ol>
                    <div *ngIf="hiddenColumns().length" class="dashboard-field">
                        <label for="dashlet-option-add-column">{{ label('LBL_DASHBOARD_ADD_COLUMN') }}</label>
                        <select id="dashlet-option-add-column" class="form-control" [ngModel]="''" name="addColumn"
                                (ngModelChange)="addColumn($event)">
                            <option value="" disabled>—</option>
                            <option *ngFor="let option of hiddenColumns()" [value]="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </fieldset>

                <fieldset *ngIf="options.fields?.length">
                    <legend>{{ label(options.type === 'list' ? 'LBL_DASHBOARD_FILTERS' : 'LBL_DASHBOARD_CHART_FIELDS') }}</legend>
                    <div *ngFor="let field of options.fields; let index = index" class="dashboard-field">
                        <label [id]="'dashlet-option-field-label-' + index" [attr.for]="field.kind === 'multi' ? null : 'dashlet-option-field-' + index">{{ field.label }}</label>
                        <ng-container [ngSwitch]="field.kind">
                            <scrm-dashlet-multi-select *ngSwitchCase="'multi'" [options]="field.options" [labelId]="'dashlet-option-field-label-' + index"
                                              [value]="$any(fields[field.name])" (valueChange)="fields[field.name] = $event"
                                              [searchLabel]="label('LBL_DASHBOARD_SEARCH')" [allLabel]="label('LBL_DASHBOARD_ALL')"
                                              [selectedLabel]="label('LBL_DASHBOARD_SELECTED')" [clearLabel]="appLabel('LBL_CLEAR_BUTTON_LABEL')">
                            </scrm-dashlet-multi-select>
                            <select *ngSwitchCase="'select'" class="form-control" [id]="'dashlet-option-field-' + index"
                                    [name]="field.name" [(ngModel)]="fields[field.name]">
                                <option *ngFor="let option of field.options" [value]="option.value">{{ option.label }}</option>
                            </select>
                            <input *ngSwitchCase="'date'" type="date" class="form-control" [id]="'dashlet-option-field-' + index"
                                   [name]="field.name" [(ngModel)]="fields[field.name]">
                            <input *ngSwitchDefault class="form-control" [id]="'dashlet-option-field-' + index"
                                   [name]="field.name" [(ngModel)]="fields[field.name]">
                        </ng-container>
                    </div>
                </fieldset>
            </form>
        </div>
        <div *ngIf="isNative" class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" (click)="modal.dismiss()">{{ appLabel('LBL_CANCEL_BUTTON_LABEL') }}</button>
            <button type="submit" form="dashboard-options-form" class="btn btn-sm btn-main" [disabled]="saving || !title.trim()">
                {{ appLabel('LBL_SAVE_BUTTON_LABEL') }}
            </button>
        </div>
    `
})
export class DashletOptionsModalComponent implements OnInit, OnDestroy {

    @Input() id = '';
    @Input() dashletTitle = '';

    options: DashletOptions | null = null;
    loading = true;
    saving = false;
    error = '';
    legacyUrl: SafeResourceUrl | null = null;

    title = '';
    rows = '';
    autoRefresh = '';
    myItemsOnly = false;
    columns: string[] = [];
    fields: { [name: string]: string | string[] } = {};

    protected messageListener = (event: MessageEvent): void => this.onFrameMessage(event);

    constructor(
        public modal: NgbActiveModal,
        protected edit: DashboardEditService,
        protected languageStore: LanguageStore,
        protected sanitizer: DomSanitizer,
        protected zone: NgZone,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get isNative(): boolean {
        return this.options?.type === 'list' || this.options?.type === 'chart';
    }

    ngOnInit(): void {
        this.edit.run<DashletOptions>({action: 'options', id: this.id}).subscribe(options => {
            this.loading = false;
            if (!options) {
                this.error = this.label('LBL_DASHBOARD_DASHLET_ERROR');
                this.cdr.detectChanges();
                return;
            }

            this.options = options;
            if (options.type === 'legacy') {
                // The legacy page reports the end of its options form (see modules/Home/Dashlet.php).
                window.addEventListener('message', this.messageListener);
                this.legacyUrl = this.sanitizer.bypassSecurityTrustResourceUrl(
                    './legacy/index.php?module=Home&action=Dashlet&configure=1&id=' + encodeURIComponent(this.id)
                );
            } else {
                this.title = options.title;
                this.rows = options.rows?.value ?? '';
                this.autoRefresh = options.autoRefresh?.value ?? '';
                this.myItemsOnly = !!options.myItemsOnly;
                this.columns = [...(options.columns?.value ?? [])];
                (options.fields ?? []).forEach(field => {
                    this.fields[field.name] = Array.isArray(field.value) ? [...field.value] : field.value;
                });
            }
            this.cdr.detectChanges();
        });
    }

    ngOnDestroy(): void {
        window.removeEventListener('message', this.messageListener);
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    appLabel(key: string): string {
        return this.languageStore.getAppString(key) || key;
    }

    columnLabel(name: string): string {
        return this.options?.columns?.options.find(option => option.value === name)?.label || name;
    }

    hiddenColumns(): DashletOptionChoice[] {
        return (this.options?.columns?.options ?? []).filter(option => !this.columns.includes(option.value));
    }

    addColumn(name: string): void {
        if (name && !this.columns.includes(name)) {
            this.columns = [...this.columns, name];
        }
    }

    removeColumn(index: number): void {
        if (this.columns.length > 1) {
            this.columns = this.columns.filter((_, position) => position !== index);
        }
    }

    shiftColumn(index: number, step: number): void {
        const columns = [...this.columns];
        moveItemInArray(columns, index, index + step);
        this.columns = columns;
    }

    moveColumn(event: CdkDragDrop<string[]>): void {
        const columns = [...this.columns];
        moveItemInArray(columns, event.previousIndex, event.currentIndex);
        this.columns = columns;
    }

    save(): void {
        if (!this.options || this.saving || !this.title.trim()) {
            return;
        }

        this.saving = true;
        this.error = '';
        this.edit.done({
            action: 'saveOptions',
            id: this.id,
            values: {
                title: this.title,
                rows: this.rows,
                autoRefresh: this.autoRefresh,
                myItemsOnly: this.myItemsOnly,
                columns: this.columns,
                fields: this.fields
            }
        }).subscribe(done => {
            this.saving = false;
            if (done) {
                this.modal.close('saved');
                return;
            }
            this.error = this.label('LBL_DASHBOARD_SAVE_ERROR');
            this.cdr.detectChanges();
        });
    }

    protected onFrameMessage(event: MessageEvent): void {
        const data = event.data;
        if (event.origin !== window.location.origin || data?.type !== 'dashlet-options' || data.id !== this.id) {
            return;
        }

        this.zone.run(() => data.saved ? this.modal.close('saved') : this.modal.dismiss());
    }
}
