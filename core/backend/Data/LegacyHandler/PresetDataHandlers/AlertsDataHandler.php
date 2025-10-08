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

class AlertsDataHandler extends ListDataHandler implements PresetListDataHandlerInterface
{
    public const HANDLER_KEY = 'alerts-data-handlers';

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
        return 'alerts';
    }

    /**
     * @param string $module
     * @param array $criteria
     * @param int $offset
     * @param int $limit
     * @param array $sort
     * @param string $type
     * @return ListData
     */
    public function fetch(
        string $module,
        array $criteria = [],
        int $offset = -1,
        int $limit = -1,
        array $sort = [],
        string $type = 'advanced'
    ): ListData {

        $this->addScheduledAlerts();

        $criteria = $this->getAlertCriteria($criteria);

        $bean = $this->getBean($module);

        $sort['orderBy'] = $sort['orderBy'] ?? 'snooze';
        $sort['sortOrder'] = $sort['sortOrder'] ?? 'asc';

        $legacyCriteria = $this->mapCriteria($criteria, $sort, $type);

        [$params, $where, $filter_fields] = $this->prepareQueryData($type, $bean, $legacyCriteria);

        global $timedate;
        $now = $timedate->nowDb();

        $where .= " AND (alerts.snooze <= '$now' OR alerts.snooze IS NULL )";

        $resultData = $this->getListDataPort()->get($bean, $where, $offset, $limit, $filter_fields, $params);

        $result = $this->buildListData($resultData);
        $unreadCount = $this->getUnreadCount($module);
        $meta = ['unreadCount' => (int)$unreadCount];
        $result->setMeta($meta);

        return $result;
    }

    /**
     * Get unread count
     * @param string $module
     * @param array $criteria
     * @param string $type
     * @return int
     */
    public function getUnreadCount(
        string $module,
        array $criteria = [],
        string $type = 'advanced'
    ): int {

        $criteria = $this->getAlertCriteria($criteria);

        $criteria['filters']['is_read'] = [
            'field' => 'is_read',
            'fieldType' => 'bool',
            'operator' => '=',
            'values' => ["0", ""]
        ];

        $bean = $this->getBean($module);

        $legacyCriteria = $this->mapCriteria($criteria, [], $type);

        [$params, $where, $filter_fields] = $this->prepareQueryData($type, $bean, $legacyCriteria);

        global $timedate;
        $now = $timedate->nowDb();

        $where .= " AND (alerts.snooze <= '$now' OR alerts.snooze IS NULL )";

        $queryParts = $this->getListDataPort()->getQueryParts($bean, $where, $filter_fields, $params);
        $queryParts['select'] = 'SELECT count(*) as unread';

        $resultData = $this->getListDataPort()->runCustomQuery($bean, $queryParts, -1, -1);

        if (is_numeric($resultData[0]['unread'] ?? '')) {
            return (int)($resultData[0]['unread'] ?? 0);
        }

        return 0;
    }

    /**
     * Get alert criteria
     * @param $criteria
     * @return array
     */
    public function getAlertCriteria($criteria): array
    {

        global $current_user;

        $criteria['filters']['assigned_user_id'] = [
            'field' => 'assigned_user_id',
            'fieldType' => 'relate',
            'operator' => '=',
            'values' => [$current_user->id]
        ];

        return $criteria;

    }

    /**
     * Add Scheduled Alerts
     */
    protected function addScheduledAlerts(): void
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'modules/Alerts/services/AddScheduledAlerts.php';

        $addScheduledAlertsService =  new \AddScheduledAlerts();
        $addScheduledAlertsService->run();
    }
}
