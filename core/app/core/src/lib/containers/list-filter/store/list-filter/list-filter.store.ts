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

import {isEmpty} from 'lodash-es';
import {Injectable} from '@angular/core';
import {StateStore} from '../../../../store/state';
import {emptyObject} from '../../../../common/utils/object-utils';
import {isVoid} from '../../../../common/utils/value-utils';
import {Field} from '../../../../common/record/field.model';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {DropdownButtonInterface} from '../../../../common/components/button/dropdown-button.model';
import {SearchCriteriaFieldFilter} from '../../../../common/views/list/search-criteria.model';
import {SearchMetaFieldMap} from '../../../../common/metadata/list.metadata.model';
import {ViewMode} from '../../../../common/views/view.model';
import {map, take, tap} from 'rxjs/operators';
import {BehaviorSubject, combineLatestWith, Observable, Subscription} from 'rxjs';
import {FilterConfig} from '../../components/list-filter/list-filter.model';
import {MessageService} from '../../../../services/message/message.service';
import {SaveFilterStoreFactory} from '../saved-filter/saved-filter.store.factory';
import {SavedFilterStore} from '../saved-filter/saved-filter.store';
import {SavedFilter} from '../../../../store/saved-filters/saved-filter.model';

@Injectable()
export class ListFilterStore implements StateStore {

    config: FilterConfig;
    panelMode: 'collapsible' | 'closable' | 'none' = 'closable';
    mode: ViewMode = 'filter';

    isCollapsed$: Observable<boolean>;

    closeButton: ButtonInterface;
    myFilterButton: DropdownButtonInterface;

    gridButtons: ButtonInterface[] = null;

    fields: Field[] = [];
    special: Field[] = [];

    vm$: Observable<any>;

    filterStore: SavedFilterStore;
    savedFilters: SavedFilter[] = [];
    displayFields: Field[];

    protected collapse: BehaviorSubject<boolean>;
    protected searchFields: SearchMetaFieldMap;
    protected subs: Subscription[] = [];

    constructor(
        protected message: MessageService,
        protected savedFilterStoreFactory: SaveFilterStoreFactory
    ) {
        this.filterStore = savedFilterStoreFactory.create();
    }


    init(config: FilterConfig): void {
        this.config = config;

        this.initSearchFields();

        this.initConfigUpdatesSubscription();

        this.vm$ = this.filterStore.stagingRecord$.pipe(
            map(stagingRecord => [stagingRecord]),
            tap(([savedFilter]) => {
                this.reset();
                this.splitCriteriaFields(savedFilter);
                this.initDisplayFields();

                if (this.filterStore.getMode() !== 'detail') {
                    this.initGridButtons();
                }

                this.initHeaderButtons();
                this.initMyFiltersButton(this.savedFilters);
            })
        );

        if (this.config.panelMode) {
            this.panelMode = this.config.panelMode;
        }

        this.collapse = new BehaviorSubject<boolean>(false);
        this.isCollapsed$ = this.collapse.asObservable();
        if (!isVoid(this.config.isCollapsed)) {
            this.collapse.next(this.config.isCollapsed);
        }

        this.reset();

        this.initGridButtons();
        this.initHeaderButtons();
    }

    clear(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.reset();
        this.filterStore.clear();
        this.filterStore = null;
        this.collapse.unsubscribe();
    }

    clearAuthBased(): void {
        this.clear();
    }

    /**
     * Reset criteria
     */
    public reset(): void {
        this.fields = [];
        this.special = [];
    }

    /**
     * Apply current filter values
     */
    public applyFilter(): void {
        this.filterStore.validateCriteria().pipe(take(1)).subscribe(valid => {

            if (valid) {

                if (this.config.panelMode === 'collapsible' && this.config.collapseOnSearch) {
                    this.collapse.next(true);
                }

                this.config.onSearch();
                this.config.updateFilter(this.filterStore.getBaseRecord());
                return;
            }

            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
        });

    }

    /**
     * Clear the current filter
     */
    public clearFilter(): void {
        this.config.resetFilter(false);
    }

    /**
     * Subscribe to config updates.
     * Each time the filter or metadata changes we need to:
     * - Reset the view state
     * - Re-initialize the filter subscription
     */
    protected initConfigUpdatesSubscription() {
        this.subs.push(
            this.config.filter$.pipe(
                combineLatestWith(this.config.searchFields$),
                tap(([filter, searchFields]: [SavedFilter, SearchMetaFieldMap]) => {
                    this.reset();
                    let mode = 'edit' as ViewMode;

                    const isReadOnly = filter.readonly ?? false;
                    if (isReadOnly) {
                        mode = 'detail' as ViewMode;
                        this.mode = mode;
                    }

                    this.filterStore.initRecord(
                        this.config.module,
                        filter,
                        searchFields,
                        this.config.listFields,
                        mode
                    );
                })
            ).subscribe()
        );

        this.subs.push(
            this.config.savedFilters$.pipe(
                map(value => [value]),
                tap(([filters]) => {
                    this.savedFilters = filters;
                    this.initMyFiltersButton(filters);
                })
            ).subscribe()
        );
    }

    /**
     * Split fields per slots
     *
     * @param {object} savedFilter to use
     */
    protected splitCriteriaFields(savedFilter: SavedFilter): void {

        if (!savedFilter || !savedFilter.criteriaFields) {
            return;
        }

        Object.entries(savedFilter.criteriaFields).forEach(([key, field]) => {
            const name = field.name || key;

            if (name.includes('_only')) {
                this.special.push(field);
                return;
            }

            if (!field.vardefBased) {
                this.fields.push(field);
                return;
            }

            if (field.readonly === true) {
                return;
            }

            const filters = savedFilter?.criteria?.filters ?? {};
            const fieldFilter = (
                filters[key] ?? {}
            ) as SearchCriteriaFieldFilter;



            if (!isEmpty(fieldFilter?.operator) && field.display() === 'none') {
                field.display.set('default');
            }

            this.fields.push(field);

        });
    }

    protected initSearchFields(): void {
        this.subs.push(this.config.searchFields$.subscribe(searchFields => {
            this.searchFields = searchFields;
        }));
    }

    protected initDisplayFields(): void {

        if (!this.searchFields || emptyObject(this.searchFields) || !this.fields) {
            this.displayFields = [];
        }

        const fields = [];
        this.fields.forEach(field => {
            const name = field.name;


            if (field?.display() === 'none' || field.source === 'groupField') {
                return;
            }

            if (!this.searchFields[name]) {
                field.readonly = true;
            }
            fields.push(field);
        });

        this.displayFields = fields;
    }

    /**
     * Initialize grid buttons
     */
    protected initGridButtons(): void {
        this.gridButtons = [
            {
                id: 'clear',
                labelKey: 'LBL_CLEAR_BUTTON_LABEL',
                klass: ['clear-filters-button', 'btn', 'btn-outline-danger', 'btn-sm'],
                onClick: this.clearFilter.bind(this)
            },
            {
                id: 'search',
                labelKey: 'LBL_SEARCH_BUTTON_LABEL',
                klass: ['filter-button', 'btn', 'btn-danger', 'btn-sm'],
                onClick: this.applyFilter.bind(this)
            }
        ] as ButtonInterface[];
    }

    /**
     * Initialize header buttons
     */
    protected initHeaderButtons(): void {

        this.closeButton = {
            onClick: (): void => {
                this.config.onClose();
            }
        } as ButtonInterface;


    }

    protected initMyFiltersButton(filters: SavedFilter[]): void {

        if (!filters || filters.length < 1) {
            this.myFilterButton = null;
            return;
        }

        const button = {
            wrapperKlass: ['filter-select'],
            labelKey: 'LBL_SAVED_FILTER_SHORTCUT',
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            items: [],
        } as DropdownButtonInterface;

        const currentKey = this.filterStore.getRecordId();

        filters.forEach(filter => {
            const item = {
                label: filter.attributes.name,
                onClick: () => {
                    this.config.setOpenFilter(filter);
                }
            } as ButtonInterface;

            if (filter.key === currentKey) {
                button.label = filter.attributes.name;
                button.labelKey = '';
                item.icon = 'filter';
                item.iconKlass = 'small';
                item.klass = 'active';
            }

            button.items.push(item);
        });

        this.myFilterButton = button;
    }
}
