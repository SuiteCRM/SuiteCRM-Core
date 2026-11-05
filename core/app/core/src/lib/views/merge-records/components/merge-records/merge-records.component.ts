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
import {ActivatedRoute, ParamMap, Router} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';

export interface MergeRecord {
    id: string;
    name: string;
    editable: boolean;
}

export interface MergeField {
    name: string;
    label: string;
    required: boolean;
    values: { [id: string]: string };
    raw: { [id: string]: string };
    // Dropdown (enum) or multi-select (multienum) list: its options, in every column. A value
    // of a record that is no longer an option is shown, and cannot be chosen.
    type?: 'enum' | 'multienum';
    options?: { value: string; label: string; disabled?: boolean }[];
}

/**
 * Merge records view of duplicate records (legacy MergeRecords Step3): one
 * column per record, the primary one first, and for each field the value to
 * keep, picked among the records (the primary value by default, or the first
 * filled one when the primary record has none). Another record can be set as
 * primary, or removed from the merge. Save Merge keeps the primary record with
 * the chosen values, moves the related records of the others to it and deletes
 * them (process `record-merge`, RecordMerger), then opens it.
 *
 * Route merge-records/index with the legacy module (action_module) and the
 * record ids (uid): list "Merge" bulk action, end of the legacy Find Duplicates.
 */
@Component({
    selector: 'scrm-merge-records',
    templateUrl: './merge-records.component.html',
    styleUrls: [],
})
export class MergeRecordsComponent implements OnInit, OnDestroy {

    module = '';
    moduleLabel = '';
    records: MergeRecord[] = [];
    fields: MergeField[] = [];
    choices: { [field: string]: string } = {};
    // Values of the dropdown lists, by field and record: the record's value, or the one chosen in it.
    listValues: { [field: string]: { [id: string]: string | string[] } } = {};
    loading = false;
    saving = false;
    error = '';
    showSame = false;

    protected returnModule = '';
    protected returnId = '';
    protected subscriptions: Subscription[] = [];

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected moduleNameMapper: ModuleNameMapper,
        protected confirmation: ConfirmationModalService,
        protected messages: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get primary(): MergeRecord {
        return this.records[0] ?? null;
    }

    get differentFields(): MergeField[] {
        return this.fields.filter(field => this.isDifferent(field));
    }

    get sameFields(): MergeField[] {
        return this.fields.filter(field => !this.isDifferent(field));
    }

    get canMerge(): boolean {
        return !this.saving && this.records.length > 1 && this.records.every(record => record.editable);
    }

    ngOnInit(): void {
        this.subscriptions.push(this.route.queryParamMap.subscribe(params => this.load(params)));
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
    }

    label(key: string): string {
        return this.language.getAppString(key) || key;
    }

    isDifferent(field: MergeField): boolean {
        return new Set(this.records.map(record => field.raw[record.id] ?? '')).size > 1;
    }

    choose(field: MergeField, record: MergeRecord): void {
        this.choices[field.name] = record.id;
    }

    /** A value chosen in the dropdown list of a record column: the value kept for the field. */
    setListValue(field: MergeField, record: MergeRecord, value: string | string[]): void {
        this.choices[field.name] = record.id;
        this.listValues[field.name][record.id] = value;
    }

    setPrimary(record: MergeRecord): void {
        this.records = [record, ...this.records.filter(item => item !== record)];
        this.resetChoices();
    }

    remove(record: MergeRecord): void {
        this.records = this.records.filter(item => item !== record);
        this.resetChoices();
    }

    /**
     * Back to the record or list the merge was opened from (return_id /
     * return_module, as the legacy Cancel), not through the history: the legacy
     * Find Duplicates page before it would open this screen again.
     */
    cancel(): void {
        const module = this.returnModule || this.module;
        this.router.navigate(this.returnId ? ['/', module, 'record', this.returnId] : ['/', module]);
    }

    save(): void {
        if (!this.canMerge) {
            return;
        }

        this.confirmation.showModal(['LBL_MERGE_RECORDS_CONFIRM'], () => this.merge());
    }

    protected merge(): void {
        const primary = this.primary;
        const choices: { [field: string]: string } = {};
        Object.keys(this.choices)
            .filter(name => this.choices[name] !== primary.id)
            .forEach(name => choices[name] = this.choices[name]);
        // The value chosen in the dropdown list of the chosen column, when it is not the record's one.
        const values: { [field: string]: string | string[] } = {};
        this.fields.filter(field => field.options).forEach(field => {
            const id = this.choices[field.name];
            const value = this.listValues[field.name][id];
            if (this.encodeListValue(value) !== this.encodeListValue(this.recordListValue(field, id))) {
                values[field.name] = value;
            }
        });

        this.saving = true;
        this.processService.submit('record-merge', {
            action: 'merge',
            module: this.module,
            primary: primary.id,
            ids: this.records.slice(1).map(record => record.id),
            choices,
            values
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.saving = false;
                this.cdr.detectChanges();
                if (process?.status !== 'success') {
                    this.messages.addDangerMessageByKey('LBL_MERGE_RECORDS_ERROR');
                    return;
                }
                this.messages.addSuccessMessageByKey('LBL_MERGE_RECORDS_DONE');
                this.router.navigate(['/', this.module, 'record', primary.id], {replaceUrl: true});
            },
            error: () => {
                this.saving = false;
                this.cdr.detectChanges();
                this.messages.addDangerMessageByKey('LBL_MERGE_RECORDS_ERROR');
            }
        });
    }

    protected load(params: ParamMap): void {
        const legacyModule = params.get('action_module') || params.get('merge_module') || '';
        const ids = (params.get('uid') || '').split(',').map(id => id.trim()).filter(id => !!id);

        this.module = this.moduleNameMapper.toFrontend(legacyModule);
        this.returnModule = params.get('return_module') ? this.moduleNameMapper.toFrontend(params.get('return_module')) : '';
        this.returnId = params.get('return_id') || '';
        this.moduleLabel = (this.language.getAppListString('moduleList') as { [key: string]: string })?.[legacyModule] || legacyModule;
        this.records = [];
        this.fields = [];
        this.error = '';

        if (!this.module || ids.length < 2) {
            this.error = 'LBL_MERGE_RECORDS_LOAD_ERROR';
            return;
        }

        this.loading = true;
        this.processService.submit('record-merge', {action: 'load', module: this.module, ids}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as any;
                if (process?.status !== 'success' || !data?.records) {
                    this.error = 'LBL_MERGE_RECORDS_LOAD_ERROR';
                } else {
                    this.records = data.records;
                    this.fields = data.fields ?? [];
                    this.fields.filter(field => field.options).forEach(field => this.addRecordOptions(field));
                    this.resetChoices();
                }
                // The response can come outside the app zone (first page load).
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.error = 'LBL_MERGE_RECORDS_LOAD_ERROR';
                this.cdr.detectChanges();
            }
        });
    }

    /**
     * The primary value of each field, or the first filled one when it has none.
     */
    protected resetChoices(): void {
        this.choices = {};
        this.listValues = {};
        const primary = this.primary;
        if (!primary) {
            return;
        }

        this.fields.forEach(field => {
            const filled = this.records.find(record => (field.raw[record.id] ?? '') !== '');
            this.choices[field.name] = (field.raw[primary.id] ?? '') !== '' || !filled ? primary.id : filled.id;
            if (field.options) {
                this.listValues[field.name] = {};
                this.records.forEach(record => this.listValues[field.name][record.id] = this.recordListValue(field, record.id));
            }
        });
    }

    /** The value of a record in the dropdown list of a list field (the items of a multi-select list). */
    protected recordListValue(field: MergeField, id: string): string | string[] {
        const raw = field.raw[id] ?? '';

        return field.type === 'multienum' ? this.multienumValues(raw) : raw;
    }

    protected encodeListValue(value: string | string[]): string {
        return Array.isArray(value) ? value.join('^,^') : value ?? '';
    }

    /**
     * The values of the records that are not options of a dropdown list (an option removed
     * from the list since): shown in the list of their column, and not offered to the others.
     */
    protected addRecordOptions(field: MergeField): void {
        if (field.type === 'multienum') {
            return;
        }
        this.records.forEach(record => {
            const raw = field.raw[record.id] ?? '';
            if (!field.options.some(option => option.value === raw)) {
                field.options.push({value: raw, label: field.values[record.id] || raw, disabled: true});
            }
        });
    }

    /** The items of a multi-select list value (^a^,^b^). */
    protected multienumValues(raw: string): string[] {
        return raw ? raw.replace(/^\^|\^$/g, '').split('^,^').filter(item => item !== '') : [];
    }
}
