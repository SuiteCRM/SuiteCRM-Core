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

import {FiltersListGQL} from './graphql/api.list.get';
import {appStateStoreMock} from '../app-state/app-state.store.spec.mock';
import {languageStoreMock} from '../language/language.store.spec.mock';
import {messageServiceMock} from '../../services/message/message.service.spec.mock';
import {systemConfigStoreMock} from '../system-config/system-config.store.spec.mock';
import {userPreferenceStoreMock} from '../user-preference/user-preference.store.spec.mock';
import {Observable, of} from 'rxjs';
import {deepClone} from '../../common/utils/object-utils';
import {User} from '../../common/types/user';
import {AuthService} from '../../services/auth/auth.service';
import {moduleNameMapperMock} from '../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {FilterListStoreFactory} from './filter-list.store.factory';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const filterListMockData = {
    recordList: {
        id: '/docroot/api/records/accounts',
        meta: {
            offsets: {
                current: 10,
                next: 20,
                prev: 0,
                end: 80,
                total: '83',
                totalCounted: true
            },
            ordering: {
                orderBy: 'date_entered',
                sortOrder: 'ASC'
            }
        },
        records: [{
            id: '29319818-dc26-f57d-03e1-5ed77dedd691',
            relationships: [],
            type: 'SavedSearch',
            attributes: {
                name: '',
                contents: {
                    name: '',
                    filters: {}
                },
            }
        }]
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class FilterListGQLSpy extends FiltersListGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(
        module: string,
        limit: number,
        offset: number,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        criteria: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        sort: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        metadata: { fields: string[] }
    ): Observable<any> {

        const data = {
            data: {
                recordList: deepClone(filterListMockData.recordList)
            }
        };

        data.data.recordList.meta.offsets = {
            current: offset,
            next: (offset + limit) || 0,
            prev: (offset - limit) || 0,
            total: 200,
            end: 180
        };

        return of(data);
    }
}

class AuthServiceMock extends AuthService {

    constructor() {
        super(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        )
    }

    getCurrentUser(): User {
        return {
            id: '1',
            firstName: 'admin',
            lastName: 'admin',
            userName: 'admin'
        };
    }
}

const authServiceMock = new AuthServiceMock();

export const filterListStoreFactoryMock = new FilterListStoreFactory(
    new FilterListGQLSpy(),
    systemConfigStoreMock,
    userPreferenceStoreMock,
    appStateStoreMock,
    languageStoreMock,
    messageServiceMock,
    authServiceMock,
    moduleNameMapperMock
);
