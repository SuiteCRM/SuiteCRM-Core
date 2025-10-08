/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

import {Component, OnInit, signal, WritableSignal} from '@angular/core';
import {Observable, Subscription} from 'rxjs';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {SubpanelStore} from "../../../subpanel/store/subpanel/subpanel.store";
import {SubpanelStoreFactory} from "../../../subpanel/store/subpanel/subpanel.store.factory";
import {map, take} from "rxjs/operators";
import {PanelCollapseMode} from "../../../../components/panel/panel.component";

@Component({
    selector: 'record-table-widget',
    templateUrl: './record-table-widget.component.html',
    styleUrls: []
})
export class RecordTableWidgetComponent extends BaseWidgetComponent implements OnInit {
    panelHeaderButtonClass: string = 'btn btn-sm btn-outline-main';
    titleLabelKey = 'LBL_INSIGHTS';
    titleKey: WritableSignal<string> = signal('');
    widgetCollapseMode: WritableSignal<PanelCollapseMode> = signal('none');
    loading$: Observable<boolean>;
    loading = true;
    protected subs: Subscription[] = [];
    store: SubpanelStore;


    constructor(
        public language: LanguageStore,
        protected subpanelFactory: SubpanelStoreFactory
    ) {
        super();
    }

    ngOnInit(): void {

        const recordTableConfig = this?.config?.options?.recordTable ?? null;

        super.ngOnInit();

        this.store = this.subpanelFactory.create();
        const parentModule = this.context.module;
        const parentRecordId = this.context.id;
        const contextRecord$ = this.context$.pipe(map(context => context.record));
        this.store.init(parentModule, parentRecordId, recordTableConfig, contextRecord$);
        this.store.recordList.setLoading(true);
        this.initPanelTitleKey(recordTableConfig);
        this.initPanelCollapseMode();

        this.store.load().pipe(take(1)).subscribe();
    }

    protected initPanelTitleKey(recordTableConfig: any): void {
        recordTableConfig.title_key = this?.config?.labelKey ?? recordTableConfig.title_key;
        this.titleKey.set(this?.config?.labelKey ?? this.titleLabelKey);
    }

    protected initPanelCollapseMode(): void {
        let widgetCollapseMode: PanelCollapseMode = 'none';
        if (this?.config?.allowCollapse) {
            widgetCollapseMode = 'collapsible';
        }
        this.widgetCollapseMode.set(widgetCollapseMode as PanelCollapseMode);
        this.store.panelCollapseMode.set(widgetCollapseMode);
    }
}
