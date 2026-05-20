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
import {ListViewStore} from '../../store/list-view/list-view.store';
import {FilterAdapter} from '../../adapters/filter.adapter';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {RecordPanelConfig} from '../../../../containers/record-panel/components/record-panel/record-panel.model';
import {Subscription} from 'rxjs';
import {RecordPanelAdapter} from '../../adapters/record-panel.adapter';
import {QuickFiltersService} from "../../services/quick-filters.service";
import {isTrue} from '../../../../common/utils/value-utils';

@Component({
    selector: 'scrm-list-header',
    templateUrl: 'list-header.component.html',
    providers: [FilterAdapter, RecordPanelAdapter],
})
export class ListHeaderComponent implements OnInit, OnDestroy {

    actionPanel = '';
    recordPanelConfig: RecordPanelConfig;
    showQuickFilters = false;
    enableQuickFilters = false;
    protected subs: Subscription[] = [];

    constructor(
        public filterAdapter: FilterAdapter,
        protected listStore: ListViewStore,
        protected moduleNavigation: ModuleNavigation,
        protected recordPanelAdapter: RecordPanelAdapter,
        public quickFilters: QuickFiltersService
    ) {
    }

    get moduleTitle(): string {
        const module = this.listStore.vm.appData.module;
        const appListStrings = this.listStore.vm.appData.language.appListStrings;
        return this.moduleNavigation.getModuleLabel(module, appListStrings);
    }

    ngOnInit(): void {
        this.listStore.actionPanel$.subscribe(actionPanel => {
            this.actionPanel = actionPanel;
            if (this.actionPanel === 'recordPanel') {
                this.recordPanelConfig = this.recordPanelAdapter.getConfig();
            } else {
                this.recordPanelConfig = null;
            }
        });

        this.subs.push(this.quickFilters.breakdown$.subscribe(breakdown => {
            this.showQuickFilters = isTrue(breakdown);
        }))

        this.subs.push(this.quickFilters.enabled$.subscribe(enabled => {
            this.enableQuickFilters = isTrue(enabled ?? false);
        }))
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.recordPanelConfig = null;
    }
}
