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
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Entity\Statistic;
use App\Statistics\Model\ChartOptions;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use BeanFactory;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class CampaignResponseByRecipientType extends LegacyHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'campaign-response-by-recipient-activity';

    protected ListDataQueryHandler $queryHandler;
    protected ModuleNameMapperInterface $moduleNameMapper;


    /**
     * CampaignResponseByRecipientType constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ListDataQueryHandler $queryHandler
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
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
        protected RecordProviderInterface $recordProvider
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
            'targeted' => 'LBL_LOG_ENTRIES_TARGETED_TITLE',
            'viewed' => 'LBL_LOG_ENTRIES_VIEWED_TITLE',
            'link' => 'LBL_LOG_ENTRIES_LINK_TITLE',
            'removed' => 'LBL_LOG_ENTRIES_REMOVED_TITLE',
        ];

        $emailMarketingId = null;

        $this->init();
        $this->startLegacyApp();

        $allowsDuplicates = true;

        if ($module === 'email-marketing') {
            $emailMarketingId = $id;
            $allowsDuplicates = $this->allowsDuplicates($emailMarketingId);
            $id = $this->getCampaignId($id);
        }

        if (empty($id)){
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $legacyName = $this->moduleNameMapper->toLegacy($module);
        $bean = BeanFactory::newBean($legacyName);

        if (!$bean instanceof SugarBean) {
            return $this->getEmptySeriesResponse(self::KEY);
        }

        $query = $this->queryHandler->getQuery($bean, $criteria, $sort);

        $query = $this->generateQuery($query, $id, $activities, $emailMarketingId);

        $result = $this->runQuery($query, $bean);

        $parsedResult = [];
        $linkEmails = [];
        foreach ($activities as $activityKey => $activityLabel) {
            if (empty($parsedResult[$activityKey])) {
                $parsedResult[$activityKey] = [
                    'activity_type' => $activityKey,
                    'hits' => 0
                ];
            }

            foreach ($result as $key => $row) {

                $isLink = $row['activity_type'] === 'link';
                $email = $row['more_information'];

                if ($isLink && in_array($email, $linkEmails, true)) {
                    continue;
                }

                if ($activityKey === $row['activity_type'] || str_starts_with($row['activity_type'], $activityKey)) {
                    $parsedResult[$activityKey] = $parsedResult[$activityKey] ?? [
                        'activity_type' => $activityKey,
                        'hits' => 0
                    ];

                    $hits = $parsedResult[$activityKey]['hits'] ?? 0;

                    if ($isLink && !$allowsDuplicates) {
                        $linkEmails[] = $email;
                    }

                    $parsedResult[$activityKey]['hits'] = $hits + 1;
                }
            }
        }

        $nameField = 'activity_type';
        $valueField = 'hits';

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

        $query['select'] = "SELECT *";
        $query['from'] = " FROM campaign_log ";
        $query['where'] = " WHERE campaign_id = '$id' AND archived=0 AND deleted=0 ";

        $typeClauses = [];
        foreach ($activities as $key => $label) {
            $typeClauses[] = " activity_type like '" . $db->quote($key) . "%'";
        }

        if (!empty($typeClauses)) {
            $query['where'] .= " AND (" . implode(" OR ", $typeClauses) . ") ";
        }

        if ($emailMarketingId === null){
            $query['where'] .= " AND is_test_entry = 0";
        }

        $query['order_by'] = "";

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

        $campaignId = '';

        foreach ($result as $row) {
            $campaignId = $row['campaign_id'];
        }

        return $campaignId;
    }

    protected function allowsDuplicates(string $emailMarketingId): bool
    {
        $record = $this->recordProvider->getRecord('EmailMarketing', $emailMarketingId);

        return $record->getAttributes()['duplicate'] === 'record';
    }
}
