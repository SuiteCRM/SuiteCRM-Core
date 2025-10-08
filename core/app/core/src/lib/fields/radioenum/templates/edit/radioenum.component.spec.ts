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
import {Component, ViewChild} from '@angular/core';
import {Field} from '../../../../common/record/field.model';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';
import {RadioEnumEditFieldComponent} from './radioenum.component';
import {RadioEnumEditFieldModule} from './radioenum.module';
import {UntypedFormControl} from '@angular/forms';

@Component({
    selector: 'enum-edit-field-test-host-component',
    template: '<scrm-radioenum-edit #fieldComponent [field]="field"></scrm-radioenum-edit>'
})
class RadioEnumEditFieldTestHostComponent {
    @ViewChild('fieldComponent') fieldComponent: RadioEnumEditFieldComponent;
    field: Field = {
        name: 'test',
        type: 'enum',
        value: '_customer',
        metadata: null,
        definition: {
            options: 'account_type_dom'
        },
        formControl: new UntypedFormControl('_customer')
    };
}

describe('RadioEnumEditFieldComponent', () => {
    let testHostComponent: RadioEnumEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<RadioEnumEditFieldTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                RadioEnumEditFieldTestHostComponent,
            ],
            imports: [
                BrowserDynamicTestingModule,
                BrowserAnimationsModule,
                RadioEnumEditFieldModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {
                    provide: CurrencyFormatter,
                    useValue: new CurrencyFormatter(userPreferenceStoreMock, numberFormatterMock, 'en_us')
                },
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(RadioEnumEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have value', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();
        await testHostFixture.whenRenderingDone();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-radioenum-edit')[0];

        const radios = field.getElementsByClassName('radioenum').item(0);

        testHostComponent.fieldComponent.options.forEach(option => {
            const id = testHostComponent.field.name + '-' + option.value;
            const radioWrapper = radios.getElementsByClassName(id).item(0);
            const radio = radios.getElementsByTagName('input').item(0);

            expect(radioWrapper).toBeTruthy();
            expect(radioWrapper.textContent).toContain(option.label);
            expect(radio).toBeTruthy();
        });
    });

    it('should allow selecting value', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();
        await testHostFixture.whenRenderingDone();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-radioenum-edit')[0];

        const radios = field.getElementsByClassName('radioenum').item(0);

        let selected = testHostComponent.field.value;
        let selectedLabel = '';

        testHostComponent.fieldComponent.options.some(option => {
            const id = testHostComponent.field.name + '-' + option.value;
            const radioWrapper = radios.getElementsByClassName(id).item(0);
            const radio = radioWrapper.getElementsByTagName('input').item(0);

            if (testHostComponent.field.value !== option.value && option.value !== '') {
                radio.click();
                selected = option.value;
                selectedLabel = option.label;
                return true;
            }

            return false;
        });

        testHostFixture.detectChanges();
        await testHostFixture.whenStable();

        expect(selected).toEqual(testHostComponent.field.value);

        const selectedId = testHostComponent.field.name + '-' + selected;
        const selectedRadioWrapper = radios.getElementsByClassName(selectedId).item(0);
        const selectedRadio = radios.getElementsByTagName('input').item(0);

        expect(selectedRadioWrapper).toBeTruthy();
        expect(selectedRadioWrapper.textContent).toContain(selectedLabel);
        expect(selectedRadio).toBeTruthy();
    });
});
