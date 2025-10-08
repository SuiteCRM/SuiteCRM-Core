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

import {Component, HostListener, OnDestroy, OnInit, Type} from '@angular/core';
import {combineLatestWith, Observable, Subscription} from 'rxjs';
import {ActivatedRoute, Params} from '@angular/router';
import {CreateViewStore} from '../../store/create-view/create-view.store';
import {RecordViewStore} from '../../../record/store/record-view/record-view.store';
import {RecordViewModel} from '../../../record/store/record-view/record-view.store.model';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {Action, ActionContext} from '../../../../common/actions/action.model';
import {ViewContext, ViewMode} from '../../../../common/views/view.model';
import {RecordActionsAdapter} from "../../../record/adapters/actions.adapter";
import {RecordPaginationStore} from "../../../record/store/record-pagination/record-pagination.store";
import {RecordSectionTabActionsAdapter} from "../../../record/adapters/section-tab-actions.adapter";
import {RecordViewSidebarWidgetService} from "../../../record/services/record-view-sidebar-widget.service";
import {filter, map} from "rxjs/operators";
import {
    BaseRecordHeaderComponent
} from "../../../record/components/record-header/base-record-header/base-record-header.component";
import {RecordHeaderRegistry} from "../../../record/components/record-header/record-header.registry";

@Component({
    selector: 'scrm-create-record',
    templateUrl: './create-record.component.html',
    styleUrls: [],
    providers: [
        CreateViewStore,
        RecordActionsAdapter,
        {
            provide: RecordViewStore,
            useExisting: CreateViewStore
        },
        RecordViewSidebarWidgetService,
        RecordPaginationStore,
        RecordSectionTabActionsAdapter
    ]
})
export class CreateRecordComponent implements OnInit, OnDestroy {
    protected subs: Subscription[] = [];
    vm$: Observable<RecordViewModel> = null;
    showStatusBar = false;
    saveAction: Action;
    context: ActionContext;
    recordHeaderComponent: Type<BaseRecordHeaderComponent>;

    actionConfig$ = this.recordStore.mode$.pipe(
        combineLatestWith(
            this.actionsAdapter.getActions(),
            this.getViewContext$()),
        filter(([mode, actions, context]) => mode === 'create'),
        map(([mode, actions, context]) => ({
            mode,
            actions,
            context
        }))
    );

    @HostListener('keyup.control.enter')
    onEnterKey() {
        if (!this.saveAction || !this.context) {
            return;
        }
        this.actionsAdapter.runAction(this.saveAction, this.context);
    }

    constructor(
        protected appState: AppStateStore,
        protected recordStore: CreateViewStore,
        protected actionsAdapter: RecordActionsAdapter,
        private route: ActivatedRoute,
        protected recordHeaderRegistry: RecordHeaderRegistry
    ) {
    }

    ngOnInit(): void {
        let mode = 'detail' as ViewMode;
        const data = (this.route.snapshot && this.route.snapshot.data) || {};

        if (data.mode) {
            mode = data.mode;
        }

        let params = (this.route.snapshot && this.route.snapshot.queryParams) || {} as Params;

        params = {...params};

        let recordId = this.route.snapshot.params.record;

        if (data.duplicate === true) {
            params.originalId = recordId;
            params.isDuplicate = true;
            recordId = '';
        }

        if (data.convert === true) {
            params.originalId = recordId;
            params.isConvert = true;
            recordId = '';
        }

        this.subs.push(this.recordStore.init(this.appState.getModule(), recordId, mode, params).subscribe());
        this.vm$ = this.recordStore.vm$;
        this.appState.removeAllPrevRoutes();

        this.subs.push(this.actionConfig$.subscribe(config => {
                this.context = config.context;
                config.actions.forEach(actionItem => {
                    if (actionItem.key === 'saveNew') {
                        this.saveAction = actionItem;
                    }
                });
            })
        );

        this.initHeaderModuleComponentType();
    }

    getViewContext$(): Observable<ViewContext> {
        return this.recordStore.viewContext$;
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());

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
