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

namespace App\PipelineBoard\Model;

/**
 * A pipeline board: the records of a module shown in columns, one per value
 * of a dropdown field (the stage), as set in config/services/module/pipeline_boards.yaml.
 */
class BoardDefinition
{
    /**
     * @param string $board front end name of the module (route <board>/board)
     * @param string $module legacy module
     * @param string $stageField dropdown field giving the column of a record
     * @param string $amountField amount shown on a card and summed by column ('' when none)
     * @param string $subtitleField field shown under the name of a card ('' when none)
     * @param string[] $filters dropdown fields, or assigned_user_id, the board can be filtered by
     * @param string[] $closedStages stages outside the active pipeline: folded by default, never stalled
     * @param int $staleDays days without a change after which a record is marked as stalled (0: never)
     * @param int $pageSize cards read at once in a column
     */
    public function __construct(
        public readonly string $board,
        public readonly string $module,
        public readonly string $stageField,
        public readonly string $amountField,
        public readonly string $subtitleField,
        public readonly array $filters,
        public readonly array $closedStages,
        public readonly int $staleDays,
        public readonly int $pageSize
    ) {
    }

    public function isClosed(string $stage): bool
    {
        return in_array($stage, $this->closedStages, true);
    }
}
