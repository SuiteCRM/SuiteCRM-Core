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

/**
 * Data of the report editor (backend ReportEditorHandler, process `report-editor`).
 */

export interface EditorChoice {
    value: string;
    label: string;
}

/** What a condition on a field offers. */
export interface EditorFieldInfo {
    type: string;
    // Input of a value of the "Value" type.
    input: 'text' | 'select' | 'multi' | 'bool' | 'date' | 'datetime' | 'number' | 'relate';
    // Module of a relate field (record picker).
    module: string;
    operators: EditorChoice[];
    valueTypes: EditorChoice[];
    options: EditorChoice[];
}

export interface EditorFieldLine {
    // Key of the line in the page (lines keep it when they move).
    key: number;
    id: string;
    path: string;
    pathLabel: string;
    field: string;
    fieldLabel: string;
    type: string;
    display: boolean;
    link: boolean;
    label: string;
    function: string;
    sort: string;
    group: boolean;
    format: string;
    total: string;
    mainGroup: boolean;
}

export type EditorConditionValue = string | string[];

export interface EditorConditionLine {
    key: number;
    id: string;
    logic: string;
    // '' for a condition, START and END for a parenthesis.
    parenthesis: '' | 'START' | 'END';
    path?: string;
    pathLabel?: string;
    field?: string;
    fieldLabel?: string;
    operator?: string;
    valueType?: string;
    value?: EditorConditionValue;
    // Name of the record of a relate field.
    valueLabel?: string;
    parameter?: boolean;
    info?: EditorFieldInfo;
}

export interface EditorChartLine {
    key: number;
    id: string;
    title: string;
    type: string;
    // Keys of the field lines of the axes (index of the field line when saved).
    x: number | null;
    y: number | null;
}

export interface EditorModule {
    name: string;
    label: string;
    fields: EditorChoice[];
    dateBases: EditorChoice[];
}

export interface EditorOptions {
    modules: EditorChoice[];
    functions: EditorChoice[];
    sorts: EditorChoice[];
    formats: EditorChoice[];
    totals: EditorChoice[];
    chartTypes: EditorChoice[];
    periods: EditorChoice[];
    dateOperators: EditorChoice[];
    dateUnits: EditorChoice[];
}

export interface EditorReport {
    id: string;
    name: string;
    assignedUserId: string;
    assignedUserName: string;
    reportModule: string;
    graphsPerRow: number;
    description: string;
    fields: EditorFieldLine[];
    conditions: EditorConditionLine[];
    charts: { id: string; title: string; type: string; x: number; y: number }[];
    options: EditorOptions;
    module: EditorModule;
    labels: { [key: string]: string };
}

/** A module of the module tree: the report module, or a relationship of its parent. */
export interface EditorTreeNode {
    link: string;
    label: string;
    module: string;
    // Link names from the report module, separated by ":" ('' for the report module).
    path: string;
    // Labels from the report module, as the legacy editor shows the module of a line.
    pathLabel: string;
    children: EditorTreeNode[] | null;
    open: boolean;
    loading: boolean;
}

export interface EditorTreeField {
    name: string;
    label: string;
    type: string;
}
