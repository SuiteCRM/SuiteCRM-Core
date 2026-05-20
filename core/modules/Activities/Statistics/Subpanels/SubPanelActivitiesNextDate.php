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


namespace App\Module\Activities\Statistics\Subpanels;

use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Service\StatisticsProviderInterface;


/**
 * Class SubPanelActivitiesNextDate
 * @package App\Legacy\Statistics
 */
class SubPanelActivitiesNextDate extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;

    public const KEY = 'activities';

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

        $dateNow = date("Y-m-d");

        $queries = $this->getQueries($module, $id, $subpanel);

        $result = $this->calculateQueryResult($queries, $dateNow);

        $min = $this->calculateSmallestDate(
            $result
        );

        if (empty($min)) {
            $statistic = $this->getEmptyResponse(self::KEY);
            $this->close();
            $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_ACTIVITIES_NEXT_DATE_TOOLTIP']);
            $this->addMetadata($statistic, ['descriptionKey' => 'LBL_ACTIVITIES_NEXT_DATE']);

            return $statistic;
        }

        $statistic = $this->buildStatistic($min);
        $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_ACTIVITIES_NEXT_DATE_TOOLTIP']);
        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_ACTIVITIES_NEXT_DATE']);
        $this->close();

        return $statistic;
    }

    /**
     * @param string $min
     * @return Statistic
     */
    protected function buildStatistic(string $min): Statistic
    {
        if (!empty($min)) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $min]);
        } else {
            $statistic = $this->getEmptyResponse(self::KEY);
        }

        return $statistic;
    }

    /**
     * @param array $result
     * @return string
     */
    protected function calculateSmallestDate(array $result): string
    {
        foreach ($result as $key => $value) {
            if (!empty($value)) {
                $key = array_key_first($value);
                $date[] = $value[$key];
            }
        }
        if (empty($date)) {
            return '';
        }

        return min($date);
    }

    /**
     * @param $queries
     * @param  $dateNow
     * @return array
     */
    protected function calculateQueryResult($queries, $dateNow): array
    {
        $result = [];
        for ($i = 0; $i <= 3; $i++) {
            if (!isset($queries[$i])) {
                continue;
            }

            $parts = $queries[$i];

            if (!$parts['select']) {
                continue;
            }

            $table = explode(" ", $parts['select']);
            $tableName = explode(".", $table[3]);
            $tableName = $tableName[0];

            $parts['select'] = "SELECT " . $tableName . ".`date_start` AS `" . $tableName . "_date_start`";
            $where = "" . $tableName . ".`date_start` >= '$dateNow' ";
            if (!empty($parts['where'])) {
                $where = " AND " . $where;
            }
            $parts['where'] .= $where;
            $parts['order_by'] .= " ORDER BY `" . $tableName . "_date_start` ASC LIMIT 1";
            $innerQuery = $this->joinQueryParts($parts);
            $result[$i] = $this->fetchRow($innerQuery);
        }


        return $result;
    }
}
