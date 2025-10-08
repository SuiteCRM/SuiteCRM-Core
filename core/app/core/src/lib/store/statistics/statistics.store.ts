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
import {StateStore} from '../state';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {deepClone} from '../../common/utils/object-utils';
import {StatisticsState} from '../../common/statistics/statistics-store.model';
import {StatisticsQuery, Statistic, StatisticsMap} from '../../common/statistics/statistics.model';
import {ViewContext} from '../../common/views/view.model';
import {distinctUntilChanged, map, shareReplay, tap} from 'rxjs/operators';
import {StatisticsFetchGQL} from './graphql/api.statistics.get';

const initialState = {
    module: '',
    query: {} as StatisticsQuery,
    statistic: {
        id: '',
        data: {}
    } as Statistic,
    loading: false
} as StatisticsState;

@Injectable()
export class StatisticsStore implements StateStore {
    state$: Observable<StatisticsState>;
    statistic$: Observable<Statistic>;
    loading$: Observable<boolean>;
    protected cache$: Observable<any> = null;
    protected internalState: StatisticsState = deepClone(initialState);
    protected store = new BehaviorSubject<StatisticsState>(this.internalState);

    constructor(
        protected fetchGQL: StatisticsFetchGQL,
    ) {
        this.state$ = this.store.asObservable();
        this.statistic$ = this.state$.pipe(map(state => state.statistic), distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading), distinctUntilChanged());
    }

    clear(): void {
        this.store.unsubscribe();
        this.cache$ = null;
    }

    clearAuthBased(): void {
        this.clear();
    }

    /**
     * Get Statistic query
     *
     * @returns {object} StatisticsQuery
     */
    public getQuery(): StatisticsQuery {
        return deepClone(this.internalState.query);
    }

    get context(): ViewContext {
        return this.internalState.query.context;
    }

    set context(context: ViewContext) {
        const query = deepClone(this.internalState.query);
        query.context = context;

        this.updateState({
            ...this.internalState,
            query
        });
    }

    /**
     * Initial list records load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @param {object} query to use
     * @param {boolean} load if to load
     * @returns {object} Observable<any>
     */
    public init(module: string, query: StatisticsQuery, load = true): Observable<Statistic> {
        this.internalState.module = module;
        this.updateState({
            ...this.internalState,
            module,
            query
        });

        if (load === false) {
            return null;
        }

        return this.load();
    }

    /**
     * Load / reload statistics
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<ListViewState>
     */
    public load(useCache = true): Observable<Statistic> {
        this.updateState({
            ...this.internalState,
            loading: true,
        });

        return this.fetchStatistics(this.internalState.module, this.getQuery(), useCache).pipe(
            map((data: StatisticsMap) => this.mapStatistics(data)),
            tap((statistic: Statistic) => {
                this.addNewState(statistic);
            })
        );
    }

    /**
     * Set loading
     *
     * @param {boolean} loading bool
     */
    public setLoading(loading: boolean): void {

        this.updateState({
            ...this.internalState,
            loading
        });
    }

    /**
     * Set Statistic value
     *
     * @param {string} key string
     * @param {object} statistic Statistic
     * @param {boolean} cache bool
     */
    public setStatistic(key: string, statistic: Statistic, cache = false): void {

        this.addNewState(statistic);

        if (!cache) {
            return;
        }

        const statMap: StatisticsMap = {};
        statMap[key] = statistic;

        this.cache$ = of(statMap).pipe(shareReplay(1));
    }

    protected addNewState(statistic: Statistic): void {
        this.updateState({
            ...this.internalState,
            statistic,
            loading: false
        });
    }

    protected mapStatistics(data: StatisticsMap): Statistic {
        const keys = Object.keys(data);
        const key = keys && keys.length && keys[0];
        let statistic = {id: '', data: {}} as Statistic;
        if (key) {
            statistic = data[key];
        }
        return statistic;
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: StatisticsState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Get records cached Observable or call the backend
     *
     * @param {string} module to use
     * @param {object} query to use
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<any>
     */
    protected fetchStatistics(
        module: string,
        query: StatisticsQuery,
        useCache = true
    ): Observable<StatisticsMap> {

        const queries = {};
        queries[query.key] = query;

        if (this.cache$ == null || useCache === false) {
            this.cache$ = this.fetchGQL.fetch(module, queries).pipe(
                shareReplay(1)
            );
        }
        return this.cache$;
    }

}
