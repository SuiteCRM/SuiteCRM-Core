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

import {SaveFilterStoreFactory} from './saved-filter.store.factory';
import {messageServiceMock} from '../../../../services/message/message.service.spec.mock';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {deepClone} from '../../../../common/utils/object-utils';
import {Record} from '../../../../common/record/record.model';
import {RecordMapperRegistry} from '../../../../common/record/record-mappers/record-mapper.registry';
import {BaseSaveRecordMapper} from '../../../../store/record/record-mappers/base-save.record-mapper';
import {SavedSearchRecordMapper} from './record-mappers/saved-search.record-mapper';
import {Observable, of} from 'rxjs';
import {recordManagerMock} from '../../../../services/record/record.manager.spec.mock';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {fieldManagerMock} from '../../../../services/record/field/field.manager.spec.mock';
import {SavedFilterSaveGQL} from './graphql/api.saved-filters.save';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const savedFilterMockData = {
    getRecordView: {
        id: '/docroot/api/record/c4da5f04-2d4a-7a14-35ff-5f242b8f8a52',
        record: {
            date_entered: null,
            date_modified: null,
            assigned_user_id: null,
            id: 'c4da5f04-2d4a-7a14-35ff-5f242b8f8a52',
            name: 'Some name',
            contents: [],
            created_by: null,
            created_by_name: null,
            modified_by_name: null,
            table_name: 'saved-search',
            object_name: 'SavedSearch',
            deleted: 0,
            module_name: 'SavedSearch',
        },
        _id: 'c4da5f04-2d4a-7a14-35ff-5f242b8f8a52'
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class SavedFilterViewGQLSpy extends RecordFetchGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, record: string, metadata: { fields: string[] }): Observable<any> {

        const data = {
            getRecordView: deepClone(savedFilterMockData.getRecordView)
        };

        return of(data);
    }
}

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class SavedFilterSaveGQLSpy extends SavedFilterSaveGQL {

    constructor() {
        super(null);
    }

    public save(record: Record): Observable<any> {

        return of({
            id: record.id,
            module: record.module,
            attributes: record.attributes
        });
    }
}

const mockFilterMapperRegistry = new RecordMapperRegistry();
const mockBaseRecordMapper = new BaseSaveRecordMapper();
const mockSavedFilterRecordMapper = new SavedSearchRecordMapper();

export const savedFilterStoreFactoryMock = new SaveFilterStoreFactory(
    new SavedFilterViewGQLSpy(),
    new SavedFilterSaveGQLSpy(),
    appStateStoreMock,
    languageStoreMock,
    metadataStoreMock,
    messageServiceMock,
    recordManagerMock,
    fieldManagerMock,
    mockFilterMapperRegistry,
    mockBaseRecordMapper,
    mockSavedFilterRecordMapper
);
