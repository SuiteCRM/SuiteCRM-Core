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


namespace App\Module\Campaigns\Statistics\Subpanels;

use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Service\StatisticsProviderInterface;
use DateFormatService;

/**
 * Class SubPanelCampaignsLastReceived
 * @package App\Legacy\Statistics
 */
class SubPanelCampaignsLastReceived extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;

    public const KEY = 'campaign-log';

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getData(array $query): Statistic
    {
        $subpanel = $query['key'];
        [$module, $id] = $this->extractContext($query);
        if (empty($module) || empty($id) || empty($subpanel)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $subpanelName = $query['params']['subpanel'] ?? '';
        if (!empty($subpanelName)) {
            $subpanel = $subpanelName;
        }

        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/DateTime/DateFormatService.php';
        $dateFormatService = new DateFormatService();
        $dateNow = date("Y-m-d");
        $campaignsWhere = "campaign_log.activity_date <= '$dateNow'";

        $queries = $this->getQueries($module, $id, $subpanel);
        $parts = $queries[0];
        $parts['select'] = ' SELECT campaign_log.`activity_date` AS `last_received` ';
        if (!empty($parts['where'])) {
            $campaignsWhere = " AND " . $campaignsWhere;
        }
        $parts['where'] .= $campaignsWhere;
        $parts['order_by'] .= 'ORDER BY `last_received` DESC LIMIT 1';
        $innerQuery = $this->joinQueryParts($parts);
        $result = $this->fetchRow($innerQuery);

        if (empty($result)) {
            $statistic = $this->getEmptyResponse(self::KEY);
            $this->close();
            $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_CAMPAIGN_LAST_RECEIVED_TOOLTIP']);
            $this->addMetadata($statistic, ['descriptionKey' => 'LBL_CAMPAIGN_LAST_RECEIVED']);

            return $statistic;
        }
        $result = $result['last_received'];
        $finalDate = $dateFormatService->toDBDate($result);
        $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $finalDate]);

        $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_CAMPAIGN_LAST_RECEIVED_TOOLTIP']);
        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_CAMPAIGN_LAST_RECEIVED']);
        $this->close();


        return $statistic;
    }
}
