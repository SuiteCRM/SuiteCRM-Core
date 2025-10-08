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
import {FilterListStore} from './filter-list.store';
import {AuthService} from '../../services/auth/auth.service';
import {ModuleNameMapper} from '../../services/navigation/module-name-mapper/module-name-mapper.service';
import {FiltersListGQL} from './graphql/api.list.get';
import {SystemConfigStore} from '../system-config/system-config.store';
import {UserPreferenceStore} from '../user-preference/user-preference.store';
import {LanguageStore} from '../language/language.store';
import {MessageService} from '../../services/message/message.service';
import {LocalStorageService} from "../../services/local-storage/local-storage.service";

@Injectable({
    providedIn: 'root',
})
export class FilterListStoreFactory {

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
    }

    /**
     * Create a new FilterListStore instance
     * @returns {object} FilterListStore
     */
    create(): FilterListStore {
        return new FilterListStore(
            this.listGQL,
            this.configs,
            this.preferences,
            this.language,
            this.message,
            this.auth,
            this.moduleNameMapper,
            this.localStorageService
        );
    }
}
