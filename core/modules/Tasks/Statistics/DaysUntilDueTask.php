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


namespace App\Module\Tasks\Statistics;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Service\StatisticsProviderInterface;
use BeanFactory;
use DateFormatService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Task;

/**
 * Class DaysUntilDueTask
 * @package App\Legacy\Statistics
 */
class DaysUntilDueTask extends LegacyHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;

    public const HANDLER_KEY = 'days-until-due-task';
    public const KEY = 'days-until-due-task';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * ListDataHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->moduleNameMapper = $moduleNameMapper;
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

        if ($legacyModuleName !== 'Tasks') {
            return $this->getEmptyResponse(self::KEY);
        }

        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/DateTime/DateFormatService.php';
        $dateFormatService = new DateFormatService();

        $dateDue = $this->getTask($id)->date_due;
        $completed = $this->getTask($id)->status;
        $dbTime = $dateFormatService->toDateTime($dateDue);
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();
        $dateComp = $dateFormatService->toDBDateTime($timestamp);


        $statistic = $this->getDateDiffStatistic(self::KEY, $dateDue, $dateComp);

        if ($completed !== 'Completed') {
            if ($dbTime > $dateTime) {
                $this->addMetadata($statistic, ['labelKey' => 'LBL_DAYS_UNTIL_DUE_TASK', 'endLabelKey' => 'LBL_STAT_DAYS']);
            } else {
                $this->addMetadata($statistic, ['labelKey' => 'LBL_DAYS_OVERDUE', 'endLabelKey' => 'LBL_STAT_DAYS']);
            }
        } else {
            $statistic = $this->getBlankResponse(self::KEY);
            $this->addMetadata($statistic, ['labelKey' => '', 'endLabelKey' => 'LBL_TASK_COMPLETED']);
        }

        $this->close();

        return $statistic;
    }

    /**
     * @param string $id
     * @return Task
     */
    protected function getTask(string $id): Task
    {
        /** @var Task $task */
        $task = BeanFactory::getBean('Tasks', $id);

        return $task;
    }

}
