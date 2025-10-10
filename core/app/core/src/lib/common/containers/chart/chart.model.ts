/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {Observable} from 'rxjs';

export interface DataItem {
    name: string | number | Date;
    value: string | number | Date;
    extra?: any;
    min?: number;
    max?: number;
    label?: string;
}

export type SingleSeries = Array<DataItem>;

export interface Series {
    name: string | number | Date;
    series: DataItem[];
}

export type MultiSeries = Array<Series>;


export interface NumberDataItem extends DataItem {
    name: string;
    value: number;
}

export interface NumberSeries extends Series {
    name: string;
    series: NumberDataItem[];
}

export type NumberMultiSeries = Array<NumberSeries>;


export interface SeriesResult {
    singleSeries?: SingleSeries;
    multiSeries?: MultiSeries;
}

export interface ChartOptionMap {
    [key: string]: any;

    height?: number;
    scheme?: any;
    gradient?: any;
    xAxis?: any;
    yAxis?: any;
    legend?: any;
    legendTitle?: string;
    showXAxisLabel?: any;
    showYAxisLabel?: any;
    xAxisLabel?: any;
    yAxisLabel?: any;
    xScaleMin?: number | string;
    xScaleMax?: number | string;
    xAxisTicks?: any[];
    yAxisTickFormatting?: boolean;
    xAxisTickFormatting?: boolean;
    tooltipDisabled?: boolean;
}

export interface ChartDataSource {
    options: ChartOptionMap;
    tickFormatting?: Function;

    getResults(): Observable<SeriesResult>;

    tooltipFormatting(value: any): any;
}

export interface ChartType {
    key: string;
    labelKey: string;
    type: string;
}

export interface ChartTypesMap {
    [key: string]: ChartType;
}
