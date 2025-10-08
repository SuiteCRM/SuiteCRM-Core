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

class RecordFieldSaveHandlerRegistry
{
    use RecordFieldSaveHandlerTrait;

    /**
     * @var RecordFieldSaveHandlerInterface[]
     */
    protected array $registry = [];

    /**
     * @var RecordFieldSaveHandlerInterface[]
     */
    protected array $defaultSaveHandlers = [];

    /**
     * @var RecordFieldSaveHandlerInterface[]
     */
    protected array $existingTypeDefaultOverrides = [];

    /**
     * RecordFieldSaveHandlerRegistry constructor.
     * @param Traversable $handlers
     */
    public function __construct(Traversable $handlers)
    {
        /** @var RecordFieldSaveHandlerInterface[] $handlersArray */
        $handlersArray = [];
        foreach ($handlers as $handler) {
            $handlersArray[] = $handler;
        }
        $this->addHandlers($handlersArray);
    }

    /**
     * Get the field type save handlers for the module and type
     * @param string $module
     * @param string $field
     * @param string|null $mode
     * @return RecordFieldSaveHandlerInterface[]
     */
    public function getSaveHandlers(string $module, string $field, ?string $mode = ''): array
    {
        $handlers = $this->getOrderedHandlers($this->registry, $module, $field);

        return $this->filterByModes($handlers, $mode);
    }

    /**
     * Get default save handler for module, field and mode
     * @param string $module
     * @param string $field
     * @param string $mode
     * @return RecordFieldSaveHandlerInterface|null
     */
    public function getDefaultSaveHandler(string $module, string $field, string $mode): ?RecordFieldSaveHandlerInterface
    {
        $moduleDefault = $this->defaultSaveHandlers[$module . '-' . $field . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->defaultSaveHandlers['default' . '-' . $field . '-' . $mode] ?? null;
    }

    /**
     * Get default save handler for module, field and mode
     * @param string $module
     * @param string $field
     * @param string $mode
     * @return RecordFieldSaveHandlerInterface|null
     */
    public function getTypeDefaultOverride(string $module, string $field, string $mode): ?RecordFieldSaveHandlerInterface
    {
        $moduleDefault = $this->existingTypeDefaultOverrides[$module . '-' . $field . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->existingTypeDefaultOverrides['default' . '-' . $field . '-' . $mode] ?? null;
    }

    /**
     * @param RecordFieldSaveHandlerInterface[] $handlers
     * @return void
     */
    protected function addHandlers(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $field = $handler->getField() ?? '';
            $module = $handler->getModule() ?? '';
            $order = $handler->getOrder() ?? 0;
            $moduleHandlers = $this->registry[$module] ?? [];
            $key = $handler->getKey();

            if ($handler->replaceDefaultTypeMapper()) {
                $key = 'default';
                foreach ($handler->getModes() as $mode) {
                    $this->existingTypeDefaultOverrides[$module . '-' . $field . '-' . $mode] = $handler;
                }
            }

            if ($key === 'default') {
                foreach ($handler->getModes() as $mode) {
                    $this->defaultSaveHandlers[$module . '-' . $field . '-' . $mode] = $handler;
                }
                continue;
            }


            $this->addHandler($moduleHandlers, $field, $order, $handler);


            $this->registry[$module] = $moduleHandlers;
        }
    }

}
