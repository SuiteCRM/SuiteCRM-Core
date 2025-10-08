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
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {SingleValueStatisticsStoreFactory} from '../../../../store/single-value-statistics/single-value-statistics.store.factory';
import {LanguageStore} from '../../../../store/language/language.store';
import {SubpanelStore} from './subpanel.store';
import {FilterListStoreFactory} from "../../../../store/saved-filters/filter-list.store.factory";
import {MetadataStore} from "../../../../store/metadata/metadata.store.service";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";

@Injectable({
    providedIn: 'root',
})
export class SubpanelStoreFactory {

    constructor(
        protected listStoreFactory: RecordListStoreFactory,
        protected languageStore: LanguageStore,
        protected statisticsStoreFactory: SingleValueStatisticsStoreFactory,
        protected filterListStoreFactory: FilterListStoreFactory,
        protected meta: MetadataStore,
        protected preferences: UserPreferenceStore
    ) {
    }

    create(): SubpanelStore {
        return new SubpanelStore(this.listStoreFactory, this.languageStore, this.statisticsStoreFactory, this.filterListStoreFactory, this.meta, this.preferences);
    }
}
