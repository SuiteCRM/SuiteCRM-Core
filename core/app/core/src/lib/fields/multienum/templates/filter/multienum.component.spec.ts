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
import {Component} from '@angular/core';
import {MultiEnumFilterFieldComponent} from './multienum.component';
import {UntypedFormControl, FormsModule} from '@angular/forms';
import {Field} from '../../../../common/record/field.model';
import {TagInputModule} from 'ngx-chips';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
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
                MultiEnumFilterFieldComponent,
            ],
            imports: [
                TagInputModule,
                FormsModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DataTypeFormatter, useValue: dataTypeFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {
                    provide: CurrencyFormatter,
                    useValue: new CurrencyFormatter(userPreferenceStoreMock, numberFormatterMock, 'en_us')
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

    it('should have value', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();
        await testHostFixture.whenRenderingDone();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-multienum-filter')[0];

        expect(field).toBeTruthy();

        const tagInput = field.getElementsByTagName('tag-input').item(0);

        expect(tagInput).toBeTruthy();

        const tag = tagInput.getElementsByTagName('tag').item(0);

        expect(tag).toBeTruthy();

        const tagText = tag.getElementsByClassName('tag__text').item(0);

        expect(tagText.textContent).toContain('Customer');
        expect(tagText.textContent).not.toContain('_customer');

        const deleteIcon = tagInput.getElementsByTagName('delete-icon').item(0);

        expect(deleteIcon).toBeTruthy();
    });

});

