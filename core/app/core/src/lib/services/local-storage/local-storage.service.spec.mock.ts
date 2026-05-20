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

import {LocalStorageService} from './local-storage.service';

class MockLocalStorage implements Storage {
    [name: string]: any;

    store = {};

    readonly length: number;

    clear(): void {
        this.store = {};
    }

    getItem(key: string): string | null {
        return this.store[key];
    }

    key(index: number): string | null {
        const keys = Object.keys(this.store);

        if (keys && keys[index]) {
            return keys[index];
        }

        return null;
    }

    removeItem(key: string): void {
        delete this.store[key];
    }

    setItem(key: string, value: string): void {
        this.store[key] = value;
    }
}

export class MockLocalStorageService extends LocalStorageService {
    store = new MockLocalStorage();

    getStore(): MockLocalStorage {
        return this.store;
    }

    protected getLocalStorage(): Storage {
        return this.store;
    }
}

export const localStorageServiceMock = new MockLocalStorageService();
