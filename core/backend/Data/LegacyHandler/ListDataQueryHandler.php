<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Data\LegacyHandler;

use SugarBean;

class ListDataQueryHandler extends BaseListDataHandler
{
    /**
     * @param SugarBean $bean
     * @param array $criteria
     * @param array $sort
     * @return array
     */
    public function getQuery(SugarBean $bean, array $criteria = [], array $sort = []): array
    {
        $type = 'advanced';

        $legacyCriteria = $this->mapCriteria($criteria, $sort, $type);

        [$params, $where, $filter_fields] = $this->prepareQueryData($type, $bean, $legacyCriteria);

        return $this->getListDataPort()->getQueryParts($bean, $where, $filter_fields, $params);
    }

    /**
     * @param SugarBean $bean
     * @param array $queryParts
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function runQuery(SugarBean $bean, array $queryParts = [], $offset = -1, $limit = -1): array
    {
        return $this->getListDataPort()->runCustomQuery($bean, $queryParts, $offset, $limit);
    }
}
