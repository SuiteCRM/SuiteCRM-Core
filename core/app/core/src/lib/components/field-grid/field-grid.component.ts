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

import {Component, Input, OnChanges} from '@angular/core';
import {BreakpointObserver} from '@angular/cdk/layout';
import {Field} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {FieldGridColumn, FieldGridRow} from './field-grid.model';
import {BaseFieldGridComponent} from './base-field-grid.component';

@Component({
    selector: 'scrm-field-grid',
    templateUrl: './field-grid.component.html',
    styles: []
})
export class FieldGridComponent extends BaseFieldGridComponent implements OnChanges {

    @Input() fields: Field[];
    @Input() record: Record;
    @Input() fieldMode = 'detail';

    constructor(protected breakpointObserver: BreakpointObserver) {
        super(breakpointObserver);
    }

    ngOnChanges(): void {
        this.buildGrid();
    }

    buildGrid(): void {
        const grid: FieldGridRow[] = [];

        if (!this.fields || this.fields.length === 0) {
            this.fieldGrid = [];
            return;
        }

        let col = 0;
        let row = {
            cols: []
        } as FieldGridRow;
        grid.push(row);

        this.fields.forEach(field => {

            if (col >= this.colNumber) {
                col = 0;
                row = {
                    cols: []
                } as FieldGridRow;
                grid.push(row);
            }

            row.cols.push({
                field
            } as FieldGridColumn);

            col++;
        });

        this.addSpecialSlots(grid);

        this.fieldGrid = grid;
    }
}
