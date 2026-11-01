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

import {FieldDefinition} from '../../common/record/field.model';

/**
 * Data of the report page (backend ReportViewerHandler and ReportDataTrait, process
 * `report-viewer`).
 */

/**
 * A column of a report table: its value in the records of a row (`record`: table alias of the
 * report module or of a related module), whether it links to its record, its total, and the
 * field definition its values are shown with (SuiteCRM 8 field components).
 */
export interface ReportColumn {
    key: string;
    label: string;
    record: string;
    module: string;
    link: boolean;
    // SUM, AVG, COUNT, or empty; type of the total.
    total: string;
    totalType: string;
    fieldDefinition: FieldDefinition;
}

/** A record of a row of the report (SuiteCRM 8 record format). */
export interface ReportRowRecord {
    id: string;
    module: string;
    attributes: { [key: string]: any };
}

export interface ReportRow {
    // By table alias.
    records: { [alias: string]: ReportRowRecord };
}

export interface ReportPager {
    start: number;
    end: number;
    total: number;
    first: number | null;
    prev: number | null;
    next: number | null;
    last: number | null;
}

export interface ReportTable {
    columns: ReportColumn[];
    rows: ReportRow[];
    // Totals of the page by column key, with the currency of the amounts (currency_id).
    totals: { [key: string]: string };
    pager: ReportPager | null;
    // Value of the grouping field of the table (grouped report).
    group: string;
}

export interface ReportGroup extends ReportTable {
    label: string;
}

export interface ReportParameterOption {
    value: string;
    label: string;
}

export type ReportParameterValue = string | string[];

export interface ReportParameter {
    key: string;
    id: string;
    label: string;
    // select (period or list), multi (several values of a list), date, text, or fixed (relative date).
    kind: 'select' | 'multi' | 'date' | 'text' | 'fixed';
    display: string;
    options: ReportParameterOption[];
    value: ReportParameterValue;
}

export interface ReportChartItem {
    name: string;
    value?: number;
    series?: { name: string; value: number }[];
}

export interface ReportChart {
    id: string;
    name: string;
    type: 'bar' | 'line' | 'pie' | 'radar' | 'rose' | 'grouped_bar' | 'stacked_bar';
    xLabel: string;
    yLabel: string;
    items: ReportChartItem[];
}

export interface ReportPage {
    id: string;
    module: string;
    graphsPerRow: number;
    parameters: ReportParameter[];
    charts: ReportChart[];
    groups: ReportGroup[];
    table: ReportTable | null;
}

/** A file of the report (export): name, type and base64 content. */
export interface ReportFile {
    name: string;
    type: string;
    content: string;
}
