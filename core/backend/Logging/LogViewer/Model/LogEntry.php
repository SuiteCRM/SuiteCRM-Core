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

namespace App\Logging\LogViewer\Model;

/**
 * An entry of a log: the line that starts it, split in its head (date, level...) and
 * its body, with the lines that follow it up to the next entry.
 */
class LogEntry
{
    /**
     * @param string $file the path of the file the entry is in
     * @param int $offset where the entry starts in the file (bytes)
     * @param float|null $time the date of the entry (timestamp), null when its line has none
     * @param string $date the date as written
     * @param string $level the level as written
     * @param string $channel the channel (Monolog lines)
     * @param string $pid the process id (legacy lines)
     * @param string $user the user id (legacy lines)
     * @param string $head the start of the line, up to the message
     * @param string $body the message, and the following lines of the entry
     */
    public function __construct(
        public readonly string $file,
        public readonly int $offset,
        public ?float $time = null,
        public readonly string $date = '',
        public readonly string $level = '',
        public readonly string $channel = '',
        public readonly string $pid = '',
        public readonly string $user = '',
        public readonly string $head = '',
        public string $body = ''
    ) {
    }

    public function getText(): string
    {
        return $this->head . $this->body;
    }
}
