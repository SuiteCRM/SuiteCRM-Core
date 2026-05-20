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

import {Inject, Injectable, LOCALE_ID} from '@angular/core';
import {UserPreferenceStore} from '../../../store/user-preference/user-preference.store';
import {formatNumber} from '@angular/common';
import {FormatOptions, Formatter} from '../formatter.model';
import {isFalse, isVoid} from '../../../common/utils/value-utils';
import {FormControlUtils} from '../../record/field/form-control.utils';

@Injectable({
    providedIn: 'root'
})
export class NumberFormatter implements Formatter {

    constructor(
        protected preferences: UserPreferenceStore,
        protected formUtils: FormControlUtils,
        @Inject(LOCALE_ID) public locale: string
    ) {
    }

    toUserFormat(value: string,  options?: FormatOptions): string {

        if (isVoid(value) || value === '') {
            return '';
        }

        if(isFalse(options?.metadata?.format ?? true)) {
            return value;
        }

        const formatted = formatNumber(Number(value), this.locale);
        return this.replaceSeparators(formatted);
    }

    toInternalFormat(value: string): string {

        if (!value) {
            return value;
        }

        const decimalSymbol = this.getDecimalsSymbol() || '.';
        const groupSymbol = this.getGroupSymbol() || ',';

        let decimalSymbolRegex = new RegExp(decimalSymbol, 'g');
        if (decimalSymbol === '.') {
            decimalSymbolRegex = new RegExp('\\.', 'g');
        }

        let groupSymbolRegex = new RegExp(groupSymbol, 'g');
        if (groupSymbol === '.') {
            groupSymbolRegex = new RegExp('\\.', 'g');
        }

        value = value ?? '';

        value = value.toString().replace(groupSymbolRegex, 'group_separator');
        value = value.toString().replace(decimalSymbolRegex, 'decimal_separator');


        value = value.toString().replace(/decimal_separator/g, '.');
        value = value.toString().replace(/group_separator/g, '');

        return value;
    }

    getFloatUserFormatPattern(): string {

        const group = this.getGroupSymbol();
        const decimals = this.getDecimalsSymbol();

        let pattern = '^-?(';
        pattern += '(\\d{1,3}(\\' + group + '\\d{3})*(\\' + decimals + '\\d+)?)|';
        pattern += '\\d*|';
        pattern += '(\\d+(\\' + decimals + '\\d+)?)|';
        pattern += '(\\d+(\\.\\d+)?)';
        pattern += ')$';
        return pattern;
    }

    getIntUserFormatPattern(): string {

        const group = this.getGroupSymbol();

        let pattern = '^-?(';
        pattern += '(\\d{1,3}(\\' + group + '\\d{3})*)|';
        pattern += '\\d*';
        pattern += ')$';
        return pattern;
    }

    getGroupSymbol(): string {

        const separator = this.preferences.getUserPreference('num_grp_sep');

        if (separator) {
            return separator;
        }


        return ',';
    }

    getDecimalsSymbol(): string {

        const separator = this.preferences.getUserPreference('dec_sep');

        if (separator) {
            return separator;
        }

        return '.';
    }

    replaceSeparators(transformed: string): string {
        if (!transformed) {
            return transformed;
        }

        transformed = transformed ?? '';
        transformed = transformed.toString().replace(/,/g, 'group_separator');
        transformed = transformed.toString().replace(/\./g, 'decimal_separator');

        const decimalSymbol = this.getDecimalsSymbol() || '.';
        const groupSymbol = this.getGroupSymbol() || ',';

        transformed = transformed.toString().replace(/decimal_separator/g, decimalSymbol);
        transformed = transformed.toString().replace(/group_separator/g, groupSymbol);

        return transformed;
    }

    replaceSeparatorsToInternalFormat(value: string): string {
        const decimalSymbol = this.getDecimalsSymbol() || '.';

        const formattedValue = this.toInternalFormat(value);

        if (decimalSymbol !== '.' && value?.toString()?.includes(decimalSymbol)) {
            value = formattedValue;
        }

        return value;
    }
    validateIntUserFormat(inputValue: any): boolean {

        const trimmedInputValue = this.formUtils.getTrimmedInputValue(inputValue);
        if (this.formUtils.isEmptyInputValue(trimmedInputValue)) {
            return false;
        }
        const regex = new RegExp(this.getIntUserFormatPattern());
        return !regex.test(trimmedInputValue);
    }

    validateFloatUserFormat(inputValue: any): boolean {

        const trimmedInputValue = this.formUtils.getTrimmedInputValue(inputValue);
        if (this.formUtils.isEmptyInputValue(trimmedInputValue)) {
            return false;
        }
        const regex = new RegExp(this.getFloatUserFormatPattern());
        return !regex.test(trimmedInputValue);

    }

}
