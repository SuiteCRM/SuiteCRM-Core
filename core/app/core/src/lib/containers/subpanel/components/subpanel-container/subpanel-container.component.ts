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

import {Component, computed, Input, OnDestroy, OnInit, Signal, signal, WritableSignal} from '@angular/core';
import {take} from 'rxjs/operators';
import {Observable, Subscription} from 'rxjs';
import {SubpanelContainerConfig} from './subpanel-container.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {SubpanelStore, SubpanelStoreMap} from '../../store/subpanel/subpanel.store';
import {MaxColumnsCalculator} from '../../../../services/ui/max-columns-calculator/max-columns-calculator.service';
import {ViewContext} from '../../../../common/views/view.model';
import {WidgetMetadata} from '../../../../common/metadata/widget.metadata';
import {isTrue} from '../../../../common/utils/value-utils';
import {GridWidgetConfig, StatisticsQueryArgs} from '../../../../components/grid-widget/grid-widget.component';
import {LocalStorageService} from "../../../../services/local-storage/local-storage.service";
import {FilterConfig} from "../../../list-filter/components/list-filter/list-filter.model";
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";

@Component({
    selector: 'scrm-subpanel-container',
    templateUrl: 'subpanel-container.component.html',
    providers: [MaxColumnsCalculator]
})
export class SubpanelContainerComponent implements OnInit, OnDestroy {

    @Input() config: SubpanelContainerConfig;

    isCollapsed = signal(true);
    toggleIcon = signal('arrow_down_filled');
    maxColumns$: Observable<number>;

    subpanels: SubpanelStoreMap;
    orderedSubpanels: WritableSignal<string[]> = signal([]);
    headerSubpanels: Signal<string[]> = signal([]);
    bodySubpanels: Signal<string[]> = signal([]);
    openSubpanels: WritableSignal<string[]> = signal([]);
    activeHiddenButtonsCount: Signal<number> = signal(0);

    filterConfig: FilterConfig;
    subs: Subscription[] = [];
    protected subpanelButtonLimits: any = {
        XSmall: 2,
        Small: 3,
        Medium: 3,
        Large: 5,
        XLarge: 5
    };
    protected subpanelButtonBreakpoint: WritableSignal<number> = signal(3);


    constructor(
        protected languageStore: LanguageStore,
        protected maxColumnCalculator: MaxColumnsCalculator,
        protected localStorage: LocalStorageService,
        protected preferences: UserPreferenceStore,
        protected systemConfigs: SystemConfigStore,
        protected screenSize: ScreenSizeObserverService,
    ) {
    }

    ngOnInit(): void {
        const module = this?.config?.parentModule ?? 'default';
        this.setCollapsed(isTrue(this.preferences.getUi(module, 'subpanel-container-collapse') ?? true));

        const subpanelButtonLimits = this.systemConfigs.getConfigValue('recordview_subpanel_button_limits') ?? {};
        if (subpanelButtonLimits && Object.keys(subpanelButtonLimits).length) {
            this.subpanelButtonLimits = subpanelButtonLimits;
        }

        this.openSubpanels.set(this.preferences.getUi(module, 'subpanel-container-open-subpanels') ?? []);

        this.subs.push(this.config.subpanels$.subscribe({
                next: (subpanelsMap) => {
                    this.subpanels = {...subpanelsMap};

                    const orderedSubpanels = Object.values(this.subpanels)
                        .filter(item => item?.metadata?.order !== undefined)
                        .sort((a, b) => (a.metadata.order ?? 0) - (b.metadata.order ?? 0))
                        .map(item => item.metadata.name);

                    if (orderedSubpanels) {
                        this.orderedSubpanels.set(orderedSubpanels);
                    }

                    if (!this.subpanels || !Object.keys(this.subpanels).length) {
                        return;
                    }

                    if (!this.openSubpanels() || this.openSubpanels().length < 1) {
                        return;
                    }

                    this.openSubpanels().forEach(subpanelKey => {
                        const subpanel = this.subpanels[subpanelKey];

                        if (!subpanel || subpanel.show) {
                            return;
                        }

                        subpanel.show = true;
                        subpanel.load().pipe(take(1)).subscribe();
                    });
                }
            })
        );

        this.headerSubpanels = computed(() => this.orderedSubpanels().slice(0, this.subpanelButtonBreakpoint()));
        this.bodySubpanels = computed(() => {
            const sliced = [...this.orderedSubpanels()];

            let count = 0;
            const groups = [];
            sliced.forEach(value => {
                if (count === 0) {
                    groups.push([]);
                }

                groups[groups.length - 1].push(value);

                count++;
                if (count >= this.subpanelButtonBreakpoint()) {
                    count = 0;
                }
            });

            if(count < this.subpanelButtonBreakpoint() && groups.length > 0) {
                const lastGroup = groups[groups.length - 1];
                const diff = this.subpanelButtonBreakpoint() - lastGroup.length;
                for (let i = 0; i < diff; i++) {
                    lastGroup.push('');
                }
                groups[groups.length - 1] = lastGroup;
            }

            return groups;
        });

        this.activeHiddenButtonsCount = computed(() => {
            const openSubpanelsSet = new Set(this.openSubpanels());
            const headerSubpanelsSet = new Set(this.headerSubpanels());

            return this.bodySubpanels().flat().reduce(
                (count, subpanelKey) => {
                    const isOpen = openSubpanelsSet.has(subpanelKey);
                    const inHeader = headerSubpanelsSet.has(subpanelKey);
                    return count + ((isOpen && !inHeader) ? 1 : 0);
                },
                0
            );
        });

        this.subs.push(this.screenSize.screenSize$.subscribe({
            next: (screenSize: ScreenSize) => {
                if (screenSize && this.subpanelButtonLimits[screenSize]) {
                    this.subpanelButtonBreakpoint.set(this.subpanelButtonLimits[screenSize]);
                }
            }
        }));

        this.maxColumns$ = this.getMaxColumns();
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getMaxColumns(): Observable<number> {
        return this.maxColumnCalculator.getMaxColumns(this.config.sidebarActive$);
    }

    toggleSubPanels(): void {
        this.setCollapsed(!this.isCollapsed());

        const module = this?.config?.parentModule ?? 'default';
        this.preferences.setUi(module, 'subpanel-container-collapse', this.isCollapsed());
    }

    showSubpanel(key: string, item: SubpanelStore): void {
        item.show = !item.show;

        let openSubpanels = [...this.openSubpanels()];

        if (item.show) {
            if (!openSubpanels.includes(key)) {
                openSubpanels.push(key);
            }
            item.load().pipe(take(1)).subscribe();
        } else {
            openSubpanels = openSubpanels.filter(subpanelKey => subpanelKey != key);
        }

        this.openSubpanels.set(openSubpanels);

        const module = this?.config?.parentModule ?? 'default';
        this.preferences.setUi(module, 'subpanel-container-open-subpanels', this.openSubpanels());
    }

    getCloseCallBack(key: string, item: SubpanelStore): Function {
        return () => this.showSubpanel(key, item);
    }

    getGridConfig(vm: SubpanelStore): GridWidgetConfig {

        if (!vm.metadata || !vm.metadata.subpanelWidget) {
            return {
                layout: null,
            } as GridWidgetConfig;
        }


        const layout = vm.getWidgetLayout();

        layout.rows.forEach(row => {

            if (!row.cols || !row.cols.length) {
                return;
            }

            row.cols.forEach(col => {

                if (!col.statistic) {
                    return;
                }

                const store = vm.getStatistic(col.statistic);
                if (store) {
                    col.store = store;
                }
            });

        });

        return {
            rowClass: 'statistics-sidebar-widget-row',
            columnClass: 'statistics-sidebar-widget-col',
            layout,
            widgetConfig: {} as WidgetMetadata,
            queryArgs: {
                module: vm.metadata.name,
                context: {module: vm.parentModule, id: vm.parentId} as ViewContext,
                params: {subpanel: vm.metadata.name},
            } as StatisticsQueryArgs,
        } as GridWidgetConfig;
    }

    protected setCollapsed(newCollapsedValue: boolean): void {
        this.isCollapsed.set(newCollapsedValue);
        this.setToggleIcon();
    }

    protected setToggleIcon(): void {
        this.toggleIcon.set((this.isCollapsed()) ? 'arrow_up_filled' : 'arrow_down_filled');
    }
}
