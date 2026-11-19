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

namespace App\Tests\unit\core\src\PipelineBoard;

use App\PipelineBoard\Service\BoardColumns;
use Codeception\Test\Unit;

/**
 * The columns of a pipeline board and the mark of the stalled records.
 */
class BoardColumnsTest extends Unit
{
    protected const STAGES = ['' => '', 'reach' => 'Reached', 'application' => 'Application', 'closedwon' => 'Closed Win'];

    protected BoardColumns $columns;

    protected function _before(): void
    {
        $this->columns = new BoardColumns();
    }

    public function testBuildsOneColumnPerStageInTheOrderOfTheDropdown(): void
    {
        $columns = $this->columns->build(
            self::STAGES,
            ['closedwon' => ['count' => 3, 'amount' => 1500.0], 'reach' => ['count' => 2, 'amount' => 99.999]],
            ['closedwon'],
            ['closedwon']
        );

        static::assertSame(['reach', 'application', 'closedwon'], array_column($columns, 'stage'));
        static::assertSame(['Reached', 'Application', 'Closed Win'], array_column($columns, 'label'));
        static::assertSame([2, 0, 3], array_column($columns, 'count'));
        static::assertSame([100.0, 0.0, 1500.0], array_column($columns, 'amount'));
        static::assertSame([false, false, true], array_column($columns, 'folded'));
        static::assertSame([false, false, true], array_column($columns, 'closed'));
        static::assertSame([false, false, false], array_column($columns, 'unknown'));
        static::assertSame([[], [], []], array_column($columns, 'cards'));
    }

    public function testFoldsTheColumnsTheUserChoseNotTheClosedOnes(): void
    {
        $columns = $this->columns->build(self::STAGES, [], ['reach'], ['closedwon']);

        static::assertSame([true, false, false], array_column($columns, 'folded'));
        static::assertSame([false, false, true], array_column($columns, 'closed'));
    }

    public function testAddsAColumnForTheRecordsOfAStageTheDropdownDoesNotHave(): void
    {
        $columns = $this->columns->build(
            self::STAGES,
            ['Prospecting' => ['count' => 4, 'amount' => 10.0], '' => ['count' => 1, 'amount' => 500.0]],
            [],
            []
        );

        static::assertSame(['reach', 'application', 'closedwon', 'Prospecting', ''], array_column($columns, 'stage'));
        static::assertSame([false, false, false, true, true], array_column($columns, 'unknown'));
        static::assertSame(4, $columns[3]['count']);
        static::assertSame('Prospecting', $columns[3]['label']);
        static::assertSame(1, $columns[4]['count']);
        static::assertSame(500.0, $columns[4]['amount']);
    }

    public function testHasNoColumnForAnUnknownStageWithoutRecords(): void
    {
        $columns = $this->columns->build(self::STAGES, ['' => ['count' => 0, 'amount' => 0.0]], [], []);

        static::assertSame(['reach', 'application', 'closedwon'], array_column($columns, 'stage'));
    }

    public function testCountsTheDaysSinceTheLastChange(): void
    {
        $now = strtotime('2026-11-19 12:00:00 UTC');

        static::assertSame(0, $this->columns->idleDays('2026-11-19 11:59:59', $now));
        static::assertSame(0, $this->columns->idleDays('2026-11-18 12:00:01', $now));
        static::assertSame(1, $this->columns->idleDays('2026-11-18 12:00:00', $now));
        static::assertSame(83, $this->columns->idleDays('2026-08-28 09:00:00', $now));
        // A date after now (clock difference) is not a negative number of days.
        static::assertSame(0, $this->columns->idleDays('2026-11-20 12:00:00', $now));
    }

    public function testDoesNotCountTheDaysOfARecordWithoutADate(): void
    {
        static::assertNull($this->columns->idleDays(null, time()));
        static::assertNull($this->columns->idleDays('', time()));
        static::assertNull($this->columns->idleDays('not a date', time()));
    }

    public function testMarksARecordOfAnActiveStageAfterTheDaysOfTheBoard(): void
    {
        static::assertFalse($this->columns->isStale(89, 90, false));
        static::assertTrue($this->columns->isStale(90, 90, false));
        static::assertTrue($this->columns->isStale(400, 90, false));
    }

    public function testNeverMarksARecordOfAClosedStage(): void
    {
        static::assertFalse($this->columns->isStale(400, 90, true));
    }

    public function testMarksNothingWithoutDaysOrWithoutADate(): void
    {
        static::assertFalse($this->columns->isStale(400, 0, false));
        static::assertFalse($this->columns->isStale(null, 90, false));
    }
}
