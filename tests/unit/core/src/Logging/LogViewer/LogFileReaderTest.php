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

use App\Logging\LogViewer\Service\LogFileReader;
use Codeception\Test\Unit;

/**
 * The reading of a log file by blocks, from its end.
 */
class LogFileReaderTest extends Unit
{
    protected LogFileReader $reader;
    protected string $file;

    protected function _before(): void
    {
        $this->reader = new LogFileReader();
        $this->file = tempnam(sys_get_temp_dir(), 'log-reader-');
    }

    protected function _after(): void
    {
        @unlink($this->file);
    }

    public function testReadsTheLinesBeforeAPositionWithWhereTheyStart(): void
    {
        file_put_contents($this->file, "one\ntwo\nthree\n");

        static::assertSame(
            ['start' => 0, 'lines' => [0 => 'one', 4 => 'two', 8 => 'three'], 'partial' => false],
            $this->reader->readBefore($this->file, 14, 100)
        );
        static::assertSame(['start' => 0, 'lines' => [0 => 'one', 4 => 'two'], 'partial' => false], $this->reader->readBefore($this->file, 8, 100));
    }

    public function testLeavesOutTheLineCutByTheStartOfTheBlock(): void
    {
        file_put_contents($this->file, "one\ntwo\nthree\n");

        // The block starts in "two": the line is read with the block before.
        static::assertSame(['start' => 8, 'lines' => [8 => 'three'], 'partial' => false], $this->reader->readBefore($this->file, 14, 8));
        // The block starts on a line break: the line after it is whole.
        static::assertSame(['start' => 8, 'lines' => [8 => 'three'], 'partial' => false], $this->reader->readBefore($this->file, 14, 7));
    }

    public function testDoesNotReadBeforeTheFloor(): void
    {
        file_put_contents($this->file, "one\ntwo\nthree\n");

        // The floor is a start of line: the first line of the block is whole.
        static::assertSame(
            ['start' => 4, 'lines' => [4 => 'two', 8 => 'three'], 'partial' => false],
            $this->reader->readBefore($this->file, 14, 100, 4)
        );
        static::assertSame(['start' => 14, 'lines' => [], 'partial' => false], $this->reader->readBefore($this->file, 14, 100, 14));
    }

    public function testABlockWithoutLineBreakIsOneLine(): void
    {
        file_put_contents($this->file, "start\n" . str_repeat('x', 50) . "\nend\n");

        // Four bytes in the middle of the long line, then its last four bytes.
        static::assertSame(['start' => 20, 'lines' => [20 => 'xxxx'], 'partial' => true], $this->reader->readBefore($this->file, 24, 4));
        static::assertSame(['start' => 52, 'lines' => [52 => 'xxxx'], 'partial' => true], $this->reader->readBefore($this->file, 57, 5));
        // The whole line, from the line break before it.
        static::assertSame(
            ['start' => 6, 'lines' => [6 => str_repeat('x', 50)], 'partial' => false],
            $this->reader->readBefore($this->file, 57, 52)
        );
    }

    public function testKeepsEmptyLinesAndReadsALastLineWithoutLineBreak(): void
    {
        file_put_contents($this->file, "one\n\ntwo");

        static::assertSame(
            ['start' => 0, 'lines' => [0 => 'one', 4 => '', 5 => 'two'], 'partial' => false],
            $this->reader->readBefore($this->file, 8, 100)
        );
    }

    public function testRemovesTheCarriageReturns(): void
    {
        file_put_contents($this->file, "one\r\ntwo\r\n");

        static::assertSame(['start' => 0, 'lines' => [0 => 'one', 5 => 'two'], 'partial' => false], $this->reader->readBefore($this->file, 10, 100));
    }

    public function testAFileThatCannotBeReadHasNoLine(): void
    {
        static::assertSame(['start' => 10, 'lines' => [], 'partial' => false], $this->reader->readBefore($this->file . '.missing', 10, 100));
        static::assertSame(['start' => 0, 'lines' => [], 'partial' => false], $this->reader->readBefore($this->file, 0, 100));
    }
}
