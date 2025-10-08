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

import {FormatCurrencyPipe} from './format-currency.pipe';
import {Component} from '@angular/core';
import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';
import {BehaviorSubject} from 'rxjs';
import {UserPreferenceMockStore} from '../../store/user-preference/user-preference.store.spec.mock';
import {CurrencyFormatter} from '../../services/formatters/currency/currency-formatter.service';
import {FormControlUtils} from '../../services/record/field/form-control.utils';

@Component({
    selector: 'format-currency-pipe-test-host-component',
    template: '{{value | formatCurrency}}'
})
class FormatCurrencyPipeTestHostComponent {
    value = '10';
}

describe('FormatCurrencyPipe', () => {
    let testHostComponent: FormatCurrencyPipeTestHostComponent;
    let testHostFixture: ComponentFixture<FormatCurrencyPipeTestHostComponent>;

    /* eslint-disable camelcase,@typescript-eslint/camelcase */
    const preferences = new BehaviorSubject<any>({
        currency: {id: '1', name: 'US Dollar', symbol: '$', iso4217: 'USD'},
        default_currency_significant_digits: '2',
        num_grp_sep: ',',
        dec_sep: '.',
    });

    const mockStore = new UserPreferenceMockStore(preferences);
    const mockNumberFormatter = new NumberFormatter(mockStore, new FormControlUtils(), 'en_us');
    /* eslint-enable camelcase,@typescript-eslint/camelcase */

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                FormatCurrencyPipeTestHostComponent,
                FormatCurrencyPipe,
            ],
            imports: [],
            providers: [
                {
                    provide: UserPreferenceStore, useValue: mockStore
                },
                {
                    provide: NumberFormatter, useValue: mockNumberFormatter
                },
                {
                    provide: CurrencyFormatter, useValue: new CurrencyFormatter(mockStore, mockNumberFormatter, 'en_us')
                }
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(FormatCurrencyPipeTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have value', () => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.nativeElement.textContent).toContain(10);
    });

    it('should have dollar formatted currency', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase,@typescript-eslint/camelcase */
        preferences.next({
            currency: {id: '1', name: 'US Dollar', symbol: '$', iso4217: 'USD'},
            default_currency_significant_digits: '2',
            num_grp_sep: '.',
            dec_sep: ',',
        });
        /* eslint-enable camelcase,@typescript-eslint/camelcase */

        testHostComponent.value = '1000.500';
        testHostFixture.detectChanges();

        testHostFixture.whenStable().then(() => {
            expect(testHostFixture.nativeElement.textContent).toContain('$1.000,50');
        });


    });

    it('should have custom formatted value', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase,@typescript-eslint/camelcase */
        preferences.next({
            currency: {id: '1', name: 'Pounds', symbol: '£', iso4217: 'GBP'},
            default_currency_significant_digits: '3',
            num_grp_sep: '.',
            dec_sep: ',',
        });
        /* eslint-enable camelcase,@typescript-eslint/camelcase */

        testHostComponent.value = '1000.500';
        testHostFixture.detectChanges();


        testHostFixture.whenStable().then(() => {
            expect(testHostFixture.nativeElement.textContent).toContain('£1.000,500');
        });
    });
});
