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
import {AuthService} from './auth.service';
import {HttpHeaders, HttpParams} from '@angular/common/http';
import {of} from 'rxjs';
import {localStorageServiceMock} from '../local-storage/local-storage.service.spec.mock';
import {BaseRouteService} from '../base-route/base-route.service';
import {SystemConfigStore} from '../../store/system-config/system-config.store';

describe('Auth Service', () => {
    let httpMock = null;
    let routerMock = null;
    let messageMock = null;
    let stateManagerMock = null;
    let languageMock = null;
    let service = null;
    let appStateMock = null;

    beforeEach(() => {
        TestBed.configureTestingModule({});
        httpMock = jasmine.createSpyObj('HttpClient', ['post']);
        httpMock.post.and.callFake(() => of([]));

        routerMock = jasmine.createSpyObj('Router', ['navigate']);
        routerMock.navigate.and.callFake(() => ({
            finally: (): void => {
            }
        }));

        messageMock = jasmine.createSpyObj('MessageService', ['addSuccessMessageByKey', 'log']);
        messageMock.addSuccessMessageByKey.and.callFake(() => {
        });

        messageMock.log.and.callFake(() => {
        });


        stateManagerMock = jasmine.createSpyObj(
            'StateManager',
            [
                'clear',
                'clearAuthBased'
            ]
        );
        stateManagerMock.clear.and.callFake(() => {
        });

        languageMock = jasmine.createSpyObj('LanguageStore', ['getAppString']);
        languageMock.getAppString.and.callFake((key: string) => key);

        appStateMock = jasmine.createSpyObj('AppStateStore', ['updateLoading', 'updateInitialAppLoading', 'setCurrentUser', 'getActiveRequests']);
        appStateMock.getActiveRequests.and.returnValue(0);
        const config = jasmine.createSpyObj<SystemConfigStore>('SystemConfigStore', ['getConfigValue', 'clear']);
        config.getConfigValue.and.returnValue({after_logout_path: '#signed-out'});

        service = new AuthService(
            httpMock,
            routerMock,
            messageMock,
            stateManagerMock,
            languageMock,
            appStateMock,
            localStorageServiceMock,
            config,
            new BaseRouteService(),
            null,
            null,
            null
        );

    });

    it('uses the configured destination for logout redirects', () => {
        const callLogout = spyOn(service, 'callLogout');

        service.logout();

        expect(callLogout).toHaveBeenCalledOnceWith(
            'logout', jasmine.any(HttpParams), jasmine.any(HttpHeaders), true, 'LBL_LOGOUT_SUCCESS', false, '#signed-out'
        );
    });

    it('posts and clears the session when logout does not redirect', () => {
        const initialLocation = window.location.href;
        service.logout('LBL_LOGOUT_SUCCESS', false);
        expect(window.location.href).toBe(initialLocation);

        const body = new HttpParams();
        const headers = new HttpHeaders().set('Content-Type', 'text/plain; charset=utf-8');

        expect(httpMock.post).toHaveBeenCalledWith('logout', body.toString(), {headers, responseType: 'text'});

        expect(stateManagerMock.clearAuthBased).toHaveBeenCalledWith();
        expect(messageMock.addSuccessMessageByKey).toHaveBeenCalledWith('LBL_LOGOUT_SUCCESS');
        expect(messageMock.log).toHaveBeenCalledWith('Logout success');
    });
});

