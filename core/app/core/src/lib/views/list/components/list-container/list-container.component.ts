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

import {Component, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {Observable, Subscription} from 'rxjs';
import {ViewContext} from '../../../../common/views/view.model';
import {WidgetMetadata} from '../../../../common/metadata/widget.metadata';
import {MaxColumnsCalculator} from '../../../../services/ui/max-columns-calculator/max-columns-calculator.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {ScreenSize} from '../../../../services/ui/screen-size-observer/screen-size-observer.service';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {TableConfig} from '../../../../components/table/table.model';
import {TableAdapter} from '../../adapters/table.adapter';
import {ListViewSidebarWidgetAdapter} from '../../adapters/sidebar-widget.adapter';
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {ListViewSidebarWidgetService} from "../../services/list-view-sidebar-widget.service";

export interface ListContainerState {
    sidebarWidgetConfig: {
        widgets: WidgetMetadata[];
        show: boolean;
        widgetsEnabled: boolean;
    }
}

@Component({
    selector: 'scrm-list-container',
    templateUrl: 'list-container.component.html',
    providers: [TableAdapter, MaxColumnsCalculator, ListViewSidebarWidgetAdapter],
})

export class ListContainerComponent implements OnInit, OnDestroy {
    screen: ScreenSize = ScreenSize.Medium;
    maxColumns = 5;
    tableConfig: TableConfig;
    showSidebar: WritableSignal<boolean> = signal(true);
    swapWidgets: WritableSignal<boolean> = signal(false);
    sidebarWidgetConfig: any;
    widgetDisplayType: WritableSignal<string> = signal('none');
    displayWidgets: WritableSignal<boolean> = signal(false);

    protected subs: Subscription[] = [];

    constructor(
        public store: ListViewStore,
        protected adapter: TableAdapter,
        protected maxColumnCalculator: MaxColumnsCalculator,
        public languageStore: LanguageStore,
        protected sidebarWidgetAdapter: ListViewSidebarWidgetAdapter,
        protected systemConfigs: SystemConfigStore,
        protected sidebarWidgetHandler: ListViewSidebarWidgetService
    ) {
    }

    ngOnInit(): void {
        this.tableConfig = this.adapter.getTable();
        this.tableConfig.maxColumns$ = this.getMaxColumns();

        if (this.store?.metadata?.listView?.maxHeight) {
            this.tableConfig.maxListHeight = this.store.metadata.listView.maxHeight;
        }
        if (!this.tableConfig?.maxListHeight) {
            const ui = this.systemConfigs.getConfigValue('ui');
            this.tableConfig.maxListHeight = ui.listview_max_height;
        }
        this.tableConfig.paginationType = this?.store?.metadata?.listView?.paginationType ?? this.tableConfig.paginationType;


        this.subs.push(this.sidebarWidgetAdapter.config$.subscribe(sidebarWidgetConfig => {
            this.sidebarWidgetConfig = sidebarWidgetConfig;
            this.showSidebar.set(this.store.widgets && this.store.showSidebarWidgets);
            const widgetDisplayType = this.getDisplay(!!(this.sidebarWidgetConfig.show && this.sidebarWidgetConfig.widgets));

            if (widgetDisplayType !== this.widgetDisplayType()) {
                if (widgetDisplayType === 'none') {
                    this.displayWidgets.set(false);
                } else {
                    setTimeout(() => {
                        this.displayWidgets.set(true);
                    }, 500)
                }
            }

            this.widgetDisplayType.set(widgetDisplayType);
        }));

        this.subs.push(this.sidebarWidgetHandler.widgetSwap$.subscribe(swap => {
            this.swapWidgets.set(swap);
        }));
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
    }

    getMaxColumns(): Observable<number> {
        return this.maxColumnCalculator.getMaxColumns(this.store.widgets$);
    }

    getDisplayWidgets(): boolean {
        return this.store.widgets && this.store.showSidebarWidgets;
    }

    getDisplay(display: boolean): string {
        let displayType = 'none';

        if (display) {
            displayType = 'block';
        }

        return displayType;
    }

    getViewContext(): ViewContext {
        return this.store.getViewContext();
    }
}
