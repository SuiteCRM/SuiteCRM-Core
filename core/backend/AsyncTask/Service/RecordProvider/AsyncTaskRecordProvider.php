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

namespace App\AsyncTask\Service\RecordProvider;

use App\Data\Service\RecordListProviderInterface;

class AsyncTaskRecordProvider
{
    public function __construct(
        protected RecordListProviderInterface $recordListProvider
    ) {
    }

    /**
     * Fetch record IDs from task options, supporting both explicit ID lists and criteria-based queries.
     *
     * @param array $options Task options containing 'module' and either 'ids' or 'criteria'+'sort'
     * @param int $offset Current enqueue offset
     * @param int $batchSize Maximum number of IDs to return
     * @return array Flat array of record ID strings
     */
    public function fetchRecordIds(array $options, int $offset, int $batchSize): array
    {
        $ids = $options['ids'] ?? [];

        if (!empty($ids) && is_array($ids)) {
            return array_values(array_slice($ids, $offset, $batchSize));
        }

        $criteria = $options['criteria'] ?? [];
        if (empty($criteria)) {
            return [];
        }

        $module = $options['module'] ?? '';
        $sort = $options['sort'] ?? [];

        $recordList = $this->recordListProvider->getList($module, $criteria, $offset, $batchSize, $sort);
        $records = $recordList->getRecords() ?? [];

        $recordIds = [];
        foreach ($records as $record) {
            $id = $record['id'] ?? '';
            if (!empty($id)) {
                $recordIds[] = $id;
            }
        }

        return $recordIds;
    }
}
