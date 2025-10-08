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

import {Component, Input} from '@angular/core';
import {FieldMap} from '../../common/record/field.model';
import {Panel} from '../../common/metadata/metadata.model';
import {Record} from '../../common/record/record.model';
import {BreakpointObserver} from '@angular/cdk/layout';
import {FieldGridColumn, FieldGridRow} from '../field-grid/field-grid.model';
import {BaseFieldGridComponent} from '../field-grid/base-field-grid.component';
import {FieldLayoutConfig, FieldLayoutDataSource} from './field-layout.model';

@Component({
    selector: 'scrm-field-layout',
    templateUrl: './field-layout.component.html'
})
export class FieldLayoutComponent extends BaseFieldGridComponent {

    @Input() dataSource: FieldLayoutDataSource;
    config: FieldLayoutConfig;
    layout: Panel;
    fields: FieldMap;
    record: Record;

    baseColClass = {
        col: true,
        'form-group': true,
        'm-1': false,
        'm-0': true,
        'pl-3': true,
        'pb-2': true,
        'pr-3': true,
        'd-flex': true,
        'flex-column': true,
        'justify-content-between': true
    } as { [key:string]: boolean };

    baseRowClass = {
        'form-row': true,
        'align-items-stretch': true
    } as { [key:string]: boolean };

    constructor(protected breakpointObserver: BreakpointObserver) {
        super(breakpointObserver);
    }

    ngOnInit(): void {

        this.subscriptions.push(this.dataSource.getConfig().subscribe(config => {
            this.config = {...config};
        }));
        this.subscriptions.push(this.dataSource.getLayout().subscribe(layout => {
            this.layout = {...layout};
        }));
        this.subscriptions.push(this.dataSource.getFields().subscribe(fields => {
            this.fields = {...fields};
        }));
        this.subscriptions.push(this.dataSource.getRecord().subscribe(record => {
            this.record = {...record};
        }));

        super.ngOnInit();
    }

    buildGrid(): void {
        const grid: FieldGridRow[] = [];

        if (!this.fields || Object.keys(this.fields).length === 0) {
            this.fieldGrid = [];
            return;
        }

        this.layout.rows.forEach(layoutRow => {
            let row = {
                cols: []
            } as FieldGridRow;

            layoutRow.cols.forEach((layoutCol, colIndex) => {
                const fieldName = layoutCol.name;
                const field = this.fields[fieldName] || null;
                const fieldActions = layoutCol.fieldActions || null;
                const fieldActionsAdapter = layoutCol.fieldActionsAdapter ?? null;
                const useFullColumn = field?.useFullColumn ?? field?.definition?.useFullColumn ?? [];
                const headerColSizesConfig = field?.metadata?.headerColSizes ?? field?.definition?.headerColSizes ?? null;
                const valuesColSizesConfig = field?.metadata?.valuesColSizes ?? field?.definition?.valuesColSizes ?? null;
                const headerColumnClassConfig = field?.metadata?.headerColumnClass ?? field?.definition?.headerColumnClass ?? null;
                const valueColumnClassConfig = field?.metadata?.valueColumnClass ?? field?.definition?.valueColumnClass ?? null;

                let headerColumnClass = 'col-sm-12 col-md-12 col-lg-3';
                let valueColumnClass = 'col-sm-12 col-md-12 col-lg-9';

                if (headerColumnClassConfig) {
                    headerColumnClass = headerColumnClassConfig;
                }

                if (valueColumnClassConfig) {
                    valueColumnClass = valueColumnClassConfig;
                }

                let headerColSizes = {'xs': '12', 'sm': '12', 'md': '12', 'lg': '3', 'xl': '3'};
                let valuesColSizes = {'xs': '12', 'sm': '12', 'md': '12', 'lg': '9', 'xl': '9'};

                if (headerColSizesConfig && Object.keys(headerColSizesConfig).length) {
                    headerColSizes = headerColSizesConfig;
                }

                if (valuesColSizesConfig && Object.keys(valuesColSizesConfig).length) {
                    valuesColSizes = valuesColSizesConfig;
                }
                const useFullColumnsMaps = useFullColumn.reduce((ac,a) => ({...ac,[a]:true}),{});

                if (useFullColumn.length) {
                    headerColumnClass = Object.keys(headerColSizes).map(size => {
                        if (useFullColumnsMaps[size]) {
                            return `col-${size}-12`;
                        }

                        return `col-${size}-${headerColSizes[size]}`
                    }).join(' ');

                    valueColumnClass = Object.keys(valuesColSizes).map(size => {
                        if (useFullColumnsMaps[size]) {
                            return `col-${size}-12`;
                        }

                        return `col-${size}-${valuesColSizes[size]}`
                    }).join(' ');
                }

                if (!field) {
                    row.cols.push({} as FieldGridColumn);
                    return;
                }

                if (fieldActions && fieldActionsAdapter) {
                    field.fieldActions = {...fieldActions};
                    field.fieldActions.adapter = fieldActionsAdapter;
                }

                row.cols.push({
                    field,
                    fieldActions,
                    valueColumnClass,
                    headerColumnClass,
                    colClasses: this.config?.colClasses[colIndex] ?? ''
                } as FieldGridColumn);

                if (this.colNumber === 1 && colIndex < layoutRow.cols.length - 1) {
                    grid.push(row);

                    row = {
                        cols: []
                    } as FieldGridRow;
                }
            });

            if (row.cols.length === 1 && row.cols.length < this.colNumber) {
                row.cols[0].colClasses = row.cols[0].colClasses + ' col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12';
            } else if (row.cols.length < this.colNumber) {
                this.fillRow(row);
            }


            grid.push(row);
        });

        this.addSpecialSlots(grid);

        this.fieldGrid = grid;
    }

    get colNumber(): number {
        const size = this.sizeMap[this.currentSize];
        if (size === 1) {
            return 1;
        }
        return this.config.maxColumns;
    }
}
