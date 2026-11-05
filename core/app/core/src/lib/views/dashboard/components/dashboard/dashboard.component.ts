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

import {
    ChangeDetectorRef,
    Component,
    OnDestroy,
    OnInit,
    QueryList,
    ViewChildren
} from '@angular/core';
import {CommonModule} from '@angular/common';
import {ActivatedRoute, Router} from '@angular/router';
import {CdkDragDrop, DragDropModule, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {NgbDropdownModule, NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {Subject, Subscription} from 'rxjs';
import {take, takeUntil} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DashboardData, DashboardPage, DashletData} from '../../dashboard.model';
import {ListDashletComponent} from '../list-dashlet/list-dashlet.component';
import {ChartDashletComponent} from '../chart-dashlet/chart-dashlet.component';
import {LegacyDashletComponent} from '../legacy-dashlet/legacy-dashlet.component';
import {FrameDashletComponent} from '../frame-dashlet/frame-dashlet.component';
import {FeedDashletComponent} from '../feed-dashlet/feed-dashlet.component';
import {DashboardEditService} from '../../services/dashboard-edit.service';
import {AddDashletsModalComponent} from '../add-dashlets-modal/add-dashlets-modal.component';
import {DashletOptionsModalComponent} from '../dashlet-options-modal/dashlet-options-modal.component';
import {DashboardTabModalComponent} from '../dashboard-tab-modal/dashboard-tab-modal.component';

/**
 * Home dashboard, on the /home route (the legacy dashboard stays on /home/index).
 *
 * The dashboard is the user's legacy one (same tabs, columns and dashlets, read
 * by the `dashboard` process), so both stay the same. List, chart, Activity
 * Stream and iFrame dashlets are rendered natively, the others (reports,
 * tools...) by their legacy page in a frame.
 *
 * The dashboard is edited here (`dashboard-edit` process): dashlets are
 * added, moved by drag and drop, configured and removed, tabs added, renamed,
 * removed and given 1 to 3 columns. Nothing can be changed when the
 * administrator locked the homepage.
 *
 * The open tab is in the URL (?tab=), so the browser history and a reload keep it.
 */
@Component({
    selector: 'scrm-dashboard',
    standalone: true,
    imports: [
        CommonModule, DragDropModule, NgbDropdownModule, ListDashletComponent, ChartDashletComponent,
        FeedDashletComponent, FrameDashletComponent, LegacyDashletComponent
    ],
    templateUrl: './dashboard.component.html'
})
export class DashboardComponent implements OnInit, OnDestroy {

    @ViewChildren(ListDashletComponent) listDashlets: QueryList<ListDashletComponent>;
    @ViewChildren(ChartDashletComponent) chartDashlets: QueryList<ChartDashletComponent>;
    @ViewChildren(FrameDashletComponent) frameDashlets: QueryList<FrameDashletComponent>;
    @ViewChildren(FeedDashletComponent) feedDashlets: QueryList<FeedDashletComponent>;
    @ViewChildren(LegacyDashletComponent) legacyDashlets: QueryList<LegacyDashletComponent>;

    page = 0;
    loading = false;
    error = false;
    data: DashboardData | null = null;

    readonly moveDirections: { key: 'up' | 'down' | 'left' | 'right', label: string }[] = [
        {key: 'up', label: 'LBL_DASHBOARD_MOVE_UP'},
        {key: 'down', label: 'LBL_DASHBOARD_MOVE_DOWN'},
        {key: 'left', label: 'LBL_DASHBOARD_MOVE_LEFT'},
        {key: 'right', label: 'LBL_DASHBOARD_MOVE_RIGHT'},
    ];

    protected loadSub: Subscription | null = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected edit: DashboardEditService,
        protected modals: NgbModal,
        protected confirmation: ConfirmationModalService,
        protected messages: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntil(this.destroy$)).subscribe(params => {
            this.page = Math.max(0, parseInt(params.get('tab') ?? '0', 10) || 0);
            this.load();
        });
    }

    ngOnDestroy(): void {
        this.loadSub?.unsubscribe();
        this.destroy$.next();
        this.destroy$.complete();
    }

    /** Label of the Home module strings (LBL_DASHBOARD_*). */
    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    get current(): DashboardPage | null {
        return this.data?.pages?.[this.data.page] ?? null;
    }

    get isEmpty(): boolean {
        return !!this.current && this.current.columns.every(column => !column.dashlets.length);
    }

    /** The dashboard can be changed (not locked by the administrator). */
    get canEdit(): boolean {
        return !!this.data && !this.data.locked;
    }

    dashlet(id: string): DashletData {
        return this.data.dashlets[id];
    }

    openTab(index: number): void {
        this.router.navigate(['/home'], {queryParams: index ? {tab: index} : {}});
    }

    refresh(id: string): void {
        this.listDashlets.find(item => item.dashlet?.id === id)?.refresh();
        this.chartDashlets.find(item => item.dashlet?.id === id)?.refresh();
        this.frameDashlets.find(item => item.dashlet?.id === id)?.refresh();
        this.feedDashlets.find(item => item.dashlet?.id === id)?.refresh();
        this.legacyDashlets.find(item => item.id === id)?.refresh();
    }

    /**
     * Columns of the dashboard tab, of equal width as on the legacy dashboard
     * (the suite8 theme splits the page evenly and ignores the stored widths).
     */
    gridColumns(page: DashboardPage): string {
        return `repeat(${Math.max(1, page.columns.length)}, minmax(0, 1fr))`;
    }

    trackById(index: number, id: string): string {
        return id;
    }

    addDashlets(): void {
        const modal = this.modals.open(AddDashletsModalComponent, {size: 'lg', scrollable: true, windowClass: 'dashboard-modal'});
        modal.componentInstance.page = this.data.page;
        modal.closed.pipe(take(1)).subscribe(() => this.load());
    }

    configure(id: string): void {
        const dashlet = this.dashlet(id);
        const modal = this.modals.open(DashletOptionsModalComponent, {
            size: dashlet.type === 'legacy' ? 'xl' : 'lg',
            scrollable: true,
            windowClass: 'dashboard-modal'
        });
        modal.componentInstance.id = id;
        modal.componentInstance.dashletTitle = dashlet.title;
        modal.closed.pipe(take(1)).subscribe(() => this.load());
    }

    remove(id: string): void {
        this.confirmation.showModal(['LBL_DASHBOARD_REMOVE_DASHLET_CONFIRM'], () => {
            this.run(this.edit.done({action: 'remove', id}));
        });
    }

    /** A dashlet dropped in a column of the tab: the new order is saved. */
    drop(event: CdkDragDrop<string[]>): void {
        if (event.previousContainer === event.container) {
            if (event.previousIndex === event.currentIndex) {
                return;
            }
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(event.previousContainer.data, event.container.data, event.previousIndex, event.currentIndex);
        }

        this.saveLayout();
    }

    /** Where the dashlet can go with the "Move" menu (the keyboard way to drag and drop). */
    canMove(id: string, direction: 'up' | 'down' | 'left' | 'right'): boolean {
        const columns = this.current?.columns ?? [];
        const column = columns.findIndex(item => item.dashlets.includes(id));
        const row = column >= 0 ? columns[column].dashlets.indexOf(id) : -1;

        switch (direction) {
            case 'up':
                return row > 0;
            case 'down':
                return row >= 0 && row < columns[column].dashlets.length - 1;
            case 'left':
                return column > 0;
            default:
                return column >= 0 && column < columns.length - 1;
        }
    }

    move(id: string, direction: 'up' | 'down' | 'left' | 'right'): void {
        if (!this.canMove(id, direction)) {
            return;
        }

        const columns = this.current.columns;
        const column = columns.findIndex(item => item.dashlets.includes(id));
        const row = columns[column].dashlets.indexOf(id);
        if (direction === 'up' || direction === 'down') {
            moveItemInArray(columns[column].dashlets, row, row + (direction === 'up' ? -1 : 1));
        } else {
            const target = columns[column + (direction === 'left' ? -1 : 1)].dashlets;
            transferArrayItem(columns[column].dashlets, target, row, Math.min(row, target.length));
        }

        this.saveLayout();
    }

    protected saveLayout(): void {
        this.edit.done({
            action: 'layout',
            page: this.data.page,
            columns: this.current.columns.map(column => column.dashlets)
        }).subscribe(done => {
            if (!done) {
                this.messages.addDangerMessageByKey('LBL_DASHBOARD_SAVE_ERROR');
                this.load();
            }
        });
    }

    addTab(): void {
        const modal = this.modals.open(DashboardTabModalComponent, {windowClass: 'dashboard-modal'});
        modal.componentInstance.isNew = true;
        modal.closed.pipe(take(1)).subscribe((value: { title: string, columns: number }) => {
            this.edit.run<{ page?: number }>({action: 'addTab', title: value.title, columns: value.columns}).subscribe(result => {
                if (typeof result?.page !== 'number') {
                    this.messages.addDangerMessageByKey('LBL_DASHBOARD_SAVE_ERROR');
                    return;
                }
                this.openTab(result.page);
            });
        });
    }

    renameTab(): void {
        const modal = this.modals.open(DashboardTabModalComponent, {windowClass: 'dashboard-modal'});
        modal.componentInstance.isNew = false;
        modal.componentInstance.title = this.current.title;
        modal.closed.pipe(take(1)).subscribe((value: { title: string }) => {
            this.run(this.edit.done({action: 'renameTab', page: this.data.page, title: value.title}));
        });
    }

    setColumns(columns: number): void {
        if (columns !== this.current.columns.length) {
            this.run(this.edit.done({action: 'columns', page: this.data.page, columns}));
        }
    }

    removeTab(): void {
        const page = this.data.page;
        this.confirmation.showModal(['LBL_DASHBOARD_REMOVE_TAB_CONFIRM'], () => {
            this.edit.done({action: 'removeTab', page}).subscribe(done => {
                if (!done) {
                    this.messages.addDangerMessageByKey('LBL_DASHBOARD_SAVE_ERROR');
                    return;
                }
                // The tab before it (another URL: the dashboard reloads).
                this.openTab(page - 1);
            });
        });
    }

    /** An editing action, then the dashboard again (or an error message). */
    protected run(action$: ReturnType<DashboardEditService['done']>): void {
        action$.subscribe(done => {
            if (!done) {
                this.messages.addDangerMessageByKey('LBL_DASHBOARD_SAVE_ERROR');
            }
            this.load();
        });
    }

    protected load(): void {
        this.loadSub?.unsubscribe();
        this.loading = true;
        this.error = false;

        this.loadSub = this.processService.submit('dashboard', {action: 'load', page: this.page}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as DashboardData;
                this.loading = false;
                if (process?.status !== 'success' || !data || data.error) {
                    this.error = true;
                    this.cdr.detectChanges();
                    return;
                }
                this.data = data;
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.error = true;
                this.cdr.detectChanges();
            }
        });
    }
}
