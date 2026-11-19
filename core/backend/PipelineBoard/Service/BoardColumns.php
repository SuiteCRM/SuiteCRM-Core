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

namespace App\PipelineBoard\Service;

/**
 * The columns of a pipeline board and the mark of its stalled records.
 */
class BoardColumns
{
    /**
     * The columns of a board: one per value of the stage dropdown, in the order of the
     * dropdown, then one per value found in the records that the dropdown does not have
     * (a removed value, or no stage at all), so that no record is left out.
     *
     * @param array<string, string> $stages values of the stage dropdown and their labels
     * @param array<string, array{count: int, amount: float}> $totals totals of the records, by stage
     * @param string[] $folded stages shown folded
     * @param string[] $closed stages outside the active pipeline
     * @return array<int, array<string, mixed>>
     */
    public function build(array $stages, array $totals, array $folded, array $closed): array
    {
        $columns = [];
        foreach ($stages as $stage => $label) {
            $stage = (string)$stage;
            if ($stage === '') {
                // The blank value of a dropdown: a column only when records have no stage (below).
                continue;
            }
            $columns[] = $this->column($stage, (string)$label, $totals, $folded, $closed, false);
        }

        foreach (array_keys($totals) as $stage) {
            $stage = (string)$stage;
            if ($stage !== '' && isset($stages[$stage])) {
                continue;
            }
            if (($totals[$stage]['count'] ?? 0) > 0) {
                $columns[] = $this->column($stage, $stage, $totals, $folded, $closed, true);
            }
        }

        return $columns;
    }

    /**
     * Days since the last change of a record, null when its date is not known.
     *
     * @param string|null $dateModified date of the database (UTC, Y-m-d H:i:s)
     */
    public function idleDays(?string $dateModified, int $now): ?int
    {
        $time = $dateModified ? strtotime($dateModified . ' UTC') : false;
        if ($time === false) {
            return null;
        }

        return max(0, intdiv($now - $time, 86400));
    }

    /**
     * A record is stalled when it stayed $staleDays days without a change in a stage of
     * the active pipeline.
     */
    public function isStale(?int $idleDays, int $staleDays, bool $closedStage): bool
    {
        return !$closedStage && $staleDays > 0 && $idleDays !== null && $idleDays >= $staleDays;
    }

    protected function column(string $stage, string $label, array $totals, array $folded, array $closed, bool $unknown): array
    {
        return [
            'stage' => $stage,
            'label' => $label,
            'count' => (int)($totals[$stage]['count'] ?? 0),
            'amount' => round((float)($totals[$stage]['amount'] ?? 0), 2),
            'folded' => in_array($stage, $folded, true),
            'closed' => in_array($stage, $closed, true),
            // Not a value of the dropdown: its records can leave the column, none can enter it.
            'unknown' => $unknown,
            'loaded' => false,
            'cards' => [],
        ];
    }
}
