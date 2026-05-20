/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {BehaviorSubject, Observable, of} from 'rxjs';
import {distinctUntilChanged, map, shareReplay} from 'rxjs/operators';
import {AppStateStore} from '../app-state/app-state.store';
import {StateStore} from '../state';
import {deepClone} from '../../common/utils/object-utils';
import {ObjectMap} from '../../common/types/object-map';
import {AdminMetadata} from './admin-metadata.model';


const initialState: AdminMetadata = {
    adminPanel: []
};

let internalState: AdminMetadata = deepClone(initialState);

let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class AdminMetadataStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    adminPanel$: Observable<ObjectMap>;

    protected store = new BehaviorSubject<AdminMetadata>(internalState);
    protected state$ = this.store.asObservable();

    constructor(
        protected appStateStore: AppStateStore,
    ) {
        this.adminPanel$ = this.state$.pipe(map(state => state.adminPanel), distinctUntilChanged());
    }

    /**
     * Public Api
     */

    /**
     * Clear state
     */
    public clear(): void {
        cache$ = null;
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
        this.clear();
    }

    /**
     * Returns the currently active admin panel
     *
     * @returns {object} the admin panel
     */
    public getAdminPanel(): AdminMetadata {
        return internalState.adminPanel;
    }

    /**
     * Check if loaded
     */
    public isCached(): boolean {
        return cache$ !== null;
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: AdminMetadata): void {
        this.store.next(internalState = state);
    }

    /**
     * Set pre-loaded adminMetadata and cache
     */
    public set(adminMetadata: AdminMetadata): void {
        cache$ = of(adminMetadata).pipe(shareReplay(1));
        this.updateState(adminMetadata);

    }


}
