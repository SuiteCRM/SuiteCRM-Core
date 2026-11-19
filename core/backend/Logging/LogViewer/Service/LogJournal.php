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
use App\Logging\LogViewer\Model\LogSource;

/**
 * Reads several logs as one: their entries by date, the newest first, a part at a
 * time. A request reads a bounded number of bytes and returns the position reached in every
 * log, so the next one goes on from there (older entries, or a search through a large file).
 */
class LogJournal
{
    /** The entries returned by a request at most. */
    public const MAX_ENTRIES = 500;

    public const SCAN_BYTES = 8388608;

    /** The text returned by a request, after which it returns what it found. */
    protected const TEXT_BYTES = 1048576;

    /** The text returned for one entry at most. */
    protected const ENTRY_BYTES = 32768;

    /**
     * The levels of Monolog and of the legacy logger, by severity.
     */
    protected const LEVELS = [
        'DEBUG' => 100,
        'INFO' => 200,
        'NOTICE' => 250,
        'WARN' => 300,
        'WARNING' => 300,
        'DEPRECATED' => 350,
        'ERROR' => 400,
        'FATAL' => 500,
        'CRITICAL' => 500,
        'ALERT' => 550,
        'SECURITY' => 580,
        'EMERGENCY' => 600,
    ];

    /**
     * @param int $blockBytes the size of the blocks the files are read by
     * @param int $scanBytes the bytes read by a request, after which it returns what it found
     */
    public function __construct(
        protected LogFileReader $reader,
        protected LogLineParser $parser,
        protected int $blockBytes = LogSourceReader::BLOCK_BYTES,
        protected int $scanBytes = self::SCAN_BYTES
    ) {
    }

    /**
     * The entries of the logs before the given positions, the newest first.
     *
     * @param LogSource[] $sources
     * @param array<string, array> $positions source key => position reached by the request
     *     before ({file, pos}); a log without one is read from its end
     * @param array<string, array> $floors source key => position where the reading stops (mark
     *     point); a log without one is read to its start
     * @param string $level the lowest level returned, '' for all the entries
     * @param string $search text the entries returned hold (whatever the case), '' for all
     * @return array{entries: array[], positions: array, ends: array, more: bool, scanned: int}
     *     positions: where the request stopped, to send to go on; ends: the ends of the logs
     *     (mark point); more: whether entries are left to read; scanned: bytes read
     */
    public function read(array $sources, array $positions, array $floors, string $level, string $search, int $limit): array
    {
        $limit = min(max($limit, 1), self::MAX_ENTRIES);
        $rank = self::LEVELS[strtoupper($level)] ?? 0;

        /** @var array<string, LogSourceReader> $readers */
        $readers = [];
        foreach ($sources as $source) {
            $readers[$source->key] = new LogSourceReader(
                $this->reader,
                $this->parser,
                $source,
                is_array($positions[$source->key] ?? null) ? $positions[$source->key] : null,
                is_array($floors[$source->key] ?? null) ? $floors[$source->key] : null,
                $this->blockBytes
            );
        }

        $ends = [];
        foreach ($readers as $key => $reader) {
            $ends[$key] = $reader->getEnd();
        }

        $entries = [];
        $text = 0;
        $scanned = 0;
        while (count($entries) < $limit && $text < self::TEXT_BYTES && $scanned < $this->scanBytes) {
            $newest = null;
            foreach ($readers as $key => $reader) {
                $entry = $reader->peek();
                if ($entry !== null && ($newest === null || $entry->time > $readers[$newest]->peek()->time)) {
                    $newest = $key;
                }
            }
            if ($newest === null) {
                break;
            }

            $entry = $readers[$newest]->take();
            if ($this->matches($entry, $rank, $search)) {
                $row = $this->toRow($newest, $entry);
                $text += strlen($row['head']) + strlen($row['body']);
                $entries[] = $row;
            }

            $scanned = 0;
            foreach ($readers as $reader) {
                $scanned += $reader->getBytesRead();
            }
        }

        $result = ['entries' => $entries, 'positions' => [], 'ends' => $ends, 'more' => false, 'scanned' => $scanned];
        foreach ($readers as $key => $reader) {
            $result['positions'][$key] = $reader->getPosition();
            $result['more'] = $result['more'] || $reader->hasMore();
        }

        return $result;
    }

    /**
     * Whether an entry is of the level or above (never when it has no known level), and holds
     * the text.
     */
    protected function matches(LogEntry $entry, int $rank, string $search): bool
    {
        if ($rank > 0 && (self::LEVELS[strtoupper($entry->level)] ?? 0) < $rank) {
            return false;
        }
        if ($search === '') {
            return true;
        }

        return preg_match('/[^\x00-\x7F]/', $search) === 1
            ? mb_stripos(mb_scrub($entry->getText(), 'UTF-8'), $search, 0, 'UTF-8') !== false
            : stripos($entry->getText(), $search) !== false;
    }

    /**
     * An entry as returned to the page: head and body make the lines of the file again. A long
     * body is cut, and the text is made valid UTF-8 (a log can hold anything).
     *
     * @return array<string, string|bool>
     */
    protected function toRow(string $source, LogEntry $entry): array
    {
        $truncated = strlen($entry->body) > self::ENTRY_BYTES;

        return [
            'source' => $source,
            'date' => mb_scrub($entry->date, 'UTF-8'),
            'level' => $entry->level,
            'channel' => $entry->channel,
            'pid' => $entry->pid,
            'user' => mb_scrub($entry->user, 'UTF-8'),
            'head' => mb_scrub($entry->head, 'UTF-8'),
            'body' => mb_scrub($truncated ? substr($entry->body, 0, self::ENTRY_BYTES) : $entry->body, 'UTF-8'),
            'truncated' => $truncated,
        ];
    }
}
