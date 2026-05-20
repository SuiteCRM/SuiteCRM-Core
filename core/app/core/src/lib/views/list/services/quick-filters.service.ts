/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

import {Injectable} from "@angular/core";
import {SavedFilter} from "../../../store/saved-filters/saved-filter.model";
import {ListViewStore} from "../store/list-view/list-view.store";
import {ButtonInterface} from '../../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../../common/components/button/button-group.model';
import {isTrue} from '../../../common/utils/value-utils';
import {BehaviorSubject, Observable, Subscription} from "rxjs";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../../services/ui/screen-size-observer/screen-size-observer.service";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";
import {map} from "rxjs/operators";

@Injectable()
export class QuickFiltersService {

    protected quickFiltersConfigState = new BehaviorSubject<ButtonGroupInterface>({buttons: []});
    protected enabledState = new BehaviorSubject<boolean>(false);
    protected screen: ScreenSize = ScreenSize.Medium;
    protected defaultBreakpoint = 5;
    protected breakpoint: number;
    protected breakdownSizes: string[] = [];
    protected subs: Subscription[] = [];

    public config$ = this.quickFiltersConfigState.asObservable();
    public enabled$ = this.enabledState.asObservable();
    public enabled = false;
    public breakdown$: Observable<boolean>;

    public constructor(
        protected systemConfigStore: SystemConfigStore,
        protected screenSize: ScreenSizeObserverService,
        protected store: ListViewStore
    ) {
        this.breakdownSizes = this.systemConfigStore.getUi('quick_filters_breakdown_screen_sizes');

        const displayedQuickFilters  = this.systemConfigStore.getUi('displayed_quick_filters');

        const quickFiltersBreakdownThresholds  = this.systemConfigStore.getUi('quick_filters_breakdown_threshold');

        this.breakdown$ = this.screenSize.screenSize$.pipe(map(screenSize => {
            const quickFiltersBreakpoint = displayedQuickFilters[screenSize] ?? 2;
            const maxQuickFiltersForDisplay = quickFiltersBreakdownThresholds[screenSize] ?? 2;

            if (quickFiltersBreakpoint > maxQuickFiltersForDisplay) {
                return true;
            }

            return isTrue(this.breakdownSizes[screenSize] ?? false);
        }))

        this.subs.push(this.screenSize.screenSize$.subscribe(currentScreenSize => {
            if (currentScreenSize) {
                this.screen = currentScreenSize;
            }
            this.init();
        }));
    }

    init(): void {
        let filters = this.store.filterList.getFilters() ?? [];
        filters = filters.filter(filter => filter?.attributes?.quick_filter ?? false);

        this.enabled = this.areConfigEnabled();
        if (!filters || filters.length < 1) {
            this.enabled = false;
            this.enabledState.next(false);
            return;
        }
        this.enabledState.next(this.enabled);

        const config = {
            buttonKlass: ['settings-button btn btn-outline-main'],
            dropdownLabel: this.store.appStrings.LBL_QUICK_FILTERS || '',
            breakpoint: this.getBreakpoint(),
            showAfterBreakpoint: false,
            dropdownOptions: {
                placement: ['bottom-right'],
                wrapperKlass: ['dropdown-button-secondary', 'filter-action-group']
            },
            buttons: []
        } as ButtonGroupInterface;

        const activeFilters = this.store.activeFilters;

        let anyActive = false;
        filters.forEach((filter: SavedFilter) => {

            const isQuickFilter = filter?.attributes?.quick_filter ?? false;

            if (!isQuickFilter) {
                return;
            }

            const isActive = Object.keys(activeFilters).some(key => key === filter.key);
            anyActive = anyActive || isActive;

            const button = {
                label: filter.attributes.name,
                title: filter.attributes.name,
                onClick: (): void => {
                    this.store.toggleQuickFilter(filter);
                }
            } as ButtonInterface;


            if (isActive) {
                button.klass = ['active'];
            }

            config.buttons.push(button);
        });

        if (anyActive) {
            config.dropdownOptions.klass = ['active'];
            config.dropdownOptions.icon = 'filter';
        }

        this.quickFiltersConfigState.next(config);
    }

    destroy() : void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
        this.quickFiltersConfigState.unsubscribe();
    }

    getBreakpoint(): number {
        const breakpointMap = this.systemConfigStore.getUi('displayed_quick_filters');

        if (this.screen && breakpointMap && breakpointMap[this.screen]) {
            this.breakpoint = breakpointMap[this.screen];
            return this.breakpoint;
        }

        if (this.breakpoint) {
            return this.breakpoint;
        }

        return this.defaultBreakpoint;
    }

    areConfigEnabled(): boolean {
        const enableMap = this.systemConfigStore.getUi('enable_quick_filters');

        if (!this.screen || !enableMap) {
            return false;
        }

        return isTrue(enableMap[this.screen] ?? false);
    }

}
