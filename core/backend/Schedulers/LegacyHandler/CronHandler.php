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

namespace App\Schedulers\LegacyHandler;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\Entity\Record;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Languages\LegacyHandler\AppStringsHandler;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Symfony\Component\HttpFoundation\RequestStack;

class CronHandler extends LegacyHandler
{
    protected ?array $intervalParsed = null;

    public const HANDLER_KEY = 'cron-handler';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected SystemConfigHandler $systemConfigHandler,
        protected AppStringsHandler $appStringsHandler,
        protected UserHandler $userHandler
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    public function getRunningUser(): ?string
    {
        $this->init();
        $user = getRunningUser();
        $this->close();

        if (empty($user)) {
            return null;
        }

        return $user;
    }

    public function getAllowedUsers(): array
    {
        return $this->systemConfigHandler->getSystemConfig('cron')?->getItems()['allowed_cron_users'] ?? [];
    }

    public function isAllowedCronUser(string $user = null): bool
    {
        if ($user === null){
            $user = $this->getRunningUser();
        }

        if ($user === null) {
            return false;
        }

        $users = $this->getAllowedUsers();

        $allowed = false;

        foreach ($users as $key => $value) {
            if (trim($value) === $user) {
                $allowed = true;
                break;
            }
        }

        return $allowed;
    }

    /**
     * Calculate the job interval
     * @param Record $record
     * @param string $field
     * @param string $value
     * @return void
     */
    public function calculateJobInterval(Record $record, string $field, string $value): void
    {
        $appStrings = $this->appStringsHandler->getAppStrings($this->userHandler->getCurrentLanguage())->getItems();

        if ($this->intervalParsed === null) {
            $this->parseInterval($value);
        }

        $ints = $this->intervalParsed;
        $tempInt = '';
        $iteration = '';

        foreach ($ints['raw'] as $key => $interval) {
            if ($tempInt !== $iteration) {
                $tempInt .= '; ';
            }
            $iteration = $tempInt;

            if ($interval === '*' || $interval === '*/1') {
                continue;
            }

            $tempInt .= $this->processInterval($interval, $key, $ints, $appStrings, $tempInt, $iteration);
        }

        if (empty($tempInt)) {
            $readable = $appStrings['LBL_OFTEN'];
        } else {
            $tempInt = rtrim(trim($tempInt), ';');
            $readable = $tempInt;
        }

        $attributes = $record->getAttributes();
        $attributes[$field] = $readable;

        $record->setAttributes($attributes);
        $this->intervalParsed = null;
    }

    /**
     *  takes the serialized interval string and renders it into an array
     */
    public function parseInterval(string $interval): void
    {
        $whitespaceChars = [' ', '\r', '\t'];
        $emptyReplacements = ['', '', ''];

        $intervalString = $interval;
        $rawValues = explode('::', $intervalString);
        $rawProcessed = str_replace($whitespaceChars, $emptyReplacements, $rawValues); // strip all whitespace

        $hours = $rawValues[1] . ':::' . $rawValues[0];
        $months = $rawValues[3] . ':::' . $rawValues[2];

        $parsedInterval = [
            'raw' => $rawProcessed,
            'hours' => $hours,
            'months' => $months,
        ];

        $this->intervalParsed = $parsedInterval;
    }


    public function handleIntervalType($type, $value, $mins, $hours): ?string
    {
        $appStrings = $this->appStringsHandler->getAppStrings($this->userHandler->getCurrentLanguage())->getItems();

        $days = [
            1 => $appStrings['LBL_MON'],
            2 => $appStrings['LBL_TUE'],
            3 => $appStrings['LBL_WED'],
            4 => $appStrings['LBL_THU'],
            5 => $appStrings['LBL_FRI'],
            6 => $appStrings['LBL_SAT'],
            0 => $appStrings['LBL_SUN'],
            '*' => $appStrings['LBL_ALL']
        ];
        switch ($type) {
            case 0: // minutes
                return $this->handleMinutesInterval($value, $hours, $appStrings);

            case 1: // hours
                return $this->handleHoursInterval($value, $mins, $appStrings);

            case 2: // day of month
                return $this->handleDayOfMonthInterval($value);

            case 3: // months
                return $this->handleMonthsInterval($value);

            case 4: // days of week
                return $days[$value] ?? null;

            default:
                return 'bad'; // no condition to touch this branch
        }
    }

    protected function handleMinutesInterval(string $value, $hours, array $appStrings): string
    {
        if ($value === '0') {
            return trim($appStrings['LBL_ON_THE']) . ' ' . $appStrings['LBL_HOUR_SING'];
        }

        if (!preg_match('/[^0-9]/', (string)$hours) && !preg_match('/[^0-9]/', (string)$value)) {
            return '';
        }

        if (preg_match('/\*\//', (string)$value)) {
            $value = str_replace('*/', '', (string)$value);
            return $value . ' ' . $appStrings['LBL_MINUTES'];
        }

        if (!preg_match('[^0-9]', (string)$value)) {
            return $appStrings['LBL_ON_THE'] . $value . $appStrings['LBL_MIN_MARK'];
        }

        return $value;
    }

    protected function handleHoursInterval(string $value, $mins, array $appStrings): string
    {
        global $current_user;

        if (preg_match('/\*\//', (string)$value)) {
            $value = str_replace('*/', '', (string)$value);
            return $value . $appStrings['LBL_HOUR'];
        }

        if (preg_match('/[^0-9]/', (string)$mins)) {
            return $value;
        }

        $datef = $current_user->getUserDateTimePreferences();
        return date($datef['time'], strtotime($value . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT)));
    }

    protected function handleDayOfMonthInterval(string $value): string
    {
        if (preg_match('/\*/', (string)$value)) {
            return $value;
        }

        return date('jS', strtotime('December ' . $value));
    }

    protected function handleMonthsInterval(string $value): string
    {
        return date('F', strtotime('2005-' . $value . '-01'));
    }

    /**
     * Process individual interval based on its type
     */
    protected function processInterval(string $interval, int $key, array $ints, array $appStrings, string $tempInt, string $iteration): string
    {
        if (str_contains($interval, ',')) {
            return $this->processCommaInterval($interval, $key, $ints, $appStrings, $tempInt, $iteration);
        }

        if (str_contains($interval, '-')) {
            return $this->processRangeInterval($interval, $key, $ints, $appStrings);
        }

        if (str_contains($interval, '*/')) {
            return $this->processWildcardInterval($interval, $key, $ints, $appStrings);
        }

        return $this->handleIntervalType($key, $interval, $ints['raw'][0], $ints['raw'][1]);
    }

    /**
     * Process comma-separated intervals
     */
    protected function processCommaInterval(string $interval, int $key, array $ints, array $appStrings, string $tempInt, string $iteration): string
    {
        $result = '';
        $exIndiv = explode(',', $interval);

        foreach ($exIndiv as $val) {
            if (str_contains($interval, '-')) {
                $result .= $this->processRangeValues($val, $key, $ints, $appStrings);
            } else {
                if ($result !== '' && $tempInt !== $iteration) {
                    $result .= $appStrings['LBL_AND'];
                }
                $result .= $this->handleIntervalType($key, $val, $ints['raw'][0], $ints['raw'][1]);
            }
        }

        return $result;
    }

    /**
     * Process range intervals (e.g., 1-5)
     */
    protected function processRangeInterval(string $interval, int $key, array $ints, array $appStrings): string
    {
        $exRange = explode('-', $interval);
        $result = $appStrings['LBL_FROM'] . ' ';
        $initial = $result;

        foreach ($exRange as $val) {
            if ($result === $initial) {
                $result .= $this->handleIntervalType($key, $val, $ints['raw'][0], $ints['raw'][1]);
                $result .= ' ' . $appStrings['LBL_RANGE'] . ' ';
            } else {
                $result .= $this->handleIntervalType($key, $val, $ints['raw'][0], $ints['raw'][1]);
            }
        }

        return $result;
    }


    protected function processWildcardInterval(string $interval, int $key, array $ints, array $appStrings): string
    {
        return $appStrings['LBL_EVERY'] . ' ' . $this->handleIntervalType($key, $interval, $ints['raw'][0], $ints['raw'][1]);
    }

    /**
     * Process range values within comma-separated intervals
     */
    protected function processRangeValues(string $val, int $key, array $ints, array $appStrings): string
    {
        $exRange = explode('-', $val);
        $rangeResult = '';

        foreach ($exRange as $valRange) {
            if ($rangeResult !== '') {
                $rangeResult .= $appStrings['LBL_AND'];
            }
            $rangeResult .= $this->handleIntervalType($key, $valRange, $ints['raw'][0], $ints['raw'][1]);
        }

        return $rangeResult;
    }

}
