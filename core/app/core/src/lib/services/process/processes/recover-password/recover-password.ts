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
import {catchError, tap} from 'rxjs/operators';
import {Process, ProcessService} from '../../process.service';
import {AppStateStore} from '../../../../store/app-state/app-state.store';

@Injectable({
    providedIn: 'root',
})
export class RecoverPasswordService {

    protected processType = 'recover-password';

    constructor(private processService: ProcessService, private appStateStore: AppStateStore) {
    }

    /**
     * Send recover password request
     *
     * @param {string} userName to check
     * @param {string} email to check
     * @returns {{}} Observable<Process>
     */
    public run(userName: string, email: string): Observable<Process> {
        const options = {
            username: userName,
            useremail: email
        };

        this.appStateStore.updateLoading('recover-password', true, false);

        return this.processService
            .submit(this.processType, options)
            .pipe(
                tap(() => this.appStateStore.updateLoading('recover-password', false, false)),
                catchError(err => {
                    this.appStateStore.updateLoading('recover-password', false, false);
                    throw err;
                }),
            );
    }
}
