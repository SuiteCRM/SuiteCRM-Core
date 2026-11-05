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


/**
 * Value of a product-line-items field (quotes, invoices and contracts): the line
 * item settings and the groups of the record with their product and service
 * lines (backend ProductLineItemsHandler). Amounts are numbers in the record
 * currency.
 */
export interface ProductLineItem {
    id: string;
    product_id: string;
    name: string;
    part_number: string;
    item_description: string;
    description: string;
    product_qty: number;
    product_cost_price: number;
    product_list_price: number;
    discount: string;
    product_discount: number;
    product_discount_amount: number;
    product_unit_price: number;
    vat: string;
    vat_amt: number;
    product_total_price: number;
}

export interface ProductLineItemTotals {
    total_amt: number;
    discount_amount: number;
    subtotal_amount: number;
    tax_amount: number;
    subtotal_tax_amount: number;
    total_amount: number;
}

export interface ProductLineItemGroup extends ProductLineItemTotals {
    id: string;
    name: string;
    lines: ProductLineItem[];
}

export interface ProductLineItemsValue {
    enableGroups: boolean;
    totalTax: boolean;
    precision: number;
    groups: ProductLineItemGroup[];
}

/** A product found by the `product-line-items` process (prices in its own currency). */
export interface ProductLineItemProduct {
    id: string;
    name: string;
    part_number: string;
    price: number;
    cost: number;
    currency_id: string;
    description: string;
}

export const PRODUCT_LINE_DISCOUNT_AMOUNT = 'Amount';
export const PRODUCT_LINE_DISCOUNT_PERCENTAGE = 'Percentage';

/** product_id of a service line. */
export const SERVICE_LINE_PRODUCT_ID = '0';

/** Record totals calculated from the line items (legacy calculateTotal). */
export const PRODUCT_LINE_ITEMS_RECORD_TOTALS = [
    'total_amt',
    'discount_amount',
    'subtotal_amount',
    'shipping_tax_amt',
    'tax_amount',
    'subtotal_tax_amount',
    'total_amount',
];

export function toLineItemNumber(value: any): number {
    const number = typeof value === 'number' ? value : parseFloat(value);
    return isFinite(number) ? number : 0;
}

/** Rounded half away from zero, as PHP round(). */
export function roundLineItemAmount(value: number, precision: number): number {
    const factor = Math.pow(10, precision);
    return Math.sign(value) * Math.round(Math.abs(value) * factor + 1e-9) / factor;
}

export function isServiceLine(line: ProductLineItem): boolean {
    return (line?.product_id ?? '').trim() === SERVICE_LINE_PRODUCT_ID;
}

/** A line the save keeps: a product line with its product, a service line with its name. */
export function isValidLine(line: ProductLineItem): boolean {
    return !!(line?.product_id || '').trim() && !!(line?.name || '').trim();
}

/**
 * Line, group and record totals, as the legacy edit view (line_items.js) and the
 * backend ProductLineItemsCalculator calculate them. Updates the lines and groups
 * of `value` and returns the record totals.
 */
export function calculateProductLineItems(value: ProductLineItemsValue, shipping = 0, shippingTax = 0): { [field: string]: number } {
    const precision = value.precision;
    const allLines: ProductLineItem[] = [];

    value.groups.forEach(group => {
        group.lines.forEach(line => calculateProductLine(line, value.totalTax, precision));
        const savedLines = group.lines.filter(isValidLine);
        Object.assign(group, sumLines(savedLines, precision));
        allLines.push(...savedLines);
    });

    const totals: { [field: string]: number } = {...sumLines(allLines, precision)};
    const shippingTaxAmount = roundLineItemAmount(shipping * shippingTax / 100, precision);
    totals.shipping_tax_amt = shippingTaxAmount;
    totals.tax_amount = roundLineItemAmount(totals.tax_amount + shippingTaxAmount, precision);
    totals.subtotal_tax_amount = roundLineItemAmount(totals.subtotal_amount + totals.tax_amount, precision);
    totals.total_amount = roundLineItemAmount(totals.subtotal_tax_amount + shipping, precision);

    return totals;
}

export function calculateProductLine(line: ProductLineItem, totalTax: boolean, precision: number): void {
    const listPrice = roundLineItemAmount(toLineItemNumber(line.product_list_price), precision);
    const emptyQuantity = (line.product_qty as any) === '' || line.product_qty === null;
    const quantity = isServiceLine(line) || emptyQuantity ? 1 : toLineItemNumber(line.product_qty);
    let discount = toLineItemNumber(line.product_discount);
    let discountAmount: number;

    if (line.discount === PRODUCT_LINE_DISCOUNT_AMOUNT) {
        discount = Math.min(discount, listPrice);
        discountAmount = discount;
    } else {
        line.discount = PRODUCT_LINE_DISCOUNT_PERCENTAGE;
        discount = Math.min(discount, 100);
        discountAmount = discount / 100 * listPrice;
    }

    const salePrice = listPrice - discountAmount;
    let total = quantity * salePrice;
    const vatAmount = total * toLineItemNumber(line.vat) / 100;
    if (totalTax) {
        total += vatAmount;
    }

    line.product_list_price = listPrice;
    line.product_qty = quantity;
    line.product_discount = discount;
    line.product_discount_amount = roundLineItemAmount(-discountAmount, 6);
    line.product_unit_price = roundLineItemAmount(salePrice, precision);
    line.vat_amt = roundLineItemAmount(vatAmount, precision);
    line.product_total_price = roundLineItemAmount(total, precision);
}

function sumLines(lines: ProductLineItem[], precision: number): ProductLineItemTotals {
    let total = 0;
    let discount = 0;
    let tax = 0;

    lines.forEach(line => {
        total += line.product_list_price * line.product_qty;
        discount += line.product_discount_amount * line.product_qty;
        tax += line.vat_amt;
    });

    const subtotal = total + discount;

    return {
        total_amt: roundLineItemAmount(total, precision),
        discount_amount: roundLineItemAmount(discount, precision),
        subtotal_amount: roundLineItemAmount(subtotal, precision),
        tax_amount: roundLineItemAmount(tax, precision),
        subtotal_tax_amount: roundLineItemAmount(subtotal + tax, precision),
        total_amount: roundLineItemAmount(subtotal + tax, precision),
    };
}
