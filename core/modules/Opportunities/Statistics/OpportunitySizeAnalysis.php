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


namespace App\Module\Opportunities\Statistics;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Data\LegacyHandler\SecurityFiltersTrait;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Entity\Statistic;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use BeanFactory;
use Doctrine\DBAL\DBALException;
use Opportunity;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class OpportunitySizeAnalysis extends SubpanelDataQueryHandler implements StatisticsProviderInterface, LoggerAwareInterface
{
    use StatisticsHandlingTrait;
    use SecurityFiltersTrait;

    public const KEY = 'opportunity-size-analysis';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PreparedStatementHandler
     */
    private $queryHandler;

    /**
     * LeadDaysOpen constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param PreparedStatementHandler $preparedStatementHandler
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        PreparedStatementHandler $preparedStatementHandler,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState,
            $moduleNameMapper, $session);
        $this->queryHandler = $preparedStatementHandler;
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
        [$module, $id] = $this->extractContext($query);

        if (empty($module) || empty($id)) {
            return $this->closeAndReturnEmpty();
        }

        if ($module !== 'opportunities') {
            $this->logger->error('OpportunitySizeAnalysis: incorrect module specified in context: ' . $module);

            return $this->closeAndReturnEmpty();
        }

        $this->init();
        $this->startLegacyApp();

        $bean = $this->getOpportunity($id);

        if ($bean === null) {
            $this->logger->error('OpportunitySizeAnalysis: Unable to load opportunity bean with id: ' . $id);

            return $this->closeAndReturnEmpty();
        }

        $queryStatuses = $this->getQuerySalesStages($bean);

        try {
            $result = $this->runQuery($bean, $id, $queryStatuses);
        } catch (DBALException $e) {
            $this->logger->error(
                'OpportunitySizeAnalysis: exception executing query',
                [
                    'exception' => $e
                ]
            );

            return $this->closeAndReturnEmpty();
        }

        if (empty($result)) {
            return $this->closeAndReturnEmpty();
        }

        $statistic = $this->buildNumberResult($result, 'int');

        $this->close();

        return $statistic;
    }

    /**
     * @return Statistic
     */
    protected function closeAndReturnEmpty(): Statistic
    {
        $statistic = $this->getEmptyResponse(self::KEY);
        $this->close();

        return $statistic;
    }

    /**
     * @param $id
     * @return Opportunity|null
     */
    protected function getOpportunity($id): ?Opportunity
    {
        /** @var Opportunity $bean */
        $bean = BeanFactory::getBean('Opportunities', $id);

        if ($bean === false) {
            $bean = null;
        }

        return $bean;
    }

    /**
     * @param Opportunity|null $bean
     * @return array|String[]
     */
    protected function getQuerySalesStages(?Opportunity $bean): array
    {
        if ($bean === null) {
            return [];
        }

        $closedStatuses = $this->getClosedSalesStages();
        $openStatuses = $this->getOpenSalesStages();

        $queryStatuses = $openStatuses;
        if (in_array($bean->sales_stage, $closedStatuses, true)) {
            $queryStatuses = $closedStatuses;
        }

        return $queryStatuses;
    }

    /**
     * @return String[]
     */
    protected function getClosedSalesStages(): array
    {
        return ['Closed Won', 'Closed Lost'];
    }

    /**
     * @return String[]
     */
    protected function getOpenSalesStages(): array
    {
        return [
            'Prospecting',
            'Qualification',
            'Needs Analysis',
            'Value Proposition',
            'Id. Decision Makers',
            'Perception Analysis',
            'Proposal/Price Quote',
            'Negotiation/Review'
        ];
    }

    /**
     * @param SugarBean $bean
     * @param string $id
     * @param array $statuses
     * @return mixed|false
     * @throws DBALException
     */
    protected function runQuery(SugarBean $bean, string $id, array $statuses)
    {
        $params = [
            'id' => $id,
        ];
        $binds = [];

        $securityWhereClause = $this->addSecurityWhereClause($bean, '', 'T2');

        if (!empty($securityWhereClause)) {
            $securityWhereClause = ' AND ' . $securityWhereClause;
        }

        $statusClause = '';
        if (!empty($statuses)) {
            $statusClause = " AND T2.sales_stage IN ('" . implode("','", $statuses) . "')";
        }

        $queryString = "
            SELECT (
                SELECT COUNT(*)
                   FROM opportunities T2
                   WHERE T2.assigned_user_id = T1.assigned_user_id
                   AND T2.deleted = '0' AND T1.deleted = '0'
                     AND (
                         T2.amount_usdollar > T1.amount_usdollar OR
                         (
                             T2.amount_usdollar = T1.amount_usdollar AND
                             T2.date_entered < T1.date_entered
                         )
                     )
                     $statusClause
                     $securityWhereClause
            ) + 1 AS value
            FROM opportunities T1
            WHERE T1.id = :id
            ORDER BY T1.amount_usdollar DESC;
        ";

        return $this->queryHandler->fetch(
            $queryString,
            $params,
            $binds
        );
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
