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


namespace App\Module\Events\Statistics\Subpanels;

use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Service\StatisticsProviderInterface;

/**
 * Class SubPanelEventsLastDate
 * @package App\Legacy\Statistics
 */
class SubPanelEventsLastDate extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;

    public const KEY = 'events';

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

        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        $dateNow = date("Y-m-d");
        $eventsWhere = " fp_events.`date_end` <= ' $dateNow ' ";

        if ($module === 'leads') {
            $subpanel = 'fp_events_leads_1';
            $queries = $this->getQueries($module, $id, $subpanel);
            $parts = $queries[0];
            $parts['select'] = 'SELECT fp_events.`date_end` AS `leads_date_end`';
            if (!empty($parts['where'])) {
                $eventsWhere = " AND " . $eventsWhere;
            }
            $parts['where'] .= $eventsWhere;
            $parts['order_by'] .= ' ORDER BY `leads_date_end` DESC LIMIT 1';
            $innerQuery = $this->joinQueryParts($parts);
            $leads = $this->fetchRow($innerQuery);
        } elseif ($module === 'contacts') {
            $subpanel = 'fp_events_contacts';
            $queries = $this->getQueries($module, $id, $subpanel);
            $parts = $queries[0];
            $parts['select'] = 'SELECT fp_events.`date_end` AS `contacts_date_end`';
            if (!empty($parts['where'])) {
                $eventsWhere = " AND " . $eventsWhere;
            }
            $parts['where'] .= $eventsWhere;
            $parts['order_by'] .= ' ORDER BY `contacts_date_end` DESC LIMIT 1';
            $innerQuery = $this->joinQueryParts($parts);
            $contacts = $this->fetchRow($innerQuery);
        } else {
            $subpanel = 'fp_events_prospects_1';
            $queries = $this->getQueries($module, $id, $subpanel);
            $parts = $queries[0];
            $parts['select'] = 'SELECT fp_events.`date_end`';
            if (!empty($parts['where'])) {
                $eventsWhere = " AND " . $eventsWhere;
            }
            $parts['where'] .= $eventsWhere;
            $parts['order_by'] .= ' ORDER BY `date_end` DESC LIMIT 1';
            $innerQuery = $this->joinQueryParts($parts);
            $other = $this->fetchRow($innerQuery);
        }


        $date = [];
        $positions = [];
        $i = 0;

        if (!empty($leads['leads_date_end'])) {
            $date[$i] = $leads['leads_date_end'];
            $positions[$date[$i]] = 'leads_date_end';
        }
        if (!empty($contacts['contacts_date_end'])) {
            $date[$i] = $contacts['contacts_date_end'];
            $positions[$date[$i]] = 'contacts_date_end';
        }
        if (!empty($other['date_end'])) {
            $date[$i] = $other['date_end'];
            $positions[$date[$i]] = 'date_end';
        }

        if (empty($date)) {
            $statistic = $this->getEmptyResponse(self::KEY);
            $this->close();
            $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_EVENTS_LAST_DATE_TOOLTIP']);
            $this->addMetadata($statistic, ['descriptionKey' => 'LBL_EVENTS_LAST_DATE']);

            return $statistic;
        }

        $dateValue = $date[0];

        if ('leads_date_end' === $positions[$dateValue]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $dateValue]);
        } elseif ('contacts_date_end' === $positions[$dateValue]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $dateValue]);
        } elseif ('date_end' === $positions[$dateValue]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $dateValue]);
        } else {
            $statistic = $this->getEmptyResponse(self::KEY);
        }
        $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_EVENTS_LAST_DATE_TOOLTIP']);
        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_EVENTS_LAST_DATE']);
        $this->close();


        return $statistic;
    }
}
