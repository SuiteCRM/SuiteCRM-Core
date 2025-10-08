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
import {FetchResult} from '@apollo/client/core';

@Injectable({
    providedIn: 'root'
})
export class EntityMutationGQL {

    constructor(private apollo: Apollo) {
    }

    /**
     * Create record on the backend
     *
     * @param {string} graphqlEntityName to use
     * @param {string} entityName to use
     * @param {object} input values
     * @param {object} metadata with the fields to ask for
     *
     * @returns {object} Observable<any>
     */
    public create(graphqlEntityName: string,
                  entityName: string,
                  input: { [key: string]: any },
                  metadata: { fields: string[] }): Observable<FetchResult<any>> {

        const fields = metadata.fields;
        const mutationOptions = {
            mutation: gql`
                mutation create${entityName}($input: create${entityName}Input!) {
                  create${entityName}(input: $input) {
                    ${graphqlEntityName} {
                      ${fields.join('\n')}
                    }
                    clientMutationId
                  }
                }
            `,
            variables: {
                input
            },
        };

        return this.apollo.mutate(mutationOptions);
    }
}
