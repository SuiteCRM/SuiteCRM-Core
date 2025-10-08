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

namespace App\DateTime\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use DateFormatService;
use Symfony\Component\HttpFoundation\RequestStack;

class DateTimeHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'date-time';
    /**
     * @var array
     */
    private $datetimeFormatMap;

    /**
     * @var DateFormatService
     */
    private $formatter;

    /**
     * SystemConfigHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param array $datetimeFormatMap
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        array $datetimeFormatMap,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->datetimeFormatMap = $datetimeFormatMap;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Map Datetime format
     * @param string $format
     * @return string
     */
    public function mapFormat(string $format): string
    {
        return strtr($format, $this->datetimeFormatMap);
    }

    /**
     * To user date format
     * @param string $dateString
     * @return string
     */
    public function toUserDate(string $dateString): string
    {
        return $this->getFormatter()->toUserDate($dateString);
    }

    /**
     * To user date format
     * @param string $dateString
     * @return string
     */
    public function toUserDateTime(string $dateString): string
    {
        return $this->getFormatter()->toUserDateTime($dateString);
    }

    /**
     * To user date format
     * @param string $dateString
     * @return string
     */
    public function toDBDateTime(string $dateString): string
    {
        return $this->getFormatter()->toDBDateTime($dateString);
    }

    /**
     * From string format to datetime object
     * @param string $dateString
     * @return \SugarDateTime
     */
    public function toDateTime(string $dateString): \SugarDateTime
    {
        return $this->getFormatter()->toDateTime($dateString);
    }

    /**
     * @return DateFormatService
     */
    protected function getFormatter(): DateFormatService
    {
        if ($this->formatter !== null) {
            return $this->formatter;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/DateTime/DateFormatService.php';
        $this->formatter = new DateFormatService();

        return $this->formatter;
    }

    public function getDateTime(): \TimeDate
    {
        $this->init();

        global $timedate;

        $this->close();

        return $timedate;
    }
}
