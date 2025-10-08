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

namespace App\Data\LegacyHandler\FilterMapper;

use App\Engine\LegacyHandler\LegacyHandler;
use DBManagerFactory;
use FilterMapper;

class LegacyFilterMapper extends LegacyHandler
{
    public const HANDLER_KEY = 'legacy-filter-mapper';

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Map Filters to legacy
     * @param array $criteria
     * @param string $type
     * @return array
     */
    public function mapFilters(array $criteria, string $type): array
    {
        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/FilterMapper/FilterMapper.php';
        $filterMapper = new FilterMapper();

        $mapped = $filterMapper->toLegacy($criteria, $type);

        $this->close();

        return $mapped;
    }

    /**
     * Get order by
     * @param array $sort
     * @return string
     */
    public function getOrderBy(array $sort): string
    {
        $this->init();

        $db = DBManagerFactory::getInstance();
        $result = $db->quote($sort['orderBy'] ?? 'date_entered');

        $this->close();
        return $result;
    }

    /**
     * Get sort order
     * @param array $sort
     * @return string
     */
    public function getSortOrder(array $sort): string
    {
        $result = $sort['sortOrder'] ?? 'DESC';

        if (in_array(strtolower($result), ['asc', 'desc'])) {
            return $result;
        }

        return 'DESC';
    }
}
