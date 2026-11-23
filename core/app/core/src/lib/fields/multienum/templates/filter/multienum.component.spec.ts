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
import {UntypedFormControl} from '@angular/forms';
import {Field} from '../../../../common/record/field.model';
import {MultiEnumFilterFieldModule} from './multienum.module';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {LanguageStore} from '../../../../store/language/language.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {dataTypeFormatterMock} from '../../../../services/formatters/data-type.formatter.spec.mock';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';

@Component({
    selector: 'multienum-filter-field-test-host-component',
    template: '<scrm-multienum-filter [field]="field"></scrm-multienum-filter>'
})
class MultiEnumFilterFieldTestHostComponent {
    field: Field = {
        type: 'multienum',
        value: null,
        valueList: [
            '_customer',
        ],
        metadata: null,
        definition: {
            options: 'account_type_dom'
        },
        criteria: {
            values: ['_customer'],
            operator: '='
        },
        formControl: new UntypedFormControl(['_customer'])
    };
}

describe('MultiEnumFilterFieldComponent', () => {
    let testHostComponent: MultiEnumFilterFieldTestHostComponent;
    let testHostFixture: ComponentFixture<MultiEnumFilterFieldTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                MultiEnumFilterFieldTestHostComponent,
            ],
            imports: [ApolloTestingModule,
                MultiEnumFilterFieldModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DataTypeFormatter, useValue: dataTypeFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {
                    provide: CurrencyFormatter,
                    useValue: new CurrencyFormatter(userPreferenceStoreMock, systemConfigStoreMock, numberFormatterMock, 'en_us')
                },
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(MultiEnumFilterFieldTestHostComponent);
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
        expect(testHostFixture.nativeElement.querySelector('[data-pc-section="clearicon"]')).not.toBeNull();
    });
});

