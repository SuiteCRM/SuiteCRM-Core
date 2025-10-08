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

import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';
import {CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';

import {LogoutUiComponent} from './logout.component';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {AuthService} from '../../services/auth/auth.service';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('LogoutComponent', () => {
    let component: LogoutUiComponent;
    let fixture: ComponentFixture<LogoutUiComponent>;

    const authServiceMock = {
        logout: jasmine.createSpy('logout')
    };

    const label = 'LBL_LOGOUT';

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    schemas: [CUSTOM_ELEMENTS_SCHEMA],
    declarations: [LogoutUiComponent],
    imports: [RouterTestingModule,
        ApolloTestingModule],
    providers: [
        {
            provide: AuthService, useValue: authServiceMock
        },
        {
            provide: LanguageStore, useValue: {
                vm$: of(languageStoreMock).pipe(take(1))
            }
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
}).compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(LogoutUiComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
        expect(component.logout.logoutAction.label).toBeTruthy();
    });

    it('should have the correct label', () => {
        expect(component).toBeTruthy();
        expect(component.logout.logoutAction.label).toEqual(label);
    });

    it('should call logout component', () => {
        expect(component).toBeTruthy();
        component.doLogout();

        expect(authServiceMock.logout).toHaveBeenCalledWith();
    });
});
