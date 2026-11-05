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

namespace App\Module\Service\Fields\ProductLineItems\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldTypeSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Service\Fields\ProductLineItems\LegacyHandler\ProductLineItemsHandler;
use App\Module\Service\Fields\ProductLineItems\ProductLineItemsCalculator;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * Saves a product-line-items field, as the legacy save does from the edit view post:
 *  - before the record save, the totals of the lines are calculated
 *    (ProductLineItemsCalculator) and set on the record (total, discount,
 *    subtotal, tax, shipping tax and grand total), and the field value is taken
 *    out of the record attributes;
 *  - after it, the groups and lines are saved (ProductLineItemsHandler). A new
 *    record (e.g. a duplicate) gets new groups and lines.
 * A save without the field value (e.g. inline edit) leaves the lines unchanged.
 */
class ProductLineItemsSaveHandler implements RecordFieldTypeSaveHandlerInterface
{
    /**
     * Calculated groups of the record being saved, null when it has no line items value.
     */
    protected ?array $pendingGroups = null;

    public function __construct(
        protected ProductLineItemsHandler $lineItemsHandler,
        protected ProductLineItemsCalculator $calculator,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function getFieldType(): string
    {
        return ProductLineItemsHandler::HANDLER_KEY;
    }

    public function getKey(): string
    {
        return ProductLineItemsHandler::HANDLER_KEY;
    }

    public function getModes(): array
    {
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition, string $field): void
    {
        if ($savedRecord === null) {
            $this->beforeSave($inputRecord, $field);
            return;
        }

        if ($this->pendingGroups === null) {
            return;
        }

        $groups = $this->pendingGroups;
        $this->pendingGroups = null;

        $this->lineItemsHandler->saveLineItems(
            $this->moduleNameMapper->toLegacy($savedRecord->getModule()),
            (string)$savedRecord->getId(),
            $groups,
            $previousVersion === null
        );
    }

    protected function beforeSave(Record $inputRecord, string $field): void
    {
        $this->pendingGroups = null;

        $attributes = $inputRecord->getAttributes() ?? [];
        if (!array_key_exists($field, $attributes)) {
            return;
        }

        $value = $attributes[$field];
        unset($attributes[$field]);

        if (is_array($value) && is_array($value['groups'] ?? null)) {
            $result = $this->calculator->calculate(
                $value['groups'],
                $this->lineItemsHandler->getSettings(),
                $this->toNumber($attributes['shipping_amount'] ?? 0),
                $this->toNumber($attributes['shipping_tax'] ?? 0)
            );

            foreach ($result['totals'] as $total => $amount) {
                $attributes[$total] = $amount;
            }

            $this->pendingGroups = $result['groups'];
        }

        $inputRecord->setAttributes($attributes);
    }

    protected function toNumber($value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }
}
