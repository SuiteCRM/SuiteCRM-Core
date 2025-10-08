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

class BaseFieldTypeMapperRegistry
{
    use RecordFieldMapperTrait;

    /**
     * @var BaseFieldTypeMapperInterface[]
     */
    protected array $registry = [];

    /**
     * @var BaseFieldTypeMapperInterface[]
     */
    protected array $defaultMappers = [];

    /**
     * BaseFieldTypeMapperRegistry constructor.
     * @param BaseFieldTypeMapperInterface[] $mappers
     */
    public function __construct(array $mappers)
    {
        $this->addMappers($mappers);
    }

    /**
     * Get the field type mappers for the module and type
     * @param string $module
     * @param string $fieldType
     * @param string|null $mode
     * @return BaseFieldTypeMapperInterface[]
     */
    public function getMappers(string $module, string $fieldType, ?string $mode = ''): array
    {
        $mappers = $this->getOrderedMappers($this->registry, $module, $fieldType);

        return $this->filterMappersByModes($mappers, $mode);
    }

    /**
     * Get default mapper for module, field and mode
     * @param string $module
     * @param string $fieldType
     * @param string $mode
     * @return BaseFieldTypeMapperInterface|null
     */
    public function getDefaultMapper(string $module, string $fieldType, string $mode): ?BaseFieldTypeMapperInterface
    {
        $moduleDefault = $this->defaultMappers[$module . '-' . $fieldType . '-' . $mode] ?? null;

        if ($moduleDefault !== null)  {
            return $moduleDefault;
        }

        return $this->defaultMappers['default' . '-' . $fieldType . '-' . $mode] ?? null;
    }


    /**
     * @param BaseFieldTypeMapperInterface[] $mappers
     * @return void
     */
    protected function addMappers(iterable $mappers): void
    {
        foreach ($mappers as $handler) {
            $fieldType = $handler->getFieldType();
            $module = $handler->getModule();
            $order = $handler->getOrder() ?? 0;
            $moduleMappers = $this->registry[$module] ?? [];
            $key = $handler->getKey();

            if ($key === 'default') {
                foreach ($handler->getModes() as $mode) {
                    $this->defaultMappers[$module . '-' . $fieldType . '-' . $mode] = $handler;
                }
                continue;
            }

            $this->addMapper($moduleMappers, $fieldType, $order, $handler);

            $this->registry[$module] = $moduleMappers;
        }
    }
}
