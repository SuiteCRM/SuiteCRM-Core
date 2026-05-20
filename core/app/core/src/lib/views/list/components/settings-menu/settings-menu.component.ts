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
import {isTrue} from '../../../../common/utils/value-utils';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../../../common/components/button/button-group.model';
import {
    DropdownButtonInterface,
    DropdownButtonSection,
    DropdownButtonSectionMap,
    GroupedButtonInterface
} from '../../../../common/components/button/dropdown-button.model';
import {SearchCriteria, SearchCriteriaFilter} from '../../../../common/views/list/search-criteria.model';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {BehaviorSubject, combineLatestWith, Subscription} from 'rxjs';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {
    ScreenSize,
    ScreenSizeObserverService
} from '../../../../services/ui/screen-size-observer/screen-size-observer.service';
import {SavedFilter, SavedFilterMap} from '../../../../store/saved-filters/saved-filter.model';
import {QuickFiltersService} from "../../services/quick-filters.service";

@Component({
    selector: 'scrm-settings-menu',
    templateUrl: 'settings-menu.component.html',
})
export class SettingsMenuComponent implements OnInit, OnDestroy {

    configState = new BehaviorSubject<ButtonGroupInterface>({buttons: []});
    config$ = this.configState.asObservable();
    showQuickFilters = true;
    enableQuickFilters = false;

    protected screen: ScreenSize = ScreenSize.Medium;
    protected defaultBreakpoint = 5;
    protected breakpoint: number;
    protected subs: Subscription[] = [];

    constructor(
        protected listStore: ListViewStore,
        protected modalService: NgbModal,
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigStore: SystemConfigStore,
        public quickFilters: QuickFiltersService
    ) {
    }

    ngOnInit(): void {
        this.configState.next(this.getButtonGroupConfig());

        const vm$ = this.listStore.widgets$.pipe(
            combineLatestWith(
                this.listStore.displayFilters$,
                this.listStore.criteria$,
                this.screenSize.screenSize$,
                this.listStore.showSidebarWidgets$,
                this.listStore.filterList.records$
            )
        );

        this.subs.push(vm$.subscribe(
                (
                    [
                        widgets,
                        displayFilters,
                        criteria,
                        screenSize,
                        showSidebarWidgets,
                        savedFilters
                    ]
                ) => {
                    if (screenSize) {
                        this.screen = screenSize;
                    }
                    this.configState.next(this.getButtonGroupConfig());
                    this.quickFilters.init();
                }
            )
        );

        this.subs.push(this.quickFilters.breakdown$.subscribe(breakdown => {
            this.showQuickFilters = !isTrue(breakdown);
        }))

        this.subs.push(this.quickFilters.enabled$.subscribe(enabled => {
            this.enableQuickFilters = isTrue(enabled ?? false);
        }))
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getButtonGroupConfig(): ButtonGroupInterface {

        const availableButtons = [
            {
                show: (): boolean => this.checkFiltersDisplay(),
                button: this.getMyFiltersButton(),
            },
            {
                show: (): boolean => true,
                button: this.getFilterButton()
            },
            {
                show: (): boolean => this.listStore.widgets,
                button: this.getInsightsButton(),
            },
        ];

        const config = {
            buttonKlass: ['settings-button'],
            dropdownLabel: this.listStore.appStrings.LBL_OPTIONS || '',
            breakpoint: this.getBreakpoint(),
            dropdownOptions: {
                placement: ['bottom-right'],
                wrapperKlass: ['dropdown-button-secondary']
            },
            buttons: []
        } as ButtonGroupInterface;

        availableButtons.forEach(availableButton => {
            if (!availableButton.show) {
                config.buttons.push(availableButton.button);
                return;
            }

            if (availableButton.show()) {
                config.buttons.push(availableButton.button);
            }
        });
        return config;
    }

    checkFiltersDisplay(): boolean {
        const filters = this.listStore.filterList.getFilters() ?? [];
        const quickFilterBreakpoint = this.quickFilters.getBreakpoint();
        const totalFilters = filters.length;
        const totalQuickFilters = filters.filter(obj => obj.attributes.quick_filter).length;

        if (totalFilters > 0 && (totalQuickFilters > quickFilterBreakpoint || (totalFilters - totalQuickFilters) > 0)) {
            return true;
        }
        return false;
    }

    getFilters(): SearchCriteriaFilter {
        return this?.listStore?.recordList?.criteria?.filters ?? {};
    }

    getCurrentCriteria(): SearchCriteria {
        return this?.listStore?.recordList?.criteria ?? {};
    }

    hasActiveFilter(): boolean {
        const activeFilters = this.listStore.activeFilters;
        if (!activeFilters) {
            return false;
        }

        const filterKeys = Object.keys(activeFilters) ?? [];
        if (!filterKeys || !filterKeys.length) {
            return false;
        }

        if (filterKeys.length > 1) {
            return true;
        }

        const currentFilter = activeFilters[filterKeys[0]];

        return currentFilter.key && currentFilter.key !== '' && currentFilter.key !== 'default'
    }

    areAllCurrentCriteriaFilterEmpty(): boolean {
        return Object.keys(this.getFilters() ?? {}).every(key => this.getFilters()[key].operator === '')
    }

    isAnyFilterApplied(): boolean {
        return this.hasActiveFilter() || !this.areAllCurrentCriteriaFilterEmpty();
    }

    getBreakpoint(): number {

        const breakpointMap = this.systemConfigStore.getConfigValue('listview_settings_limits');

        if (this.screen && breakpointMap && breakpointMap[this.screen]) {
            this.breakpoint = breakpointMap[this.screen];
            return this.breakpoint;
        }

        if (this.breakpoint) {
            return this.breakpoint;
        }

        return this.defaultBreakpoint;
    }


    getFilterButton(): DropdownButtonInterface {

        const groupedFilterButton = {
            type: 'grouped',
            items: []
        } as GroupedButtonInterface;

        const filterButton = {
            label: this.listStore.appStrings.LBL_FILTER || '',
            klass: {
                'filter-settings-button': true,
                'btn btn-sm settings-button': true,
                active: this.isAnyFilterApplied()
            },
            onClick: (): void => {
                this.listStore.showFilters = !this.listStore.showFilters;
            }
        } as ButtonInterface;

        if (this.isAnyFilterApplied()) {
            filterButton.icon = 'filter';
        }

        groupedFilterButton.items.push(filterButton);

        if (this.isAnyFilterApplied()) {
            groupedFilterButton.items.push(this.getClearButton());
        }

        return groupedFilterButton;
    }

    getMyFiltersButton(): DropdownButtonInterface {
        const filters = this.listStore.filterList.getFilters();

        const dropdownConfig = {
            label: this.listStore.appStrings.LBL_SAVED_FILTER_SHORTCUT || '',
            klass: ['dropdown-toggle'],
            wrapperKlass: ['filter-action-group'],
            items: [],
            sections: {
                'quick-filters': {
                    labelKey: 'LBL_QUICK_FILTERS'
                } as DropdownButtonSection,
                'default': {
                    labelKey: 'LBL_SAVED_FILTER_SHORTCUT'
                } as DropdownButtonSection,
            } as DropdownButtonSectionMap
        } as DropdownButtonInterface;

        const activeFilters = this.listStore.activeFilters;

        let anyActive = false;
        let quickFilterCount = 0;
        const quickFilterBreakpoint = this.quickFilters.getBreakpoint();
        const isQuickFiltersEnabled = this.quickFilters.areConfigEnabled();
        filters.forEach((filter: SavedFilter) => {

            const isQuickFilterButton = isTrue(filter?.attributes?.quick_filter ?? false);
            if (isQuickFiltersEnabled && isQuickFilterButton && quickFilterCount < quickFilterBreakpoint) {
                quickFilterCount++;
                return;
            }

            const isActive = Object.keys(activeFilters).some(key => key === filter.key);
            anyActive = anyActive || isActive;

            const button = {
                label: filter.attributes.name,
                title: filter.attributes.name,
                section: isQuickFilterButton ? 'quick-filters' : 'default',
                onClick: (): void => {
                    this.listStore.showFilters = false;

                    if (isActive) {
                        this.listStore.resetFilters();

                    } else {
                        this.listStore.setOpenFilter(filter);
                        const selectedFilters = {} as SavedFilterMap;
                        selectedFilters[filter.key] = filter;
                        this.listStore.setFilters(selectedFilters);
                    }

                }
            } as ButtonInterface;


            if (isActive) {
                button.icon = 'filter';
                button.iconKlass = 'small';
                button.klass = ['active'];
            }

            dropdownConfig.items.push(button);
        });

        if (anyActive) {
            dropdownConfig.klass = ['dropdown-toggle', 'active'];
        }

        return dropdownConfig;
    }

    getClearButton(): ButtonInterface {
        return {
            label: 'x',
            titleKey: 'LBL_CLEAR_FILTER',
            klass: {
                'btn btn-sm settings-button clear-filter-button btn-main-light': true
            },
            onClick: (): void => {
                this.listStore.showFilters = false;
                this.listStore.resetFilters();
            }
        };
    }

    getInsightsButton(): ButtonInterface {

        return {
            label: this.listStore.appStrings.LBL_INSIGHTS || '',
            klass: {
                active: this.listStore.showSidebarWidgets
            },
            icon: 'pie',
            onClick: (): void => {
                this.listStore.showSidebarWidgets = !this.listStore.showSidebarWidgets;
            }
        };
    }
}
