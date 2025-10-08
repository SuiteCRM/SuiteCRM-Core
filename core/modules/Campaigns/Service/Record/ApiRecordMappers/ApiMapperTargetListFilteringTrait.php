<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Module\Campaigns\Service\Record\ApiRecordMappers;

use App\Data\Entity\Record;

trait ApiMapperTargetListFilteringTrait
{

    /**
     * @param Record $record
     * @param string $sourceField
     * @param string $targetField
     * @param array $types
     * @return void
     */
    protected function filterListsByTypes(Record $record, string $sourceField, string $targetField, array $types): void
    {
        $attributes = $record->getAttributes();
        $value = $attributes[$sourceField] ?? [];

        $filteredLists = $this->filterByType($value, $types);

        $attributes[$targetField] = $filteredLists;
        $record->setAttributes($attributes);
    }


    /**
     * @param array $lists
     * @param array $types
     * @return array
     */
    protected function filterByType(array $lists, array $types): array
    {
        $filteredLists = [];
        foreach ($lists as $key => $list) {
            $attributes = $list['attributes'] ?? [];
            $type = $attributes['list_type'] ?? '';
            if (empty($type) || !($types[$type] ?? false)) {
                continue;
            }

            $filteredLists[] = $list;
        }
        return $filteredLists;
    }

    protected function getSuppressionTypes(): array
    {
        return [
            'exempt' => true,
            'exempt_domain' => true,
            'exempt_address' => true
        ];
    }

    protected function getTargetListTypes(): array
    {
        return [
            'seed' => true,
            'default' => true,
        ];
    }

    protected function joinList(array $listValues, array $mergedLists): array
    {
        $listsById = [];

        foreach ($listValues as $key => $list) {
            $id = $list['id'] ?? '';
            if (empty($id)) {
                $mergedLists[] = $list;
                continue;
            }

            $listsById[$id] = $list;
        }

        $mergedLists = array_merge($mergedLists, $listsById);
        return array_values($mergedLists);
    }
}
