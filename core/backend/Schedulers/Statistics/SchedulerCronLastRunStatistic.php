<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Schedulers\Statistics;

use App\Install\Service\CommandLastRunTracker;
use App\Schedulers\LegacyHandler\CronHandler;
use App\Statistics\Entity\Statistic;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;

class SchedulerCronLastRunStatistic implements StatisticsProviderInterface
{

    use StatisticsHandlingTrait;

    public const KEY = 'scheduler-cron-last-run';

    public function __construct(
        protected CommandLastRunTracker $commandLastRunTracker,
        protected CronHandler $cronHandler,
    )
    {
    }

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getData(array $query): Statistic
    {
        $lastRunInfo = $this->commandLastRunTracker->getFormattedLastRunInfo('schedulers:run');

        if (!isset($lastRunInfo['last_run']) || $lastRunInfo['last_run'] === 'Never') {
            $result = [
                'fields' => [
                    'validUser' => ['value' => 'noUser'],
                    'user' => ['value' => 'N/A'],
                    'lastRun' => ['labelKey' => 'LBL_SCHEDULERS_NEVER_RUN', 'useLabelAsValue' => true]
                ]
            ];

            return $this->buildSingleValueResponse(self::KEY, 'string', $result);
        }


        if (!$this->cronHandler->isAllowedCronUser($lastRunInfo['user'])) {
            $result = [
                'fields' => [
                    'validUser' => ['value' => false],
                    'user' => ['value' => $lastRunInfo['user']],
                    'lastRun' => ['value' => $lastRunInfo['last_run']]
                ]
            ];

            return $this->buildSingleValueResponse(self::KEY, 'string', $result);
        }

        $result = [
            'fields' => [
                'validUser' => ['value' => true],
                'user' => ['value' => $lastRunInfo['user']],
                'lastRun' => ['value' => $lastRunInfo['last_run']]
            ]
        ];

        return $this->buildSingleValueResponse(self::KEY, 'string', $result);
    }
}
