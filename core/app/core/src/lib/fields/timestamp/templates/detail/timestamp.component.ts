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

import {Component} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

/**
 * A Unix timestamp (seconds) shown as the date and time in the user format, as the legacy
 * views show it (displayDateFromTs), instead of the number.
 *
 * Display type `timestamp` of `long` and `int` fields, list and detail modes.
 */
@Component({
    selector: 'scrm-timestamp-detail',
    templateUrl: './timestamp.component.html',
    styleUrls: []
})
export class TimestampDetailFieldComponent extends BaseFieldComponent {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected datetimeFormatter: DatetimeFormatter
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    /** The date and time of the timestamp for the user, or nothing without timestamp. */
    getDatetime(): string {
        const seconds = Number(this.field?.value);
        if (!this.field?.value || !Number.isFinite(seconds)) {
            return '';
        }

        // The internal (GMT) format, yyyy-MM-dd HH:mm:ss, that the formatter turns into the user one.
        const internal = new Date(seconds * 1000).toISOString().slice(0, 19).replace('T', ' ');

        return this.datetimeFormatter.toUserFormat(internal);
    }
}
