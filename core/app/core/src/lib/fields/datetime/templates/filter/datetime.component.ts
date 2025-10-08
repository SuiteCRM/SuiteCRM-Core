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

import {Component, OnDestroy, OnInit, ViewChild,} from '@angular/core';
import {NgbCalendar, NgbDateStruct, NgbPopover, NgbPopoverConfig, NgbTimeStruct} from '@ng-bootstrap/ng-bootstrap';
import {isEmptyString} from '../../../../common/utils/value-utils';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {BaseDateTimeComponent} from '../../../base/datetime/base-datetime.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {DatetimeFormatter} from "../../../../services/formatters/datetime/datetime-formatter.service";
import {DateTimeModel} from "../../datetime.model";
import {PlacementArray} from "@ng-bootstrap/ng-bootstrap/util/positioning";
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

@Component({
    selector: 'scrm-datetime-filter',
    templateUrl: './datetime.component.html',
    styleUrls: []
})
export class DateTimeFilterFieldComponent extends BaseDateTimeComponent implements OnInit, OnDestroy {

    @ViewChild(NgbPopover, {static: true})
    private popover: NgbPopover;

    dateTimeModel: DateTimeModel = new DateTimeModel();

    constructor(
        protected formatter: DatetimeFormatter,
        protected typeFormatter: DataTypeFormatter,
        protected calendar: NgbCalendar,
        protected config: NgbPopoverConfig,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(formatter, typeFormatter, logic, logicDisplay);
        config.autoClose = "outside";
        config.placement = this.getPlacement();
    }

    ngOnInit(): void {
        super.ngOnInit();

        const values = (this.field && this.field.criteria && this.field.criteria.values) || [];

        let criteria = '';
        if (values.length > 0) {
            criteria = this.field.criteria.values[0];
        }

        // Note: handle NgbDatePicker default validation
        // Note: convert empty form value to null for the ngb date validator to pass it
        if (isEmptyString(criteria)) {
            this.dateTimeModel.date = this.calendar.getToday() as NgbDateStruct;
            this.dateTimeModel.time = {hour: 0, minute: 0, second: 0} as NgbTimeStruct;
            this.field.formControl.setValue(null);
        } else {
            this.dateTimeModel = DateTimeModel.internalToDateTimeStruct(this.formatter, criteria);
            if (this.dateTimeModel === null) {
                this.field.formControl.setValue(null);
                return;
            }
            this.setFormValues(this.dateTimeModel.toUserFormat(this.formatter));
        }

        // enable seconds in timepicker
        if (this.formatter.getTimeFormat().includes('ss')) {
            this.dateTimeModel.displaySeconds = true;
        }

        this.subscribeValueChanges();
    }

    ngOnDestroy(): void {
        this.unsubscribeAll();
    }

    protected setFormValues(dateTimeString: string) {
        this.field.formControl.setValue(dateTimeString);
        this.field.formControl.markAsDirty();
    }

    protected setFieldValue(newValue): void {
        this.field.value = newValue;
        this.field.criteria.operator = '=';
        this.field.criteria.values = [newValue];
    }

    onDateChange(date: NgbDateStruct | null) {
        this.dateTimeModel.date = date;
        this.setFormValues(this.dateTimeModel.toUserFormat(this.formatter));
    }

    onTimeChange(time: NgbTimeStruct | null) {
        this.dateTimeModel.time = time;
        this.setFormValues(this.dateTimeModel.toUserFormat(this.formatter));
    }

    onInputChange($event: any) {
        const dateTimeModel = DateTimeModel.toDateTimeStruct(this.formatter, $event.target.value);
        if(!dateTimeModel){
            return;
        }
        this.dateTimeModel = dateTimeModel;
    }

    getOpenButton(): ButtonInterface {
        return {
            klass: 'btn btn-sm btn-outline-secondary m-0 border-0',
            icon: 'calendar'
        };
    }

    getPlacement(): PlacementArray {
        return ['bottom-right', 'top-right', 'bottom-left', 'top-left'];
    }

}
