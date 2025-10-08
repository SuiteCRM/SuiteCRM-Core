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
import {formatCurrency, formatNumber} from '@angular/common';
import {NumberFormatter} from '../number/number-formatter.service';
import {FormatOptions, Formatter} from '../formatter.model';
import {SystemConfigStore} from "../../../store/system-config/system-config.store";
import {isVoid} from "../../../common/utils/value-utils";

export interface CurrencyFormat {
    iso4217: string;
    name: string;
    symbol: string;
}

@Injectable({
    providedIn: 'root'
})
export class CurrencyFormatter implements Formatter {

    constructor(
        protected preferences: UserPreferenceStore,
        protected configs: SystemConfigStore,
        protected numberFormatter: NumberFormatter,
        @Inject(LOCALE_ID) public locale: string
    ) {
    }

    toUserFormat(value: string, options: FormatOptions = null): string {

        if (isVoid(value) || value === '') {
            return '';
        }

        const symbol = (options && options.symbol) || this.getSymbol();
        const code = (options && options.code) || this.getCode();
        const defaultGroup = this.configs.getConfigValue('default_number_grouping_seperator');
        let digits = null;
        if (options && options.digits !== null && isFinite(options.digits)) {
            digits = options.digits;
        }

        const digitsInfo = this.getDigitsInfo(digits);
        let formatted: string;

        if (options?.fromFormat === 'system' && value.includes(defaultGroup)){
            value = value.replace(defaultGroup, '');
        } else {
            value = this.replaceSeparatorsToInternalFormat(value);
        }

        if (options && options.mode === 'edit') {
            formatted = formatNumber(Number(value), this.locale, digitsInfo);
            return this.replaceSeparators(formatted);
        }

        formatted = formatCurrency(Number(value), this.locale, symbol, code, digitsInfo);
        return this.replaceSeparators(formatted);
    }

    toInternalFormat(value: string): string {
        if (!value) {
            return '';
        }

        const transformed = value.replace(this.getSymbol(), '');
        return this.numberFormatter.toInternalFormat(transformed);
    }

    getCurrencyFormat(): CurrencyFormat {
        const currencyFormat = this.preferences.getUserPreference('currency');

        if (currencyFormat) {
            return currencyFormat;
        }

        return this.getDefaultFormat();
    }

    getDefaultFormat(): CurrencyFormat {

        return {
            iso4217: 'USD',
            name: 'US Dollars',
            symbol: '$'
        };
    }

    getCode(): string {
        return this.getCurrencyFormat().iso4217;
    }

    getSymbol(): string {
        return this.getCurrencyFormat().symbol;
    }

    getDigits(): number {
        const digits = this.preferences.getUserPreference('default_currency_significant_digits');

        if (digits) {
            return digits;
        }

        return 2;
    }

    getDigitsInfo(definedDigits?: number): string {
        let digitInfo = '1.2-2';
        let digits = this.getDigits();

        if (definedDigits !== null && isFinite(definedDigits)) {
            digits = definedDigits;
        }

        if (digits !== null && isFinite(digits)) {
            if (digits < 1) {
                digitInfo = '1.0-0';
            } else {
                digitInfo = `1.${digits}-${digits}`;
            }
        }

        return digitInfo;
    }

    replaceSeparators(transformed: string): string {
        return this.numberFormatter.replaceSeparators(transformed);
    }

    replaceSeparatorsToInternalFormat(value: string): string {
        return this.numberFormatter.replaceSeparatorsToInternalFormat(value);
    }
}
