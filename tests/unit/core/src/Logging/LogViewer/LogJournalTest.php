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

namespace App\Tests\unit\core\src\Logging\LogViewer;

use App\Logging\LogViewer\Model\LogSource;
use App\Logging\LogViewer\Service\LogFileReader;
use App\Logging\LogViewer\Service\LogJournal;
use App\Logging\LogViewer\Service\LogLineParser;
use App\Logging\LogViewer\Service\LogSourceReader;
use Codeception\Test\Unit;

/**
 * The reading of several logs as one, by steps.
 */
class LogJournalTest extends Unit
{
    protected string $directory;

    protected function _before(): void
    {
        $this->directory = sys_get_temp_dir() . '/log-journal-' . uniqid('', true);
        mkdir($this->directory);
    }

    protected function _after(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    public function testReadsSeveralLogsAsOneByDate(): void
    {
        $main = $this->source('main', [$this->write('main.log', [
            '[2026-11-12T10:00:01.000000+01:00] app.INFO: main 1',
            '[2026-11-12T10:00:03.000000+01:00] app.ERROR: main 3',
            '[2026-11-12T10:00:05.000000+01:00] app.INFO: main 5',
        ])]);
        $security = $this->source('security', [$this->write('security.log', [
            '[2026-11-12T10:00:02.000000+01:00] security.ERROR: security 2',
            '[2026-11-12T10:00:04.000000+01:00] security.ERROR: security 4',
        ])]);

        $result = $this->journal()->read([$main, $security], [], [], '', '', 100);

        static::assertSame(['main 5', 'security 4', 'main 3', 'security 2', 'main 1'], array_column($result['entries'], 'body'));
        static::assertSame(['main', 'security', 'main', 'security', 'main'], array_column($result['entries'], 'source'));
        static::assertFalse($result['more']);
        static::assertSame(
            [
                'source' => 'security',
                'date' => '2026-11-12T10:00:04.000000+01:00',
                'level' => 'ERROR',
                'channel' => 'security',
                'pid' => '',
                'user' => '',
                'head' => '[2026-11-12T10:00:04.000000+01:00] security.ERROR: ',
                'body' => 'security 4',
                'truncated' => false,
            ],
            $result['entries'][1]
        );
    }

    public function testGoesOnFromThePositionsOfTheStepBefore(): void
    {
        $sources = [
            $this->source('main', [$this->write('main.log', [
                '[2026-11-12T10:00:01.000000+01:00] app.INFO: main 1',
                '[2026-11-12T10:00:03.000000+01:00] app.ERROR: main 3',
                '[2026-11-12T10:00:05.000000+01:00] app.INFO: main 5',
            ])]),
            $this->source('security', [$this->write('security.log', [
                '[2026-11-12T10:00:02.000000+01:00] security.ERROR: security 2',
            ])]),
        ];
        $journal = $this->journal();

        $first = $journal->read($sources, [], [], '', '', 2);
        $second = $journal->read($sources, $first['positions'], [], '', '', 2);
        $third = $journal->read($sources, $second['positions'], [], '', '', 2);

        static::assertSame(['main 5', 'main 3'], array_column($first['entries'], 'body'));
        static::assertTrue($first['more']);
        static::assertSame(['security 2', 'main 1'], array_column($second['entries'], 'body'));
        static::assertFalse($second['more']);
        static::assertSame([], $third['entries']);
        static::assertFalse($third['more']);
    }

    public function testAnEntryOnSeveralLinesIsOneEntry(): void
    {
        $source = $this->source('legacy', [$this->write('suitecrm.log', [
            'Tue Nov 10 2026 at 10:47:28 [27][1][FATAL] Query Failed: SELECT *',
            'FROM accounts',
            '',
            'WHERE deleted = 0',
            'Tue Nov 10 2026 at 10:47:29 [31][-none-][ERROR] next',
        ])], LogSource::PART_LEGACY);

        $entries = $this->journal()->read([$source], [], [], '', '', 100)['entries'];

        static::assertCount(2, $entries);
        static::assertSame('next', $entries[0]['body']);
        static::assertSame("Query Failed: SELECT *\nFROM accounts\n\nWHERE deleted = 0", $entries[1]['body']);
        static::assertSame('Tue Nov 10 2026 at 10:47:28 [27][1][FATAL] ', $entries[1]['head']);
        static::assertSame(['Tue Nov 10 2026 at 10:47:28', 'FATAL', '27', '1'], [
            $entries[1]['date'], $entries[1]['level'], $entries[1]['pid'], $entries[1]['user'],
        ]);
    }

    /**
     * Whatever the size of the blocks and of the steps, every entry is read once, whole and in
     * the same order: an entry cut by a block is read again with the block before.
     */
    public function testTheBlocksAndTheStepsDoNotChangeWhatIsRead(): void
    {
        mt_srand(458);
        $lines = [];
        for ($index = 0; $index < 150; $index++) {
            $message = 'entry ' . $index . ' ' . str_repeat('m', mt_rand(0, 150));
            $lines[] = $index % 3 === 0
                ? sprintf('Tue Nov 10 2026 at 10:%02d:%02d [%d][1][FATAL] %s', intdiv($index, 60), $index % 60, $index, $message)
                : sprintf('[2026-11-10T10:%02d:%02d.000000+01:00] app.INFO: %s', intdiv($index, 60), $index % 60, $message);
            for ($following = mt_rand(0, 4); $following > 0; $following--) {
                $lines[] = mt_rand(0, 3) === 0 ? '' : '  following ' . str_repeat('f', mt_rand(0, 40));
            }
        }
        $source = $this->source('mixed', [$this->write('mixed.log', $lines)]);

        $expected = $this->walk($this->journal(), [$source], 500);
        static::assertCount(150, $expected);
        static::assertSame(implode("\n", $lines), implode("\n", array_map(
            static fn (array $row): string => $row['head'] . $row['body'],
            array_reverse($expected)
        )));

        foreach ([64, 100, 257, 1000] as $blockBytes) {
            foreach ([1, 7, 500] as $limit) {
                static::assertSame(
                    $expected,
                    $this->walk($this->journal($blockBytes), [$source], $limit),
                    'blocks of ' . $blockBytes . ' bytes, steps of ' . $limit . ' entries'
                );
            }
        }
    }

    public function testReadsTheRotatedFilesAfterTheLog(): void
    {
        $rotated = $this->write('suitecrm_1.log', [
            'Mon Nov 9 2026 at 09:00:00 [1][1][FATAL] rotated 1',
            'Mon Nov 9 2026 at 09:00:01 [1][1][FATAL] rotated 2',
        ]);
        $current = $this->write('suitecrm.log', [
            'Tue Nov 10 2026 at 10:00:00 [2][1][FATAL] current 1',
            'Tue Nov 10 2026 at 10:00:01 [2][1][FATAL] current 2',
        ]);
        $source = $this->source('legacy', [$current, $rotated], LogSource::PART_LEGACY);
        $journal = $this->journal();

        static::assertSame(
            ['current 2', 'current 1', 'rotated 2', 'rotated 1'],
            array_column($this->walk($journal, [$source], 1), 'body')
        );

        $first = $journal->read([$source], [], [], '', '', 3);
        static::assertSame(['current 2', 'current 1', 'rotated 2'], array_column($first['entries'], 'body'));
        static::assertSame('suitecrm_1.log', $first['positions']['legacy']['file']);
        static::assertTrue($first['more']);
        static::assertSame(['file' => 'suitecrm.log', 'pos' => filesize($current)], $first['ends']['legacy']);
    }

    public function testStopsAtTheMarkPoint(): void
    {
        $file = $this->write('main.log', [
            '[2026-11-12T10:00:01.000000+01:00] app.INFO: before 1',
            '[2026-11-12T10:00:02.000000+01:00] app.INFO: before 2',
        ]);
        $source = $this->source('main', [$file]);
        $journal = $this->journal();

        $mark = $journal->read([$source], [], [], '', '', 100)['ends'];
        static::assertSame([], $journal->read([$source], [], $mark, '', '', 100)['entries']);

        file_put_contents($file, "[2026-11-12T10:00:03.000000+01:00] app.INFO: after 1\n", FILE_APPEND);
        file_put_contents($file, "[2026-11-12T10:00:04.000000+01:00] app.INFO: after 2\n", FILE_APPEND);

        $result = $journal->read([$source], [], $mark, '', '', 100);
        static::assertSame(['after 2', 'after 1'], array_column($result['entries'], 'body'));
        static::assertFalse($result['more']);
        static::assertSame(['after 2', 'after 1'], array_column($this->walk($journal, [$source], 1, $mark), 'body'));
    }

    public function testAMarkPointInARotatedFile(): void
    {
        $older = $this->write('suitecrm_1.log', [
            'Mon Nov 9 2026 at 09:00:00 [1][1][FATAL] older 1',
            'Mon Nov 9 2026 at 09:00:01 [1][1][FATAL] older 2',
        ]);
        $newer = $this->write('suitecrm.log', ['Tue Nov 10 2026 at 10:00:00 [2][1][FATAL] newer 1']);
        $source = $this->source('legacy', [$newer, $older], LogSource::PART_LEGACY);
        $journal = $this->journal();

        // The mark is in the older file of the log (a new file was started since).
        $mark = ['legacy' => ['file' => 'suitecrm_1.log', 'pos' => strlen("Mon Nov 9 2026 at 09:00:00 [1][1][FATAL] older 1\n")]];
        static::assertSame(['newer 1', 'older 2'], array_column($this->walk($journal, [$source], 1, $mark), 'body'));

        // The marked file was renamed by the rotation and a new one written: the new one is
        // read whole, as the legacy viewer did.
        $mark = ['legacy' => ['file' => 'suitecrm.log', 'pos' => 100000]];
        static::assertSame(['newer 1'], array_column($this->walk($journal, [$source], 1, $mark), 'body'));

        // The marked file is gone.
        $mark = ['legacy' => ['file' => 'suitecrm_9.log', 'pos' => 10]];
        static::assertSame(['newer 1'], array_column($this->walk($journal, [$source], 1, $mark), 'body'));
    }

    public function testAPositionInAFileThatIsGoneReadsNothing(): void
    {
        $source = $this->source('main', [$this->write('main.log', ['[2026-11-12T10:00:01.000000+01:00] app.INFO: line'])]);

        $result = $this->journal()->read([$source], ['main' => ['file' => 'gone.log', 'pos' => 10]], [], '', '', 100);

        static::assertSame([], $result['entries']);
        static::assertFalse($result['more']);
    }

    public function testReturnsTheEntriesOfALevelAndAbove(): void
    {
        $symfony = $this->source('main', [$this->write('main.log', [
            '[2026-11-12T10:00:01.000000+01:00] app.DEBUG: debug',
            '[2026-11-12T10:00:02.000000+01:00] app.INFO: info',
            '[2026-11-12T10:00:03.000000+01:00] app.WARNING: warning',
            '2026-11-12 10:00:04 no level',
            '[2026-11-12T10:00:05.000000+01:00] app.ERROR: error',
            '[2026-11-12T10:00:06.000000+01:00] app.CRITICAL: critical',
        ])]);
        $legacy = $this->source('legacy', [$this->write('suitecrm.log', [
            'Tue Nov 10 2026 at 10:00:00 [2][1][WARN] warn',
            'Tue Nov 10 2026 at 10:00:01 [2][1][DEPRECATED] deprecated',
            'Tue Nov 10 2026 at 10:00:02 [2][1][ERROR] error',
            'Tue Nov 10 2026 at 10:00:03 [2][1][FATAL] fatal',
            'Tue Nov 10 2026 at 10:00:04 [2][1][SECURITY] security',
        ])], LogSource::PART_LEGACY);
        $journal = $this->journal();

        static::assertSame(
            ['critical', 'error', 'warning'],
            array_column($journal->read([$symfony], [], [], 'WARNING', '', 100)['entries'], 'body')
        );
        static::assertCount(6, $journal->read([$symfony], [], [], '', '', 100)['entries']);
        static::assertSame(
            ['security', 'fatal', 'error', 'deprecated'],
            array_column($journal->read([$legacy], [], [], 'deprecated', '', 100)['entries'], 'body')
        );
        static::assertSame(
            ['security', 'fatal'],
            array_column($journal->read([$legacy], [], [], 'FATAL', '', 100)['entries'], 'body')
        );
    }

    public function testSearchesTheWholeEntryWhateverTheCase(): void
    {
        $source = $this->source('legacy', [$this->write('suitecrm.log', [
            'Tue Nov 10 2026 at 10:00:00 [2][1][FATAL] Query Failed: SELECT *',
            'FROM accounts',
            'Tue Nov 10 2026 at 10:00:01 [2][1][FATAL] Échec de la connexion',
            'Tue Nov 10 2026 at 10:00:02 [2][1][FATAL] other',
        ])], LogSource::PART_LEGACY);
        $journal = $this->journal();

        $found = $journal->read([$source], [], [], '', 'from ACCOUNTS', 100)['entries'];
        static::assertCount(1, $found);
        static::assertStringStartsWith('Query Failed', $found[0]['body']);
        static::assertSame(
            ['Échec de la connexion'],
            array_column($journal->read([$source], [], [], '', 'éCHEC', 100)['entries'], 'body')
        );
        static::assertSame(['other'], array_column($journal->read([$source], [], [], 'fatal', '[fatal] OTHER', 100)['entries'], 'body'));
        static::assertSame([], $journal->read([$source], [], [], '', 'missing', 100)['entries']);
    }

    public function testAFileWithoutEntriesIsReadByLine(): void
    {
        $small = $this->source('small', [$this->write('small.log', ['alpha', '', 'beta', 'gamma'])]);
        $entries = $this->journal()->read([$small], [], [], '', '', 100)['entries'];
        static::assertSame(['gamma', 'beta', 'alpha'], array_column($entries, 'body'));
        static::assertSame(['', '', ''], array_column($entries, 'head'));

        // A file larger than the largest block read to find a start of entry.
        $lines = [];
        for ($index = 0; $index < 80; $index++) {
            $lines[] = 'line ' . $index . ' ' . str_repeat('x', 30);
        }
        $large = $this->source('large', [$this->write('large.log', $lines)]);
        static::assertSame(
            array_reverse($lines),
            array_column($this->walk($this->journal(64), [$large], 7), 'body')
        );
    }

    public function testALineLongerThanTheLargestBlockIsReadInPieces(): void
    {
        $long = '[2026-11-12T10:00:02.000000+01:00] app.INFO: ' . str_repeat('x', 3000);
        $source = $this->source('main', [$this->write('main.log', [
            '[2026-11-12T10:00:01.000000+01:00] app.INFO: first',
            $long,
            '[2026-11-12T10:00:03.000000+01:00] app.INFO: last',
            'following the last',
        ])]);

        // Blocks of 64 bytes, read again up to 1024 bytes when they have no start of entry.
        $entries = $this->walk($this->journal(64), [$source], 3);
        $pieces = array_slice($entries, 1, -1);

        static::assertSame("last\nfollowing the last", $entries[0]['body']);
        static::assertSame('first', $entries[count($entries) - 1]['body']);
        static::assertGreaterThan(1, count($pieces));
        static::assertSame($long, implode('', array_map(
            static fn (array $row): string => $row['head'] . $row['body'],
            array_reverse($pieces)
        )));
    }

    public function testCutsALongEntryAndReturnsValidText(): void
    {
        $source = $this->source('main', [$this->write('main.log', [
            '[2026-11-12T10:00:01.000000+01:00] app.ERROR: ' . str_repeat('a', 40000),
            "[2026-11-12T10:00:02.000000+01:00] app.ERROR: broken \xC3\x28 text",
        ])]);

        $entries = $this->journal()->read([$source], [], [], '', '', 100)['entries'];

        static::assertFalse($entries[0]['truncated']);
        static::assertNotFalse(json_encode($entries));
        static::assertStringStartsWith('broken ', $entries[0]['body']);
        static::assertTrue($entries[1]['truncated']);
        static::assertSame(32768, strlen($entries[1]['body']));
    }

    public function testAStepReadsABoundedNumberOfBytes(): void
    {
        $lines = ['[2026-11-12T09:00:00.000000+01:00] app.INFO: the needle'];
        for ($index = 0; $index < 200; $index++) {
            $lines[] = sprintf('[2026-11-12T10:%02d:%02d.000000+01:00] app.INFO: hay %d', intdiv($index, 60), $index % 60, $index);
        }
        $source = $this->source('main', [$this->write('main.log', $lines)]);
        // Blocks of 1 KB, steps of 2 KB at most, for a file of about 11 KB.
        $journal = $this->journal(1024, 2048);

        $first = $journal->read([$source], [], [], '', 'needle', 100);
        static::assertSame([], $first['entries']);
        static::assertTrue($first['more']);
        static::assertLessThanOrEqual(3072, $first['scanned']);
        static::assertLessThan(filesize($source->files[0]), $first['positions']['main']['pos']);

        $steps = 0;
        $found = [];
        $positions = [];
        do {
            $result = $journal->read([$source], $positions, [], '', 'needle', 100);
            $positions = $result['positions'];
            $found = array_merge($found, array_column($result['entries'], 'body'));
            $steps++;
        } while ($result['more'] && $steps < 100);

        static::assertSame(['the needle'], $found);
        static::assertGreaterThan(3, $steps);
        static::assertLessThan(100, $steps);
    }

    public function testReadsNothingWithoutLog(): void
    {
        $result = $this->journal()->read([], [], [], '', '', 100);

        static::assertSame([], $result['entries']);
        static::assertFalse($result['more']);

        $empty = $this->source('empty', [$this->write('empty.log', [])]);
        $result = $this->journal()->read([$empty], [], [], '', '', 100);
        static::assertSame([], $result['entries']);
        static::assertFalse($result['more']);
        static::assertSame(['empty' => ['file' => 'empty.log', 'pos' => 0]], $result['ends']);
    }

    protected function journal(int $blockBytes = LogSourceReader::BLOCK_BYTES, int $scanBytes = LogJournal::SCAN_BYTES): LogJournal
    {
        return new LogJournal(new LogFileReader(), new LogLineParser(), $blockBytes, $scanBytes);
    }

    /**
     * @param string[] $lines
     */
    protected function write(string $name, array $lines): string
    {
        $path = $this->directory . '/' . $name;
        file_put_contents($path, empty($lines) ? '' : implode("\n", $lines) . "\n");

        return $path;
    }

    /**
     * @param string[] $files
     */
    protected function source(string $key, array $files, string $part = LogSource::PART_SYMFONY): LogSource
    {
        return new LogSource($key, $part, $key, $files);
    }

    /**
     * All the entries of the logs, read by steps.
     *
     * @param LogSource[] $sources
     */
    protected function walk(LogJournal $journal, array $sources, int $limit, array $floors = []): array
    {
        $entries = [];
        $positions = [];
        $steps = 0;
        do {
            $result = $journal->read($sources, $positions, $floors, '', '', $limit);
            $positions = $result['positions'];
            $entries = array_merge($entries, $result['entries']);
        } while ($result['more'] && ++$steps < 10000);

        return $entries;
    }
}
