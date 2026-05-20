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

import {Injectable} from '@angular/core';
import {DataTypeUnitConverter} from '../../../../unit-converters/data-type.unit-converter.service';
import {SeriesVisitor} from '../series-traverser.service';
import {DataItem} from '../../../../../common/containers/chart/chart.model';
import {ObjectMap} from '../../../../../common/types/object-map';
import {DataTypeFormatter} from '../../../../formatters/data-type.formatter.service';

@Injectable({
    providedIn: 'root'
})
export class DataTypeSeriesMapper implements SeriesVisitor {


    constructor(protected converter: DataTypeUnitConverter, protected formatter: DataTypeFormatter) {
    }

    visit(item: DataItem, options?: ObjectMap): void {

        const dataType = options?.dataType ?? null;
        const direction = options?.direction ?? 'user-unit';

        if (!dataType) {
            return;
        }

        // Convert from user format to internal format before parsing to handle custom separators
        const internalValue = this.formatter.toInternalFormat(dataType, item.value.toString());
        const numberValue = parseFloat(internalValue.toString());

        if (!isFinite(numberValue)) {
            return;
        }

        if (direction === 'user-unit') {
            item.value = this.converter.toUserFormat(dataType, numberValue.toString());
            return;
        }

        item.value = this.converter.toInternalFormat(dataType, numberValue.toString());

    }


}
