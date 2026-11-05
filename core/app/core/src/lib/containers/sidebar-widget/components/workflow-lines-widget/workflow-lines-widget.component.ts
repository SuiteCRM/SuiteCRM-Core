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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {
    WfActionLine,
    WfChoice,
    WfConditionLine,
    WfOptions,
    WfRecipient,
    WfValueLine,
    WfWorkflow
} from '../../../../views/workflow-editor/workflow-editor.types';

/**
 * The conditions and actions of a workflow under its details (record view, bottom widget
 * `workflow-lines`), as the Conditions and Actions panels of the legacy page; they are changed
 * in the workflow editor (Edit). Process `workflow-editor`, action view.
 */
@Component({
    selector: 'scrm-workflow-lines-widget',
    standalone: true,
    imports: [CommonModule],
    template: `
        <div class="workflow-lines">
            <ng-container *ngIf="data">
                <h3>{{ l('AOW_WorkFlow.LBL_CONDITION_LINES') }}</h3>
                <table *ngIf="data.conditions.length; else none" class="workflow-lines-table">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{ l('AOW_Conditions.LBL_MODULE_PATH') }}</th>
                        <th>{{ l('AOW_Conditions.LBL_FIELD') }}</th>
                        <th>{{ l('AOW_Conditions.LBL_OPERATOR') }}</th>
                        <th>{{ l('AOW_Conditions.LBL_VALUE_TYPE') }}</th>
                        <th>{{ l('AOW_Conditions.LBL_VALUE') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr *ngFor="let line of data.conditions; let index = index">
                        <td class="workflow-lines-number">{{ index + 1 }}</td>
                        <td>{{ condition(line, 'path') }}</td>
                        <td>{{ condition(line, 'field') }}</td>
                        <td>{{ label(line.info?.operators, line.operator) }}</td>
                        <td>{{ label(line.info?.valueTypes, line.valueType) }}</td>
                        <td>{{ valueText(line) }}</td>
                    </tr>
                    </tbody>
                </table>

                <h3>{{ l('AOW_WorkFlow.LBL_ACTION_LINES') }}</h3>
                <table *ngIf="data.actions.length; else none" class="workflow-lines-table">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{ l('AOW_Actions.LBL_NAME') }}</th>
                        <th>{{ l('AOW_Actions.LBL_ACTION') }}</th>
                        <th>{{ l('AOW_Actions.LBL_PARAMETERS') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr *ngFor="let action of data.actions; let index = index">
                        <td class="workflow-lines-number">{{ index + 1 }}</td>
                        <td>{{ action.name }}</td>
                        <td>{{ label(data.options.actions, action.action) }}</td>
                        <td>
                            <div *ngFor="let detail of details(action)">{{ detail }}</div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </ng-container>
            <ng-template #none><p class="workflow-lines-none">{{ text('LBL_WORKFLOW_EDITOR_NONE') }}</p></ng-template>
        </div>
    `,
    styles: [`
        .workflow-lines { padding: 8px 16px 16px; }
        .workflow-lines h3 { margin: 12px 0 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .workflow-lines-table { width: 100%; border-collapse: collapse; }
        .workflow-lines-table th { padding: 4px 8px; color: #6a6676; font-size: 12px; font-weight: 600; text-align: left; border-bottom: 1px solid #e3e0e8; }
        .workflow-lines-table td { padding: 6px 8px; vertical-align: top; border-bottom: 1px solid #f1eff4; }
        .workflow-lines-number { width: 1%; color: #6a6676; font-weight: 600; }
        .workflow-lines-none { color: #6a6676; }
    `]
})
export class WorkflowLinesWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    data: WfWorkflow | null = null;

    protected workflowId = '';
    protected subscriptions: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.open(this.context?.id);
        if (this.context$) {
            this.subscriptions.push(this.context$.subscribe(context => this.open(context?.id)));
        }
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
    }

    l(key: string): string {
        return this.data?.labels?.[key] || key.replace(/^.*\./, '');
    }

    text(key: string): string {
        return this.language.getFieldLabel(key, 'workflow') || key;
    }

    label(choices: WfChoice[] | undefined, value: string): string {
        return choices?.find(choice => choice.value === value)?.label ?? value ?? '';
    }

    condition(line: WfConditionLine, part: 'path' | 'field'): string {
        if (part === 'path') {
            return this.label(this.data.module.relationships, line.path) || line.pathLabel || line.path;
        }

        return line.fieldLabel || line.field;
    }

    /** The value of a line in words, as the legacy page shows it. */
    valueText(line: WfValueLine): string {
        const value = line.value;
        const info = line.info;
        const options = this.data.options;
        switch (line.valueType) {
            case 'Any_Change':
                return '';
            case 'Field':
                return this.label(info?.fields, value as string);
            case 'Date': {
                const parts = Array.isArray(value) ? value : [];
                const text = [this.label(info?.dateBases, parts[0]), this.label(options.dateOperators, parts[1])];
                if (['plus', 'minus'].includes(parts[1])) {
                    text.push(parts[2], this.label(options.dateUnits, parts[3]));
                }

                return text.filter(Boolean).join(' ');
            }
            case 'Round_Robin':
            case 'Least_Busy':
            case 'Random':
                return this.assignText(value);
            case 'SecurityGroup':
                return line.valueLabel || (value as string);
            default:
                if (Array.isArray(value)) {
                    return value.map(item => this.label(info?.options, item)).join(', ');
                }
                if (info?.input === 'select') {
                    return this.label(info.options, value);
                }
                if (info?.input === 'bool') {
                    return this.language.getAppString(value === '1' ? 'LBL_YES' : 'LBL_NO') || value;
                }
                if (info?.input === 'datetime') {
                    return (value || '').replace('T', ' ');
                }

                return line.valueLabel || (value as string);
        }
    }

    /** The parameters of an action in words. */
    details(action: WfActionLine): string[] {
        const params = action.params;
        const options = this.data.options;
        switch (action.action) {
            case 'SendEmail':
                return [
                    this.l('AOW_Actions.LBL_EMAIL_TEMPLATE') + ': ' + this.label(options.emailTemplates, params.emailTemplate),
                    ...(params.recipients ?? []).map(recipient => this.recipientText(recipient)),
                    ...(params.individualEmail ? [this.l('AOW_Actions.LBL_INDIVIDUAL_EMAILS')] : [])
                ];
            case 'CreateRecord':
            case 'ModifyRecord': {
                const target = action.action === 'CreateRecord'
                    ? this.label(options.modules, params.recordType)
                    : this.label(this.data.module.relationships, params.relType);
                const fields = params.target?.fields ?? [];
                const relationships = params.target?.relationships ?? [];

                return [
                    this.l('AOW_Actions.LBL_RECORD_TYPE') + ': ' + target,
                    ...(params.fields ?? []).map(line => this.label(fields, line.field) + ' = ' + this.valueText(line)),
                    ...(params.relationships ?? []).map(line => this.label(relationships, line.rel) + ' = ' + this.valueText(line))
                ];
            }
            case 'ComputeField': {
                const fields = this.data.module.fields;

                return [
                    ...(params.parameters ?? []).map((line, index) => `{P${index}} = ${this.label(fields, line.field)}`),
                    ...(params.relationParameters ?? []).map((line, index) => `{R${index}} = ${this.label(this.data.module.computeRelations, line.relation)} : ${line.field}`),
                    ...(params.formulas ?? []).map(line => this.label(fields, line.field) + ' = ' + line.formula)
                ];
            }
            default:
                return [];
        }
    }

    protected recipientText(recipient: WfRecipient): string {
        const options: WfOptions = this.data.options;
        let value = '';
        switch (recipient.type) {
            case 'Users':
                value = this.assignText(recipient.value);
                break;
            case 'Related Field':
                value = this.label(this.data.module.emailFields, recipient.value as string);
                break;
            case 'Specify User':
                value = recipient.valueLabel || (recipient.value as string);
                break;
            case 'Email Address':
                value = recipient.value as string;
                break;
        }

        return [
            this.label(options.emailTo, recipient.to) + ':',
            this.label(this.data.module.emailTypes, recipient.type),
            value
        ].filter(Boolean).join(' ');
    }

    protected assignText(value: string | string[]): string {
        const parts = Array.isArray(value) ? value : [];
        const options = this.data.options;

        return [
            this.label(options.assignOptions, parts[0]),
            parts[0] === 'security_group' ? this.label(options.securityGroups, parts[1]) : '',
            parts[2] ? this.label(options.roles, parts[2]) : ''
        ].filter(Boolean).join(' – ');
    }

    protected open(id: string): void {
        if (!id || id === this.workflowId) {
            return;
        }
        this.workflowId = id;
        this.subscriptions.push(this.processService.submit('workflow-editor', {action: 'view', id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.data = process?.status === 'success' ? process.data as WfWorkflow : null;
                this.cdr.detectChanges();
            },
            error: () => {
                this.data = null;
                this.cdr.detectChanges();
            }
        }));
    }
}
