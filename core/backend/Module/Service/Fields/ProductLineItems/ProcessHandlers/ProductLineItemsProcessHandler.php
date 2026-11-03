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

namespace App\Module\Service\Fields\ProductLineItems\ProcessHandlers;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\Service\Fields\ProductLineItems\LegacyHandler\ProductLineItemsHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Process of the product-line-items field:
 *  - action `products` (query): the products to add, by name or part number (all of them
 *    for an empty query, as the legacy product field lists them);
 *  - action `settings`: the line item settings, for a record being created.
 */
class ProductLineItemsProcessHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';
    public const PROCESS_TYPE = 'product-line-items';
    protected const ACTIONS = ['products', 'settings'];

    public function __construct(protected ProductLineItemsHandler $lineItemsHandler)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $action = $process->getOptions()['action'] ?? '';

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        if (($options['action'] ?? '') === 'settings') {
            $data = $this->lineItemsHandler->getSettings();
        } else {
            $data = ['products' => $this->lineItemsHandler->searchProducts((string)($options['query'] ?? ''))];
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($data);
    }
}
