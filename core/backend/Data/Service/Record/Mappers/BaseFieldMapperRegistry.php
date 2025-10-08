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


namespace App\Data\Service\Record\Mappers;

class BaseFieldMapperRegistry
{
    use RecordFieldMapperTrait;

    /**
     * @var BaseFieldMapperInterface[]
     */
    protected array $registry = [];

    /**
     * @var BaseFieldMapperInterface[]
     */
    protected array $defaultMappers = [];

    /**
     * @var BaseFieldMapperInterface[]
     */
    protected array $existingTypeDefaultOverrides = [];

    /**
     * RecordLevelMapperInterface constructor.
     * @param BaseFieldMapperInterface[] $mappers
     */
    public function __construct(array $mappers)
    {
        $this->addMappers($mappers);
    }

    /**
     * Get the field mappers for the module, field and mode
     * @param string $module
     * @param string $field
     * @param string|null $mode
     * @return BaseFieldMapperInterface[]
     */
    public function getMappers(string $module, string $field, ?string $mode = ''): array
    {
        $mappers = $this->getOrderedMappers($this->registry, $module, $field);

        return $this->filterMappersByModes($mappers, $mode);
    }

    /**
     * Get default mapper for module, field and mode
     * @param string $module
     * @param string $field
     * @param string $mode
     * @return BaseFieldMapperInterface|null
     */
    public function getDefaultMapper(string $module, string $field, string $mode): ?BaseFieldMapperInterface
    {
        $moduleDefault = $this->defaultMappers[$module . '-' . $field . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->defaultMappers['default' . '-' . $field . '-' . $mode] ?? null;
    }

    /**
     * Get default mapper for module, field and mode
     * @param string $module
     * @param string $field
     * @param string $mode
     * @return BaseFieldMapperInterface|null
     */
    public function getTypeDefaultOverride(string $module, string $field, string $mode): ?BaseFieldMapperInterface
    {
        $moduleDefault = $this->existingTypeDefaultOverrides[$module . '-' . $field . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->existingTypeDefaultOverrides['default' . '-' . $field . '-' . $mode] ?? null;
    }

    /**
     * @param BaseFieldMapperInterface[] $mappers
     * @return void
     */
    protected function addMappers(iterable $mappers): void
    {
        foreach ($mappers as $handler) {
            $field = $handler->getField() ?? '';
            $module = $handler->getModule() ?? '';
            $order = $handler->getOrder() ?? 0;
            $moduleMappers = $this->registry[$module] ?? [];
            $key = $handler->getKey();

            if ($handler->replaceDefaultTypeMapper()) {
                $key = 'default';
                foreach ($handler->getModes() as $mode) {
                    $this->existingTypeDefaultOverrides[$module . '-' . $field . '-' . $mode] = $handler;
                }
            }

            if ($key === 'default') {
                foreach ($handler->getModes() as $mode) {
                    $this->defaultMappers[$module . '-' . $field . '-' . $mode] = $handler;
                }
                continue;
            }

            $this->addMapper($moduleMappers, $field, $order, $handler);

            $this->registry[$module] = $moduleMappers;
        }
    }

}
