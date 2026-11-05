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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {ActivatedRoute, Router} from '@angular/router';
import {combineLatest, Observable, of, Subscription} from 'rxjs';
import {catchError, map, take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {SelectModalService} from '../../../../services/modals/select-modal.service';
import {
    WfActionLine,
    WfActionParams,
    WfChoice,
    WfComputeLine,
    WfConditionLine,
    WfFieldInfo,
    WfFieldLine,
    WfModule,
    WfRecipient,
    WfRelationLine,
    WfTarget,
    WfValue,
    WfValueLine,
    WfWorkflow
} from '../../workflow-editor.types';

/**
 * The workflow editor of SuiteCRM 8 (create, edit and duplicate a workflow), as the
 * legacy one (AOW_WorkFlow EditView):
 *
 * - details: name, user, module, status, when and on which records it runs, repeated runs, runs
 *   on import, description;
 * - conditions: module (the workflow module or a related one), field, operator, type and value
 *   (as offered for the field: value, field, any change, date, one of, security group);
 * - actions, each with its name and parameters: Create Record (module, fields, relationships),
 *   Modify Record (the record or its related records, fields, relationships), Send Email (template,
 *   recipients), Calculate Fields (parameters, relation parameters, formulas).
 *
 * Process `workflow-editor` (backend WorkflowEditor): the workflow is saved as the legacy form
 * saves it. Routes workflow/edit (new workflow; ?record=<id>&isDuplicate=true to duplicate one)
 * and workflow/edit/<id>.
 */
@Component({
    selector: 'scrm-workflow-editor',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './workflow-editor.component.html',
    styleUrls: []
})
export class WorkflowEditorComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'workflow-editor';
    static readonly LIST_TYPES = ['enum', 'multienum', 'dynamicenum'];

    workflow: WfWorkflow | null = null;
    conditions: WfConditionLine[] = [];
    actions: WfActionLine[] = [];
    loading = false;
    saving = false;
    error = false;

    // The workflow edited ('' for a new workflow or a copy), and the workflow copied or edited.
    protected id = '';
    protected sourceId = '';
    protected nextKey = 1;
    // Fields of the modules of the conditions, by module path.
    protected pathFields = new Map<string, WfChoice[]>();
    protected subscriptions: Subscription[] = [];

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected messages: MessageService,
        protected selectModal: SelectModalService,
        protected confirmation: ConfirmationModalService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    get module(): WfModule | null {
        return this.workflow?.module ?? null;
    }

    ngOnInit(): void {
        this.subscriptions.push(combineLatest([this.route.paramMap, this.route.queryParamMap]).subscribe(([params, query]) => {
            const record = params.get('record') || query.get('record') || '';
            const duplicate = query.get('isDuplicate') === 'true';
            this.sourceId = record;
            this.id = duplicate ? '' : record;
            this.load(record, duplicate);
        }));
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
    }

    /** A label of the legacy editor ("<module>.<label key>", see WorkflowEditor::getLabels()). */
    l(key: string): string {
        return this.workflow?.labels?.[key] || key.replace(/^.*\./, '');
    }

    /** A label of the page (AOW_WorkFlow strings). */
    text(key: string): string {
        return this.language.getFieldLabel(key, 'workflow') || key;
    }

    app(key: string): string {
        return this.language.getAppString(key) || key;
    }

    trackLine(index: number, line: { key: number }): number {
        return line.key;
    }

    // Details.

    selectUser(): void {
        this.selectModal.showSelectModal('users', (record: Record) => {
            if (record?.id) {
                this.workflow.assignedUserId = record.id;
                this.workflow.assignedUserName = this.recordLabel(record);
                this.cdr.detectChanges();
            }
        });
    }

    /**
     * Another workflow module: the conditions and actions are removed (they are on the fields of
     * the previous one), after a confirmation when there are some.
     */
    changeModule(module: string): void {
        const previous = this.workflow.flowModule;
        const apply = (): void => {
            this.workflow.flowModule = module;
            this.conditions = [];
            this.actions = [];
            this.pathFields.clear();
            this.loadModule(module);
        };

        if (!this.conditions.length && !this.actions.length) {
            apply();
            return;
        }
        this.workflow.flowModule = module;
        this.confirmation.showModal(['LBL_WORKFLOW_EDITOR_CHANGE_MODULE'], apply, () => {
            this.workflow.flowModule = previous;
            this.cdr.detectChanges();
        });
    }

    // Conditions.

    fieldsOf(path: string): WfChoice[] {
        return this.pathFields.get(path) ?? [];
    }

    addCondition(): void {
        const path = this.workflow.flowModule;
        this.conditions.push({key: this.nextKey++, id: '', path, field: '', operator: '', valueType: 'Value', value: '', valueLabel: '', info: null});
        this.loadPathFields(path);
    }

    removeCondition(line: WfConditionLine): void {
        this.conditions = this.conditions.filter(item => item !== line);
    }

    changePath(line: WfConditionLine): void {
        line.field = '';
        line.info = null;
        this.loadPathFields(line.path);
    }

    changeConditionField(line: WfConditionLine): void {
        line.info = null;
        if (!line.field) {
            return;
        }
        this.describe('condition', line.path, line.field).subscribe(info => {
            if (info) {
                line.info = info;
                line.operator = info.operators?.[0]?.value ?? '';
                line.valueType = 'Value';
                this.resetValue(line);
            }
            this.cdr.detectChanges();
        });
    }

    resetValue(line: WfValueLine): void {
        line.value = this.defaultValue(line.info, line.valueType);
        line.valueLabel = '';
    }

    isPlusMinus(value: WfValue): boolean {
        return Array.isArray(value) && ['plus', 'minus'].includes(value[1]);
    }

    selectRecord(line: { value: WfValue; valueLabel: string }, module: string): void {
        if (!module) {
            return;
        }
        this.selectModal.showSelectModal(module, (record: Record) => {
            if (record?.id) {
                line.value = record.id;
                line.valueLabel = this.recordLabel(record);
                this.cdr.detectChanges();
            }
        });
    }

    // Actions.

    addAction(): void {
        this.actions.push({key: this.nextKey++, id: '', name: '', action: '', supported: true, params: {}});
    }

    removeAction(action: WfActionLine): void {
        this.actions = this.actions.filter(item => item !== action);
    }

    actionLabel(action: WfActionLine): string {
        return this.workflow.options.actions.find(option => option.value === action.action)?.label || action.action;
    }

    /** The parameters of a new action, as the legacy action forms start. */
    changeActionType(action: WfActionLine): void {
        switch (action.action) {
            case 'SendEmail':
                action.params = {individualEmail: false, emailTemplate: '', recipients: [this.newRecipient()]};
                break;
            case 'CreateRecord':
                action.params = {recordType: '', relateToWorkflow: true, copyEmailAddresses: false, fields: [], relationships: [], target: null};
                break;
            case 'ModifyRecord':
                action.params = {relType: this.workflow.flowModule, fields: [], relationships: [], target: null};
                this.loadTarget(action);
                break;
            case 'ComputeField':
                action.params = {parameters: [], relationParameters: [], formulas: []};
                break;
            default:
                action.params = {};
        }
    }

    /** Another module for Create Record or Modify Record: its fields and relationships are removed. */
    changeRecordType(action: WfActionLine): void {
        action.params.fields = [];
        action.params.relationships = [];
        action.params.target = null;
        this.loadTarget(action);
    }

    addField(action: WfActionLine): void {
        action.params.fields.push({key: this.nextKey++, field: '', valueType: 'Value', value: '', valueLabel: '', info: null});
    }

    removeField(action: WfActionLine, line: WfFieldLine): void {
        action.params.fields = action.params.fields.filter(item => item !== line);
    }

    changeActionField(action: WfActionLine, line: WfFieldLine): void {
        line.info = null;
        if (!line.field || !action.params.target) {
            return;
        }
        this.describe('action', action.params.target.name, line.field).subscribe(info => {
            if (info) {
                line.info = info;
                line.valueType = info.valueTypes[0]?.value ?? 'Value';
                this.resetValue(line);
            }
            this.cdr.detectChanges();
        });
    }

    addRelation(action: WfActionLine): void {
        action.params.relationships.push({key: this.nextKey++, rel: '', valueType: 'Value', value: '', valueLabel: '', info: null});
    }

    removeRelation(action: WfActionLine, line: WfRelationLine): void {
        action.params.relationships = action.params.relationships.filter(item => item !== line);
    }

    changeRelation(action: WfActionLine, line: WfRelationLine): void {
        line.info = null;
        if (!line.rel || !action.params.target) {
            return;
        }
        this.describe('relation', action.params.target.name, line.rel).subscribe(info => {
            if (info) {
                line.info = info;
                line.valueType = 'Value';
                this.resetValue(line);
            }
            this.cdr.detectChanges();
        });
    }

    addRecipient(action: WfActionLine): void {
        action.params.recipients.push(this.newRecipient());
    }

    removeRecipient(action: WfActionLine, recipient: WfRecipient): void {
        action.params.recipients = action.params.recipients.filter(item => item !== recipient);
    }

    changeRecipientType(recipient: WfRecipient): void {
        recipient.value = recipient.type === 'Users' ? ['all', '', ''] : '';
        recipient.valueLabel = '';
    }

    /** The email template page, to create one or change the chosen one (legacy Create and Edit links). */
    templateUrl(id: string): string {
        return id ? `#/email-templates/record/${id}` : '#/email-templates/edit';
    }

    // Calculate Fields.

    addParameter(action: WfActionLine): void {
        action.params.parameters.push({key: this.nextKey++, field: this.module.fields[0]?.value ?? '', type: 'raw'});
    }

    addRelationParameter(action: WfActionLine): void {
        const relation = this.module.computeRelations[0];
        action.params.relationParameters.push({key: this.nextKey++, relation: relation?.value ?? '', field: relation?.fields[0]?.value ?? '', type: 'raw'});
    }

    addFormula(action: WfActionLine): void {
        action.params.formulas.push({key: this.nextKey++, field: this.module.fields[0]?.value ?? '', formula: ''});
    }

    removeCompute(lines: WfComputeLine[], line: WfComputeLine): void {
        lines.splice(lines.indexOf(line), 1);
    }

    relationFields(relation: string): WfChoice[] {
        return this.module.computeRelations.find(item => item.value === relation)?.fields ?? [];
    }

    changeComputeRelation(line: WfComputeLine): void {
        line.field = this.relationFields(line.relation)[0]?.value ?? '';
        line.type = 'raw';
    }

    /** The formatted value of a field is for its list fields only (legacy Calculate Fields). */
    isListField(fields: WfChoice[], field: string): boolean {
        return WorkflowEditorComponent.LIST_TYPES.includes(fields.find(item => item.value === field)?.type ?? '');
    }

    // Save.

    save(): void {
        if (!this.workflow || this.saving) {
            return;
        }
        if (!this.workflow.name.trim() || !this.workflow.flowModule) {
            this.messages.addWarningMessage(this.text('LBL_WORKFLOW_EDITOR_REQUIRED'));
            return;
        }
        if (this.conditions.some(line => !line.field || !line.info)
            || this.actions.some(action => !action.action || !this.isComplete(action))) {
            this.messages.addWarningMessage(this.text('LBL_WORKFLOW_EDITOR_INCOMPLETE'));
            return;
        }

        const lineValue = (line: WfValueLine) => ({valueType: line.valueType, value: line.value});
        const workflow = {
            name: this.workflow.name,
            assignedUserId: this.workflow.assignedUserId,
            flowModule: this.workflow.flowModule,
            status: this.workflow.status,
            runWhen: this.workflow.runWhen,
            flowRunOn: this.workflow.flowRunOn,
            multipleRuns: this.workflow.multipleRuns,
            runOnImport: this.workflow.runOnImport,
            description: this.workflow.description,
            conditions: this.conditions.map(line => ({
                id: line.id, path: line.path, field: line.field, operator: line.operator, ...lineValue(line)
            })),
            actions: this.actions.map(action => ({
                id: action.id,
                name: action.name,
                action: action.action,
                params: {
                    ...action.params,
                    target: undefined,
                    fields: action.params.fields?.map(line => ({field: line.field, ...lineValue(line)})),
                    relationships: action.params.relationships?.map(line => ({rel: line.rel, ...lineValue(line)})),
                    recipients: action.params.recipients?.map(recipient => ({to: recipient.to, type: recipient.type, value: recipient.value}))
                }
            }))
        };

        this.saving = true;
        this.call({action: 'save', id: this.id, workflow}).subscribe(data => {
            this.saving = false;
            if (!data?.id) {
                this.messages.addDangerMessage(this.text('LBL_WORKFLOW_EDITOR_SAVE_ERROR'));
                this.cdr.detectChanges();
                return;
            }
            this.router.navigate(['/workflow/record', data.id]).then();
        });
    }

    cancel(): void {
        this.router.navigate(this.sourceId ? ['/workflow/record', this.sourceId] : ['/workflow']).then();
    }

    protected isComplete(action: WfActionLine): boolean {
        const params = action.params;
        switch (action.action) {
            case 'SendEmail':
                return !!params.emailTemplate && !!params.recipients?.length;
            case 'CreateRecord':
            case 'ModifyRecord':
                return !!params.target && params.fields.every(line => line.field && line.info)
                    && params.relationships.every(line => line.rel && line.info);
            default:
                return true;
        }
    }

    protected load(record: string, duplicate: boolean): void {
        this.loading = true;
        this.error = false;
        this.workflow = null;
        this.pathFields.clear();
        this.call({action: 'load', id: record, duplicate}).subscribe((data: WfWorkflow) => {
            this.loading = false;
            if (!data?.options) {
                this.error = true;
                this.cdr.detectChanges();
                return;
            }

            this.workflow = data;
            this.conditions = data.conditions.map(line => ({...line, key: this.nextKey++}));
            this.actions = data.actions.map(action => ({...action, key: this.nextKey++, params: this.keyed(action.params)}));
            [...new Set(this.conditions.map(line => line.path))].forEach(path => this.loadPathFields(path));
            this.cdr.detectChanges();
        });
    }

    /** The lines of the parameters of an action, with their keys. */
    protected keyed(params: WfActionParams): WfActionParams {
        const keys = <T>(lines: T[] | undefined): T[] | undefined => lines?.map(line => ({...line, key: this.nextKey++}));

        return {
            ...params,
            recipients: keys(params.recipients),
            fields: keys(params.fields),
            relationships: keys(params.relationships),
            parameters: keys(params.parameters),
            relationParameters: keys(params.relationParameters),
            formulas: keys(params.formulas)
        };
    }

    protected loadModule(module: string): void {
        this.call({action: 'module', flowModule: module}).subscribe((data: WfModule) => {
            if (data?.name) {
                this.workflow.module = data;
            }
            this.cdr.detectChanges();
        });
    }

    protected loadPathFields(path: string): void {
        if (!path || this.pathFields.has(path)) {
            return;
        }
        this.pathFields.set(path, []);
        this.call({action: 'target', flowModule: this.workflow.flowModule, relType: path}).subscribe((data: WfTarget) => {
            this.pathFields.set(path, data?.fields ?? []);
            this.cdr.detectChanges();
        });
    }

    /** The module a Create Record or Modify Record action changes, with its fields and relationships. */
    protected loadTarget(action: WfActionLine): void {
        const params = action.params;
        const options = action.action === 'CreateRecord'
            ? {module: params.recordType}
            : {relType: params.relType || this.workflow.flowModule};
        if (action.action === 'CreateRecord' && !params.recordType) {
            return;
        }
        this.call({action: 'target', flowModule: this.workflow.flowModule, ...options}).subscribe((data: WfTarget) => {
            params.target = data?.name ? data : null;
            this.cdr.detectChanges();
        });
    }

    protected describe(kind: string, module: string, field: string): Observable<WfFieldInfo | null> {
        return this.call({action: 'field', flowModule: this.workflow.flowModule, kind, module, field});
    }

    protected newRecipient(): WfRecipient {
        return {key: this.nextKey++, to: 'to', type: this.module?.emailTypes[0]?.value ?? 'Email Address', value: '', valueLabel: ''};
    }

    /** The value of a new line, or of a line of another type, as the legacy inputs start. */
    protected defaultValue(info: WfFieldInfo | null, valueType: string): WfValue {
        switch (valueType) {
            case 'Date':
                return [info?.dateBases?.[0]?.value ?? 'now', 'now', '', ''];
            case 'Round_Robin':
            case 'Least_Busy':
            case 'Random':
                return ['all', '', ''];
            case 'Multi':
                return [];
            case 'Value':
                if (info?.input === 'multi') {
                    return [];
                }
                if (info?.input === 'bool') {
                    return '0';
                }
                if (info?.input === 'select') {
                    return info.options[0]?.value ?? '';
                }

                return '';
            default:
                return '';
        }
    }

    protected recordLabel(record: Record): string {
        const attributes = record.attributes ?? {};

        return attributes.name || attributes.full_name || attributes.user_name || record.id;
    }

    protected call(options: { [key: string]: any }): Observable<any> {
        return this.processService.submit(WorkflowEditorComponent.PROCESS, options).pipe(
            take(1),
            map((process: Process) => process?.status === 'success' ? process.data : null),
            catchError(() => of(null))
        );
    }
}
