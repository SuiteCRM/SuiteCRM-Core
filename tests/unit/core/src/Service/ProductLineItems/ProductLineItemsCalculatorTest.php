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

namespace App\Tests\unit\core\src\Service\ProductLineItems;

use App\Module\Service\Fields\ProductLineItems\ProductLineItemsCalculator;
use Codeception\Test\Unit;

/**
 * Class ProductLineItemsCalculatorTest
 * @package App\Tests
 */
class ProductLineItemsCalculatorTest extends Unit
{
    protected ProductLineItemsCalculator $calculator;

    protected function _before(): void
    {
        $this->calculator = new ProductLineItemsCalculator();
    }

    protected function line(array $values): array
    {
        return array_merge([
            'id' => '',
            'product_id' => 'product-1',
            'name' => 'Product',
            'product_qty' => 1,
            'product_list_price' => 0,
            'discount' => 'Percentage',
            'product_discount' => 0,
            'vat' => '0.0',
        ], $values);
    }

    public function testPercentageDiscountAndTax(): void
    {
        $line = $this->calculator->calculateLine(
            $this->line(['product_qty' => 3, 'product_list_price' => 100, 'product_discount' => 10, 'vat' => '20.0']),
            false,
            2
        );

        static::assertSame(90.0, $line['product_unit_price']);
        static::assertSame(-10.0, $line['product_discount_amount']);
        static::assertSame(54.0, $line['vat_amt']);
        static::assertSame(270.0, $line['product_total_price']);
    }

    public function testTotalTaxAddsTheTaxToTheLineTotal(): void
    {
        $line = $this->calculator->calculateLine(
            $this->line(['product_qty' => 2, 'product_list_price' => 50, 'vat' => '10.0']),
            true,
            2
        );

        static::assertSame(10.0, $line['vat_amt']);
        static::assertSame(110.0, $line['product_total_price']);
    }

    public function testDiscountsAreCapped(): void
    {
        $amount = $this->calculator->calculateLine(
            $this->line(['product_list_price' => 40, 'discount' => 'Amount', 'product_discount' => 60]),
            false,
            2
        );
        $percentage = $this->calculator->calculateLine(
            $this->line(['product_list_price' => 40, 'product_discount' => 150]),
            false,
            2
        );

        static::assertSame(40.0, $amount['product_discount']);
        static::assertSame(0.0, $amount['product_unit_price']);
        static::assertSame(100.0, $percentage['product_discount']);
        static::assertSame(0.0, $percentage['product_total_price']);
    }

    public function testServiceLineCountsOnce(): void
    {
        $line = $this->calculator->calculateLine(
            $this->line([
                'product_id' => '0',
                'name' => 'Installation',
                'product_qty' => 5,
                'part_number' => 'P-1',
                'product_cost_price' => 30,
                'product_list_price' => 200,
            ]),
            false,
            2
        );

        static::assertSame(1.0, $line['product_qty']);
        static::assertSame('', $line['part_number']);
        static::assertSame(0.0, $line['product_cost_price']);
        static::assertSame(200.0, $line['product_total_price']);
    }

    public function testGroupAndRecordTotals(): void
    {
        $result = $this->calculator->calculate(
            [
                [
                    'id' => 'group-1',
                    'name' => 'Hardware',
                    'lines' => [
                        $this->line(['product_qty' => 2, 'product_list_price' => 100, 'product_discount' => 10, 'vat' => '20.0']),
                        $this->line(['product_id' => '', 'name' => 'Not chosen', 'product_list_price' => 999]),
                    ],
                ],
                [
                    'id' => '',
                    'name' => ' Services ',
                    'lines' => [
                        $this->line(['product_id' => '0', 'name' => 'Support', 'product_list_price' => 50]),
                        $this->line(['product_id' => '0', 'name' => '', 'product_list_price' => 999]),
                    ],
                ],
            ],
            ['totalTax' => false, 'precision' => 2],
            10.0,
            20.0
        );

        $groups = $result['groups'];
        static::assertCount(2, $groups);
        static::assertCount(1, $groups[0]['lines']);
        static::assertCount(1, $groups[1]['lines']);
        static::assertSame('Services', $groups[1]['name']);
        static::assertSame(200.0, $groups[0]['total_amt']);
        static::assertSame(-20.0, $groups[0]['discount_amount']);
        static::assertSame(180.0, $groups[0]['subtotal_amount']);
        static::assertSame(36.0, $groups[0]['tax_amount']);
        static::assertSame(216.0, $groups[0]['total_amount']);
        static::assertSame(50.0, $groups[1]['total_amount']);

        $totals = $result['totals'];
        static::assertSame(250.0, $totals['total_amt']);
        static::assertSame(-20.0, $totals['discount_amount']);
        static::assertSame(230.0, $totals['subtotal_amount']);
        static::assertSame(2.0, $totals['shipping_tax_amt']);
        static::assertSame(38.0, $totals['tax_amount']);
        static::assertSame(268.0, $totals['subtotal_tax_amount']);
        static::assertSame(278.0, $totals['total_amount']);
    }
}
