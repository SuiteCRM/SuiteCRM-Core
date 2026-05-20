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
import {ListViewStore} from "../store/list-view/list-view.store";
import {isTrue} from '../../../common/utils/value-utils';
import {Observable, Subscription} from "rxjs";
import {ScreenSizeObserverService} from "../../../services/ui/screen-size-observer/screen-size-observer.service";
import {SystemConfigStore} from "../../../store/system-config/system-config.store";
import {map} from "rxjs/operators";

@Injectable()
export class ListViewSidebarWidgetService {

    protected swapSizes: string[] = [];
    protected subs: Subscription[] = [];
    protected widgetSwap?: boolean;

    public widgetSwap$: Observable<boolean>;


    public constructor(
        protected systemConfigStore: SystemConfigStore,
        protected screenSize: ScreenSizeObserverService,
        protected store: ListViewStore
    ) {
        this.swapSizes = this.systemConfigStore.getUi('widget_swap_screen_sizes');

        this.widgetSwap$ = this.screenSize.screenSize$.pipe(map(screenSize => {
            const swap = isTrue(this.swapSizes[screenSize] ?? false);
            if ((this.widgetSwap === null && swap === true) || (this.widgetSwap !== swap && swap === true)) {
                this.store.showSidebarWidgets = false;
            }
            this.widgetSwap = swap;
            return swap;
        }))
    }

    destroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
    }
}
