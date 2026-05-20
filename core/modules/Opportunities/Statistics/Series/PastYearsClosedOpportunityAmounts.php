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

namespace App\Module\Opportunities\Statistics\Series;

use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Entity\Statistic;
use App\Statistics\Model\ChartOptions;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use DateInterval;
use DateTime;
use Exception;

class PastYearsClosedOpportunityAmounts extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'accounts-past-years-closed-opportunity-amounts';

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
        [$module, $id] = $this->extractContext($query);
        $subpanel = 'opportunities';

        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        $queries = $this->getQueries($module, $id, $subpanel);

        $years = 5;

        try {
            $yearsList = $this->getPastYearsList($years);
            $nextYear = $this->getNextYear();
        } catch (Exception $exception) {
            return $this->getEmptyResponse(self::KEY);
        }

        $startYear = $yearsList[0];
        $startYearDateString = $startYear . '-01-01';
        $endYear = $yearsList[$years - 1];
        $endYearDateString = $nextYear . '-01-01';

        $statusesList = ['Closed Won', 'Closed Lost'];
        $statuses = implode("' , '", $statusesList);

        $parts = $queries[0];
        $parts['select'] = 'SELECT SUM(opportunities.amount_usdollar) as amount_by_year, EXTRACT(YEAR FROM opportunities.date_closed) as year, opportunities.sales_stage as sales_stage ';
        $parts['where'] .= ' AND opportunities.date_closed is not null ';
        $parts['where'] .= ' AND opportunities.amount_usdollar is not null ';
        $parts['where'] .= ' AND opportunities.sales_stage is not null ';
        $parts['where'] .= " AND opportunities.date_closed >= '$startYearDateString'";
        $parts['where'] .= " AND opportunities.date_closed < '$endYearDateString'";
        $parts['where'] .= " AND opportunities.sales_stage IN ('$statuses') ";
        $parts['group_by'] = ' GROUP BY EXTRACT(YEAR FROM opportunities.date_closed), opportunities.sales_stage ';
        $parts['order_by'] = ' ORDER BY opportunities.sales_stage ';

        $innerQuery = $this->joinQueryParts($parts);

        $result = $this->fetchAll($innerQuery);

        $groupingField = 'sales_stage';
        $nameField = 'year';
        $valueField = 'amount_by_year';


        $series = $this->buildMultiSeries($result, $groupingField, $nameField, $valueField, $yearsList);


        $chartOptions = new ChartOptions();
        $chartOptions->legend = false;
        $chartOptions->yAxisTickFormatting = true;
        $chartOptions->xAxisTicks = $yearsList;
        $chartOptions->xScaleMin = $startYear;
        $chartOptions->xScaleMax = $endYear;

        $statistic = $this->buildSeriesResponse(self::KEY, 'currency', $series, $chartOptions);

        $this->close();

        return $statistic;
    }

    /**
     * @param int $years
     * @return string[]
     * @throws Exception
     */
    protected function getPastYearsList(int $years): array
    {
        $yearsList = [];
        for ($i = 0; $i < $years; $i++) {
            $now = new DateTime();
            $now->sub(new DateInterval('P' . $i . 'Y'));
            array_unshift($yearsList, $now->format('Y'));
        }

        return $yearsList;
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function getNextYear(): string
    {
        $now = new DateTime();
        $now->ADD(new DateInterval('P1Y'));

        return $now->format('Y');
    }
}
