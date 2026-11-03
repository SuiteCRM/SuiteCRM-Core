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


import {ChangeDetectorRef, Component, inject, OnDestroy, OnInit} from '@angular/core';
import {of, Subject, Subscription} from 'rxjs';
import {catchError, debounceTime, switchMap, take} from 'rxjs/operators';
import {BaseProductLineItemsComponent} from '../../../base/base-product-line-items.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {
    calculateProductLineItems,
    PRODUCT_LINE_DISCOUNT_AMOUNT,
    PRODUCT_LINE_DISCOUNT_PERCENTAGE,
    PRODUCT_LINE_ITEMS_RECORD_TOTALS,
    ProductLineItem,
    ProductLineItemGroup,
    ProductLineItemProduct,
    SERVICE_LINE_PRODUCT_ID,
    toLineItemNumber
} from '../../product-line-items.model';

interface ProductSearch {
    line: ProductLineItem;
    query: string;
}

/**
 * Line items edited as in the legacy edit view (modules/AOS_Products_Quotes/line_items.js):
 * groups, product lines (the product chosen in the list of the products shown in
 * the empty field and narrowed by name or part number as it is typed, its price
 * converted to the record currency) and service lines added and removed;
 * quantity, list price, discount (percentage or amount) and tax edited; line,
 * group and record totals (the total, discount, subtotal, tax and grand total
 * fields, with the shipping and its tax) calculated while editing. A currency
 * change converts the prices and the shipping. The save calculates the totals
 * again (backend ProductLineItemsSaveHandler).
 */
@Component({
    selector: 'scrm-product-line-items-edit',
    templateUrl: './product-line-items.component.html',
    styleUrls: []
})
export class ProductLineItemsEditFieldComponent extends BaseProductLineItemsComponent implements OnInit, OnDestroy {

    search: ProductSearch = null;
    foundProducts: ProductLineItemProduct[] = [];
    searching = false;
    /**
     * The last product search failed (an error, not "no product").
     */
    searchFailed = false;
    vatOptions: { value: string; label: string }[] = [];
    discountOptions: { value: string; label: string }[] = [];

    protected static readonly PROCESS_TYPE = 'product-line-items';

    protected currencyId = '-99';
    protected shipping = '';
    protected searchQuery = new Subject<ProductSearch>();
    /**
     * All the products (the list of the empty field), read once.
     */
    protected productList: ProductLineItemProduct[] = null;
    protected subscriptions: Subscription[] = [];

    protected processService = inject(ProcessService);
    protected changeDetector = inject(ChangeDetectorRef);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        const hasValue = Array.isArray(this.field?.valueObject?.groups ?? this.record?.attributes?.[this.field?.name]?.groups);
        super.ngOnInit();

        this.vatOptions = this.listOptions('vat_list');
        this.discountOptions = this.listOptions('discount_list');
        this.currencyId = this.getRecordCurrencyId();

        if (!hasValue) {
            this.loadSettings();
        }

        if (this.value.groups.length === 0) {
            this.addGroup();
        }

        // Every search is sent, even the same text again: the list is emptied when it closes, so
        // a search left out (field focused again) would wait for results forever. A failed search
        // shows an error and leaves the next ones running.
        this.subscriptions.push(this.searchQuery.pipe(
            debounceTime(300),
            switchMap(search => this.processService.submit(
                ProductLineItemsEditFieldComponent.PROCESS_TYPE,
                {action: 'products', query: search.query}
            ).pipe(
                take(1),
                catchError(() => of(null))
            ))
        ).subscribe((process: Process) => {
            this.searching = false;
            this.searchFailed = process?.status !== 'success';
            this.foundProducts = (process?.data as any)?.products ?? [];
            if (!this.searchFailed && this.search && this.search.query === '') {
                this.productList = this.foundProducts;
            }
            this.changeDetector.markForCheck();
        }));

        // The currency and shipping fields have their own rows, possibly built after
        // this one: their changes are followed through the record form.
        this.shipping = this.getShipping();
        if (this.record?.formGroup) {
            this.subscriptions.push(this.record.formGroup.valueChanges.pipe(
                debounceTime(200)
            ).subscribe(() => this.onRecordChange()));
        }
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
        super.ngOnDestroy();
    }

    amountInput(value: number): string {
        return this.currencyFormatter.toUserFormat(String(toLineItemNumber(value)), {mode: 'edit', digits: this.value?.precision} as any);
    }

    addGroup(): void {
        this.value.groups.push({
            id: '',
            name: '',
            lines: [],
            total_amt: 0,
            discount_amount: 0,
            subtotal_amount: 0,
            tax_amount: 0,
            subtotal_tax_amount: 0,
            total_amount: 0
        });
        this.addProductLine(this.value.groups[this.value.groups.length - 1]);
    }

    removeGroup(group: ProductLineItemGroup): void {
        this.value.groups = this.value.groups.filter(item => item !== group);
        this.update();
    }

    addProductLine(group: ProductLineItemGroup): void {
        group.lines.push(this.newLine(''));
        this.update();
    }

    addServiceLine(group: ProductLineItemGroup): void {
        group.lines.push(this.newLine(SERVICE_LINE_PRODUCT_ID));
        this.update();
    }

    removeLine(group: ProductLineItemGroup, line: ProductLineItem): void {
        group.lines = group.lines.filter(item => item !== line);
        this.update();
    }

    setGroupName(group: ProductLineItemGroup, name: string): void {
        group.name = name;
        this.update();
    }

    setText(line: ProductLineItem, field: 'name' | 'item_description' | 'description', text: string): void {
        line[field] = text;
        this.update();
    }

    setNumber(line: ProductLineItem, field: 'product_qty' | 'product_list_price' | 'product_discount', text: string): void {
        const internal = field === 'product_qty' ? this.numberFormatter.toInternalFormat(text) : this.currencyFormatter.toInternalFormat(text);
        line[field] = toLineItemNumber(internal);
        this.update();
    }

    setOption(line: ProductLineItem, field: 'discount' | 'vat', option: string): void {
        line[field] = option;
        this.update();
    }

    /**
     * The products of the line, as the legacy product field: all of them in the empty field,
     * those whose name or part number contains the text typed (the product is dropped until
     * one is picked in the list).
     */
    searchProducts(line: ProductLineItem, query: string): void {
        if (line.name !== query) {
            line.name = query;
            if (line.product_id) {
                line.product_id = '';
                this.update();
            }
        }

        this.search = {line, query: query.trim()};
        this.searchFailed = false;
        if (this.search.query === '' && this.productList) {
            this.foundProducts = this.productList;
            this.searching = false;
            return;
        }

        this.foundProducts = [];
        this.searching = true;
        this.searchQuery.next(this.search);
    }

    /**
     * A product search open on a line of the group.
     */
    isSearching(group: ProductLineItemGroup): boolean {
        return !!this.search && group.lines.includes(this.search.line);
    }

    closeSearch(line: ProductLineItem): void {
        // After a click in the result list (picked on mousedown).
        setTimeout(() => {
            if (this.search?.line === line) {
                this.search = null;
                this.foundProducts = [];
                this.changeDetector.markForCheck();
            }
        }, 150);
    }

    selectProduct(line: ProductLineItem, product: ProductLineItemProduct): void {
        line.product_id = product.id;
        line.name = product.name;
        line.part_number = product.part_number;
        line.item_description = product.description;
        line.product_list_price = this.convert(product.price, product.currency_id, this.currencyId);
        line.product_cost_price = this.convert(product.cost, product.currency_id, this.currencyId);
        this.search = null;
        this.foundProducts = [];
        this.update();
    }

    protected newLine(productId: string): ProductLineItem {
        return {
            id: '',
            product_id: productId,
            name: '',
            part_number: '',
            item_description: '',
            description: '',
            product_qty: 1,
            product_cost_price: 0,
            product_list_price: 0,
            discount: PRODUCT_LINE_DISCOUNT_PERCENTAGE,
            product_discount: 0,
            product_discount_amount: 0,
            product_unit_price: 0,
            vat: this.vatOptions[0]?.value ?? '0.0',
            vat_amt: 0,
            product_total_price: 0
        };
    }

    /**
     * Totals calculated again; the value is given to the field (saved with the
     * record) and the totals to the record total fields.
     */
    protected update(): void {
        this.shipping = this.getShipping();
        const totals = calculateProductLineItems(
            this.value,
            toLineItemNumber(this.getRecordValue('shipping_amount')),
            toLineItemNumber(this.getRecordValue('shipping_tax'))
        );

        const value = JSON.parse(JSON.stringify(this.value));
        this.field.valueObject = value;
        this.field.value = value;
        this.field.formControl?.setValue(value);
        this.field.formControl?.markAsDirty();
        if (this.record?.attributes) {
            this.record.attributes[this.field.name] = value;
        }

        PRODUCT_LINE_ITEMS_RECORD_TOTALS.forEach(name => this.setRecordAmount(name, totals[name] ?? 0));
    }

    protected setRecordAmount(name: string, amount: number): void {
        const internal = String(amount);
        if (this.record?.attributes) {
            this.record.attributes[name] = internal;
        }

        const field = this.record?.fields?.[name];
        if (!field) {
            return;
        }

        field.value = internal;
        field.formControl?.setValue(this.currencyFormatter.toUserFormat(internal, {mode: 'edit'} as any));
    }

    /**
     * A change in the record form: the prices and the shipping are converted when
     * the currency changed (as the legacy edit view does), the totals calculated
     * again when the shipping changed.
     */
    protected onRecordChange(): void {
        const currencyId = this.getRecordCurrencyId();
        if (currencyId !== this.currencyId) {
            const previousCurrencyId = this.currencyId;
            this.currencyId = currencyId;
            this.convertAmounts(previousCurrencyId, currencyId);
            return;
        }

        if (this.getShipping() !== this.shipping) {
            this.update();
            this.changeDetector.markForCheck();
        }
    }

    protected getShipping(): string {
        return this.getRecordValue('shipping_amount') + '|' + this.getRecordValue('shipping_tax');
    }

    protected getRecordValue(name: string): string {
        return String(this.record?.fields?.[name]?.value ?? this.record?.attributes?.[name] ?? '');
    }

    protected convertAmounts(fromCurrencyId: string, toCurrencyId: string): void {
        this.value.groups.forEach(group => group.lines.forEach(line => {
            line.product_list_price = this.convert(line.product_list_price, fromCurrencyId, toCurrencyId);
            line.product_cost_price = this.convert(line.product_cost_price, fromCurrencyId, toCurrencyId);
            if (line.discount === PRODUCT_LINE_DISCOUNT_AMOUNT) {
                line.product_discount = this.convert(line.product_discount, fromCurrencyId, toCurrencyId);
            }
        }));

        const shipping = this.getRecordValue('shipping_amount');
        if (shipping !== '') {
            const amount = this.convert(toLineItemNumber(shipping), fromCurrencyId, toCurrencyId);
            this.setRecordAmount('shipping_amount', Number(amount.toFixed(this.value.precision)));
        }

        this.update();
        this.changeDetector.markForCheck();
    }

    protected convert(amount: number, fromCurrencyId: string, toCurrencyId: string): number {
        const from = fromCurrencyId || '-99';
        const to = toCurrencyId || '-99';
        if (from === to) {
            return toLineItemNumber(amount);
        }

        return this.currencyService.baseToCurrency(to, this.currencyService.currencyToBase(from, toLineItemNumber(amount)));
    }

    protected listOptions(list: string): { value: string; label: string }[] {
        const strings = this.language.getAppListString(list) as { [key: string]: string } || {};
        return Object.keys(strings).filter(key => key !== '').map(key => ({value: key, label: strings[key]}));
    }

    /**
     * Settings of a record being created (it has no line items value yet).
     */
    protected loadSettings(): void {
        this.processService.submit(ProductLineItemsEditFieldComponent.PROCESS_TYPE, {action: 'settings'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const settings = process?.data as any;
                if (!settings) {
                    return;
                }

                this.value.enableGroups = !!settings.enableGroups;
                this.value.totalTax = !!settings.totalTax;
                this.value.precision = toLineItemNumber(settings.precision);
                this.update();
                this.changeDetector.markForCheck();
            },
            error: () => {
            }
        });
    }
}
