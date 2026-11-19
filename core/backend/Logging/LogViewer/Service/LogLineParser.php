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

namespace App\Logging\LogViewer\Service;

use App\Logging\LogViewer\Model\LogEntry;

/**
 * Tells the lines that start an entry of a log, and reads their date and level:
 * Monolog lines (Symfony logs), lines of the legacy logger, and lines that start with a date
 * (other logs). The other lines follow the entry before them (query or trace on several lines).
 */
class LogLineParser
{
    /**
     * [2026-11-12T10:21:30.048556+01:00] request.CRITICAL: message {context} [extra]
     * (Monolog LineFormatter; "[2024-10-21 16:36:34]" in the older files).
     */
    protected const MONOLOG = '/^\[(\d{4}-\d\d-\d\d[T ]\d\d:\d\d:\d\d[^\]]{0,40})\] ([\w.\-]+)\.([A-Z]+): ?/';

    /**
     * Tue Nov 10 2026 at 10:47:28 [27][1][FATAL] message
     * (SugarLogger::formatLog(): date, process id, user id, level; the date format is a setting).
     */
    protected const LEGACY = '/^([^\[\]]{0,80}?) ?\[(\d+)\]\[([^\[\]]{0,64})\]\[([A-Za-z]+)\] ?/';

    /**
     * 2024-11-08 08:55:00...DBG: message
     */
    protected const DATED = '/^\[?(\d{4}-\d\d-\d\d[T ]\d\d:\d\d:\d\d(?:[.,]\d+)?(?:Z|[+-]\d\d:?\d\d)?)\]?/';

    /**
     * The entry a line starts, null when the line does not start one.
     *
     * @param int $offset where the line starts in the file
     */
    public function parse(string $file, int $offset, string $line): ?LogEntry
    {
        $first = $line[0] ?? '';
        if ($first === '') {
            return null;
        }

        if ($first === '[' && preg_match(self::MONOLOG, $line, $match) === 1) {
            return new LogEntry(
                $file,
                $offset,
                $this->toTime($match[1]),
                $match[1],
                $match[3],
                $match[2],
                '',
                '',
                $match[0],
                (string)substr($line, strlen($match[0]))
            );
        }

        if (preg_match(self::LEGACY, $line, $match) === 1) {
            // The date is kept as written: its format and language are settings of the logger.
            return new LogEntry(
                $file,
                $offset,
                null,
                trim($match[1]),
                strtoupper($match[4]),
                '',
                $match[2],
                $match[3],
                $match[0],
                (string)substr($line, strlen($match[0]))
            );
        }

        if (($first === '[' || ctype_digit($first)) && preg_match(self::DATED, $line, $match) === 1) {
            return new LogEntry(
                $file,
                $offset,
                $this->toTime($match[1]),
                $match[1],
                '',
                '',
                '',
                '',
                $match[0],
                (string)substr($line, strlen($match[0]))
            );
        }

        return null;
    }

    /**
     * The timestamp of a date, with its fraction of second (the order of the lines of a same
     * second), null when it is not a date.
     */
    protected function toTime(string $date): ?float
    {
        $time = strtotime(str_replace(',', '.', $date));
        if ($time === false) {
            return null;
        }

        return (float)$time + (preg_match('/:\d\d[.,](\d{1,9})/', $date, $match) === 1 ? (float)('0.' . $match[1]) : 0.0);
    }
}
