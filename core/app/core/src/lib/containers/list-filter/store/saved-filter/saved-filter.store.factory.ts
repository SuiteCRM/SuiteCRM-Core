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
import {SavedFilterStore} from './saved-filter.store';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {SavedFilterSaveGQL} from './graphql/api.saved-filters.save';
import {RecordMapperRegistry} from '../../../../common/record/record-mappers/record-mapper.registry';
import {BaseSaveRecordMapper} from '../../../../store/record/record-mappers/base-save.record-mapper';
import {SavedSearchRecordMapper} from './record-mappers/saved-search.record-mapper';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {SavedFilterRecordStoreFactory} from './saved-filter-record.store.factory';

@Injectable({
    providedIn: 'root',
})
export class SaveFilterStoreFactory {

    protected savedFilterStoreFactory: SavedFilterRecordStoreFactory;

    constructor(
        protected recordFetchGQL: RecordFetchGQL,
        protected recordSaveGQL: SavedFilterSaveGQL,
        protected appStateStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected metadataStore: MetadataStore,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected fieldManager: FieldManager,
        protected recordMappers: RecordMapperRegistry,
        protected baseMapper: BaseSaveRecordMapper,
        protected savedSearchMapper: SavedSearchRecordMapper
    ) {
        this.savedFilterStoreFactory = new SavedFilterRecordStoreFactory(
            recordFetchGQL,
            recordSaveGQL,
            message,
            recordManager,
            recordMappers,
            baseMapper,
            fieldManager,
            languageStore
        );
        this.recordMappers.register('saved-search', this.savedSearchMapper.getKey(), this.savedSearchMapper);
    }

    create(): SavedFilterStore {
        return new SavedFilterStore(
            this.appStateStore,
            this.metadataStore,
            this.message,
            this.fieldManager,
            this.languageStore,
            this.savedFilterStoreFactory
        );
    }
}
