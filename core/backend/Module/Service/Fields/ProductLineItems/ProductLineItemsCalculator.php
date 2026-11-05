<?php
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

namespace App\Module\Service\Fields\ProductLineItems;

/**
 * Totals of the product and service lines of a quote, invoice or contract, as the
 * legacy edit view calculates them (modules/AOS_Products_Quotes/line_items.js,
 * calculateLine / calculateTotal):
 *  - sale price = list price less the discount (a percentage, at most 100, or an
 *    amount, at most the list price);
 *  - line total = quantity x sale price (a service line counts once), plus the
 *    tax when the totals include it (aos.lineItems.totalTax);
 *  - group and record totals = sums of their lines; the record total adds the
 *    shipping and its tax.
 * The product-line-items field calculates the same totals while editing; these
 * ones are saved.
 */
class ProductLineItemsCalculator
{
    public const GROUP_TOTALS = [
        'total_amt',
        'discount_amount',
        'subtotal_amount',
        'tax_amount',
        'subtotal_tax_amount',
        'total_amount',
    ];

    public const DISCOUNT_AMOUNT = 'Amount';
    public const DISCOUNT_PERCENTAGE = 'Percentage';

    /**
     * product_id of a service line.
     */
    public const SERVICE_PRODUCT_ID = '0';

    /**
     * @param array $groups groups of the field value, each with its lines
     * @param array $settings totalTax and precision (ProductLineItemsHandler::getSettings)
     * @param float $shipping record shipping amount
     * @param float $shippingTax record shipping tax rate (%)
     * @return array{groups: array, totals: array<string, float>}
     */
    public function calculate(array $groups, array $settings, float $shipping = 0.0, float $shippingTax = 0.0): array
    {
        $totalTax = !empty($settings['totalTax']);
        $precision = (int)($settings['precision'] ?? 2);
        $allLines = [];
        $calculatedGroups = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $lines = [];
            foreach ($group['lines'] ?? [] as $line) {
                if (!is_array($line) || !$this->isValidLine($line)) {
                    continue;
                }
                $lines[] = $this->calculateLine($line, $totalTax, $precision);
            }

            $calculatedGroups[] = array_merge(
                [
                    'id' => (string)($group['id'] ?? ''),
                    'name' => trim((string)($group['name'] ?? '')),
                    'lines' => $lines,
                ],
                $this->sumLines($lines, $precision)
            );
            array_push($allLines, ...$lines);
        }

        $totals = $this->sumLines($allLines, $precision);
        $shippingTaxAmount = round($shipping * $shippingTax / 100, $precision);
        $totals['shipping_tax_amt'] = $shippingTaxAmount;
        $totals['tax_amount'] = round($totals['tax_amount'] + $shippingTaxAmount, $precision);
        $totals['subtotal_tax_amount'] = round($totals['subtotal_amount'] + $totals['tax_amount'], $precision);
        $totals['total_amount'] = round($totals['subtotal_tax_amount'] + $shipping, $precision);

        return ['groups' => $calculatedGroups, 'totals' => $totals];
    }

    /**
     * A line the legacy save keeps: a product line with its product, or a service
     * line with its name.
     */
    public function isValidLine(array $line): bool
    {
        return trim((string)($line['product_id'] ?? '')) !== '' && trim((string)($line['name'] ?? '')) !== '';
    }

    public function isServiceLine(array $line): bool
    {
        return trim((string)($line['product_id'] ?? '')) === self::SERVICE_PRODUCT_ID;
    }

    public function calculateLine(array $line, bool $totalTax, int $precision): array
    {
        $service = $this->isServiceLine($line);
        $listPrice = round($this->toNumber($line['product_list_price'] ?? 0), $precision);
        $quantity = $service || trim((string)($line['product_qty'] ?? '')) === '' ? 1.0 : $this->toNumber($line['product_qty']);
        $type = (string)($line['discount'] ?? '');
        $discount = $this->toNumber($line['product_discount'] ?? 0);

        if ($type === self::DISCOUNT_AMOUNT) {
            $discount = min($discount, $listPrice);
            $discountAmount = $discount;
        } elseif ($type === self::DISCOUNT_PERCENTAGE) {
            $discount = min($discount, 100.0);
            $discountAmount = $discount / 100 * $listPrice;
        } else {
            $type = self::DISCOUNT_PERCENTAGE;
            $discount = 0.0;
            $discountAmount = 0.0;
        }

        $salePrice = $listPrice - $discountAmount;
        $total = $quantity * $salePrice;
        $vat = $this->toNumber($line['vat'] ?? 0);
        $vatAmount = $total * $vat / 100;
        if ($totalTax) {
            $total += $vatAmount;
        }

        return [
            'id' => (string)($line['id'] ?? ''),
            'product_id' => trim((string)$line['product_id']),
            'name' => trim((string)$line['name']),
            'part_number' => $service ? '' : (string)($line['part_number'] ?? ''),
            'item_description' => (string)($line['item_description'] ?? ''),
            'description' => (string)($line['description'] ?? ''),
            'product_qty' => $quantity,
            'product_cost_price' => $service ? 0.0 : round($this->toNumber($line['product_cost_price'] ?? 0), $precision),
            'product_list_price' => $listPrice,
            'discount' => $type,
            'product_discount' => $discount,
            'product_discount_amount' => round(-$discountAmount, 6),
            'product_unit_price' => round($salePrice, $precision),
            'vat' => (string)($line['vat'] ?? ''),
            'vat_amt' => round($vatAmount, $precision),
            'product_total_price' => round($total, $precision),
        ];
    }

    /**
     * Totals of calculated lines (legacy calculateTotal, without the shipping).
     */
    protected function sumLines(array $lines, int $precision): array
    {
        $total = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($lines as $line) {
            $total += $line['product_list_price'] * $line['product_qty'];
            $discount += $line['product_discount_amount'] * $line['product_qty'];
            $tax += $line['vat_amt'];
        }

        $subtotal = $total + $discount;

        return [
            'total_amt' => round($total, $precision),
            'discount_amount' => round($discount, $precision),
            'subtotal_amount' => round($subtotal, $precision),
            'tax_amount' => round($tax, $precision),
            'subtotal_tax_amount' => round($subtotal + $tax, $precision),
            'total_amount' => round($subtotal + $tax, $precision),
        ];
    }

    protected function toNumber($value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }
}
