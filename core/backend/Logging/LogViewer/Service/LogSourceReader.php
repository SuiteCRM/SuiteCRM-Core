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
 * Gives the entries of a log from the newest to the oldest, from a position, reading
 * the file (then its rotated files) by blocks as the entries are taken.
 *
 * A position is a file of the log (its name) and where the reading stopped in it; everything
 * after it has been given. The floor is the position where the reading stops (mark point).
 */
class LogSourceReader
{
    public const BLOCK_BYTES = 262144;

    /**
     * A block without any start of entry (an entry on many lines) is read again, this many
     * times larger, up to this many times the size of a block. Beyond, the file is taken as one
     * without entries: every line is one.
     */
    protected const GROWTH = 4;
    protected const MAX_GROWTH = 16;

    /** @var LogEntry[] the entries read and not taken yet, the newest first */
    protected array $buffer = [];
    protected int $index = 0;
    protected int $position = 0;
    protected int $floorIndex;
    protected int $floorPosition = 0;
    protected int $bytesRead = 0;
    protected ?float $time = null;
    protected bool $plain = false;

    /**
     * @param array{file?: mixed, pos?: mixed}|null $position null: the end of the log
     * @param array{file?: mixed, pos?: mixed}|null $floor null: the start of the log
     */
    public function __construct(
        protected LogFileReader $reader,
        protected LogLineParser $parser,
        protected LogSource $source,
        ?array $position = null,
        ?array $floor = null,
        protected int $blockBytes = self::BLOCK_BYTES
    ) {
        clearstatcache();
        $names = array_map('basename', $source->files);
        $last = max(count($names) - 1, 0);

        $this->floorIndex = $last;
        if ($floor !== null) {
            // A floor on a file that is gone (rotation), or after the end of its file (the
            // file was rotated and written again): the newest file is read from its start.
            $index = array_search((string)($floor['file'] ?? ''), $names, true);
            $this->floorIndex = $index === false ? 0 : $index;
            $floorPosition = max((int)($floor['pos'] ?? 0), 0);
            $this->floorPosition = $index === false || $floorPosition > $this->getSize($this->floorIndex) ? 0 : $floorPosition;
        }

        $index = $position === null ? 0 : array_search((string)($position['file'] ?? ''), $names, true);
        if ($index === false) {
            // The file of the position is gone: nothing older is left to read.
            $this->index = $this->floorIndex;
            $this->position = $this->floorPosition;

            return;
        }
        $this->index = $index;
        $size = $this->getSize($index);
        $this->position = $position === null ? $size : min(max((int)($position['pos'] ?? 0), 0), $size);
    }

    /**
     * The next entry (the newest not taken yet), null at the floor.
     */
    public function peek(): ?LogEntry
    {
        while (empty($this->buffer) && !$this->isAtFloor()) {
            $this->fill();
        }

        return $this->buffer[0] ?? null;
    }

    /**
     * Takes the next entry: the position moves to its start.
     */
    public function take(): ?LogEntry
    {
        $entry = $this->peek();
        if ($entry !== null) {
            array_shift($this->buffer);
            $this->position = $entry->offset;
        }

        return $entry;
    }

    public function hasMore(): bool
    {
        return !empty($this->buffer) || !$this->isAtFloor();
    }

    /**
     * @return array{file: string, pos: int}
     */
    public function getPosition(): array
    {
        return ['file' => basename($this->source->files[$this->index] ?? ''), 'pos' => $this->position];
    }

    /**
     * The end of the log: the position a reading starts from, and a mark point is set at.
     *
     * @return array{file: string, pos: int}
     */
    public function getEnd(): array
    {
        return ['file' => basename($this->source->files[0] ?? ''), 'pos' => $this->getSize(0)];
    }

    public function getBytesRead(): int
    {
        return $this->bytesRead;
    }

    /**
     * Whether nothing is left to read: the floor is reached (or the start of the oldest file).
     */
    protected function isAtFloor(): bool
    {
        return empty($this->source->files)
            || $this->index > $this->floorIndex
            || ($this->index === $this->floorIndex && $this->position <= $this->floorPosition);
    }

    /**
     * Where the reading of the current file stops.
     */
    protected function getFloor(): int
    {
        return $this->index === $this->floorIndex ? $this->floorPosition : 0;
    }

    protected function getSize(int $index): int
    {
        $file = $this->source->files[$index] ?? null;

        return $file !== null && is_file($file) ? (int)filesize($file) : 0;
    }

    /**
     * Reads the block before the position into the buffer, or goes to the older file when the
     * current one is read.
     */
    protected function fill(): void
    {
        $floor = $this->getFloor();
        if ($this->position <= $floor) {
            $this->index++;
            $this->position = $this->getSize($this->index);

            return;
        }

        $file = $this->source->files[$this->index];
        $size = $this->blockBytes;
        do {
            $block = $this->reader->readBefore($file, $this->position, $size, $floor);
            $this->bytesRead += min($size, $this->position - $floor);
            // The end of a line longer than the block starts no entry: it is read larger.
            [$entries, $orphans] = $block['partial'] ? [[], $block['lines']] : $this->group($file, $block['lines']);
            $whole = $size >= $this->position - $floor;
            $retry = empty($entries) && !$whole && !$this->plain && $size < $this->blockBytes * self::MAX_GROWTH;
            $size *= self::GROWTH;
        } while ($retry);

        if (empty($block['lines'])) {
            // Nothing could be read (the file is gone, or shorter than it was): it is taken as read.
            $this->position = $floor;

            return;
        }

        $this->plain = empty($entries) && !$whole;
        if (empty($entries)) {
            // No start of entry in the largest block, or in the whole file: a file without
            // entries (or a line longer than the largest block), every line is an entry, and
            // the next blocks are not read larger until one has a start of entry.
            foreach ($orphans as $offset => $line) {
                if (trim($line) !== '') {
                    $entries[] = new LogEntry($file, $offset, null, '', '', '', '', '', '', $line);
                }
            }
            if (empty($entries)) {
                $this->position = $block['start'];

                return;
            }
        } elseif (!empty($orphans) && $whole) {
            // The lines before the first entry of the file follow no entry.
            $offset = array_key_first($orphans);
            array_unshift($entries, new LogEntry($file, $offset, null, '', '', '', '', '', '', implode("\n", $orphans)));
        }
        // Otherwise the lines before the first entry of the block follow an entry of the block
        // before: they are read again with it.

        // An entry without a date is given the one of the entry after it in the file, the
        // newest one the last write of the file.
        $time = $this->time ?? (float)filemtime($file);
        foreach (array_reverse($entries) as $entry) {
            $entry->time ??= $time;
            $time = $entry->time;
            $this->buffer[] = $entry;
        }
        $this->time = $time;
    }

    /**
     * The entries of the lines of a block, in the order of the file, and the lines before the
     * first one (they follow an entry that starts before the block).
     *
     * @param array<int, string> $lines position => line
     * @return array{0: LogEntry[], 1: array<int, string>}
     */
    protected function group(string $file, array $lines): array
    {
        $entries = [];
        $orphans = [];
        $current = null;
        $following = [];

        foreach ($lines as $offset => $line) {
            $entry = $this->parser->parse($file, $offset, $line);
            if ($entry === null) {
                if ($current === null) {
                    $orphans[$offset] = $line;
                } else {
                    $following[] = $line;
                }
                continue;
            }

            if ($current !== null) {
                $entries[] = $this->close($current, $following);
            }
            $current = $entry;
            $following = [];
        }
        if ($current !== null) {
            $entries[] = $this->close($current, $following);
        }

        return [$entries, $orphans];
    }

    /**
     * @param string[] $following the lines that follow the line of the entry
     */
    protected function close(LogEntry $entry, array $following): LogEntry
    {
        if (!empty($following)) {
            $entry->body .= "\n" . implode("\n", $following);
        }

        return $entry;
    }
}
