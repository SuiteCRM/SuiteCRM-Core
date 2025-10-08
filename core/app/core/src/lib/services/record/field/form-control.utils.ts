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

@Injectable({
    providedIn: 'root'
})

export class FormControlUtils {

    getTrimmedInputValue(inputValue: any): any {
        // Handle the cases, when input values are not string e.g. multienums: String[]
        // Process the input, only when it's a string else return as it is
        if (typeof inputValue !== 'string') {
            return inputValue;
        }
        return inputValue.trim();
    }

    isEmptyInputValue(inputValue: any): boolean {
        // Handle the cases, when input value is an string, array, objects or any other type
        return inputValue == null
            || typeof inputValue === 'undefined'
            || inputValue === ''
            || inputValue.length === 0;
    }

    isEmptyTrimmedInputValue(inputValue: any): boolean {
        return this.isEmptyInputValue(this.getTrimmedInputValue(inputValue));
    }

    isEmptyBooleanInputValue(inputValue: any): boolean {
        return this.isEmptyInputValue(inputValue) || inputValue === 'false' || inputValue === false || inputValue === '';
    }
}
