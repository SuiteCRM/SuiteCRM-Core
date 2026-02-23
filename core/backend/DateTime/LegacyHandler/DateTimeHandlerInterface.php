<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

interface DateTimeHandlerInterface
{
    /**
     * Map Datetime format
     * @param string $format
     * @return string
     */
    public function mapFormat(string $format): string;

    /**
     * To user date format
     * @param string $dateString
     * @return string
     */
    public function toUserDate(string $dateString): string;

    /**
     * To user datetime format
     * @param string $dateString
     * @return string
     */
    public function toUserDateTime(string $dateString): string;

    /**
     * To DB datetime format
     * @param string $dateString
     * @return string
     */
    public function toDBDateTime(string $dateString): string;

    /**
     * From string format to datetime object
     * @param string $dateString
     * @return \SugarDateTime
     */
    public function toDateTime(string $dateString): \SugarDateTime;

    /**
     * Get the legacy TimeDate instance
     * @return \TimeDate
     */
    public function getDateTime(): \TimeDate;

    /**
     * Get the current datetime as a DB-format string (UTC, Y-m-d H:i:s).
     * @return string
     */
    public function nowDb(): string;
}
