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


namespace App\Module\Leads\Statistics;

use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\AuditQueryingTrait;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Service\StatisticsProviderInterface;
use BeanFactory;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lead;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadDaysOpen extends LegacyHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;
    use AuditQueryingTrait;

    public const HANDLER_KEY = 'lead-days-open';
    public const KEY = 'lead-days-open';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ListDataHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        EntityManagerInterface $entityManager,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
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
     * @throws Exception
     */
    public function getData(array $query): Statistic
    {
        [$module, $id] = $this->extractContext($query);

        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);

        if ($legacyModuleName !== 'Leads') {
            return $this->getEmptyResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        $lead = $this->getLead($id);
        $rows = $this->getAuditInfo($lead);


        $start = $lead->date_entered;

        if (!empty($rows) && !empty($rows[$this->getRecycledStatus()])) {
            $start = $rows[$this->getRecycledStatus()]['last_update'] ?? $lead->date_entered;
        }

        $end = null;

        if ($this->inClosedStatus($lead)) {
            $end = $lead->date_entered;
            if (!empty($rows)) {
                $end = $rows[$lead->status]['last_update'] ?? $lead->date_entered;
            }
        }

        $statistic = $this->getDateDiffStatistic(self::KEY, $start, $end);

        $this->close();

        return $statistic;
    }

    /**
     * @param string $id
     * @return Lead
     */
    protected function getLead(string $id): Lead
    {
        /** @var Lead $lead */
        $lead = BeanFactory::getBean('Leads', $id);

        return $lead;
    }

    /**
     * @param SugarBean $bean
     * @return array
     * @throws DBALException
     */
    protected function getAuditInfo(SugarBean $bean): array
    {
        return $this->queryAuditInfo($this->entityManager, $bean, 'status');
    }

    /**
     * @return string
     */
    protected function getRecycledStatus(): string
    {
        return 'Recycled';
    }

    /**
     * @param Lead $lead
     * @return bool
     */
    protected function inClosedStatus(Lead $lead): bool
    {
        return $lead->status === 'Converted' || $lead->status === 'Dead';
    }
}
