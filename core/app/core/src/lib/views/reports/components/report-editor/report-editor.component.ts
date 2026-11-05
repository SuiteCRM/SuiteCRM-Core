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
import {CdkDragDrop, DragDropModule, moveItemInArray} from '@angular/cdk/drag-drop';
import {combineLatest, Observable, of, Subscription} from 'rxjs';
import {catchError, map, take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {SelectModalService} from '../../../../services/modals/select-modal.service';
import {
    EditorChartLine,
    EditorConditionLine,
    EditorConditionValue,
    EditorFieldInfo,
    EditorFieldLine,
    EditorModule,
    EditorReport,
    EditorTreeField,
    EditorTreeNode
} from '../../report-editor.model';

/**
 * The report editor of SuiteCRM 8 (create, edit and duplicate a report), as the
 * legacy one (AOR_Reports EditView): the details of the report, the module tree of the report
 * module (its relationships; a click on a module lists its fields, a click on a field adds it
 * to the fields or the conditions), and the fields, conditions and charts of the report.
 *
 * - Fields: display, link, label, function, sort, group, format (dates), total, the main group
 *   of the report; their order is changed by drag and drop.
 * - Conditions: logic with the previous condition, operator, type and value (as offered for
 *   the field), parameter; parentheses; their order is changed by drag and drop.
 * - Charts: title, type, X and Y axis fields.
 *
 * Process `report-editor` (backend ReportEditorHandler): the report is saved as the legacy form
 * saves it. Routes reports/edit (new report; ?record=<id>&isDuplicate=true to duplicate one)
 * and reports/edit/<id>.
 */
@Component({
    selector: 'scrm-report-editor',
    standalone: true,
    imports: [CommonModule, FormsModule, DragDropModule],
    templateUrl: './report-editor.component.html'
})
export class ReportEditorComponent implements OnInit, OnDestroy {

    report: EditorReport | null = null;
    fields: EditorFieldLine[] = [];
    conditions: EditorConditionLine[] = [];
    charts: EditorChartLine[] = [];
    tab: 'fields' | 'conditions' | 'charts' = 'fields';
    tabs: { key: 'fields' | 'conditions' | 'charts'; label: string }[] = [];
    tree: EditorTreeNode | null = null;
    selectedNode: EditorTreeNode | null = null;
    treeFields: EditorTreeField[] = [];
    loading = false;
    saving = false;
    error = false;

    // The report edited ('' for a new report or a copy), and the report copied or edited.
    protected id = '';
    protected sourceId = '';
    protected nextKey = 1;
    protected treeFieldCache = new Map<string, EditorTreeField[]>();
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

    get mainGroup(): number | null {
        return this.fields.find(line => line.mainGroup)?.key ?? null;
    }

    set mainGroup(key: number | null) {
        this.fields.forEach(line => line.mainGroup = line.key === key);
    }

    get chartFields(): { key: number; label: string }[] {
        return this.fields.map(line => ({key: line.key, label: line.pathLabel + ' - ' + line.label}));
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

    /** A label of the legacy editor ("<module>.<label key>", see ReportEditorHandler::getLabels()). */
    l(key: string): string {
        return this.report?.labels?.[key] || key.replace(/^.*\./, '');
    }

    /** A label of the page (AOR_Reports strings). */
    text(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }

    app(key: string): string {
        return this.language.getAppString(key) || key;
    }

    trackLine(index: number, line: { key: number }): number {
        return line.key;
    }

    // Module tree.

    toggle(node: EditorTreeNode): void {
        node.open = !node.open;
        if (node.open && node.children === null) {
            this.loadChildren(node);
        }
    }

    select(node: EditorTreeNode): void {
        this.selectedNode = node;
        const cached = this.treeFieldCache.get(node.path);
        if (cached) {
            this.treeFields = cached;
            return;
        }
        this.treeFields = [];
        this.call({action: 'fields', reportModule: this.report.reportModule, path: node.path}).subscribe(data => {
            this.treeFields = data?.fields ?? [];
            this.treeFieldCache.set(node.path, this.treeFields);
            this.cdr.detectChanges();
        });
    }

    /** A field of the module tree added to the fields, or to the conditions on that tab. */
    add(field: EditorTreeField): void {
        if (this.tab === 'conditions') {
            this.addCondition(field);
            return;
        }
        this.tab = 'fields';
        this.fields.push({
            key: this.nextKey++,
            id: '',
            path: this.selectedNode.path,
            pathLabel: this.selectedNode.pathLabel,
            field: field.name,
            fieldLabel: field.label,
            type: field.type,
            display: true,
            link: false,
            label: field.label,
            function: '',
            sort: '',
            group: false,
            format: '',
            total: '',
            mainGroup: false
        });
    }

    // Fields.

    isDate(line: EditorFieldLine): boolean {
        return ['date', 'datetime', 'datetimecombo'].includes(line.type);
    }

    removeField(line: EditorFieldLine): void {
        this.fields = this.fields.filter(item => item !== line);
        this.charts.forEach(chart => {
            chart.x = chart.x === line.key ? null : chart.x;
            chart.y = chart.y === line.key ? null : chart.y;
        });
    }

    dropField(event: CdkDragDrop<EditorFieldLine[]>): void {
        moveItemInArray(this.fields, event.previousIndex, event.currentIndex);
    }

    // Conditions.

    addParenthesis(): void {
        this.conditions.push(
            {key: this.nextKey++, id: '', logic: 'AND', parenthesis: 'START'},
            {key: this.nextKey++, id: '', logic: '', parenthesis: 'END'}
        );
    }

    /** A condition, or a parenthesis with its closing one. */
    removeCondition(line: EditorConditionLine): void {
        const index = this.conditions.indexOf(line);
        const end = line.parenthesis === 'START' ? this.closingIndex(index) : -1;
        this.conditions = this.conditions.filter((item, position) => position !== index && position !== end);
    }

    dropCondition(event: CdkDragDrop<EditorConditionLine[]>): void {
        moveItemInArray(this.conditions, event.previousIndex, event.currentIndex);
    }

    /** Depth of a condition in the parentheses (its indent). */
    depth(index: number): number {
        let depth = 0;
        for (let position = 0; position <= index; position++) {
            const parenthesis = this.conditions[position].parenthesis;
            if (parenthesis === 'END') {
                depth--;
            }
            if (parenthesis === 'START' && position < index) {
                depth++;
            }
        }

        return Math.max(0, depth);
    }

    /** The logic with the previous line, except for the first one, after "(" and for ")". */
    hasLogic(index: number): boolean {
        return index > 0 && this.conditions[index - 1].parenthesis !== 'START' && this.conditions[index].parenthesis !== 'END';
    }

    changeValueType(line: EditorConditionLine): void {
        line.value = this.defaultValue(line.info, line.valueType);
        line.valueLabel = '';
    }

    isPlusMinus(line: EditorConditionLine): boolean {
        return Array.isArray(line.value) && ['plus', 'minus'].includes(line.value[1]);
    }

    selectRecord(line: EditorConditionLine): void {
        this.selectModal.showSelectModal(line.info.module, (record: Record) => {
            if (record?.id) {
                line.value = record.id;
                line.valueLabel = record.attributes?.name || record.attributes?.full_name || record.id;
                this.cdr.detectChanges();
            }
        });
    }

    // Charts.

    addChart(): void {
        const first = this.fields[0]?.key ?? null;
        this.charts.push({key: this.nextKey++, id: '', title: '', type: this.report.options.chartTypes[0]?.value ?? 'bar', x: first, y: first});
    }

    removeChart(chart: EditorChartLine): void {
        this.charts = this.charts.filter(item => item !== chart);
    }

    // Details.

    selectUser(): void {
        this.selectModal.showSelectModal('users', (record: Record) => {
            if (record?.id) {
                this.report.assignedUserId = record.id;
                this.report.assignedUserName = record.attributes?.full_name || record.attributes?.name || record.attributes?.user_name || record.id;
                this.cdr.detectChanges();
            }
        });
    }

    /**
     * Another report module: the fields, conditions and charts of the report are removed (they
     * are on the modules of the previous one), after a confirmation when there are some.
     */
    changeModule(module: string): void {
        const previous = this.report.reportModule;
        const apply = (): void => {
            this.report.reportModule = module;
            this.fields = [];
            this.conditions = [];
            this.charts = [];
            this.loadModule(module);
        };

        if (!this.fields.length && !this.conditions.length && !this.charts.length) {
            apply();
            return;
        }
        this.report.reportModule = module;
        this.confirmation.showModal(['LBL_REPORT_EDITOR_CHANGE_MODULE'], apply, () => {
            this.report.reportModule = previous;
            this.cdr.detectChanges();
        });
    }

    // Save.

    save(): void {
        if (!this.report || this.saving) {
            return;
        }
        if (!this.report.name.trim() || !this.report.reportModule) {
            this.messages.addWarningMessage(this.text('LBL_REPORT_EDITOR_REQUIRED'));
            return;
        }
        const index = new Map(this.fields.map((line, position) => [line.key, position]));
        if (this.charts.some(chart => !index.has(chart.x) || !index.has(chart.y))) {
            this.messages.addWarningMessage(this.text('LBL_REPORT_EDITOR_CHART_AXES'));
            return;
        }

        const report = {
            name: this.report.name,
            assignedUserId: this.report.assignedUserId,
            reportModule: this.report.reportModule,
            graphsPerRow: this.report.graphsPerRow,
            description: this.report.description,
            fields: this.fields.map(line => ({
                id: line.id, path: line.path, field: line.field, display: line.display, link: line.link, label: line.label,
                function: line.function, sort: line.sort, group: line.group, format: line.format, total: line.total,
                mainGroup: line.mainGroup
            })),
            conditions: this.conditions.map(line => line.parenthesis ? {id: line.id, logic: line.logic, parenthesis: line.parenthesis} : {
                id: line.id, logic: line.logic, parenthesis: '', path: line.path, field: line.field, operator: line.operator,
                valueType: line.valueType, value: line.value, parameter: line.parameter
            }),
            charts: this.charts.map(chart => ({id: chart.id, title: chart.title, type: chart.type, x: index.get(chart.x), y: index.get(chart.y)}))
        };

        this.saving = true;
        this.call({action: 'save', id: this.id, report}).subscribe(data => {
            this.saving = false;
            if (!data?.id) {
                this.messages.addDangerMessage(this.text('LBL_REPORT_EDITOR_SAVE_ERROR'));
                this.cdr.detectChanges();
                return;
            }
            this.router.navigate(['/reports/record', data.id]).then();
        });
    }

    cancel(): void {
        this.router.navigate(this.sourceId ? ['/reports/record', this.sourceId] : ['/reports']).then();
    }

    protected load(record: string, duplicate: boolean): void {
        this.loading = true;
        this.error = false;
        this.report = null;
        this.call({action: 'load', id: record, duplicate}).subscribe((data: EditorReport) => {
            this.loading = false;
            if (!data?.options) {
                this.error = true;
                this.cdr.detectChanges();
                return;
            }

            this.report = data;
            this.tabs = [
                {key: 'fields', label: this.l('AOR_Reports.LBL_AOR_FIELDS_SUBPANEL_TITLE')},
                {key: 'conditions', label: this.l('AOR_Reports.LBL_AOR_CONDITIONS_SUBPANEL_TITLE')},
                {key: 'charts', label: this.l('AOR_Reports.LBL_AOR_CHARTS_SUBPANEL_TITLE')}
            ];
            this.fields = data.fields.map(line => ({...line, key: this.nextKey++}));
            this.conditions = data.conditions.map(line => ({...line, key: this.nextKey++}));
            // The axes of a chart are field positions: they follow their field line.
            this.charts = data.charts.map(chart => ({
                ...chart, key: this.nextKey++, x: this.fields[chart.x]?.key ?? null, y: this.fields[chart.y]?.key ?? null
            }));
            this.initTree();
            this.cdr.detectChanges();
        });
    }

    protected loadModule(module: string): void {
        this.tree = null;
        this.selectedNode = null;
        this.treeFields = [];
        this.treeFieldCache.clear();
        this.call({action: 'module', reportModule: module}).subscribe((data: EditorModule) => {
            if (data?.name) {
                this.report.module = data;
                this.initTree();
            }
            this.cdr.detectChanges();
        });
    }

    /** The module tree from the report module, open, with its fields listed. */
    protected initTree(): void {
        this.treeFieldCache.clear();
        if (!this.report.reportModule) {
            this.tree = null;
            return;
        }
        const label = this.report.module.label;
        this.tree = {link: '', label, module: this.report.reportModule, path: '', pathLabel: label, children: null, open: true, loading: false};
        this.loadChildren(this.tree);
        this.select(this.tree);
    }

    protected loadChildren(node: EditorTreeNode): void {
        node.loading = true;
        this.call({action: 'tree', reportModule: this.report.reportModule, path: node.path}).subscribe(data => {
            node.loading = false;
            node.children = (data?.nodes ?? []).map((child: EditorTreeNode) => ({
                ...child, pathLabel: node.pathLabel + ' : ' + child.label, children: null, open: false, loading: false
            }));
            this.cdr.detectChanges();
        });
    }

    protected addCondition(field: EditorTreeField): void {
        const node = this.selectedNode;
        this.call({action: 'field', reportModule: this.report.reportModule, path: node.path, field: field.name})
            .subscribe((info: EditorFieldInfo) => {
                if (!info?.operators) {
                    return;
                }
                this.conditions.push({
                    key: this.nextKey++,
                    id: '',
                    logic: 'AND',
                    parenthesis: '',
                    path: node.path,
                    pathLabel: node.pathLabel,
                    field: field.name,
                    fieldLabel: field.label,
                    operator: info.operators[0]?.value ?? '',
                    valueType: 'Value',
                    value: this.defaultValue(info, 'Value'),
                    valueLabel: '',
                    parameter: false,
                    info
                });
                this.cdr.detectChanges();
            });
    }

    /** The value of a new condition, or of a condition of another type, as the legacy inputs start. */
    protected defaultValue(info: EditorFieldInfo, valueType: string): EditorConditionValue {
        switch (valueType) {
            case 'Date':
                return ['now', 'now', '', ''];
            case 'Multi':
                return [];
            case 'Period':
                return this.report.options.periods[0]?.value ?? '';
            case 'Value':
                if (info.input === 'multi') {
                    return [];
                }
                if (info.input === 'bool') {
                    return '0';
                }
                if (info.input === 'select') {
                    return info.options[0]?.value ?? '';
                }

                return '';
            default:
                return '';
        }
    }

    /** Index of the ")" closing the "(" at an index, -1 when there is none. */
    protected closingIndex(start: number): number {
        let depth = 0;
        for (let position = start; position < this.conditions.length; position++) {
            const parenthesis = this.conditions[position].parenthesis;
            depth += parenthesis === 'START' ? 1 : parenthesis === 'END' ? -1 : 0;
            if (depth === 0) {
                return position;
            }
        }

        return -1;
    }

    protected call(options: { [key: string]: any }): Observable<any> {
        return this.processService.submit('report-editor', options).pipe(
            take(1),
            map((process: Process) => process?.status === 'success' ? process.data : null),
            catchError(() => of(null))
        );
    }
}
