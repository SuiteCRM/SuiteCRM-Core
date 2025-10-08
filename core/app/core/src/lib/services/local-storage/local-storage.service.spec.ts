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

import {TestBed} from '@angular/core/testing';

import {LocalStorageService} from './local-storage.service';
import {
    localStorageServiceMock,
    MockLocalStorageService
} from './local-storage.service.spec.mock';

describe('LocalStorageService', () => {
    beforeEach(() => TestBed.configureTestingModule({}));

    it('should be created', () => {
        const service: LocalStorageService = TestBed.inject(LocalStorageService);

        expect(service).toBeTruthy();
    });

    it('should store items', () => {
        const mockService: MockLocalStorageService = localStorageServiceMock;

        expect(mockService).toBeTruthy();

        const criteria = {filters: {name: {values: ['test']}}};
        mockService.clear();
        mockService.set('criteria', criteria);
        const store = mockService.getStore();
        const internalItem = store.getItem('scrm-session-storage');

        expect(internalItem).toBeTruthy();
        expect(internalItem).toEqual(JSON.stringify({criteria}));

        const retrievedCriteria = mockService.get('criteria');

        expect(retrievedCriteria).toBeTruthy();
        expect(retrievedCriteria).toEqual(criteria);
    });

    it('should allow to remove items', () => {
        const mockService: MockLocalStorageService = localStorageServiceMock;

        expect(mockService).toBeTruthy();

        const criteria = {filters: {name: {values: ['test']}}};
        mockService.clear();
        mockService.set('criteria', criteria);
        let store = mockService.getStore();
        let internalItem = store.getItem('scrm-session-storage');

        expect(internalItem).toBeTruthy();
        expect(internalItem).toEqual(JSON.stringify({criteria}));

        mockService.clear();

        store = mockService.getStore();
        internalItem = store.getItem('scrm-session-storage');

        expect(internalItem).not.toBeTruthy();
    });
});
