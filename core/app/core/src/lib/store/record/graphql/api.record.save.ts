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
import {Apollo, gql} from 'apollo-angular';
import {Observable} from 'rxjs';
import {deepClone} from '../../../common/utils/object-utils';
import {Record} from '../../../common/record/record.model';
import {map} from 'rxjs/operators';
import {ApolloQueryResult} from '@apollo/client/core';

interface SaveInput {
    module: string;
    attributes: { [key: string]: any };
    _id?: string;
}

@Injectable({
    providedIn: 'root'
})
export class RecordSaveGQL {

    constructor(protected apollo: Apollo) {
    }

    /**
     * Save record on the backend
     *
     * @param {object} record to save
     *
     * @returns {object} Observable<Record>
     */
    public save(record: Record): Observable<Record> {

        const input: SaveInput = {
            module: record.module,
            attributes: deepClone(record.attributes),
        };

        if (record.id) {
            // eslint-disable-next-line no-underscore-dangle
            input._id = record.id;
        }

        const mutationOptions = {
            mutation: gql`
                mutation saveRecord($input: saveRecordInput!) {
                    saveRecord(input: $input) {
                        record {
                            attributes
                            favorite
                            id
                            _id
                            module
                            acls
                        }
                    }
                }
            `,
            variables: {
                input
            },
        };

        return this.apollo.mutate(mutationOptions).pipe(map((result: ApolloQueryResult<any>) => this.mapToRecord(result)));
    }

    protected mapToRecord(response: ApolloQueryResult<any>): Record {
        if (!response.data || !response.data.saveRecord || !response.data.saveRecord.record) {
            return null;
        }

        return {
            // eslint-disable-next-line no-underscore-dangle
            id: response.data.saveRecord.record._id,
            type: response?.data?.saveRecord?.record?.type ?? '',
            module: response?.data?.saveRecord?.record?.module ?? '',
            attributes: response?.data?.saveRecord?.record?.attributes ?? [],
            acls: response?.data?.saveRecord?.record?.acls ?? [],
            favorite: response?.data.saveRecord?.record?.favorite ?? false
        } as Record;
    }
}
