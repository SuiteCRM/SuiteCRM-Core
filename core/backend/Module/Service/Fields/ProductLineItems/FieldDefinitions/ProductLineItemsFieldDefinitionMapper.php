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

namespace App\Module\Service\Fields\ProductLineItems\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\Service\Fields\ProductLineItems\LegacyHandler\ProductLineItemsHandler;

/**
 * The legacy line items function field of quotes, invoices and contracts
 * (`line_items`, display_lines) is a product-line-items field in the record
 * views. The totals of these records are calculated from their lines, as in the
 * legacy edit views: they are read only (record view, inline edit, mass update).
 * The shipping and its tax are edited with the lines, not inline: the totals are
 * calculated when the line items are saved.
 */
class ProductLineItemsFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public const LEGACY_FUNCTION = 'display_lines';

    public const TOTAL_FIELDS = [
        'total_amt',
        'discount_amount',
        'subtotal_amount',
        'tax_amount',
        'subtotal_tax_amount',
        'shipping_tax_amt',
        'total_amount',
    ];

    public const SHIPPING_FIELDS = [
        'shipping_amount',
        'shipping_tax',
    ];

    public function getKey(): string
    {
        return 'product-line-items-field-definition';
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        if (empty($vardefs)) {
            return;
        }

        $hasLineItems = false;
        foreach ($vardefs as $name => $vardef) {
            $function = $vardef['function'] ?? '';
            if (is_array($function)) {
                $function = $function['name'] ?? '';
            }

            if (($vardef['type'] ?? '') !== 'function' || $function !== self::LEGACY_FUNCTION) {
                continue;
            }

            $vardefs[$name]['type'] = ProductLineItemsHandler::HANDLER_KEY;
            $vardefs[$name]['inline_edit'] = false;
            $hasLineItems = true;
        }

        if (!$hasLineItems) {
            return;
        }

        foreach (self::TOTAL_FIELDS as $field) {
            if (!isset($vardefs[$field])) {
                continue;
            }
            $vardefs[$field]['readonly'] = true;
            $vardefs[$field]['inline_edit'] = false;
            $vardefs[$field]['massupdate'] = false;
        }

        foreach (self::SHIPPING_FIELDS as $field) {
            if (isset($vardefs[$field])) {
                $vardefs[$field]['inline_edit'] = false;
            }
        }

        $definition->setVardef($vardefs);
    }
}
