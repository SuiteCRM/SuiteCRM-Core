/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {Component, OnDestroy, OnInit} from '@angular/core';
import {Observable, Subscription} from 'rxjs';
import {ListViewModel, ListViewStore} from '../../store/list-view/list-view.store';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {QuickFiltersService} from "../../services/quick-filters.service";
import {ListViewSidebarWidgetService} from "../../services/list-view-sidebar-widget.service";
import {RecordPaginationService} from "../../../record/store/record-pagination/record-pagination.service";

@Component({
    selector: 'scrm-list',
    templateUrl: './list.component.html',
    styleUrls: [],
    providers: [ListViewStore, QuickFiltersService, ListViewSidebarWidgetService]
})
export class ListComponent implements OnInit, OnDestroy {
    listSub: Subscription;
    module: string;

    vm$: Observable<ListViewModel> = null;

    constructor(
        protected appState: AppStateStore,
        protected listStore: ListViewStore,
        protected quickFilters: QuickFiltersService,
        protected sidebarWidgetHandler: ListViewSidebarWidgetService,
        protected recordPaginationService: RecordPaginationService
    ) {

    }

    ngOnInit(): void {
        this.appState.removeAllPrevRoutes();
        this.module = this.appState.getModule();
        this.listSub = this.listStore.init(this.module).subscribe();
        this.vm$ = this.listStore.vm$;
    }

    ngOnDestroy(): void {
        if (this.listSub) {
            this.listSub.unsubscribe();
        }

        this.updateListLocalStorage();

        this.quickFilters.destroy();
        this.sidebarWidgetHandler.destroy();

        this.listStore.destroy();
    }

    public updateListLocalStorage(): void {
        this.recordPaginationService.updateRecordListLocalStorage(this.listStore.recordList.records, this.listStore.recordList.pagination);
    }
}
