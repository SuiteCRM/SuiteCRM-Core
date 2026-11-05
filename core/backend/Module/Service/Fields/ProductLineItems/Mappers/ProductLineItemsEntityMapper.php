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

namespace App\Module\Service\Fields\ProductLineItems\Mappers;

use App\Data\Service\Record\EntityRecordMappers\EntityRecordFieldTypeMapperInterface;
use App\Module\Service\Fields\ProductLineItems\LegacyHandler\ProductLineItemsHandler;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * Line items of a record when it is retrieved: record view, and the copy of a
 * record (duplicate), which reads it without the api mappers.
 */
class ProductLineItemsEntityMapper implements EntityRecordFieldTypeMapperInterface
{
    use ProductLineItemsMapperTrait;

    public function __construct(ProductLineItemsHandler $lineItemsHandler, ModuleNameMapperInterface $moduleNameMapper)
    {
        $this->lineItemsHandler = $lineItemsHandler;
        $this->moduleNameMapper = $moduleNameMapper;
    }

    public function getModes(): array
    {
        return ['retrieve'];
    }
}
