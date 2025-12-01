/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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


import {Component, Input, OnDestroy, OnInit, signal} from "@angular/core";
import {TableConfig} from "../table.model";
import {ColumnDefinition} from "../../../common/metadata/list.metadata.model";
import {SortDirectionDataSource} from "../../sort-button/sort-button.model";
import {Observable, Subscription} from "rxjs";
import {SortDirection, SortingSelection} from "../../../common/views/list/list-navigation.model";
import {map} from "rxjs/operators";
import {ButtonInterface} from "../../../common/components/button/button.model";
import {ActiveLineAction} from "../../../common/actions/action.model";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../../services/ui/screen-size-observer/screen-size-observer.service";

@Component({
    selector: 'scrm-table-body-skeleton',
    templateUrl: 'table-body-skeleton.component.html',
})
export class TableBodySkeletonComponent implements OnInit, OnDestroy {

    @Input() config: TableConfig;
    @Input() columns: ColumnDefinition[] = [];
    @Input() displayedColumns: string[] = [];
    @Input() pageSize: number = 20;
    @Input() activeLineAction: ActiveLineAction;

    protected subs: Subscription[] = [];
    isMobile = signal(false);

    constructor(
        protected screenSize: ScreenSizeObserverService,
    ) {
    }

    showMoreButton: ButtonInterface;
    showActions: boolean = false;

    ngOnInit() {
        this.initButtons();
        this.subs.push(this.screenSize.screenSize$.subscribe((size) => {
            if (size === ScreenSize.XSmall && !this.isMobile()) {
                this.isMobile.set(true);
            } else if (size !== ScreenSize.XSmall && this.isMobile()) {
                this.isMobile.set(false);
            }
        }));

        this.subs.push(this.config.lineActions.getActions().pipe().subscribe((value) => {
            if (value.length > 0) {
                this.showActions = true;
                return;
            }

            this.showActions = false;
        }));

    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
    }


    getDisplayedColumns(column) {
        return Object.values(this.displayedColumns).includes(column.name);
    }


    getFieldSort(field: ColumnDefinition): SortDirectionDataSource {
        return {
            getSortDirection: (): Observable<SortDirection> => this.config.sort$.pipe(
                map((sort: SortingSelection) => {
                    let direction = SortDirection.NONE;

                    if (sort.orderBy === field.name) {
                        direction = sort.sortOrder;
                    }

                    return direction;
                })
            ),
            changeSortDirection: (direction: SortDirection): void => {
                this.config.updateSorting(field.name, direction);
            }
        } as SortDirectionDataSource;
    }

    protected initButtons() {
        this.showMoreButton = {
            icon: 'dots-vertical',
            disabled: signal(true),
            klass: 'line-action-item line-action float-right',
        } as ButtonInterface;
    }
}
