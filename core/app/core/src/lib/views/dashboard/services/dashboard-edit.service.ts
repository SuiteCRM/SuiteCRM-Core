/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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
import {Observable, of} from 'rxjs';
import {catchError, map, take} from 'rxjs/operators';
import {Process, ProcessService} from '../../../services/process/process.service';

/**
 * Calls of the `dashboard-edit` process (see DashboardEditProcessHandler):
 * the data of the action, or null when the process failed. An action refused
 * by the editor gives its reason (error: 'max', 'locked'...).
 */
@Injectable({providedIn: 'root'})
export class DashboardEditService {

    constructor(protected processService: ProcessService) {
    }

    run<T = { [key: string]: any }>(options: { [key: string]: any }): Observable<T | null> {
        return this.processService.submit('dashboard-edit', options).pipe(
            take(1),
            map((process: Process) => process?.status === 'success' && process.data && (process.data as any).error !== true
                ? process.data as T
                : null),
            catchError(() => of(null))
        );
    }

    /** {done: true} actions (remove, layout, tabs...). */
    done(options: { [key: string]: any }): Observable<boolean> {
        return this.run<{ done: boolean }>(options).pipe(map(data => !!data?.done));
    }
}
