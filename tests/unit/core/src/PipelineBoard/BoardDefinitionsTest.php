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

use App\PipelineBoard\Service\BoardDefinitions;
use Codeception\Test\Unit;

/**
 * The pipeline boards read from the configuration.
 */
class BoardDefinitionsTest extends Unit
{
    public function testReadsABoard(): void
    {
        $definitions = new BoardDefinitions([
            'opportunities' => [
                'module' => 'Opportunities',
                'stage_field' => 'sales_stage',
                'amount_field' => 'amount',
                'subtitle_field' => 'account_name',
                'filters' => ['opportunity_type', 'assigned_user_id'],
                'closed_stages' => ['closedwon', 'closedlost'],
                'stale_days' => 60,
                'page_size' => 40,
            ],
        ]);

        $board = $definitions->get('opportunities');

        static::assertNotNull($board);
        static::assertSame('opportunities', $board->board);
        static::assertSame('Opportunities', $board->module);
        static::assertSame('sales_stage', $board->stageField);
        static::assertSame('amount', $board->amountField);
        static::assertSame('account_name', $board->subtitleField);
        static::assertSame(['opportunity_type', 'assigned_user_id'], $board->filters);
        static::assertSame(['closedwon', 'closedlost'], $board->closedStages);
        static::assertSame(60, $board->staleDays);
        static::assertSame(40, $board->pageSize);
        static::assertTrue($board->isClosed('closedlost'));
        static::assertFalse($board->isClosed('reach'));
        static::assertSame(['opportunities'], array_keys($definitions->all()));
    }

    public function testHasNoBoardForAnotherModule(): void
    {
        $definitions = new BoardDefinitions(['opportunities' => ['module' => 'Opportunities', 'stage_field' => 'sales_stage']]);

        static::assertNull($definitions->get('leads'));
        static::assertNull($definitions->get(''));
    }

    public function testGivesTheDefaultsOfABoard(): void
    {
        $board = (new BoardDefinitions(['leads' => ['module' => 'Leads', 'stage_field' => 'status']]))->get('leads');

        static::assertSame('', $board->amountField);
        static::assertSame('', $board->subtitleField);
        static::assertSame([], $board->filters);
        static::assertSame([], $board->closedStages);
        static::assertSame(BoardDefinitions::DEFAULT_STALE_DAYS, $board->staleDays);
        static::assertSame(BoardDefinitions::DEFAULT_PAGE_SIZE, $board->pageSize);
    }

    public function testNeedsAModuleAndAStageField(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BoardDefinitions(['opportunities' => ['module' => 'Opportunities']]);
    }

    public function testRefusesANameThatIsNotAFieldName(): void
    {
        // The names go into SQL.
        $this->expectException(\InvalidArgumentException::class);

        new BoardDefinitions(['opportunities' => ['module' => 'Opportunities', 'stage_field' => 'sales_stage; DROP TABLE x']]);
    }

    public function testLeavesOutTheFiltersThatAreNotFieldNames(): void
    {
        $board = (new BoardDefinitions(['opportunities' => [
            'module' => 'Opportunities',
            'stage_field' => 'sales_stage',
            'amount_field' => 'amount, 1',
            'filters' => ['opportunity_type', 'opportunity_type', 'sales_stage', "x' OR 1=1", 12, 'lead_source'],
        ]]))->get('opportunities');

        // Once each, without the stage field itself (the columns already split by it).
        static::assertSame(['opportunity_type', 'lead_source'], $board->filters);
        static::assertSame('', $board->amountField);
    }

    public function testKeepsThePageSizeAndTheStaleDaysInTheirLimits(): void
    {
        $board = (new BoardDefinitions(['opportunities' => [
            'module' => 'Opportunities',
            'stage_field' => 'sales_stage',
            'stale_days' => -5,
            'page_size' => 5000,
        ]]))->get('opportunities');

        static::assertSame(0, $board->staleDays);
        static::assertSame(BoardDefinitions::MAX_PAGE_SIZE, $board->pageSize);

        $board = (new BoardDefinitions(['opportunities' => [
            'module' => 'Opportunities',
            'stage_field' => 'sales_stage',
            'page_size' => 0,
        ]]))->get('opportunities');

        static::assertSame(BoardDefinitions::DEFAULT_PAGE_SIZE, $board->pageSize);
    }
}
