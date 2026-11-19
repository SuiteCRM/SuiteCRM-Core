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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {ActivatedRoute, ParamMap, Router} from '@angular/router';
import {CdkDrag, CdkDragDrop, CdkDropList} from '@angular/cdk/drag-drop';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {CurrencyService} from '../../../../services/currency/currency.service';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {BoardCard, BoardColumn, BoardData, BoardFilter} from '../../pipeline-board.model';

/**
 * The pipeline board (Kanban view) of a module: its records in columns, one per
 * value of a dropdown field (the sales stage of the opportunities), in the order of the
 * dropdown. Each column shows the number of its records and the sum of their amounts
 * (system currency); its cards are read by pages, the last changed first.
 *
 * A card dragged to another column, or moved with its "Move to" menu (keyboard), changes
 * the stage of its record; the cards the user cannot edit do not move. The stages outside
 * the active pipeline are folded until the user unfolds them (kept by user), and a record
 * of an active stage not changed for the days of the board is marked on its card.
 *
 * Route <module>/board (link of the module menu, board: true in the module routing), the
 * filters in the query params. Data from the `pipeline-board` process
 * (PipelineBoardProcessHandler); the boards are set in config/services/module/pipeline_boards.yaml.
 */
@Component({
    selector: 'scrm-pipeline-board',
    templateUrl: './pipeline-board.component.html',
    styleUrls: []
})
export class PipelineBoardComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'pipeline-board';

    data: BoardData = null;
    loading = false;
    error = false;
    /** Cards being saved in their new column. */
    moving: { [id: string]: boolean } = {};
    /** Stage of the column the dragged card comes from. */
    dragSource: string = null;
    /** A long press starts the drag on touch screens, so that a column still scrolls. */
    readonly dragDelay = {touch: 250, mouse: 0};

    protected board = '';
    protected filters: { [field: string]: string } = {};
    protected subs: Subscription[] = [];
    protected loadSub: Subscription;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected currencyFormatter: CurrencyFormatter,
        protected currencyService: CurrencyService,
        protected datetimeFormatter: DatetimeFormatter,
        protected language: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    /** The columns of the active pipeline: the summary of the board counts their records. */
    get active(): BoardColumn[] {
        return (this.data?.columns ?? []).filter(column => !column.closed);
    }

    get activeCount(): number {
        return this.active.reduce((total, column) => total + column.count, 0);
    }

    get activeAmount(): number {
        return this.active.reduce((total, column) => total + column.amount, 0);
    }

    ngOnInit(): void {
        this.board = this.route.snapshot.data?.module ?? '';
        this.subs.push(this.route.queryParamMap.subscribe(params => {
            this.readParams(params);
            this.load();
        }));
    }

    ngOnDestroy(): void {
        this.loadSub?.unsubscribe();
        this.subs.forEach(sub => sub.unsubscribe());
    }

    /**
     * A label of the page (application strings), with its values.
     */
    label(key: string, values: { [name: string]: string | number } = {}): string {
        let label = this.language.getAppString(key) || key;
        Object.keys(values).forEach(name => label = label.replace('{' + name + '}', String(values[name])));

        return label;
    }

    /** "1 record", "12 records". */
    records(count: number): string {
        return count + ' ' + this.label(count === 1 ? 'LBL_PIPELINE_BOARD_RECORD' : 'LBL_PIPELINE_BOARD_RECORDS');
    }

    columnLabel(column: BoardColumn): string {
        return column.label || (column.stage ? column.stage : this.label('LBL_PIPELINE_BOARD_NO_STAGE'));
    }

    /**
     * An amount without decimals: in the currency of a record, or in the system currency
     * (the totals of the columns).
     */
    money(value: number | null, currencyId: string = null): string {
        if (value === null || value === undefined || !isFinite(value)) {
            return '';
        }

        const base = this.currencyService.getBaseCurrency();
        const currency = currencyId ? this.currencyService.getCurrency(currencyId) : null;

        return this.currencyFormatter.toUserFormat(String(Math.round(value)), {
            symbol: currency?.symbol ?? base?.symbol,
            code: currency?.iso4217 ?? base?.iso4217,
            fromFormat: 'system',
            digits: 0
        });
    }

    date(value: string): string {
        return value ? this.datetimeFormatter.toUserFormat(value) : '';
    }

    recordLink(card: BoardCard): string[] {
        return ['/', this.data.board, 'record', card.id];
    }

    /** The other columns a card can be moved to. */
    targets(column: BoardColumn): BoardColumn[] {
        return this.data.columns.filter(item => item !== column && !item.unknown);
    }

    canDrag(card: BoardCard): boolean {
        return card.canEdit && !this.moving[card.id];
    }

    /** No record enters a column that is not a value of the dropdown. */
    readonly canEnter = (drag: CdkDrag<BoardCard>, drop: CdkDropList<BoardColumn>): boolean => !drop.data?.unknown;

    setFilter(filter: BoardFilter, value: string): void {
        const queryParams: { [field: string]: string } = {};
        this.data.filters.forEach(item => {
            const current = item === filter ? value : item.value;
            if (current) {
                queryParams[item.field] = current;
            }
        });

        this.router.navigate([], {relativeTo: this.route, queryParams});
    }

    /** Folds or unfolds a column, kept for the user; a column reads its cards when first unfolded. */
    toggle(column: BoardColumn): void {
        column.folded = !column.folded;
        if (!column.folded && !column.loaded) {
            this.loadCards(column);
        }

        this.processService.submit(PipelineBoardComponent.PROCESS, {
            action: 'fold',
            board: this.board,
            stage: column.stage,
            folded: column.folded
        }).pipe(take(1)).subscribe({
            error: () => {
                // The column stays as the user set it on this page.
            }
        });
    }

    more(column: BoardColumn): void {
        this.loadCards(column);
    }

    /** A card dropped in another column. */
    drop(event: CdkDragDrop<BoardColumn>): void {
        this.dragSource = null;
        this.move(event.item.data as BoardCard, event.previousContainer.data, event.container.data);
    }

    /**
     * Moves a card to another column (drop, or "Move to" menu): shown at once at the top
     * of the column, as the last changed record, and put back when the record is not saved.
     */
    move(card: BoardCard, from: BoardColumn, to: BoardColumn): void {
        if (!from || !to || from === to || to.unknown || !this.canDrag(card)) {
            return;
        }

        const index = from.cards.indexOf(card);
        this.transfer(card, from, to, 0);
        this.moving[card.id] = true;

        const failed = (denied: boolean): void => {
            delete this.moving[card.id];
            this.transfer(card, to, from, index);
            this.message.addDangerMessage(this.label(denied ? 'LBL_PIPELINE_BOARD_MOVE_DENIED' : 'LBL_PIPELINE_BOARD_MOVE_ERROR'));
            this.cdr.detectChanges();
        };

        this.processService.submit(PipelineBoardComponent.PROCESS, {
            action: 'move',
            board: this.board,
            id: card.id,
            stage: to.stage
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status !== 'success' || process?.data?.result !== 'moved') {
                    failed(process?.data?.result === 'denied');
                    return;
                }
                delete this.moving[card.id];
                // The record as saved: changed now, no longer stalled.
                Object.assign(card, process.data.card ?? {});
                this.cdr.detectChanges();
            },
            error: () => failed(false)
        });
    }

    trackByStage(index: number, column: BoardColumn): string {
        return column.stage;
    }

    trackById(index: number, card: BoardCard): string {
        return card.id;
    }

    /** Takes a card out of a column and puts it in another, with their totals. */
    protected transfer(card: BoardCard, from: BoardColumn, to: BoardColumn, index: number): void {
        const position = from.cards.indexOf(card);
        if (position >= 0) {
            from.cards.splice(position, 1);
        }
        from.count = Math.max(0, from.count - 1);
        from.amount -= card.baseAmount;

        // A column never unfolded has no cards yet: it reads them, with this one, when unfolded.
        if (to.loaded) {
            to.cards.splice(Math.max(0, Math.min(index, to.cards.length)), 0, card);
        }
        to.count += 1;
        to.amount += card.baseAmount;
    }

    protected readParams(params: ParamMap): void {
        this.filters = {};
        params.keys.forEach(field => this.filters[field] = params.get(field) ?? '');
    }

    protected load(): void {
        this.loadSub?.unsubscribe();
        this.loading = true;
        this.error = false;
        this.cdr.detectChanges();

        const failed = (): void => {
            this.loading = false;
            this.error = true;
            this.cdr.detectChanges();
        };

        this.loadSub = this.processService.submit(PipelineBoardComponent.PROCESS, {
            action: 'load',
            board: this.board,
            filters: this.filters
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as BoardData;
                if (process?.status !== 'success' || !data?.columns) {
                    failed();
                    return;
                }
                this.loading = false;
                this.data = data;
                this.cdr.detectChanges();
            },
            error: failed
        });
    }

    /** Reads the next cards of a column. */
    protected loadCards(column: BoardColumn): void {
        if (column.loading) {
            return;
        }
        column.loading = true;

        const done = (): void => {
            column.loading = false;
            this.cdr.detectChanges();
        };

        this.processService.submit(PipelineBoardComponent.PROCESS, {
            action: 'cards',
            board: this.board,
            filters: this.filters,
            stage: column.stage,
            offset: column.cards.length
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const cards = process?.data?.cards as BoardCard[];
                if (process?.status === 'success' && Array.isArray(cards)) {
                    const known = new Set(column.cards.map(card => card.id));
                    column.cards.push(...cards.filter(card => !known.has(card.id)));
                    column.loaded = true;
                } else {
                    this.message.addDangerMessage(this.label('LBL_PIPELINE_BOARD_ERROR'));
                }
                done();
            },
            error: () => {
                this.message.addDangerMessage(this.label('LBL_PIPELINE_BOARD_ERROR'));
                done();
            }
        });
    }
}
