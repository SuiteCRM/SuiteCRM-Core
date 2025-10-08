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

import {Component, OnDestroy, OnInit,} from '@angular/core';
import {NgbDateAdapter, NgbDateParserFormatter, NgbDateStruct, NgbInputDatepicker} from '@ng-bootstrap/ng-bootstrap';
import {isVoid, isEmptyString} from '../../../../common/utils/value-utils';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {DateParserFormatter} from '../../../base/datetime/date/date-parser-formatter.service';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {PlacementArray} from '@ng-bootstrap/ng-bootstrap/util/positioning';
import {DateAdapter} from '../../../base/datetime/date/date-adapter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {BaseDateComponent} from '../../../base/datetime/base-date.component';

@Component({
    selector: 'scrm-date-edit',
    templateUrl: './date.component.html',
    styleUrls: [],
    providers: [
        {provide: NgbDateAdapter, useClass: DateAdapter},
        {provide: NgbDateParserFormatter, useClass: DateParserFormatter}
    ]
})
export class DateEditFieldComponent extends BaseDateComponent implements OnInit, OnDestroy {

    public dateModel: NgbDateStruct;

    constructor(
        protected formatter: DateFormatter,
        protected dateAdapter: NgbDateAdapter<string>,
        protected dateParserFormatter: NgbDateParserFormatter,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(formatter, typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {

        // Note: handle NgbDatePicker default validation
        // Note: convert empty form value to null for the ngb date validator to pass it
        if (isVoid(this.field.value) || isEmptyString(this.field.value)) {
            this.field.formControl.setValue(null);
        } else {
            this.field.formControl.setValue(this.formatter.toUserFormat(this.field.value, {toFormat: this.getDateFormat()}));
        }

        const adapter = this.dateAdapter as DateAdapter;
        adapter.setUserFormat(this.getDateFormat());
        const parserFormatter = this.dateParserFormatter as DateParserFormatter;
        parserFormatter.setUserFormat(this.getDateFormat());
        this.dateModel = this.formatter.dateFormatToStruct(this.field.value, this.formatter.getInternalFormat());
        this.subscribeValueChanges();
    }

    ngOnDestroy(): void {
        this.unsubscribeAll();
    }

    setModel(value: any): void {
        this.field.value = this.formatter.toInternalFormat(value, {fromFormat: this.getDateFormat()});
        this.dateModel = this.formatter.dateFormatToStruct(value, this.getDateFormat());
    }

    getOpenButton(datepicker: NgbInputDatepicker): ButtonInterface {
        return {
            klass: 'btn btn-sm btn-outline-secondary m-0 border-0',
            // eslint-disable-next-line @typescript-eslint/explicit-function-return-type
            onClick: () => {
                datepicker.toggle();
                datepicker.navigateTo(this.dateModel);
            },
            icon: 'calendar'
        };
    }

    openDatePicker(datepicker: any): void {
        datepicker.toggle(); // Open the datepicker popup
        datepicker.navigateTo(this.dateModel);
    }

    getPlacement(): PlacementArray {
        return ['bottom-left', 'bottom-right', 'top-left', 'top-right'];
    }

}
