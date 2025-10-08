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


namespace App\Data\LegacyHandler\PresetDataHandlers;

use App\Data\LegacyHandler\BaseListDataHandler;
use App\Data\LegacyHandler\ListData;
use App\Data\LegacyHandler\ListDataHandlerInterface;
use App\Data\LegacyHandler\PresetListDataHandlerInterface;

class MultiModuleDataHandler extends BaseListDataHandler implements ListDataHandlerInterface, PresetListDataHandlerInterface
{
    public const HANDLER_KEY = 'multi-module';


    /**
     * @return string
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }


    /**
     * @return string
     */
    public function getType(): string
    {
        return self::HANDLER_KEY;
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

        $relatedModules = $criteria['preset']['params']['relatedModules'] ?? [];

        $resultData = [];
        foreach ($relatedModules as $relatedModule) {
            $bean = $this->getBean($relatedModule);

            $legacyCriteria = $this->mapCriteria($criteria, $sort, $type);

            [$params, $where, $filter_fields] = $this->prepareQueryData($type, $bean, $legacyCriteria);

            $relatedModuleResultData = $this->getListDataPort()->get($bean, $where, $offset, $limit, $filter_fields, $params);
            if (empty($relatedModuleResultData)) {
                $resultData = $relatedModuleResultData;
                continue;
            }

            $resultData['data'] = array_merge($resultData['data'] ?? [], $relatedModuleResultData['data'] ?? []);
        }

        return $this->buildListData($resultData);
    }

    /**
     * @param array $resultData
     * @return ListData
     */
    protected function buildListData(array $resultData): ListData
    {
        $listData = new ListData();
        $records = $this->recordMapper->mapRecords($resultData['data'] ?? [], $resultData['pageData'] ?? []);
        $listData->setRecords($records);
        $listData->setOrdering($resultData['pageData']['ordering'] ?? []);
        if (isset($resultData['pageData']['offsets']['total']) && is_numeric($resultData['pageData']['offsets']['total'])) {
            $resultData['pageData']['offsets']['total'] = (int)$resultData['pageData']['offsets']['total'];
        }
        $listData->setOffsets($resultData['pageData']['offsets'] ?? []);

        return $listData;
    }
}
