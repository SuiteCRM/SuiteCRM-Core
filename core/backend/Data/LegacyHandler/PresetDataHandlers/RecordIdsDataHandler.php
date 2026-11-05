<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

namespace App\Data\LegacyHandler\PresetDataHandlers;

use App\Data\LegacyHandler\ListData;
use App\Data\LegacyHandler\ListDataHandler;
use App\Data\LegacyHandler\PresetListDataHandlerInterface;

/**
 * List preset restricting the module list to the given record ids.
 * Used by confirmation list modals, e.g. the possible duplicates found on save.
 */
class RecordIdsDataHandler extends ListDataHandler implements PresetListDataHandlerInterface
{
    public const HANDLER_KEY = 'record-ids-data-handlers';
    public const PRESET_TYPE = 'record-ids';

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::PRESET_TYPE;
    }

    /**
     * @param string $module
     * @param array $criteria
     * @param int $offset
     * @param int $limit
     * @param array $sort
     * @return ListData
     */
    public function fetch(
        string $module,
        array $criteria = [],
        int $offset = -1,
        int $limit = -1,
        array $sort = []
    ): ListData {
        $type = 'advanced';

        $ids = $this->getIds($criteria);
        unset($criteria['preset']);

        $bean = $this->getBean($module);

        $legacyCriteria = $this->mapCriteria($criteria, $sort, $type);

        [$params, $where, $filter_fields] = $this->prepareQueryData($type, $bean, $legacyCriteria);

        $idsClause = '1 = 0';
        if (!empty($ids)) {
            $quotedIds = array_map(static fn(string $id) => $bean->db->quoted($id), $ids);
            $idsClause = "$bean->table_name.id IN (" . implode(', ', $quotedIds) . ')';
        }

        $where = $where === '' ? $idsClause : "($where) AND $idsClause";

        $resultData = $this->getListDataPort()->get($bean, $where, $offset, $limit, $filter_fields, $params);

        return $this->buildListData($resultData);
    }

    /**
     * Get the record ids from the preset params
     * @param array $criteria
     * @return string[]
     */
    public function getIds(array $criteria): array
    {
        $ids = $criteria['preset']['params']['ids'] ?? [];
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_filter($ids, static fn($id) => is_string($id) && $id !== '');

        return array_values(array_unique($ids));
    }
}
