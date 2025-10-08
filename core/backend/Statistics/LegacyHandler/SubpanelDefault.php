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


namespace App\Statistics\LegacyHandler;

use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Statistics\Entity\Statistic;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class SubpanelDefault extends SubpanelDataQueryHandler implements StatisticsProviderInterface, LoggerAwareInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'default';

    /**
     * @var LoggerInterface
     */
    protected $logger;

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

        $count = 0;
        $subpanel = $query['params']['subpanel'] ?? $query['key'];

        [$module, $id] = $this->extractContext($query);
        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        $queries = $this->getQueries($module, $id, $subpanel);

        if (empty($queries)) {
            $this->logger->error('default-statistic: No queries found.', ['query' => $query]);

            return $this->getErrorResponse(self::KEY);
        }

        if (!empty($queries['isDatasourceFunction'])) {
            $dbQuery = $queries['isDatasourceFunction']['countQuery'];
            $result = $this->fetchRow($dbQuery);
            foreach ($result as $i => $value) {
                $count = $value;
            }
        } else {
            foreach($queries as $query) {
                $parts = $query;
                $parts['select'] = 'SELECT COUNT(*) as value';
                $dbQuery = $this->joinQueryParts($parts);
                $result = $this->fetchRow($dbQuery);
                $count = $count + $result['value'];
            }
        }

        if ($count === 0){
            $count = "0";
        }

        $result['value'] = $count;

        $statistic = $this->buildSingleValueResponse(self::KEY, 'int', $result);

        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_DEFAULT_TOTAL']);
        $this->close();

        return $statistic;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
