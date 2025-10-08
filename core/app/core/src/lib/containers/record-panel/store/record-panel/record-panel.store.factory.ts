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
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {RecordManager} from '../../../../services/record/record.manager';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {RecordPanelStore} from './record-panel.store';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';

@Injectable({
    providedIn: 'root',
})
export class RecordPanelStoreFactory {

    constructor(
        protected recordFetchGQL: RecordFetchGQL,
        protected recordSaveGQL: RecordSaveGQL,
        protected appStateStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected metadataStore: MetadataStore,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected fieldManager: FieldManager,
        protected recordStoreFactory: RecordStoreFactory
    ) {
    }

    create(): RecordPanelStore {
        return new RecordPanelStore(
            this.appStateStore,
            this.metadataStore,
            this.message,
            this.fieldManager,
            this.languageStore,
            this.recordStoreFactory
        );
    }
}
