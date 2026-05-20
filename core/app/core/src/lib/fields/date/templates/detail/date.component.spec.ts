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
import {BehaviorSubject} from 'rxjs';
import {CommonModule} from '@angular/common';
import {Field} from '../../../../common/record/field.model';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {DateDetailFieldComponent} from './date.component';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';

@Component({
    selector: 'date-detail-field-test-host-component',
    template: '<scrm-date-detail [field]="field"></scrm-date-detail>'
})
class DateDetailFieldTestHostComponent {
    field: Field = {
        type: 'data',
        value: '2020-05-01'
    };
}

describe('DateDetailFieldComponent', () => {
    let testHostComponent: DateDetailFieldTestHostComponent;
    let testHostFixture: ComponentFixture<DateDetailFieldTestHostComponent>;

    const preferences = new BehaviorSubject({
        // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
        date_format: 'yyyy-MM-dd',
    });


    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                DateDetailFieldTestHostComponent,
                DateDetailFieldComponent,
            ],
            imports: [
                CommonModule
            ],
            providers: [
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

        testHostFixture = TestBed.createComponent(DateDetailFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have user preferences based formatted date', () => {

        expect(testHostComponent).toBeTruthy();

        preferences.next({
            // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
            date_format: 'yyyy-MM-dd',
        });

        testHostComponent.field.value = '2020-04-14';
        testHostFixture.detectChanges();


        expect(testHostFixture.nativeElement.textContent).toContain('14.04.2020');
    });

    it('should have custom formatted date', () => {

        expect(testHostComponent).toBeTruthy();

        preferences.next({
            // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
            date_format: 'yyyy/MM/dd',
        });

        testHostComponent.field.value = '2020-03-15';
        testHostFixture.detectChanges();


        expect(testHostFixture.nativeElement.textContent).toContain('15.03.2020');
    });
});
