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
import {ActivatedRoute} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

export interface RepairToolField {
    name: string;
    label: string;
    type: 'bool' | 'text' | 'date' | 'select' | 'multiselect';
    help?: string;
    options?: { value: string; label: string }[];
    required?: boolean;
    value?: string;
}

export interface RepairToolStep {
    step: string;
    label: string;
    // A label key of the Administration strings.
    confirm?: string;
    fields?: RepairToolField[];
    params?: { [name: string]: any };
    // A step run at once (the next batch).
    auto?: boolean;
}

export interface RepairTool {
    key: string;
    title: string;
    description: string;
    confirm?: string;
    params?: RepairToolField[];
}

export interface RepairToolResult {
    status: string;
    html: string;
    sql?: string;
    exportLabel?: string;
    links?: { label: string; href: string }[];
    next?: RepairToolStep[];
    progress?: { done: number; total: number };
}

/**
 * A tools page of the administration, as a SuiteCRM 8 view: Repair (the tools of the
 * legacy page), Backups or Diagnostic (their tool), run on the server as the legacy pages run
 * them, their output shown under the tool, with their next steps (a confirmation, the database
 * differences of Quick Repair to execute or export, the batches of Remove XSS run one after the
 * other, the backup once its settings checked, the diagnostic file to download or delete).
 *
 * The page is the `adminToolsPage` data of the route (admin panel link). Data from the
 * `admin-tools` process (AdminToolsProcessHandler).
 */
@Component({
    selector: 'scrm-admin-tools',
    templateUrl: './admin-tools.component.html',
    styleUrls: []
})
export class AdminToolsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'admin-tools';

    page = '';
    title = '';
    intro = '';
    tools: RepairTool[] = [];
    params: { [tool: string]: { [name: string]: any } } = {};
    results: { [tool: string]: RepairToolResult } = {};
    running = '';
    loading = false;

    protected sub: Subscription;
    // The runs of the tools, stopped when the page is left (as the batches of the legacy pages).
    protected runs = new Subscription();

    constructor(
        protected route: ActivatedRoute,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.page = this.route.snapshot.data?.adminToolsPage ?? 'repair';
        this.loading = true;
        this.sub = this.processService.submit(AdminToolsComponent.PROCESS, {page: this.page, action: 'tools'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                this.title = process?.data?.title ?? '';
                this.intro = process?.data?.intro ?? '';
                this.tools = process?.data?.tools ?? [];
                this.tools.forEach(tool => this.params[tool.key] = this.defaults(tool.params));
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
        this.runs.unsubscribe();
    }

    appLabel(key: string): string {
        return this.language.getAppString(key) || key;
    }

    /**
     * Runs a tool (after its confirmation, as the legacy links).
     */
    run(tool: RepairTool): void {
        this.confirm(tool.confirm, () => this.submit(tool, 'run', this.params[tool.key]));
    }

    /**
     * Runs a next step of a tool, with its fields and its own parameters.
     */
    runStep(tool: RepairTool, step: RepairToolStep): void {
        const params = {...this.params[tool.key], ...(step.params ?? {}), ...(this.params[tool.key + ':' + step.step] ?? {})};
        this.confirm(step.confirm, () => this.submit(tool, step.step, params));
    }

    stepParams(tool: RepairTool, step: RepairToolStep): { [name: string]: any } {
        const key = tool.key + ':' + step.step;
        if (!this.params[key]) {
            this.params[key] = this.defaults(step.fields);
        }

        return this.params[key];
    }

    /**
     * The database differences of Quick Repair, as the SQL file of the legacy Export button.
     */
    exportSql(result: RepairToolResult): void {
        const url = URL.createObjectURL(new Blob([result.sql ?? ''], {type: 'text/sql'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'repairSugarDB.sql';
        document.body.appendChild(link);
        link.click();
        link.remove();
        // Once the download started.
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    trackTool(index: number, tool: RepairTool): string {
        return tool.key;
    }

    protected defaults(fields: RepairToolField[] = []): { [name: string]: any } {
        const values = {};
        fields.forEach(field => values[field.name] = field.type === 'multiselect' ? [] : field.type === 'bool' ? false
            : (field.value ?? field.options?.[0]?.value ?? ''));

        return values;
    }

    protected confirm(key: string, proceed: () => void): void {
        if (!key) {
            proceed();
            return;
        }
        this.confirmation.showModal([key], proceed, () => {}, {}, {module: 'administration'});
    }

    protected submit(tool: RepairTool, step: string, params: { [name: string]: any }): void {
        this.running = tool.key;
        this.cdr.detectChanges();

        this.runs.add(this.processService.submit(AdminToolsComponent.PROCESS, {page: this.page, action: 'run', tool: tool.key, step, params})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    const result = process?.data as RepairToolResult;
                    this.running = '';
                    if (result) {
                        this.results[tool.key] = result;
                    }
                    if (process?.status !== 'success') {
                        this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                    }
                    this.cdr.detectChanges();

                    // The next batch of a tool runs at once.
                    const auto = result?.next?.find(next => next.auto);
                    if (process?.status === 'success' && auto) {
                        this.submit(tool, auto.step, {...params, ...(auto.params ?? {})});
                    }
                },
                error: () => {
                    this.running = '';
                    this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                    this.cdr.detectChanges();
                }
            }));
    }
}
