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

import {Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {FetchResult} from '@apollo/client/core';
import {ProcessService} from '../../process.service';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {AsyncActionService} from './async-action';
import {messageServiceMock} from '../../../message/message.service.spec.mock';
import {redirectBulkActionMock} from './actions/redirect/redirect.async-action.spec.mock';
import {exportBulkActionMock} from './actions/export/export.async-action.spec.mock';
import {EntityMutationGQL} from '../../../api/graphql-api/api.record.create';
import {noopActionMock} from './actions/noop/noop.async-action.spec.mock';
import {changelogAsyncActionMock} from './actions/changelog/changelog.async-action.spec.mock';

export const bulkActionMockData = {
    'bulk-merge': {
        data: {
            createProcess: {
                process: {
                    _id: 'bulk-password',
                    status: 'success',
                    async: false,
                    type: 'bulk-password',
                    messages: [],
                    data: {}
                },
                clientMutationId: null
            }
        }
    }

};

class BulkActionProcessMutationGQLSpy extends EntityMutationGQL {
    constructor() {
        super(null);
    }

    /* eslint-disable @typescript-eslint/no-unused-vars */
    public create(
        graphqlModuleName: string,
        coreModuleName: string,
        input: { [key: string]: any },
        metadata: { fields: string[] }
    ): Observable<FetchResult<any>> {

        return of(bulkActionMockData[input.options.action]).pipe(shareReplay());
    }

    /* eslint-enable @typescript-eslint/no-unused-vars */
}

const processServiceMock = new ProcessService(new BulkActionProcessMutationGQLSpy());

export const asyncActionServiceMock = new AsyncActionService(
    processServiceMock,
    appStateStoreMock,
    messageServiceMock,
    redirectBulkActionMock,
    exportBulkActionMock,
    noopActionMock,
    changelogAsyncActionMock
);
