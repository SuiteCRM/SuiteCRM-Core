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

/**
 * Data of the pipeline board page (`pipeline-board` process).
 */

/** A record of the board. */
export interface BoardCard {
    id: string;
    name: string;
    /** Field shown under the name (the account of an opportunity). */
    subtitle: string;
    /** Amount in the currency of the record, null when the board or the record has none. */
    amount: number | null;
    /** Amount in the system currency, summed by column. */
    baseAmount: number;
    currencyId: string;
    userName: string;
    /** Labels of the dropdown filters of the record (the opportunity type). */
    tags: string[];
    /** Last change (database date, UTC). */
    modified: string;
    idleDays: number | null;
    /** Not changed for the days of the board, in a stage of the active pipeline. */
    stale: boolean;
    canEdit: boolean;
}

/** A stage of the board. */
export interface BoardColumn {
    stage: string;
    label: string;
    count: number;
    /** Sum of the amounts of its records, in the system currency. */
    amount: number;
    folded: boolean;
    /** Stage outside the active pipeline (won, lost, after the sale, on hold). */
    closed: boolean;
    /** Not a value of the stage dropdown: its records can leave it, none can enter it. */
    unknown: boolean;
    /** Whether its first cards were read (a folded column reads them when unfolded). */
    loaded: boolean;
    cards: BoardCard[];
    loading?: boolean;
}

export interface BoardFilter {
    field: string;
    label: string;
    value: string;
    options: { value: string; label: string }[];
}

export interface BoardData {
    /** Front end name of the module. */
    board: string;
    module: string;
    title: string;
    stageLabel: string;
    hasAmount: boolean;
    canCreate: boolean;
    staleDays: number;
    pageSize: number;
    filters: BoardFilter[];
    columns: BoardColumn[];
}
