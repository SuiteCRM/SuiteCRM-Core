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
import {BooleanEditFieldComponent} from './boolean.component';
import {Field} from '../../../../common/record/field.model';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {currencyFormatterMock} from '../../../../services/formatters/currency/currency-formatter.service.spec.mock';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';
import {UntypedFormControl} from '@angular/forms';

@Component({
    selector: 'boolean-edit-field-test-host-component',
    template: '<scrm-boolean-edit [field]="field"></scrm-boolean-edit>'
})
class BooleanEditFieldTestHostComponent {
    field: Field = {
        type: 'boolean',
        value: 'true',
        formControl: new UntypedFormControl(true)
    };
}

describe('BooleanEditFieldComponent', () => {
    let testHostComponent: BooleanEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<BooleanEditFieldTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                BooleanEditFieldTestHostComponent,
                BooleanEditFieldComponent,
            ],
            imports: [],
            providers: [
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {provide: CurrencyFormatter, useValue: currencyFormatterMock},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(BooleanEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have checkbox', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.field.value = 'true';

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            const container = testHostFixture.nativeElement.querySelector('.checkbox-container');
            const input = testHostFixture.nativeElement.querySelector('input');

            expect(container).toBeTruthy();
            expect(input).toBeTruthy();
            expect(input.checked).toBeTruthy();
            expect(input.type).toContain('checkbox');
            expect(input.disabled).toBeFalsy();
            expect(input.readOnly).toBeFalsy();
        });
    });

    it('should have updated input when field changes', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.field.value = 'false';

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            const input = testHostFixture.nativeElement.querySelector('input');

            expect(input.checked).toBeFalsy();

            testHostComponent.field.value = 'true';

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                expect(input.checked).toBeTruthy();
            });
        });

    });

    it('should have update field when input changes', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.field.value = 'false';

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            const input = testHostFixture.nativeElement.querySelector('input');
            input.click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                expect(testHostComponent.field.value).toContain('true');
            });
        });
    });

});
