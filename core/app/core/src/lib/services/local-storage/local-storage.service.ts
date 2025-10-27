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

@Injectable({
    providedIn: 'root'
})
export class LocalStorageService {

    protected storageKey = 'scrm-session-storage';
    protected stickyStorageKey = 'scrm-sticky-session-storage';

    constructor() {
    }

    clear(): void {
        this.getLocalStorage().removeItem(this.storageKey);
        const sticky = this.getLocalStorage().getItem(this.stickyStorageKey);

        if (sticky) {
            this.getLocalStorage().setItem(this.storageKey, sticky);
        }
    }

    set(key: string, data: any, sticky = false): void {
        this.store(this.storageKey, key, data);

        if (sticky) {
            this.store(this.stickyStorageKey, key, data);
        }
    }

    protected store(storageKey: string, key: string, data: any) {
        const storeJson = this.getLocalStorage().getItem(storageKey);
        let store = {};

        if (storeJson) {
            store = JSON.parse(storeJson);
        }

        store[key] = data;

        this.getLocalStorage().setItem(storageKey, JSON.stringify(store));
    }

    get(key: string): any {
        const storeJson = this.getLocalStorage().getItem(this.storageKey);
        let store = {};

        if (storeJson) {
            store = JSON.parse(storeJson);
        }

        return store[key] ?? {};
    }

    protected getLocalStorage(): Storage {
        return localStorage;
    }
}
