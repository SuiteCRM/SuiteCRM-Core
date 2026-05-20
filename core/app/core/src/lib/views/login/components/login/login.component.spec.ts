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
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {LoginUiComponent} from './login.component';
import {By} from '@angular/platform-browser';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {ButtonLoadingUiModule} from '../../../../directives/button-loading/button-loading.module';
import {RecoverPasswordService} from '../../../../services/process/processes/recover-password/recover-password';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {recoverPasswordMock} from '../../../../services/process/processes/recover-password/recover-password.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('LoginComponent', () => {
    let component: LoginUiComponent;
    let fixture: ComponentFixture<LoginUiComponent>;

    beforeEach(waitForAsync(() => {

        TestBed.configureTestingModule({
    schemas: [CUSTOM_ELEMENTS_SCHEMA],
    declarations: [LoginUiComponent],
    imports: [RouterTestingModule,
        FormsModule,
        NoopAnimationsModule,
        ApolloTestingModule,
        ButtonLoadingUiModule],
    providers: [
        { provide: SystemConfigStore, useValue: systemConfigStoreMock },
        { provide: LanguageStore, useValue: languageStoreMock },
        { provide: RecoverPasswordService, useValue: recoverPasswordMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(LoginUiComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should flip on forgot password click', () => {
        expect(component).toBeTruthy();
        fixture.detectChanges();

        expect(fixture.debugElement.query(By.css('[name="email"]'))).toBeNull();
        expect(fixture.debugElement.query(By.css('.submit-button'))).toBeNull();
        expect(fixture.debugElement.query(By.css('.back-link'))).toBeNull();

        expect(fixture.debugElement.query(By.css('[name="password"]'))).toBeTruthy();
        expect(fixture.debugElement.query(By.css('#login-button'))).toBeTruthy();
        component.flipCard();

        fixture.detectChanges();

        expect(fixture.debugElement.query(By.css('[name="password"]'))).toBeNull();
        expect(fixture.debugElement.query(By.css('#login-button'))).toBeNull();
        expect(fixture.debugElement.query(By.css('.forgotten-password'))).toBeNull();

        expect(fixture.debugElement.query(By.css('[name="email"]'))).toBeTruthy();
        expect(fixture.debugElement.query(By.css('.submit-button'))).toBeTruthy();
        expect(fixture.debugElement.query(By.css('.back-link'))).toBeTruthy();
    });

    it('should output login fail status', () => {
        expect(component).toBeTruthy();
        console.log = jasmine.createSpy('log');
        component.onLoginError();

        expect(console.log).toHaveBeenCalledWith('Login failed');
    });
});
