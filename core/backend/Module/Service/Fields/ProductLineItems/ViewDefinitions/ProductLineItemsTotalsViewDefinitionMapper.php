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

namespace App\Module\Service\Fields\ProductLineItems\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Service\Fields\ProductLineItems\FieldDefinitions\ProductLineItemsFieldDefinitionMapper;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * Currency of the totals of a record with line items (quotes, invoices and
 * contracts). Registered per module, so that it runs after the default currency
 * mapper (RecordViewCurrencyFieldMapper), which groups each amount with the
 * currency and its base amount:
 *  - the currency is picked once, next to the grand total (total_amount); the
 *    other totals show their amount;
 *  - the totals and the shipping have no inline edit (see
 *    ProductLineItemsFieldDefinitionMapper), and no currency logic: it sets the
 *    amounts again from their base amounts when the currency changes, over the
 *    totals calculated from the lines. The product-line-items field converts the
 *    prices and the shipping itself, and the save calculates the base amounts.
 *    The other amounts (e.g. the contract value) keep it.
 */
abstract class ProductLineItemsTotalsViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    public const CURRENCY_FIELD = 'total_amount';
    protected const GROUP_SUFFIX = '-group';

    public function getKey(): string
    {
        return 'product-line-items-totals';
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $recordView = $definition->getRecordView() ?? [];
        $panels = $recordView['panels'] ?? [];
        if (empty($panels)) {
            return;
        }

        foreach ($panels as $panelKey => $panel) {
            foreach ($panel['rows'] ?? [] as $rowKey => $row) {
                foreach ($row['cols'] ?? [] as $colKey => $col) {
                    $panels[$panelKey]['rows'][$rowKey]['cols'][$colKey] = $this->mapCol($col);
                }
            }
        }

        $recordView['panels'] = $panels;
        $definition->setRecordView($recordView);
    }

    protected function mapCol(array $col): array
    {
        $name = (string)($col['name'] ?? '');
        $groupDefinition = $col['fieldDefinition'] ?? [];

        if (($groupDefinition['type'] ?? '') !== 'grouped-field' || !str_ends_with($name, self::GROUP_SUFFIX)) {
            return $col;
        }

        if (!isset($groupDefinition['groupFields']['currency_id'])) {
            return $col;
        }

        $field = substr($name, 0, -strlen(self::GROUP_SUFFIX));
        $lineItemsAmounts = array_merge(
            ProductLineItemsFieldDefinitionMapper::TOTAL_FIELDS,
            ProductLineItemsFieldDefinitionMapper::SHIPPING_FIELDS
        );

        if (in_array($field, $lineItemsAmounts, true)) {
            $groupDefinition['inline_edit'] = false;
            foreach (array_keys($groupDefinition['groupFields']) as $groupField) {
                unset($groupDefinition['groupFields'][$groupField]['logic']['currency']);
            }
        }

        if ($field !== self::CURRENCY_FIELD) {
            $groupDefinition['layout'] = array_values(array_filter(
                $groupDefinition['layout'] ?? [],
                static fn ($groupField) => $groupField !== 'currency_id'
            ));
            unset($groupDefinition['groupFields']['currency_id']);
        }

        $col['fieldDefinition'] = $groupDefinition;

        return $col;
    }
}
