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
import {ApolloTestingModule} from 'apollo-angular/testing';
import {Component} from '@angular/core';
import {Subject} from 'rxjs';
import {Field} from '../../../../common/record/field.model';
import {MultiEnumEditFieldModule} from './multienum.module';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {UntypedFormControl} from '@angular/forms';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';

@Component({
    selector: 'multienum-edit-field-test-host-component',
    template: '<scrm-multienum-edit [field]="field"></scrm-multienum-edit>'
})
class MultiEnumEditFieldTestHostComponent {
    field: Field = {
        type: 'multienum',
        value: null,
        valueChanges$: new Subject(),
        valueList: [
            '_customer',
            '_reseller'
        ],
        metadata: null,
        definition: {
            options: 'account_type_dom'
        },
        formControl: new UntypedFormControl([
            '_customer',
            '_reseller'
        ])
    };
}

describe('MultiEnumEditFieldComponent', () => {
    let testHostComponent: MultiEnumEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<MultiEnumEditFieldTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                MultiEnumEditFieldTestHostComponent,
            ],
            imports: [ApolloTestingModule,
                MultiEnumEditFieldModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {
                    provide: CurrencyFormatter,
                    useValue: new CurrencyFormatter(userPreferenceStoreMock, systemConfigStoreMock, numberFormatterMock, 'en_us')
                },
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(MultiEnumEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('renders selected values as translated labels', async () => {
        await testHostFixture.whenStable();
        testHostFixture.detectChanges();

        const label = testHostFixture.nativeElement.querySelector('.p-multiselect-label');
        expect(label).not.toBeNull();
        expect(label.textContent).toContain('Customer');
        expect(label.textContent).not.toContain('_customer');
        expect(label.textContent).toContain('Reseller');
        expect(label.textContent).not.toContain('_reseller');
        expect(testHostFixture.nativeElement.querySelector('[data-pc-section="clearicon"]')).not.toBeNull();
    });

    it('removes one selection without changing the others', async () => {
        await testHostFixture.whenStable();
        testHostFixture.detectChanges();
        const root = testHostFixture.nativeElement;

        root.querySelector('.p-multiselect-trigger').click();
        testHostFixture.detectChanges();
        await testHostFixture.whenStable();
        testHostFixture.detectChanges();

        const customer = [...root.querySelectorAll('[role="option"]')]
            .find((option: HTMLElement) => option.textContent.trim() === 'Customer') as HTMLElement;
        expect(customer).toBeDefined();
        customer.click();
        testHostFixture.detectChanges();

        expect(testHostComponent.field.formControl.value).toEqual(['_reseller']);
        expect(root.querySelector('.p-multiselect-label').textContent).toContain('Reseller');
    });

    it('clears the selected values through the multiselect clear control', async () => {
        await testHostFixture.whenStable();
        testHostFixture.detectChanges();

        const clear = testHostFixture.nativeElement.querySelector('[data-pc-section="clearicon"]');
        expect(clear).not.toBeNull();
        clear.dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await testHostFixture.whenStable();
        testHostFixture.detectChanges();

        expect(testHostComponent.field.formControl.value).toEqual([]);
        expect(testHostFixture.nativeElement.querySelector('.p-multiselect-label').textContent).not.toContain('Customer');
    });
});
