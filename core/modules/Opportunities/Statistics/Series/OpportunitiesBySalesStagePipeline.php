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

use App\Data\LegacyHandler\ListDataQueryHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Entity\Statistic;
use App\Statistics\Model\ChartOptions;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use App\Languages\Service\LanguageManagerInterface;
use BeanFactory;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class OpportunitiesBySalesStagePipeline extends LegacyHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'opportunities-by-sales-stage-price';

    /**
     * OpportunitiesBySalesStagePipeline constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ListDataQueryHandler $queryHandler
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
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
        RequestStack $session,
        protected LanguageManagerInterface $languageManager
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
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

        if (empty($module) || $module !== 'opportunities') {
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

        $query = $this->generateQuery($query);

        $result = $this->runQuery($query, $bean);

        $series = $this->buildSingleSeries(
            $result,
            'sales_stage',
            'amount_usdollar',
        );

        foreach ($series->singleSeries as $seriesItem) {
            $seriesItem->name = $this->languageManager->getListLabel(
                'opportunities',
                'sales_stage',
                $seriesItem->name
            );
        }

        $chartOptions = new ChartOptions();
        $chartOptions->yAxisTickFormatting = true;

        $statistic = $this->buildSeriesResponse(self::KEY, 'currency', $series, $chartOptions);

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

    /**
     * @param array $query
     * @return array
     */
    protected function generateQuery(array $query): array
    {
        $query['select'] = 'SELECT opportunities.sales_stage, SUM(opportunities.amount_usdollar) as amount_usdollar';
        $query['where'] .= ' AND opportunities.amount_usdollar is not null AND opportunities.sales_stage is not null ';
        $query['order_by'] = '';
        $query['group_by'] = ' GROUP BY opportunities.sales_stage DESC';

        return $query;
    }
}
