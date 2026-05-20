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

import {ComponentFixture, TestBed} from '@angular/core/testing';
import {CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';

import {NgbModule} from '@ng-bootstrap/ng-bootstrap';

import {NavbarUiComponent} from './navbar.component';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {navigationMock} from '../../store/navigation/navigation.store.spec.mock';
import {NavigationStore} from '../../store/navigation/navigation.store';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {userPreferenceStoreMock} from '../../store/user-preference/user-preference.store.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('NavbarUiComponent', () => {


    describe('Test with mock service', () => {
        let component: NavbarUiComponent;
        let fixture: ComponentFixture<NavbarUiComponent>;

        beforeEach(() => {
            TestBed.configureTestingModule({
    schemas: [CUSTOM_ELEMENTS_SCHEMA],
    declarations: [NavbarUiComponent],
    imports: [RouterTestingModule,
        NgbModule,
        ApolloTestingModule],
    providers: [
        { provide: NavigationStore, useValue: navigationMock },
        { provide: LanguageStore, useValue: languageStoreMock },
        { provide: UserPreferenceStore, useValue: userPreferenceStoreMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
}).compileComponents();
            fixture = TestBed.createComponent(NavbarUiComponent);
            component = fixture.componentInstance;
        });

        it('should create', () => {
            fixture.detectChanges();

            expect(component).toBeTruthy();
        });

    });

});
