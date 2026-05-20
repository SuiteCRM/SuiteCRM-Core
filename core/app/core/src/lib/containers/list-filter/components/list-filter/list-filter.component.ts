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

import {Component, HostListener, Input, OnDestroy, OnInit} from '@angular/core';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {Record} from '../../../../common/record/record.model';
import {ScreenSizeMap} from '../../../../common/services/ui/resize.model';
import {Observable, of, Subscription} from 'rxjs';
import {map, shareReplay} from 'rxjs/operators';
import {FilterConfig} from './list-filter.model';
import {RecordGridConfig} from '../../../../components/record-grid/record-grid.model';
import {ListFilterStoreFactory} from '../../store/list-filter/list-filter.store.factory';
import {ListFilterStore} from '../../store/list-filter/list-filter.store';
import {SavedFilterActionsAdapter} from '../../adapters/actions.adapter';
import {SavedFilterActionAdapterFactory} from '../../adapters/actions.adapter.factory';

@Component({
    selector: 'scrm-list-filter',
    templateUrl: './list-filter.component.html',
    styleUrls: [],
})
export class ListFilterComponent implements OnInit, OnDestroy {

    @Input() config: FilterConfig;

    vm$: Observable<Record>;
    store: ListFilterStore;
    filterActionsAdapter: SavedFilterActionsAdapter;
    selectedActionButton:ButtonInterface;
    searchActionButton: ButtonInterface;
    saveActionButton: ButtonInterface;
    gridConfig: RecordGridConfig;

    protected subs: Subscription[] = [];

    @HostListener('keydown.enter', ['$event'])
    onEnterKey() {
        if (!this.selectedActionButton) {
            return;
        }
        this.selectedActionButton.onClick();
        event.preventDefault();
    }

    constructor(
        protected storeFactory: ListFilterStoreFactory,
        protected actionAdapterFactory: SavedFilterActionAdapterFactory
    ) {
        this.store = storeFactory.create();
        this.filterActionsAdapter = actionAdapterFactory.create(this.store.filterStore, this.store);
    }

    ngOnInit(): void {
        this.store.init(this.config);
        this.vm$ = this.store.vm$.pipe(map(([savedFilter]) => {
            const record = {...savedFilter};
            record.fields = savedFilter.criteriaFields;
            return record;
        }));

        this.searchActionButton = this.store.gridButtons.find(button => button.id === "search");

        this.saveActionButton = {
            id: 'save',
            onClick: () => {
                this.filterActionsAdapter.run('save');
            }
        } as ButtonInterface;

        this.gridConfig = {
            record$: this.store.filterStore.stagingRecord$,
            mode$: this.store.filterStore.mode$,
            fields$: this.store.filterStore.getViewFieldsKeys$(),
            actions: this.filterActionsAdapter,
            appendActions: true,
            klass: 'mt-2 p-2 saved-search-container rounded',
            buttonClass: 'btn btn-outline-danger btn-sm',
            labelDisplay: 'inline',
            rowClass: {
                'align-items-start': true,
                'align-items-center': false
            },
            colAlignItems: 'align-items-start',
            maxColumns$: of(4).pipe(shareReplay(1)),
            sizeMap$: of({
                handset: 1,
                tablet: 2,
                web: 4,
                wide: 4
            } as ScreenSizeMap).pipe(shareReplay(1))
        } as RecordGridConfig;
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.store.clear();
        this.store = null;
    }

    onFocusSearch(): void {
        this.selectedActionButton = this.searchActionButton;
    }

    onFocusSave(): void {
        this.selectedActionButton = this.saveActionButton;
    }
}
