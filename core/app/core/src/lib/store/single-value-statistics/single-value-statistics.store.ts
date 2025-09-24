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
import {BehaviorSubject, Observable} from 'rxjs';
import {deepClone} from '../../common/utils/object-utils';
import {StatisticsFetchGQL} from '../statistics/graphql/api.statistics.get';
import {StatisticsStore} from '../statistics/statistics.store';
import {distinctUntilChanged, map} from 'rxjs/operators';
import {FieldManager} from '../../services/record/field/field.manager';
import {
    SingleValueStatistic,
    SingleValueStatisticsData,
    Statistic,
    StatisticsQuery
} from '../../common/statistics/statistics.model';
import {
    SingleValueStatisticsState,
    SingleValueStatisticsStoreInterface
} from '../../common/statistics/statistics-store.model';
import {LanguageStore} from "../language/language.store";

const initialState = {
    module: '',
    query: {} as StatisticsQuery,
    statistic: {
        id: '',
        data: {} as SingleValueStatisticsData
    } as SingleValueStatistic,
    loading: false
} as SingleValueStatisticsState;


@Injectable()
export class SingleValueStatisticsStore extends StatisticsStore implements SingleValueStatisticsStoreInterface {
    state$: Observable<SingleValueStatisticsState>;
    statistic$: Observable<Statistic>;
    loading$: Observable<boolean>;
    protected cache$: Observable<any> = null;
    protected internalState: SingleValueStatisticsState = deepClone(initialState);
    protected store = new BehaviorSubject<SingleValueStatisticsState>(this.internalState);

    constructor(
        protected fetchGQL: StatisticsFetchGQL,
        protected fieldManager: FieldManager,
        protected language: LanguageStore,
    ) {
        super(fetchGQL);
        this.state$ = this.store.asObservable();
        this.statistic$ = this.state$.pipe(map(state => state.statistic), distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading), distinctUntilChanged());
    }

    protected addNewState(statistic: Statistic): void {

        if (!statistic.metadata || !statistic.metadata.dataType) {
            return;
        }

        if (Object.keys((statistic?.data?.fields ?? [])).length === 0){
            const field = this.fieldManager.buildShallowField(statistic.metadata.dataType, statistic.data.value);

            field.metadata = {
                digits: 0
            };

            this.updateState({
                ...this.internalState,
                statistic,
                field,
                loading: false
            });
            return;
        }

        const fields = [];

        Object.keys(statistic.data.fields).forEach((key) => {
            const label = statistic.data.fields[key].labelKey;
            let value = statistic.data.fields[key].value;

            if (statistic?.data?.fields[key]?.useLabelAsValue ?? false) {
                value = this.language.getFieldLabel(label, this.internalState?.module);
            }

            const builtField = this.fieldManager.buildShallowField(statistic.metadata.dataType, value, label);

            builtField.metadata = {
                digits: 0
            }

            fields[key] = builtField;
        })

        this.updateState({
            ...this.internalState,
            statistic,
            fields,
            loading: false
        });
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: SingleValueStatisticsState): void {
        super.updateState(state);
    }
}
