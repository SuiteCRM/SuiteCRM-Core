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

use App\Engine\LegacyHandler\LegacyHandler;
use App\Statistics\Entity\Statistic;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;

class SchedulerCronSetupWidgetStatistic extends LegacyHandler implements StatisticsProviderInterface
{

    use StatisticsHandlingTrait;

    public const KEY = 'scheduler-cron-setup-widget';

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
        $runningUser = $this->getRunningUser();

        if (empty($runningUser)) {
            $result = [
                'fields' => [
                    'runningUser' => [
                        'label' => 'Could not determine the user running schedulers',
                        'value' => 'warning'
                    ]
                ]
            ];
            return $this->buildSingleValueResponse(self::KEY, 'string', $result);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $result = $this->getWindowsCronInfo();
            return $this->buildSingleValueResponse(self::KEY, 'string', $result);
        }

        $result = $this->getUnixCronInfo($runningUser);
        return $this->buildSingleValueResponse(self::KEY, 'string', $result);
    }

    protected function getRunningUser(): string
    {
        $this->init();
        $runningUser = getRunningUser();
        $this->close();

        return $runningUser;
    }

    protected function getWindowsCronInfo(): array
    {

        return [
            'fields' => [
                'type' => ['value' => 'Windows'],
                'desc1' => ['labelKey' => 'LBL_CRON_WINDOWS_DESC', 'useLabelAsValue' => true],
                'desc2' => ['labelKey' => 'LBL_CRON_WINDOWS_DESC2', 'useLabelAsValue' => true],
                'desc3' => ['value' => 'cd [path\to\suite\instance]'],
                'desc4' => ['value' => '[path\to\php.exe] [path\to\suite\instance]\bin\console schedulers:run'],
                'desc5' => ['labelKey' => 'LBL_CRON_WINDOWS_DESC3', 'useLabelAsValue' => true],
            ]
        ];
    }

    protected function getUnixCronInfo(string $runningUser): array
    {
        return [
            'fields' => [
                'type' => ['value' => 'Unix'],
                'desc1' => ['labelKey' => 'LBL_CRON_LINUX_DESC1', 'useLabelAsValue' => true],
                'desc2' => ['labelKey' => 'LBL_CRON_LINUX_DESC2', 'useLabelAsValue' => true],
                'desc3' => ['value' => 'sudo crontab -e -u ' . $runningUser],
                'desc4' => ['labelKey' => 'LBL_CRON_LINUX_DESC3', 'useLabelAsValue' => true],
                'desc5' => ['value' => "* * * * *; [path/to/php] [path/to/suite/instance]/bin/console schedulers:run > /dev/null 2>&1"],
                'desc6' => ['labelKey' => 'LBL_CRON_LINUX_DESC5', 'useLabelAsValue' => true],
                'desc7' => ['value' => "* * * * *; [path/to/php] [path/to/suite/instance]/bin/console -e [env] schedulers:run > /dev/null 2>&1"],
                'desc8' => ['labelKey' => 'LBL_CRON_LINUX_DESC6', 'useLabelAsValue' => true],
            ]
        ];
    }
}
