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

namespace App\Data\Service\Record\Duplicate;

class DuplicateFieldMapperRegistry
{
    /**
     * Indexed by composite key: "{module}-{fieldType}-{fieldName}" for specific field names,
     * or "{module}-{fieldType}" when getFieldName() returns null (matches any field name).
     *
     * @var array<string, DuplicateFieldMapperInterface>
     */
    protected array $mappers = [];

    public function __construct(iterable $mappers)
    {
        foreach ($mappers as $mapper) {
            $key = $this->buildKey($mapper->getModule(), $mapper->getFieldType(), $mapper->getFieldName());
            $this->mappers[$key] = $mapper;
        }
    }

    /**
     * Return the most specific mapper for the given module, field type and field name.
     * Priority (highest first):
     *   1. exact module + exact field name
     *   2. exact module + any field name
     *   3. default module + exact field name
     *   4. default module + any field name
     */
    public function getMapper(string $module, string $fieldType, string $fieldName): ?DuplicateFieldMapperInterface
    {
        $exactModuleExactField = $this->mappers[$module . '-' . $fieldType . '-' . $fieldName] ?? null;
        if ($exactModuleExactField !== null) {
            return $exactModuleExactField;
        }

        $exactModuleAnyField = $this->mappers[$module . '-' . $fieldType] ?? null;
        if ($exactModuleAnyField !== null) {
            return $exactModuleAnyField;
        }

        $defaultModuleExactField = $this->mappers['default-' . $fieldType . '-' . $fieldName] ?? null;
        if ($defaultModuleExactField !== null) {
            return $defaultModuleExactField;
        }

        return $this->mappers['default-' . $fieldType] ?? null;
    }

    protected function buildKey(string $module, string $fieldType, ?string $fieldName): string
    {
        if ($fieldName === null) {
            return $module . '-' . $fieldType;
        }

        return $module . '-' . $fieldType . '-' . $fieldName;
    }
}
