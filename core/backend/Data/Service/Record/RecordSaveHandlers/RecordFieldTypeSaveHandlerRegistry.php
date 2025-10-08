<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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


namespace App\Data\Service\Record\RecordSaveHandlers;

use Traversable;

class RecordFieldTypeSaveHandlerRegistry
{
    use RecordFieldSaveHandlerTrait;

    /**
     * @var RecordFieldTypeSaveHandlerInterface[]
     */
    protected array $registry = [];

    /**
     * @var RecordFieldTypeSaveHandlerInterface[]
     */
    protected array $defaultHandlers = [];

    /**
     * RecordFieldTypeSaveHandlerRegistry constructor.
     * @param Traversable $handlers
     */
    public function __construct(Traversable $handlers)
    {
        /** @var RecordFieldTypeSaveHandlerInterface[] $handlersArray */
        $handlersArray = [];
        foreach ($handlers as $handler) {
            $handlersArray[] = $handler;
        }
        $this->addHandlers($handlersArray);
    }

    /**
     * Get the field type save handlers for the module, type and mode
     * @param string $module
     * @param string $fieldType
     * @param string|null $mode
     * @return RecordFieldTypeSaveHandlerInterface[]
     */
    public function getHandlers(string $module, string $fieldType, ?string $mode = ''): array
    {
        $handlers = $this->getOrderedHandlers($this->registry, $module, $fieldType);

        return $this->filterByModes($handlers, $mode);
    }

    /**
     * Get default save handler for module, field and mode
     * @param string $module
     * @param string $fieldType
     * @param string $mode
     * @return RecordFieldTypeSaveHandlerInterface|null
     */
    public function getDefaultHandler(string $module, string $fieldType, string $mode): ?RecordFieldTypeSaveHandlerInterface
    {
        $moduleDefault = $this->defaultHandlers[$module . '-' . $fieldType . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->defaultHandlers['default' . '-' . $fieldType . '-' . $mode] ?? null;
    }


    /**
     * @param RecordFieldTypeSaveHandlerInterface[] $handlers
     * @return void
     */
    protected function addHandlers(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $fieldType = $handler->getFieldType();
            $module = $handler->getModule();
            $order = $handler->getOrder() ?? 0;
            $moduleHandlers = $this->registry[$module] ?? [];
            $key = $handler->getKey();

            if ($key === 'default') {
                foreach ($handler->getModes() as $mode) {
                    $this->defaultHandlers[$module . '-' . $fieldType . '-' . $mode] = $handler;
                }
                continue;
            }

            $this->addHandler($moduleHandlers, $fieldType, $order, $handler);

            $this->registry[$module] = $moduleHandlers;
        }
    }
}
