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

use App\Logging\LogViewer\Service\LogLineParser;
use Codeception\Test\Unit;

/**
 * The lines that start an entry of a log, and what is read from them.
 */
class LogLineParserTest extends Unit
{
    protected LogLineParser $parser;

    protected function _before(): void
    {
        $this->parser = new LogLineParser();
    }

    public function testReadsAMonologLine(): void
    {
        $line = '[2026-11-12T10:21:30.048556+01:00] request.CRITICAL: Uncaught PHP Exception {"exception":"[object]"} []';
        $entry = $this->parser->parse('/logs/prod.log', 120, $line);

        static::assertNotNull($entry);
        static::assertSame('/logs/prod.log', $entry->file);
        static::assertSame(120, $entry->offset);
        static::assertSame('2026-11-12T10:21:30.048556+01:00', $entry->date);
        static::assertSame('request', $entry->channel);
        static::assertSame('CRITICAL', $entry->level);
        static::assertSame('[2026-11-12T10:21:30.048556+01:00] request.CRITICAL: ', $entry->head);
        static::assertSame('Uncaught PHP Exception {"exception":"[object]"} []', $entry->body);
        static::assertSame($line, $entry->getText());
        static::assertEqualsWithDelta(strtotime('2026-11-12T10:21:30+01:00') + 0.048556, $entry->time, 0.000001);
    }

    public function testReadsTheMonologLinesOfTheOlderFiles(): void
    {
        // No fraction of second nor time zone, and a channel with a dot.
        $entry = $this->parser->parse('f', 0, '[2024-11-08 08:46:33] install.log.INFO: Starting Server Checks [] []');

        static::assertNotNull($entry);
        static::assertSame('2024-11-08 08:46:33', $entry->date);
        static::assertSame('install.log', $entry->channel);
        static::assertSame('INFO', $entry->level);
        static::assertSame('Starting Server Checks [] []', $entry->body);
        static::assertSame((float)strtotime('2024-11-08 08:46:33'), $entry->time);
    }

    public function testOrdersTheLinesOfASameSecond(): void
    {
        $first = $this->parser->parse('f', 0, '[2026-11-12T10:21:30.048556+01:00] app.INFO: first');
        $second = $this->parser->parse('f', 0, '[2026-11-12T10:21:30.357432+01:00] app.INFO: second');

        static::assertGreaterThan($first->time, $second->time);
    }

    public function testReadsALineOfTheLegacyLogger(): void
    {
        $line = 'Tue Nov 10 2026 at 10:47:28 [27][1][FATAL] Email address save error';
        $entry = $this->parser->parse('f', 0, $line);

        static::assertNotNull($entry);
        static::assertSame('Tue Nov 10 2026 at 10:47:28', $entry->date);
        static::assertSame('27', $entry->pid);
        static::assertSame('1', $entry->user);
        static::assertSame('FATAL', $entry->level);
        static::assertSame('', $entry->channel);
        static::assertSame('Email address save error', $entry->body);
        static::assertSame($line, $entry->getText());
        // The date is kept as written: its format is a setting of the logger.
        static::assertNull($entry->time);
    }

    public function testReadsTheUserOfALegacyLine(): void
    {
        $none = $this->parser->parse('f', 0, 'Wed Nov 11 2026 at 15:53:00 [234183][-none-][FATAL] Could not connect to DB server');
        $guid = $this->parser->parse('f', 0, '11/17/26 16:02:11 [12][6a1b5c3e-0f4d-4e2a-9c1d-5e46ce7531b0][warn] Slow query');

        static::assertSame('-none-', $none->user);
        static::assertSame('6a1b5c3e-0f4d-4e2a-9c1d-5e46ce7531b0', $guid->user);
        static::assertSame('11/17/26 16:02:11', $guid->date);
        static::assertSame('WARN', $guid->level);
    }

    public function testALineThatStartsWithADateStartsAnEntry(): void
    {
        $line = '2024-11-08 08:55:00...DBG: SugarThemeRegistry::getDefault';
        $entry = $this->parser->parse('f', 0, $line);

        static::assertNotNull($entry);
        static::assertSame('2024-11-08 08:55:00', $entry->date);
        static::assertSame('', $entry->level);
        static::assertSame($line, $entry->getText());
        static::assertSame((float)strtotime('2024-11-08 08:55:00'), $entry->time);
    }

    /**
     * @dataProvider followingLines
     */
    public function testTheOtherLinesFollowTheEntryBeforeThem(string $line): void
    {
        static::assertNull($this->parser->parse('f', 0, $line));
    }

    public static function followingLines(): array
    {
        return [
            'empty line' => [''],
            'query on several lines' => ['LEFT JOIN email_addresses `accounts:email_addresses_primary` ON 1 = 1'],
            'stack trace' => ['#0 /var/www/html/public/legacy/include/utils.php(1234): sugar_die()'],
            'brackets that are not a date' => ['[object] (RuntimeException(code: 0): failed at /var/www/html/a.php:30)'],
            'legacy line without process id' => ['Tue Nov 10 2026 at 10:47:28 [FATAL] message'],
            'print_r of an array' => ['    [0] => Array'],
        ];
    }
}
