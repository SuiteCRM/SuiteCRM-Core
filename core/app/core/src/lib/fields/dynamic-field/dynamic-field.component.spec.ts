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

import {DynamicFieldComponent} from './dynamic-field.component';
import {UntypedFormControl, FormsModule} from '@angular/forms';
import {CommonModule} from '@angular/common';
import {RouterTestingModule} from '@angular/router/testing';
import {TagInputModule} from 'ngx-chips';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {interval} from 'rxjs';
import {take} from 'rxjs/operators';
import {DynamicModule} from 'ng-dynamic-component';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {VarcharDetailFieldComponent} from '../varchar/templates/detail/varchar.component';
import {fieldModules} from '../field.manifest';
import {datetimeFormatterMock} from '../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../services/formatters/currency/currency-formatter.service';
import {LanguageStore} from '../../store/language/language.store';
import {userPreferenceStoreMock} from '../../store/user-preference/user-preference.store.spec.mock';
import {numberFormatterMock} from '../../services/formatters/number/number-formatter.spec.mock';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {currencyFormatterMock} from '../../services/formatters/currency/currency-formatter.service.spec.mock';
import {DatetimeFormatter} from '../../services/formatters/datetime/datetime-formatter.service';
import {systemConfigStoreMock} from '../../store/system-config/system-config.store.spec.mock';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';

describe('DynamicFieldComponent', () => {
    let component: DynamicFieldComponent;
    let fixture: ComponentFixture<DynamicFieldComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [DynamicFieldComponent],
            imports: [
                ...fieldModules,
                CommonModule,
                RouterTestingModule,
                TagInputModule,
                FormsModule,
                DynamicModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: CurrencyFormatter, useValue: currencyFormatterMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock}
            ],
        })
            .compileComponents();

        fixture = TestBed.createComponent(DynamicFieldComponent);
        component = fixture.componentInstance;
        component.mode = 'detail';
        component.type = 'varchar';
        component.field = {
            type: 'varchar',
            value: 'My Varchar',
            formControl: new UntypedFormControl('My Varchar')
        };
        component.klass = {'test-class': true};
        component.componentType = VarcharDetailFieldComponent;

        fixture.detectChanges();
    }));

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should render field component', async () => {

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(300).pipe(take(1)).toPromise();

        const el = fixture.nativeElement;

        expect(el).toBeTruthy();
        expect(el.textContent).toContain('My Varchar');
    });
});
