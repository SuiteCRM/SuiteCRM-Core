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

/**
 * Reads a log file by blocks, from its end: a file is never loaded whole (the Symfony
 * logs are not rotated).
 */
class LogFileReader
{
    /**
     * The lines of the block of a file that ends at a position. A line cut by the start of the
     * block is left out, to be read with the block before. A block without any line break is
     * the end of a line longer than it: it is returned as one line, marked as partial.
     *
     * @param int $end where the block ends (bytes), a start of line or the end of the file
     * @param int $size the size of the block at most (bytes)
     * @param int $floor where the reading stops, a start of line: no block starts before
     * @return array{start: int, lines: array<int, string>, partial: bool} where the first line
     *     starts ($end when none), the lines by the position they start at, without their line
     *     break, and whether the only line returned is the end of a longer one
     */
    public function readBefore(string $path, int $end, int $size, int $floor = 0): array
    {
        $start = max($floor, $end - $size, 0);
        $empty = ['start' => $end, 'lines' => [], 'partial' => false];
        if ($end <= $start) {
            return $empty;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return $empty;
        }
        try {
            $buffer = stream_get_contents($handle, $end - $start, $start);
        } finally {
            fclose($handle);
        }
        if (!is_string($buffer) || $buffer === '') {
            return $empty;
        }

        if (str_ends_with($buffer, "\n")) {
            $buffer = substr($buffer, 0, -1);
        }
        $partial = false;
        if ($start > $floor) {
            $break = strpos($buffer, "\n");
            $partial = $break === false;
            if (!$partial) {
                $start += $break + 1;
                $buffer = substr($buffer, $break + 1);
            }
        }

        $lines = [];
        $offset = $start;
        foreach (explode("\n", $buffer) as $line) {
            $lines[$offset] = rtrim($line, "\r");
            $offset += strlen($line) + 1;
        }

        return ['start' => $start, 'lines' => $lines, 'partial' => $partial];
    }
}
