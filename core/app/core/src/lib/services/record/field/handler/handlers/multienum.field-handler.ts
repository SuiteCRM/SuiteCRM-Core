/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {MultiEnumField} from "../../types/multi-enum.value-object-type";
import {trimEnd, trimStart} from "lodash-es";
import {Injectable} from "@angular/core";
import {Record} from '../../../../../common/record/record.model';
import {BaseFieldHandler} from "./base.field-handler";

@Injectable({
    providedIn: 'root'
})
export class MultiEnumFieldHandler extends BaseFieldHandler<MultiEnumField> {

    initDefaultValue(field: MultiEnumField, record: Record): void {

        if (field.defaultValueInitialized) {
            return;
        }

        let defaultValue = field?.default ?? field?.definition?.default ?? null;
        if (!defaultValue) {
            field.defaultValueInitialized = true;
            return;
        }

        if (typeof defaultValue !== "string") {
            return;
        }

        defaultValue = trimStart(defaultValue, '^');
        defaultValue = trimEnd(defaultValue, '^');
        const defaultValues = defaultValue.split("^,^");
        if (!defaultValue) {
            field.defaultValueInitialized = true;
            return;
        }

        if (defaultValues && defaultValues.length) {
            field.valueList = defaultValues;
        }

        field.defaultValueInitialized = true;
    }
}
