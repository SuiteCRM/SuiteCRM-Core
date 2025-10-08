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


namespace App\Statistics\Service;

use App\Statistics\Entity\BatchedStatistics;
use App\Statistics\Entity\Statistic;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class StatisticsManager implements LoggerAwareInterface, StatisticsManagerInterface
{
    /**
     * @var StatisticsProviderRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * StatisticsItemResolver constructor.
     * @param StatisticsProviderRegistry $statisticsProviderRegistry
     */
    public function __construct(StatisticsProviderRegistry $statisticsProviderRegistry)
    {
        $this->registry = $statisticsProviderRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getBatched(string $module, array $queries): ?BatchedStatistics
    {
        $result = [];

        foreach ($queries as $statKey => $query) {
            if (empty($query)) {
                continue;
            }

            if (empty($query['key'])) {
                continue;
            }

            $stat = $this->getStatistic($module, $query);

            if ($stat === null) {
                continue;
            }

            $result[$statKey] = $stat->toArray();
        }

        $batched = new BatchedStatistics();
        $batched->setId($module);
        $batched->setItems($result);

        return $batched;
    }

    /**
     * @inheritDoc
     */
    public function getStatistic(string $module, array $query): ?Statistic
    {

        if (empty($query)) {
            return null;
        }

        $originalKey = $query['key'] ?? 'default';
        $key = $originalKey;

        $moduleKey = $module . '-' . $key;

        if ($this->registry->has($moduleKey)) {
            return $this->callStatistic($originalKey, $moduleKey, $query);
        }

        if (!$this->registry->has($key)) {
            $key = 'default';
        }

        return $this->callStatistic($originalKey, $key, $query);
    }

    /**
     * @param string $originalKey
     * @param string $key
     * @param array $query
     * @return Statistic
     */
    protected function callStatistic(string $originalKey, string $key, array $query): Statistic
    {
        try {
            $result = $this->registry->get($key)->getData($query);
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage(), ['exception' => $t]);
            $result = $this->registry->get('error')->getData($query);
        }

        $result->setId($originalKey);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
