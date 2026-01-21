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

namespace App\AsyncTask\Service\TaskCompletedHandler;

use Traversable;

class AsyncTaskCompletedHandlerRegistry implements AsyncTaskCompletedHandlerRegistryInterface
{
    /**
     * @var AsyncTaskCompletedHandlerInterface[][]
     */
    protected array $registry = [];

    /**
     * AsyncTaskRunnerRegistry constructor.
     * @param Traversable $handlers
     */
    public function __construct(Traversable $handlers)
    {
        /** @var AsyncTaskCompletedHandlerInterface[] $handlersArray */
        $handlersArray = [];
        foreach ($handlers as $handler) {
            $handlersArray[] = $handler;
        }
        $this->addHandlers($handlersArray);
    }

    public function getHandler(string $type, string $handlerKey): ?AsyncTaskCompletedHandlerInterface
    {
        if (!empty($this->registry[$type][$handlerKey])) {
            return $this->registry[$type][$handlerKey];
        }

        if (!empty($this->registry['default'][$handlerKey])) {
            return $this->registry['default'][$handlerKey];
        }

        return $this->registry['default']['default'] ?? null;
    }

    protected function addHandlers(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $type = $handler->getType();
            $typeHandlers = $this->registry[$type] ?? [];
            $handlerKey = $handler->getHandlerKey();
            $typeHandlers[$handlerKey] = $handler;
            $this->registry[$type] = $typeHandlers;
        }
    }
}
