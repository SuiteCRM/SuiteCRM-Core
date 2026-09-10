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

require_once __DIR__ . '/../FilterMapperInterface.php';

/**
 * Without a dedicated mapper, a 'multirelate' filter falls back to DefaultFilterMapper, which
 * collapses a single-value selection from an array to a bare scalar (see DefaultFilterMapper::mapValue()).
 * SearchForm2's multirelate handling requires an array of related record ids regardless of how many
 * are selected, so this mapper always preserves the array shape, mirroring MultiEnumFilterMapper.
 */
class MultiRelateFilterMapper implements FilterMapperInterface
{
    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'multirelate';
    }

    /**
     * @inheritDoc
     */
    public function mapValue(string $mappedValue, array $criteriaItem)
    {
        /** @var array */
        $values = $criteriaItem['values'] ?? [];

        if (empty($values)) {
            return [];
        }

        $filterValues = $values;

        $mapEmptyString = false;
        foreach ($filterValues as $legacyValueKey => $legacyValueValue) {
            switch ($legacyValueValue) {
                case "__SuiteCRMEmptyString__":
                    $mapEmptyString = true;
                    $filterValues[$legacyValueKey] = '';
                    break;
            }
        }

        if ($mapEmptyString) {
            return $filterValues;
        }

        return $criteriaItem['values'];
    }

    public function toApi(string $mappedValue, array $criteriaItem): string
    {
        if ($mappedValue === '') {
            $mappedValue = "__SuiteCRMEmptyString__";
        }

        return $mappedValue;
    }
}
