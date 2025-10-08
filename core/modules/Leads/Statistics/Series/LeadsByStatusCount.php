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

namespace App\Module\Leads\Statistics\Series;

use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\ListDataQueryHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use App\Statistics\Model\ChartOptions;
use App\Module\Service\ModuleNameMapperInterface;
use App\Languages\Service\LanguageManagerInterface;
use BeanFactory;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadsByStatusCount extends LegacyHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'leads-by-status-count';

    /**
     * LeadsByStatusCount constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ListDataQueryHandler $queryHandler
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $requestStack
     * @param LanguageManagerInterface $languageManager
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        protected ListDataQueryHandler $queryHandler,
        protected ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $requestStack,
        protected LanguageManagerInterface $languageManager
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $requestStack);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return $this->getKey();
    }

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
        [$module, $id, $criteria, $sort] = $this->extractContext($query);

        if (empty($module) || $module !== 'leads') {
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        $legacyName = $this->moduleNameMapper->toLegacy($module);

        $bean = $this->getBean($legacyName);

        if (!$bean instanceof SugarBean) {
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $query = $this->queryHandler->getQuery($bean, $criteria, $sort);
        $query['select'] = 'SELECT leads.status, count(*) as value';
        $query['order_by'] = '';
        $query['group_by'] = ' GROUP BY leads.status ';

        $result = $this->runQuery($query, $bean);

        $series = $this->buildSingleSeries(
            $result,
            'status',
            'value',
        );

        foreach ($series->singleSeries as $seriesItem) {
            $seriesItem->name = $this->languageManager->getListLabel(
                'leads',
                'status',
                $seriesItem->name
            );
        }

        $chartOptions = new ChartOptions();

        $statistic = $this->buildSeriesResponse(self::KEY, 'int', $series, $chartOptions);

        $this->close();

        return $statistic;
    }

    /**
     * @param string $legacyName
     * @return bool|SugarBean
     */
    protected function getBean(string $legacyName)
    {
        return BeanFactory::newBean($legacyName);
    }

    /**
     * @param array $query
     * @param $bean
     * @return array
     */
    protected function runQuery(array $query, $bean): array
    {
        // send limit -2 to not add a limit
        return $this->queryHandler->runQuery($bean, $query, -1, -2);
    }
}
