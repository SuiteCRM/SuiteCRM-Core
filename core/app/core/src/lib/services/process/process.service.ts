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
import {Observable} from 'rxjs';
import {map} from 'rxjs/operators';
import {EntityMutationGQL} from '../api/graphql-api/api.record.create';

export interface ProcessOptions {
    [key: string]: any;
}

export interface Process {
    id: string;
    _id: string;
    status: string;
    async: boolean;
    type: string;
    options: ProcessOptions;
    data?: ProcessOptions;
    messages: string[];
}

const blankProcess: Process = {
    id: null,
    _id: null,
    status: null,
    async: null,
    type: null,
    options: null,
    messages: []
};


@Injectable({
    providedIn: 'root',
})
export class ProcessService {

    protected graphqlName = 'process';
    protected coreName = 'Process';

    protected createFieldsMetadata = {
        fields: [
            '_id',
            'status',
            'async',
            'type',
            'messages',
            'data'
        ]
    };

    constructor(private recordMutationGQL: EntityMutationGQL) {
    }

    /**
     * Public Api
     */

    /**
     * Submit and action/process request
     * Returns observable
     *
     * @param {string} type to create
     * @param {object} options to send
     * @returns {object} Observable<any>
     */
    public submit(type: string, options: ProcessOptions): Observable<Process> {
        return this.create(type, options);
    }

    /**
     * Internal API
     */


    /**
     * Create a process on the backend
     *
     * @param {string} type to create
     * @param {object} options to send
     * @returns {object} Observable<any>
     */
    protected create(type: string, options: ProcessOptions): Observable<Process> {

        const input = {
            type,
            options
        };

        return this.recordMutationGQL.create(this.graphqlName, this.coreName, input, this.createFieldsMetadata)
            .pipe(map(({data}) => {
                const process: Process = {...blankProcess};

                if (data.createProcess && data.createProcess.process) {
                    return data.createProcess.process;
                }

                return process;
            }));
    }
}
