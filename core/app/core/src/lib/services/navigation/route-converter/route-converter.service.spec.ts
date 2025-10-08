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
import {RouteConverter} from './route-converter.service';
import {routeConverterMock} from './route-converter.service.spec.mock';

describe('Route Converter Service', () => {
    const service: RouteConverter = routeConverterMock;

    beforeEach(() => {
        TestBed.configureTestingModule({});
    });

    it('#toFrontEnd', () => {
        const baseUrl = 'index.php';
        const params = '?module=Accounts&return_module=Accounts&action=DetailView&record=9551c669-c8d4-01b8-ec01-5ea2ef347a6b';
        const legacyLink = baseUrl + params;
        const route = service.toFrontEndRoute(legacyLink);

        const expected = 'accounts/record/9551c669-c8d4-01b8-ec01-5ea2ef347a6b?return_module=Accounts';

        expect(route).toEqual(expected);
    });

    it('#toLegacy', () => {
        const params = {
            action: 'index',
            module: 'accounts'
        };

        const queryParams = {
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            return_action: 'DetailView',
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            return_module: 'Accounts'
        };
        const legacyLink = service.toLegacy(params, queryParams);

        const link = './legacy/index.php?return_action=DetailView&return_module=Accounts&module=Accounts&action=index';

        expect(legacyLink).toEqual(link);
    });
});

