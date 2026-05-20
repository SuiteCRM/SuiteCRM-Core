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

import {Component, OnDestroy, OnInit, Type} from '@angular/core';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {Observable, Subscription} from 'rxjs';
import {RecordViewStore} from '../../store/record-view/record-view.store';
import {ActivatedRoute, Params} from '@angular/router';
import {RecordViewModel} from '../../store/record-view/record-view.store.model';
import {ViewMode} from '../../../../common/views/view.model';
import {RecordActionsAdapter} from '../../adapters/actions.adapter';
import {RecordViewSidebarWidgetService} from "../../services/record-view-sidebar-widget.service";
import {RecordPaginationStore} from "../../store/record-pagination/record-pagination.store";
import {RecordSectionTabActionsAdapter} from "../../adapters/section-tab-actions.adapter";
import {BaseRecordHeaderComponent} from "../record-header/base-record-header/base-record-header.component";
import {RecordHeaderRegistry} from "../record-header/record-header.registry";

@Component({
    selector: 'scrm-record',
    templateUrl: './record.component.html',
    styleUrls: [],
    providers: [RecordViewStore, RecordActionsAdapter, RecordViewSidebarWidgetService, RecordPaginationStore, RecordSectionTabActionsAdapter]
})
export class RecordComponent implements OnInit, OnDestroy {
    recordSub: Subscription;
    vm$: Observable<RecordViewModel> = null;
    showStatusBar = false;
    recordHeaderComponent: Type<BaseRecordHeaderComponent>;

    constructor(
        protected appState: AppStateStore,
        protected recordStore: RecordViewStore,
        private route: ActivatedRoute,
        protected sidebarWidgetHandler: RecordViewSidebarWidgetService,
        protected recordHeaderRegistry: RecordHeaderRegistry
    ) {
    }

    ngOnInit(): void {
        let mode = 'detail' as ViewMode;
        this.appState.addToPrevRoute(this.appState.getRouteUrl());
        const data = (this.route.snapshot && this.route.snapshot.data) || {};

        if (data.mode) {
            mode = data.mode;
        }

        const params = (this.route.snapshot && this.route.snapshot.queryParams) || {} as Params;

        this.recordSub = this.recordStore.init(this.appState.getModule(), this.route.snapshot.params.record, mode, params).subscribe();
        this.vm$ = this.recordStore.vm$;

        this.initHeaderModuleComponentType();
    }

    ngOnDestroy(): void {
        if (this.recordSub) {
            this.recordSub.unsubscribe();
        }
        this.sidebarWidgetHandler.destroy();
        this.recordStore.destroy();
    }

    protected initHeaderModuleComponentType() {
        let headerModule = 'default';
        if (this.recordHeaderRegistry.has(this.appState.getModule(), 'default')) {
            headerModule = this.appState.getModule();
        }
        this.recordHeaderComponent = this.recordHeaderRegistry.get(headerModule, 'default');
    }
}
