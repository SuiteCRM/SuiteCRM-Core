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
import {DateTimeEditFieldComponent} from './datetime.component';
import {Field} from '../../../../common/record/field.model';
import {UntypedFormControl, FormsModule} from '@angular/forms';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {BehaviorSubject} from 'rxjs';
import {ButtonModule} from '../../../../components/button/button.module';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {DateTimeEditFieldModule} from './datetime.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {UserPreferenceMockStore} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {FormControlUtils} from '../../../../services/record/field/form-control.utils';

@Component({
    selector: 'datetime-edit-field-test-host-component',
    template: '<scrm-datetime-edit [field]="field"></scrm-datetime-edit>'
})
class DatetimeEditFieldTestHostComponent {
    field: Field = {
        type: 'datetime',
        value: '2020-11-09 10:12:14',
        formControl: new UntypedFormControl('2020-11-09 12:12:12')
    };
}

describe('DateTimeEditFieldComponent', () => {
    let testHostComponent: DatetimeEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<DatetimeEditFieldTestHostComponent>;

    /* eslint-disable camelcase, @typescript-eslint/camelcase */
    const preferences = new BehaviorSubject({
        date_format: 'MM.dd.yyyy',
        time_format: 'HH.mm.ss',
    });
    /* eslint-enable camelcase, @typescript-eslint/camelcase */

    const mockStore = new UserPreferenceMockStore(preferences);
    const mockDatetimeFormatter = new DatetimeFormatter(mockStore, new FormControlUtils(), 'en_us');

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                DatetimeEditFieldTestHostComponent,
                DateTimeEditFieldComponent,
            ],
            imports: [
                FormsModule,
                NgbModule,
                ButtonModule,
                DateTimeEditFieldModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: mockStore},
                {provide: DatetimeFormatter, useValue: mockDatetimeFormatter},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(DatetimeEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have formatted datetime value', () => {
        expect(testHostComponent).toBeTruthy();
        const input = testHostFixture.nativeElement.querySelector('input');
        // user default datetime format from preferences as defined above
        expect(input.value).toContain('11.09.2020 10.12.14');
    });

    it('should have update input when field changes', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.field.formControl.setValue('11.09.2020 10.12.14');

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            const input = testHostFixture.nativeElement.querySelector('input');
            // component formControl points to input element
            expect(input.value).toContain('11.09.2020 10.12.14');
        });

    }));

    it('should have update field when input changes', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        const input = testHostFixture.nativeElement.querySelector('input');
        input.value = '11.09.2020 10.12.14';
        input.dispatchEvent(new Event('input'));

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            // internal format
            expect(testHostComponent.field.value).toContain('2020-11-09 10:12:14');
        });

    }));

});
