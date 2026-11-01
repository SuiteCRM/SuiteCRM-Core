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

import {ColumnDefinition} from '../../common/metadata/list.metadata.model';
import {Record} from '../../common/record/record.model';
import {ReportTable} from '../reports/report.model';

/** A column of a list dashlet (see DashboardHandler::buildColumns). */
export interface DashletColumn extends ColumnDefinition {
    labelText?: string;
    orderBy?: string;
}

export interface DashletOffsets {
    current: number;
    next: number;
    prev: number;
    total: number;
}

export interface DashletSort {
    orderBy: string;
    sortOrder: string;
}

export interface DashletChartItem {
    name: string;
    key: string;
    value: number;
}

/** A chart dashlet (see DashboardHandler::loadChart). */
export interface DashletChart {
    type: 'pie' | 'stacked' | 'stacked-vertical' | 'funnel' | 'bar';
    currency: string;
    unit: string;
    subtitle: string;
    empty: string;
    series: DashletChartItem[];
    groups?: { name: string, key: string, series: DashletChartItem[] }[];
    // Opportunities list filter of a click: fixed params, then the group / item keys.
    link?: { params: { [key: string]: string | string[] }, group: string, item: string };
}

/** A report dashlet (see DashboardHandler::loadReport); null when no report is chosen yet. */
export interface DashletReport extends ReportTable {
    id: string;
    name: string;
}

/** A post or reply of an Activity Stream dashlet (see DashboardHandler::describeFeed). */
export interface DashletFeedItem {
    id: string;
    author: string;
    // Message HTML (bold, italic, record links), sanitised by Angular.
    html: string;
    age: string;
    date: string;
    canDelete: boolean;
    replies?: DashletFeedItem[];
}

export interface DashletFeed {
    items: DashletFeedItem[];
    canPost: boolean;
    offsets: DashletOffsets;
}

/** A dashlet of the `dashboard` process; list, chart, report and feed dashlets come with their data. */
export interface DashletData {
    id: string;
    title: string;
    type: 'list' | 'chart' | 'report' | 'feed' | 'frame' | 'legacy';
    module?: string;
    legacyModule?: string;
    columns?: DashletColumn[];
    records?: Record[];
    offsets?: DashletOffsets;
    sort?: DashletSort;
    chart?: DashletChart;
    report?: DashletReport | null;
    feed?: DashletFeed;
    // iFrame and SuiteCRM News: the page shown (empty: not an http(s) URL).
    frame?: { url: string, height: number };
    // The dashlet has options (legacy isConfigurable).
    configurable?: boolean;
    error?: string;
    // Report and Activity Stream: come with the dashboard without their content, which they load.
    deferred?: boolean;
}

/** A dashlet the user can add (`dashboard-edit` process, catalog action). */
export interface DashletCatalogItem {
    className: string;
    title: string;
    description: string;
    module: string;
}

export interface DashletCatalog {
    categories: { key: string, label: string, dashlets: DashletCatalogItem[] }[];
    // Most dashlets a tab can hold.
    max: number;
}

export interface DashletOptionChoice {
    value: string;
    label: string;
}

/** A filter (list) or field (chart) of the legacy options form, with its legacy name. */
export interface DashletOptionField {
    label: string;
    name: string;
    kind: 'multi' | 'select' | 'text' | 'date';
    options: DashletOptionChoice[];
    value: string | string[];
}

/** A parameter of the report of a report dashlet (condition of the report marked as parameter). */
export interface DashletReportParameter {
    key: string;
    label: string;
    kind: 'select' | 'multi' | 'date' | 'text' | 'fixed';
    options: DashletOptionChoice[];
    value: string | string[];
    // Text of a parameter that cannot be changed (relative date).
    display?: string;
}

/** Charts and parameters of a report (`dashboard-edit` process, reportOptions action). */
export interface DashletReportOptions {
    charts?: { value: string[], options: DashletOptionChoice[] };
    parameters?: DashletReportParameter[];
    error?: string;
}

/**
 * Options form of a dashlet (`dashboard-edit` process, options action):
 * list, chart and report dashlets natively, the others with their legacy form.
 */
export interface DashletOptions extends DashletReportOptions {
    id: string;
    type: 'list' | 'chart' | 'report' | 'legacy' | 'none';
    title: string;
    rows?: { value: string, options: string[] };
    autoRefresh?: { value: string, options: DashletOptionChoice[] } | null;
    myItemsOnly?: boolean | null;
    columns?: { value: string[], options: DashletOptionChoice[] };
    fields?: DashletOptionField[];
    report?: string;
    reports?: DashletOptionChoice[];
    onlyCharts?: boolean;
}

export interface DashboardColumn {
    dashlets: string[];
}

export interface DashboardPage {
    title: string;
    columns: DashboardColumn[];
}

/** Data of the `dashboard` process, `load` action. */
export interface DashboardData {
    error?: boolean;
    page: number;
    pages: DashboardPage[];
    dashlets: { [id: string]: DashletData };
    locked: boolean;
}
