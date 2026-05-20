<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Module\Campaigns\Statistics;

use App\Data\LegacyHandler\ListDataQueryHandler;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Entity\Statistic;
use App\Statistics\Model\ChartOptions;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use BeanFactory;
use Psr\Log\LoggerInterface;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class CampaignSendStatus extends LegacyHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'campaign-send-status';

    protected ListDataQueryHandler $queryHandler;
    protected ModuleNameMapperInterface $moduleNameMapper;


    /**
     * CampaignSendStatus constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ListDataQueryHandler $queryHandler
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     * @param PreparedStatementHandler $preparedStatementHandler
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ListDataQueryHandler $queryHandler,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->queryHandler = $queryHandler;
        $this->moduleNameMapper = $moduleNameMapper;
    }

    public function getHandlerKey(): string
    {
        return self::KEY;
    }

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getData(array $query): Statistic
    {
        [$module, $id, $criteria, $sort] = $this->extractContext($query);

        $allowedModules = [
            'email-marketing',
            'campaigns'
        ];

        if (empty($module) || !in_array($module, $allowedModules, true)) {
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $activities = [
            'track_queue' => 'LBL_MESSAGE_QUEUE_TITLE',
            'targeted' => 'LBL_LOG_ENTRIES_TARGETED_TITLE',
            'blocked' => 'LBL_LOG_ENTRIES_BLOCKED_TITLE',
            'sent error' => 'LBL_LOG_ENTRIES_SEND_ERROR_TITLE',
            'invalid email' => 'LBL_LOG_ENTRIES_INVALID_EMAIL_TITLE',
        ];

        $emailMarketingId = null;

        $this->init();
        $this->startLegacyApp();

        $legacyName = $this->moduleNameMapper->toLegacy($module);
        $bean = BeanFactory::newBean($legacyName);

        if ($module === 'email-marketing') {
            $emailMarketingId = $id;
            $id = $this->getCampaignId($id);
        }

        if (!$bean instanceof SugarBean) {
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $query = $this->queryHandler->getQuery($bean, $criteria, $sort);

        $query = $this->generateQuery($query, $id, $activities, $emailMarketingId);

        $result = $this->runQuery($query, $bean);

        $queueCount = $this->getMessageQueueCount($emailMarketingId, $id);

        $result[] = [
            'activity_type' => 'track_queue',
            'hits' => $queueCount
        ];

        $nameField = 'activity_type';
        $valueField = 'hits';

        $parsedResult = [];

        foreach ($activities as $activityKey => $activityLabel) {
            if (empty($parsedResult[$activityKey])) {
                $parsedResult[$activityKey] = [
                    'activity_type' => $activityKey,
                    'hits' => 0
                ];
            }

            foreach ($result as $key => $row) {
                if ($activityKey === $row['activity_type'] || str_starts_with($row['activity_type'], $activityKey)) {
                    $parsedResult[$activityKey] = $parsedResult[$activityKey] ?? [
                        'activity_type' => $activityKey,
                        'hits' => 0
                    ];
                    $hits = $parsedResult[$activityKey]['hits'] ?? 0;
                    $parsedResult[$activityKey]['hits'] = $hits + (int)($row['hits'] ?? 0);
                }
            }
        }

        $series = $this->buildSingleSeries($parsedResult, $nameField, $valueField, $activities, [], true);

        $chartOptions = new ChartOptions();

        $statistic = $this->buildSeriesResponse(self::KEY, 'int', $series, $chartOptions);

        $this->close();

        return $statistic;
    }


    public function generateQuery(array $query, string $id, array $activities, string $emailMarketingId = null): array
    {
        global $db;

        $id = $db->quote($id);

        $query['select'] = "SELECT activity_type, count(*) hits ";
        $query['from'] = " FROM campaign_log ";
        $query['where'] = " WHERE campaign_id = '$id' AND archived=0 AND deleted=0 ";

        $typeClauses = [];
        foreach ($activities as $key => $label) {
            $typeClauses[] = " activity_type like '" . $db->quote($key) . "%'";
        }

        if (!empty($typeClauses)) {
            $query['where'] .= " AND (" . implode(" OR ", $typeClauses) . ") ";
        }

        if ($emailMarketingId === null) {
            $query['where'] .= " AND is_test_entry = 0";
        }

        $query['group_by'] = " GROUP BY  activity_type";
        $query['order_by'] = " ORDER BY  activity_type";

        if ($emailMarketingId !== null) {
            $emailMarketingId = $db->quote($emailMarketingId);
            $query['where'] .= " AND marketing_id ='$emailMarketingId'";
        }

        return $query;
    }

    /**
     * @param array $query
     * @param $bean
     * @return array
     */
    protected function runQuery(array $query, $bean): array
    {
        return $this->queryHandler->runQuery($bean, $query, -1, -2);
    }

    protected function getCampaignId(string $id): string
    {
        global $db;
        $id = $db->quote($id);

        $bean = BeanFactory::newBean('Campaigns');
        $query = [];
        $query['select'] = 'SELECT campaign_id';
        $query['from'] = ' FROM email_marketing';
        $query['where'] = " WHERE id = '$id'";

        $result = $this->runQuery($query, $bean);

        $campaignId = null;

        foreach ($result as $row) {
            $campaignId = $row['campaign_id'];
        }

        return $campaignId;
    }

    /**
     * @param string |null $emailMarketingId
     * @param string $id
     * @return int|mixed
     */
    protected function getMessageQueueCount(?string $emailMarketingId, string $id): mixed
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->select('COUNT(*) as count')
                     ->from('emailman', 'e')
                     ->where('e.deleted = 0')
                     ->andWhere('e.campaign_id = :campaignId');

        if ($emailMarketingId !== null) {
            $queryBuilder->andWhere('e.marketing_id = :emailMarketingId');
            $queryBuilder->setParameter('emailMarketingId', $emailMarketingId);
        }
        $queryBuilder->setParameter('campaignId', $id);

        $queueResult = [];
        try {
            $queueResult = $queryBuilder->fetchOne();
        } catch (\Doctrine\DBAL\Exception  $e) {
            $this->logger->error('CampaignSendStatus::getQueueCount query failed | ' . $e->getMessage());
        }

        return $queueResult ?? 0;
    }

}
