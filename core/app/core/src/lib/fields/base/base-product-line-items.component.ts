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


import {Component, inject, OnInit} from '@angular/core';
import {BaseFieldComponent} from './base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../store/language/language.store';
import {CurrencyService} from '../../services/currency/currency.service';
import {CurrencyFormatter} from '../../services/formatters/currency/currency-formatter.service';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';
import {
    isServiceLine,
    isValidLine,
    PRODUCT_LINE_DISCOUNT_AMOUNT,
    ProductLineItem,
    ProductLineItemsValue,
    toLineItemNumber
} from '../product-line-items/product-line-items.model';

/**
 * Line items of a quote, invoice or contract (legacy `line_items` function field,
 * see the backend ProductLineItemsFieldDefinitionMapper): the value, the labels
 * of the record module and the amounts in the record currency.
 */
@Component({template: ''})
export class BaseProductLineItemsComponent extends BaseFieldComponent implements OnInit {

    value: ProductLineItemsValue = null;

    protected language = inject(LanguageStore);
    protected currencyService = inject(CurrencyService);
    protected currencyFormatter = inject(CurrencyFormatter);
    protected numberFormatter = inject(NumberFormatter);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        const value = this.field?.valueObject ?? this.record?.attributes?.[this.field?.name] ?? null;
        if (value && Array.isArray(value.groups)) {
            this.value = JSON.parse(JSON.stringify(value));
            return;
        }

        this.value = {enableGroups: true, totalTax: false, precision: this.currencyService.getPrecision(), groups: []};
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.record?.module ?? '') || this.language.getAppString(key) || key;
    }

    money(value: number): string {
        const currencyId = this.getRecordCurrencyId();
        return this.currencyFormatter.toUserFormat(String(toLineItemNumber(value)), {
            symbol: this.currencyService.getSymbol(currencyId),
            code: this.currencyService.getCode(currencyId),
            digits: this.value?.precision
        });
    }

    quantity(value: number): string {
        return this.numberFormatter.toUserFormat(String(toLineItemNumber(value)));
    }

    discount(line: ProductLineItem): string {
        if (!toLineItemNumber(line.product_discount)) {
            return '';
        }

        if (line.discount === PRODUCT_LINE_DISCOUNT_AMOUNT) {
            return this.money(line.product_discount);
        }

        return this.quantity(line.product_discount) + '%';
    }

    vat(value: string): string {
        return this.language.getListLabel('vat_list', value) || (value ? this.quantity(toLineItemNumber(value)) + '%' : '');
    }

    isService(line: ProductLineItem): boolean {
        return isServiceLine(line);
    }

    isValid(line: ProductLineItem): boolean {
        return isValidLine(line);
    }

    products(lines: ProductLineItem[]): ProductLineItem[] {
        return (lines ?? []).filter(line => !isServiceLine(line));
    }

    services(lines: ProductLineItem[]): ProductLineItem[] {
        return (lines ?? []).filter(line => isServiceLine(line));
    }

    hasLines(): boolean {
        return !!this.value?.groups?.some(group => group.lines.length > 0);
    }

    trackByIndex(index: number): number {
        return index;
    }

    /**
     * The currency picked in the form (its control is updated before the record
     * form notifies the change), else the record one.
     */
    protected getRecordCurrencyId(): string {
        const field = this.record?.fields?.currency_id;
        return field?.formControl?.value || field?.value || this.record?.attributes?.currency_id || '-99';
    }
}
