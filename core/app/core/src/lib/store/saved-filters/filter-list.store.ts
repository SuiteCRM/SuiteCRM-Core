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

import {Injectable} from '@angular/core';
import {StringMap} from '../../common/types/string-map';
import {Observable} from 'rxjs';
import {AuthService} from '../../services/auth/auth.service';
import {RecordListStore} from '../record-list/record-list.store';
import {SavedFilter, SavedFilterList} from './saved-filter.model';
import {ModuleNameMapper} from '../../services/navigation/module-name-mapper/module-name-mapper.service';
import {SystemConfigStore} from '../system-config/system-config.store';
import {UserPreferenceStore} from '../user-preference/user-preference.store';
import {LanguageStore} from '../language/language.store';
import {MessageService} from '../../services/message/message.service';
import {FiltersListGQL} from './graphql/api.list.get';
import {LocalStorageService} from "../../services/local-storage/local-storage.service";

@Injectable()
export class FilterListStore extends RecordListStore {

    records$: Observable<SavedFilter[]>;

    protected moduleName = 'saved-search';
    protected filterFields: StringMap = {
        module: 'search_module',
        user: 'assigned_user_id',
    };

    /**
     * Constructor
     * @param listGQL
     * @param configs
     * @param preferences
     * @param language
     * @param message
     * @param auth
     * @param moduleNameMapper
     * @param localStorageService
     */
    constructor(
        protected listGQL: FiltersListGQL,
        protected configs: SystemConfigStore,
        protected preferences: UserPreferenceStore,
        protected language: LanguageStore,
        protected message: MessageService,
        protected auth: AuthService,
        protected moduleNameMapper: ModuleNameMapper,
        protected localStorageService: LocalStorageService
    ) {
        super(listGQL, configs, preferences, language, message, localStorageService);
    }

    /**
     * Initialize store
     * @param module
     */
    init(module: string): Observable<SavedFilterList> {
        const result$ = super.init(this.moduleName, false);
        this.initCriteria(module);

        return result$;
    }

    /**
     * Load / reload records using current pagination and criteria
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<RecordList>
     */
    load(useCache = true): Observable<SavedFilterList> {
        return super.load(useCache);
    }

    /**
     * Get current list of saved filters
     */
    getFilters(): SavedFilter[] {
        return this.records;
    }

    /**
     * Add new filter to list
     * @param filter
     */
    addFilter(filter: SavedFilter): void {

        let isNew = true;
        const filters = this.records;

        const newList = [];

        filters.forEach(record => {
            if (record.id === filter.id) {
                newList.push(filter);
                isNew = false;
                return;
            }

            newList.push(record);
        });

        if (isNew) {
            newList.push(filter);
        }

        this.updateState({
            ...this.internalState,
            records: newList,
        });
    }

    /**
     * Remove existing filter
     * @param filter
     */
    removeFilter(filter: SavedFilter): void {


        if (!filter || !filter.id) {
            return;
        }

        const filters = this.records;

        const newList = [];

        filters.forEach(record => {
            if (record.id === filter.id) {
                return;
            }

            newList.push(record);
        });

        this.updateState({
            ...this.internalState,
            records: newList,
        });
    }

    /**
     * Initialize criteria
     * @param module
     */
    protected initCriteria(module: string): void {

        const criteria = this.criteria;
        criteria.filters[this.filterFields.module] = {
            field: this.filterFields.module,
            operator: '=',
            values: [this.moduleNameMapper.toLegacy(module)]
        };

        criteria.filters[this.filterFields.user] = {
            field: this.filterFields.user,
            operator: '=',
            values: [this.auth.getCurrentUser().id]
        };

        this.updateSearchCriteria(criteria, false);
    }

}
