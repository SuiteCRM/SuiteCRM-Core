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
import {BehaviorSubject, of} from 'rxjs';
import {FloatDetailFieldComponent} from './float.component';
import {Field} from '../../../../common/record/field.model';
import {UserPreferenceMockStore} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {FormControlUtils} from '../../../../services/record/field/form-control.utils';
import {FormatNumberPipe} from '../../../../pipes/format-number/format-number.pipe';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';

@Component({
    selector: 'float-detail-field-test-host-component',
    template: '<scrm-float-detail [field]="field"></scrm-float-detail>'
})
class FloatDetailFieldTestHostComponent {
    field: Field = {
        type: 'float',
        value: '10'
    };
}

describe('FloatDetailFieldComponent', () => {
    let testHostComponent: FloatDetailFieldTestHostComponent;
    let testHostFixture: ComponentFixture<FloatDetailFieldTestHostComponent>;

    /* eslint-disable camelcase,@typescript-eslint/camelcase */
    const preferences = new BehaviorSubject<any>({
        num_grp_sep: ',',
        dec_sep: '.',
    });

    const mockStore = new UserPreferenceMockStore(preferences);
    const mockNumberFormatter = new NumberFormatter(mockStore, new FormControlUtils(), 'en-US');


    /* eslint-enable camelcase,@typescript-eslint/camelcase */

    /* eslint-disable camelcase,@typescript-eslint/camelcase */
    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                FloatDetailFieldTestHostComponent,
                FloatDetailFieldComponent,
                FormatNumberPipe,
            ],
            imports: [],
            providers: [
                {
                    provide: UserPreferenceStore, useValue: mockStore
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
                            }
                        })
                    }
                },
                {provide: NumberFormatter, useValue: mockNumberFormatter},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {
                    provide: CurrencyFormatter,
                    useValue: new CurrencyFormatter(mockStore, mockNumberFormatter, 'en_us')
                },
            ],
        }).compileComponents();
        /* eslint-enable camelcase,@typescript-eslint/camelcase */

        testHostFixture = TestBed.createComponent(FloatDetailFieldTestHostComponent);
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

    it('should have en_us formatted value', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase,@typescript-eslint/camelcase */
        preferences.next({
            num_grp_sep: ',',
            dec_sep: '.',
        });
        /* eslint-enable camelcase,@typescript-eslint/camelcase */

        testHostComponent.field.value = '10.500';
        testHostFixture.detectChanges();


        expect(testHostFixture.nativeElement.textContent).toContain('10.5');
    });

    it('should have custom formatted value', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase,@typescript-eslint/camelcase */
        preferences.next({
            num_grp_sep: '.',
            dec_sep: ',',
        });
        /* eslint-enable camelcase,@typescript-eslint/camelcase */

        testHostComponent.field.value = '1000.500';
        testHostFixture.detectChanges();


        expect(testHostFixture.nativeElement.textContent).toContain('1.000,5');
    });

});
