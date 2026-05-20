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

import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {
    ContentAlign,
    ContentJustify,
    StatisticWidgetLayoutRow,
    TextColor,
    TextSizes,
} from '../../common/metadata/widget.metadata';
import {Record} from '../../common/record/record.model';
import {Field} from '../../common/record/field.model';
import {ScreenSizeMap} from '../../common/services/ui/resize.model';
import {ViewFieldDefinition} from '../../common/metadata/metadata.model';
import {ViewMode} from '../../common/views/view.model';
import {isTrue} from '../../common/utils/value-utils';
import {Subscription} from 'rxjs';
import {FieldFlexbox, FieldFlexboxCol, RecordFlexboxConfig} from './record-flexbox.model';
import {LabelDisplay} from '../field-grid/field-grid.model';

@Component({
    selector: 'scrm-record-flexbox',
    templateUrl: './record-flexbox.component.html',
    styles: []
})
export class RecordFlexboxComponent implements OnInit, OnDestroy {

    @Input() config: RecordFlexboxConfig;

    mode: ViewMode = 'detail';
    record: Record;
    layout: FieldFlexbox;

    maxColumns: number = 4;
    sizeMap: ScreenSizeMap = {
        handset: 1,
        tablet: 2,
        web: 3,
        wide: 4
    };

    protected subs: Subscription[] = [];

    constructor() {
    }

    ngOnInit(): void {
        if (!this.config) {
            return;
        }
        const config = this.config;

        if (config.record$) {
            this.subs.push(config.record$.subscribe(record => {
                this.record = record ?? null;
            }));
        }

        if (config.mode$) {
            this.subs.push(config.mode$.subscribe(mode => {
                this.mode = mode ?? 'detail';
            }));
        }

        if (config.layout$) {
            this.subs.push(config.layout$.subscribe(layout => {
                this.layout = layout ?? null;
            }));
        }
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
    }


    public getRowClass(): { [klass: string]: any } {
        return this.config.rowClass;
    }

    public getColClass(): { [klass: string]: any } {
        return this.config.colClass;
    }

    public getSizeClass(size: TextSizes): string {
        const sizeMap = {
            regular: 'text-regular',
            medium: 'text-medium',
            large: 'text-large',
            'x-large': 'text-x-large',
            'xx-large': 'text-xx-large',
            huge: 'text-huge'
        };

        return sizeMap[size] || 'text-regular';
    }

    public getFontWeight(bold: string | boolean): string {
        let fontWeight = 'font-weight-normal';

        if (bold && isTrue(bold)) {
            fontWeight = 'font-weight-bolder';
        }

        return fontWeight;
    }

    public getColor(color: TextColor): string {
        const sizeMap = {
            yellow: 'text-yellow',
            blue: 'text-blue',
            green: 'text-green',
            red: 'text-red',
            purple: 'text-purple',
            dark: 'text-dark',
            grey: 'text-grey'
        };

        return sizeMap[color] || '';
    }

    public getJustify(justify: ContentJustify): string {
        const justifyMap = {
            start: 'justify-content-start',
            end: 'justify-content-end',
            center: 'justify-content-center',
            between: 'justify-content-between',
            around: 'justify-content-around'
        };

        return justifyMap[justify] || '';
    }

    public getAlign(align: ContentAlign): string {
        const alignMap = {
            start: 'align-items-start',
            end: 'align-items-end',
            center: 'align-items-center',
            baseline: 'align-items-baseline',
            stretch: 'align-items-stretch'
        };

        return alignMap[align] || '';
    }

    public getLayoutRowClass(row: StatisticWidgetLayoutRow): string {
        return (row && row.class) || '';
    }

    public getClass(layoutCol: FieldFlexboxCol): string {

        if (!layoutCol) {
            return '';
        }

        const klasses = [];
        klasses.push(layoutCol.class || '');
        layoutCol.size && klasses.push(this.getSizeClass(layoutCol.size) || '');
        layoutCol.bold && klasses.push(this.getFontWeight(layoutCol.bold) || '');
        layoutCol.color && klasses.push(this.getColor(layoutCol.color) || '');

        return klasses.join(' ');
    }

    getLabelDisplay(col: FieldFlexboxCol, mode: ViewMode): LabelDisplay {
        const displayInMode = this.shouldLabelDisplayInMode(col, mode);
        if (!displayInMode){
            return 'none';
        }

        return col.labelDisplay || (this.config && this.config.labelDisplay) || 'inline';
    }

    getField(record: Record, field: ViewFieldDefinition): Field {
        if (!field || !field.name || !record || !record.fields) {
            return null;
        }

        return record.fields[field.name] ?? null;
    }

    getFieldClass(col: FieldFlexboxCol): { [key: string]: any } {
        let klasses = this.config.inputClass || {} as { [key: string]: any };

        if (col.inputClass) {
            klasses[col.inputClass] = true;
        }

        return klasses;
    }

    getLabelClass(col: FieldFlexboxCol): { [key: string]: any } {
        let klasses = this.config.labelClass || {} as { [key: string]: any };

        if (col.labelClass) {
            klasses[col.labelClass] = true
        }

        return klasses;
    }

    shouldDisplay(col: FieldFlexboxCol, field: Field, mode: ViewMode) {

        const displayInMode = this.shouldFieldDisplayInMode(col, mode);

        if (!displayInMode){
            return false;
        }

        if (!col.hideIfEmpty) {
            return true;
        }

        let hasValue = false;
        hasValue = hasValue || !!field.value;
        hasValue = hasValue || !!(field.valueList && field.valueList.length);
        hasValue = hasValue || !!(field.valueObject && Object.keys(field.valueObject).length);

        return hasValue;
    }

    shouldColDisplayInMode(col: FieldFlexboxCol, mode: ViewMode): boolean {
        return this.shouldFieldDisplayInMode(col, mode) || this.shouldLabelDisplayInMode(col, mode);
    }

    shouldFieldDisplayInMode(col: FieldFlexboxCol, mode: ViewMode): boolean {
        const modes = col?.modes ?? null;
        return !(modes && modes.length && !modes.includes(mode));
    }

    shouldLabelDisplayInMode(col: FieldFlexboxCol, mode: ViewMode): boolean {
        const modes = col?.labelModes ?? null;
        return !(modes && modes.length && !modes.includes(mode));
    }

    getDisplay(col: FieldFlexboxCol): string {
        return col.display || '';
    }
}
