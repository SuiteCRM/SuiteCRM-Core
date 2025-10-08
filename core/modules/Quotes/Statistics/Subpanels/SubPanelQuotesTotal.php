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


namespace App\Module\Quotes\Statistics\Subpanels;

use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use DBManagerFactory;

class SubPanelQuotesTotal extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'quotes';

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
        $subpanel = $query['key'] ?? '';

        $subpanelName = $query['params']['subpanel'] ?? '';
        if (!empty($subpanelName)) {
            $subpanel = $subpanelName;
        }

        if (empty($module) || empty($id) || empty($subpanel)) {
            return $this->getEmptyResponse(self::KEY);
        }


        $this->init();
        $this->startLegacyApp();

        $dateNow = date("Y-m-d");
        global $app_strings;

        $db = DBManagerFactory::getInstance();
        $relateId = $db->quote($id);

        $queries = $this->getQueries($module, $id, $subpanel);
        $parts = $queries[0];
        $parts['select'] = 'SELECT q.`expiration`';
        $parts['from'] = ' FROM aos_quotes as q ';
        $parts['where'] = " WHERE q.`expiration` >= '$dateNow' AND q.deleted = 0  AND (q.billing_account_id = '$relateId' OR q.billing_contact_id = '$relateId') ";
        $parts['order_by'] = ' ORDER BY q.expiration ASC LIMIT 1 ';
        $innerQuery = $this->joinQueryParts($parts);
        $result = $this->fetchRow($innerQuery);

        if (empty($result)) {
            $empty = $app_strings['LBL_NONE_OUTSTANDING'];
            $statistic = $this->buildSingleValueResponse(self::KEY, 'string', ['value' => $empty]);
            $this->close();
            $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_QUOTES_EXPIRY_TOOLTIP']);
            $this->addMetadata($statistic, ['descriptionKey' => 'LBL_QUOTES_EXPIRY']);

            return $statistic;
        }

        $date = $result['expiration'];
        $statistic = $this->buildSingleValueResponse(self::KEY, 'date', ['value' => $date]);
        $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_QUOTES_EXPIRY_TOOLTIP']);
        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_QUOTES_EXPIRY']);

        return $statistic;
    }
}
