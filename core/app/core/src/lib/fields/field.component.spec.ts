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
import {FieldComponent} from './field.component';
import {CommonModule} from '@angular/common';
import {Component} from '@angular/core';
import {BehaviorSubject, of} from 'rxjs';
import {By} from '@angular/platform-browser';
import {RouterTestingModule} from '@angular/router/testing';
import {TagInputModule} from 'ngx-chips';
import {UntypedFormControl, FormsModule} from '@angular/forms';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {Field} from '../common/record/field.model';
import {UserPreferenceMockStore} from '../store/user-preference/user-preference.store.spec.mock';
import {SystemConfigStore} from '../store/system-config/system-config.store';
import {UserPreferenceStore} from '../store/user-preference/user-preference.store';
import {DynamicFieldModule} from './dynamic-field/dynamic-field.module';
import {fieldModules} from './field.manifest';
import {languageStoreMock} from '../store/language/language.store.spec.mock';
import {datetimeFormatterMock} from '../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../services/formatters/currency/currency-formatter.service';
import {LanguageStore} from '../store/language/language.store';
import {DatetimeFormatter} from '../services/formatters/datetime/datetime-formatter.service';
import {FormControlUtils} from '../services/record/field/form-control.utils';
import {NumberFormatter} from '../services/formatters/number/number-formatter.service';

const buildField = (field: Field): Field => {
    field.formControl = new UntypedFormControl(field.value);
    return field;
};

@Component({
    selector: 'field-test-host-component',
    template: `
        <div id="wrapper">
            <div *ngFor="let wrapper of fields" [id]="wrapper.field.type + '-' + wrapper.mode">
                <scrm-field [mode]="wrapper.mode" [type]="wrapper.field.type" [field]="wrapper.field"
                            [record]="wrapper.record">
                </scrm-field>
            </div>
        </div>`
})
class FieldTestHostComponent {
    value = '10';
    fields = [
        {field: buildField({type: 'varchar', value: 'My Varchar'}), mode: 'detail', expected: 'My Varchar'},
        {field: buildField({type: 'varchar', value: 'My Varchar'}), mode: 'list', expected: 'My Varchar'},
        {field: buildField({type: 'varchar', value: 'My Varchar'}), mode: 'edit', expected: 'My Varchar'},
        {
            field: buildField({type: 'varchar', value: 'test', criteria: {values: ['test'], operator: '='}}),
            mode: 'filter',
            expected: 'test'
        },
        {field: buildField({type: 'int', value: '10'}), mode: 'detail', expected: '10'},
        {field: buildField({type: 'int', value: '10'}), mode: 'list', expected: '10'},
        {field: buildField({type: 'float', value: '1000.5'}), mode: 'detail', expected: '1,000.5'},
        {field: buildField({type: 'float', value: '1000.5'}), mode: 'list', expected: '1,000.5'},
        {field: buildField({type: 'phone', value: '+44 1111 123456'}), mode: 'detail', expected: '+44 1111 123456'},
        {field: buildField({type: 'phone', value: '+44 1111 123456'}), mode: 'list', expected: '+44 1111 123456'},
        {field: buildField({type: 'date', value: '2020-05-15'}), mode: 'detail', expected: '15.05.2020'},
        {field: buildField({type: 'date', value: '2020-05-16'}), mode: 'list', expected: '16.05.2020'},
        {
            field: buildField({type: 'datetime', value: '2020-05-14 23:11:01'}),
            mode: 'detail',
            expected: '14.05.2020 23.11.01'
        },
        {
            field: buildField({type: 'datetime', value: '2020-05-13 23:12:02'}),
            mode: 'list',
            expected: '13.05.2020 23.12.02'
        },
        {
            field: buildField({type: 'url', value: 'https://suitecrm.com/'}),
            mode: 'detail',
            expected: 'https://suitecrm.com/'
        },
        {
            field: buildField({type: 'url', value: 'https://suitecrm.com/'}),
            mode: 'list',
            expected: 'https://suitecrm.com/'
        },
        {field: buildField({type: 'currency', value: '1000.5'}), mode: 'detail', expected: '£1,000.5'},
        {field: buildField({type: 'currency', value: '1000.5'}), mode: 'list', expected: '£1,000.5'},
        {field: buildField({type: 'text', value: 'My Text'}), mode: 'detail', expected: 'My Text'},
        {
            field: buildField({
                type: 'relate', definition: {
                    module: 'Contacts',
                    // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
                    id_name: 'contact_id'
                }, value: 'Related Contact'
            }), mode: 'detail', expected: 'Related Contact'
        },
        {
            field: buildField({type: 'fullname', value: 'salutation first_name last_name'}),
            mode: 'detail',
            expected: 'User Test Name',
            record: {
                type: '',
                module: 'leads',
                attributes: {
                    // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
                    salutation: 'User',
                    // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
                    first_name: 'Test',
                    // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
                    last_name: 'Name',
                }
            },
        },
        {
            field: buildField({type: 'enum', value: '_customer', definition: {options: 'account_type_dom'}}),
            mode: 'list',
            expected: 'Customer'
        },
        {
            field: buildField({type: 'enum', value: '_customer', definition: {options: 'account_type_dom'}}),
            mode: 'detail',
            expected: 'Customer'
        },
        {
            field: buildField({
                type: 'multienum',
                valueList: ['_customer', '_reseller'],
                definition: {options: 'account_type_dom'}
            }),
            mode: 'list',
            expected: 'Customer'
        },
        {
            field: buildField({
                type: 'multienum',
                valueList: ['_customer', '_reseller'],
                definition: {options: 'account_type_dom'}
            }),
            mode: 'detail',
            expected: 'Customer'
        },
    ];
}

describe('FieldComponent', () => {
    let testHostComponent: FieldTestHostComponent;
    let testHostFixture: ComponentFixture<FieldTestHostComponent>;

    /* eslint-disable camelcase, @typescript-eslint/camelcase */
    const preferences = new BehaviorSubject({
        num_grp_sep: ',',
        dec_sep: '.',
        date_format: 'yyyy-MM-dd',
        time_format: 'HH:mm:ss',
        currency: {id: '1', name: 'Stirling Pound', symbol: '£', iso4217: 'GBP'},
        default_currency_significant_digits: 2
    });
    const mockStore = new UserPreferenceMockStore(preferences);
    const mockNumberFormatter = new NumberFormatter(mockStore, new FormControlUtils(), 'en_us');
    /* eslint-enable camelcase, @typescript-eslint/camelcase */

    /* eslint-disable camelcase, @typescript-eslint/camelcase */
    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [FieldComponent, FieldTestHostComponent],
            imports: [
                ...fieldModules,
                CommonModule,
                RouterTestingModule,
                TagInputModule,
                FormsModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule,
                DynamicFieldModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {
                    provide: UserPreferenceStore, useValue: mockStore
                },
                {
                    provide: NumberFormatter, useValue: mockNumberFormatter
                },
                {
                    provide: DatetimeFormatter, useValue: datetimeFormatterMock
                },
                {
                    provide: CurrencyFormatter, useValue: new CurrencyFormatter(mockStore, mockNumberFormatter, 'en_us')
                },
                {
                    provide: SystemConfigStore, useValue: {
                        configs$: of({
                            default_number_grouping_seperator: {
                                id: '/docroot/api/system-configs/default_number_grouping_seperator',
                                _id: 'default_number_grouping_seperator',
                                value: ';',
                                items: []
                            },
                            default_decimal_seperator: {
                                id: '/docroot/api/system-configs/default_decimal_seperator',
                                _id: 'default_decimal_seperator',
                                value: ',',
                                items: []
                            },
                            date_format: {
                                id: '/docroot/api/system-configs/date_format',
                                _id: 'date_format',
                                value: 'dd.MM.yyyy',
                                items: []
                            },
                            time_format: {
                                id: '/docroot/api/system-configs/time_format',
                                _id: 'time_format',
                                value: 'HH.mm.ss',
                                items: []
                            },
                            currency: {
                                id: '/docroot/api/system-configs/currency',
                                _id: 'currency',
                                value: null,
                                items: {
                                    id: '-99',
                                    name: 'US Dollars',
                                    symbol: '$',
                                    iso4217: 'USD'
                                }
                            },
                            default_currency_significant_digits: {
                                id: '/docroot/api/system-configs/default_currency_significant_digits',
                                _id: 'default_currency_significant_digits',
                                value: 3,
                                items: []
                            }
                        })
                    }
                }
            ],
        })
            .compileComponents();
        /* eslint-enable camelcase, @typescript-eslint/camelcase */

        testHostFixture = TestBed.createComponent(FieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create the dynamic component', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should render components', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.fields.forEach((fieldWrapper) => {
            let selector = '#' + fieldWrapper.field.type + '-' + fieldWrapper.mode;

            if (fieldWrapper.mode === 'edit' || fieldWrapper.mode === 'filter') {

                selector += ' input';
                const el = testHostFixture.debugElement.query(By.css(selector)).nativeElement;

                expect(el).toBeTruthy();
                expect(el.value).toContain(fieldWrapper.expected);
            } else {
                const el = testHostFixture.debugElement.query(By.css(selector)).nativeElement;

                expect(el).toBeTruthy();
                expect(el.textContent).toContain(fieldWrapper.expected);
            }
        });
    });
});

