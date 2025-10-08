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
import {NgbDateAdapter, NgbDateParserFormatter, NgbDateStruct} from '@ng-bootstrap/ng-bootstrap';
import {isVoid, isEmptyString} from '../../../../common/utils/value-utils';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {DateParserFormatter} from '../../../base/datetime/date/date-parser-formatter.service';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {DateAdapter} from '../../../base/datetime/date/date-adapter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import { DateEditFieldComponent } from '../edit/date.component'


@Component({
    selector: 'scrm-date-filter',
    templateUrl: './date.component.html',
    styleUrls: [],
    providers: [
        {provide: NgbDateAdapter, useClass: DateAdapter},
        {provide: NgbDateParserFormatter, useClass: DateParserFormatter}
    ]
})
export class DateFilterFieldComponent extends DateEditFieldComponent implements OnInit, OnDestroy {

    public dateModel: NgbDateStruct;

    constructor(
        protected formatter: DateFormatter,
        protected dateAdapter: NgbDateAdapter<string>,
        protected dateParserFormatter: NgbDateParserFormatter,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(formatter,
          dateAdapter,
          dateParserFormatter,
          typeFormatter,
          logic,
          logicDisplay
        )
    }

    ngOnInit(): void {
        let current = null;
        if (this.field.criteria.values && this.field.criteria.values.length > 0) {
            current = this.field.criteria.values[0];
        }

        if (!isVoid(current) && !isEmptyString(current)) {
            current = current.trim();
        }

        this.field.value = current ?? '';

        super.ngOnInit();
    }

    protected setFieldValue(newValue): void {
        super.setFieldValue(newValue);
        this.field.criteria.operator = '=';
        this.field.criteria.values = [newValue];
    }

}
