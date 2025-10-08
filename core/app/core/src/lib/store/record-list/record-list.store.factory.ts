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
import {ListGQL} from './graphql/api.list.get';
import {SystemConfigStore} from '../system-config/system-config.store';
import {UserPreferenceStore} from '../user-preference/user-preference.store';
import {LanguageStore} from '../language/language.store';
import {MessageService} from '../../services/message/message.service';
import {RecordListStore} from './record-list.store';
import {LocalStorageService} from "../../services/local-storage/local-storage.service";

@Injectable({
    providedIn: 'root',
})
export class RecordListStoreFactory {

    constructor(
        protected listGQL: ListGQL,
        protected configStore: SystemConfigStore,
        protected preferencesStore: UserPreferenceStore,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected localStorageService: LocalStorageService
    ) {
    }

    create(): RecordListStore {
        return new RecordListStore(
            this.listGQL,
            this.configStore,
            this.preferencesStore,
            this.languageStore,
            this.message,
            this.localStorageService
        );
    }
}
