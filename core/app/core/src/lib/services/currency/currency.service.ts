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
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {Field} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {divide, multiply, round} from 'mathjs';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';

@Injectable({
    providedIn: 'root'
})
export class CurrencyService {

    constructor(
        protected config: SystemConfigStore,
        protected preferences: UserPreferenceStore
    ) {
    }

    getFieldCurrencyValue(field: Field, record: Record): string {
        const isBase = this.isBase(field);
        const currencyId = this.getCurrencyId(record);

        if (!isBase && currencyId !== null) {
            return field.value;
        }

        const value = parseFloat(field.value);

        if (!isFinite(value)) {
            return field.value;
        }

        let currency = this.getUserCurrency();

        if (!currency?.id) {
            currency = this.getBaseCurrency();
        }

        return this.baseToCurrency(currency.id, value).toString();
    }

    baseToCurrency(currencyId: string, value: number): number {

        const conversionRate = this.getConversionRate(currencyId);
        if (!isFinite(conversionRate)) {
            return value;
        }

        return this.round(multiply(value, conversionRate));
    }

    currencyToBase(currencyId: string, value: number): number {

        const conversionRate = this.getConversionRate(currencyId);
        if (!isFinite(conversionRate)) {
            return value;
        }

        return this.round(divide(value, conversionRate));
    }

    round(value: number): number {
        const precision = this.getPrecision();
        return round(value, precision);
    }

    getCurrencyId(record: Record): string {
        return record?.fields?.currency_id?.value ?? null;
    }

    isBase(field: Field): boolean {
        return field?.metadata?.isBaseCurrency ?? false;
    }

    getCurrency(id: string): any {
        const currencies = this.config.getConfigValue('currencies');

        return currencies[id] ?? [];
    }

    getBaseCurrency(): any {
        return this.config.getConfigValue('currency');
    }

    getUserCurrency(): any {
        return this.preferences.getUserPreference('currency');
    }

    getPrecision(): number {

        const userPrecision = parseInt(this.preferences.getUserPreference('default_currency_significant_digits'));

        if (isFinite(userPrecision)) {
            return userPrecision;
        }

        const systemPrecision = parseInt(this.config.getConfigValue('default_currency_significant_digits'));

        if (isFinite(systemPrecision)) {
            return systemPrecision;
        }

        return 0;
    }

    getConversionRate(id: string): number {
        const currency = this.getCurrency(id);
        return parseFloat(currency['conversion_rate']);
    }

    getCode(id: string): string {
        return this.getCurrency(id).iso4217;
    }

    getSymbol(id: string): string {
        return this.getCurrency(id).symbol;
    }
}
