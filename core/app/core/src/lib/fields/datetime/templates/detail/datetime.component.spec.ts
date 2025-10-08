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
import {DateTimeDetailFieldComponent} from './datetime.component';
import {CommonModule} from '@angular/common';
import {BehaviorSubject} from 'rxjs';
import {Field} from '../../../../common/record/field.model';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {UserPreferenceMockStore} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {FormControlUtils} from '../../../../services/record/field/form-control.utils';

@Component({
    selector: 'datetime-detail-field-test-host-component',
    template: '<scrm-datetime-detail [field]="field"></scrm-datetime-detail>'
})
class DateTimeDetailFieldTestHostComponent {
    field: Field = {
        type: 'datetime',
        value: '2020-05-01 23:23:23'
    };
}

describe('DatetimeDetailFieldComponent', () => {
    let testHostComponent: DateTimeDetailFieldTestHostComponent;
    let testHostFixture: ComponentFixture<DateTimeDetailFieldTestHostComponent>;

    /* eslint-disable camelcase, @typescript-eslint/camelcase */
    const preferences = new BehaviorSubject({
        date_format: 'yyyy-MM-dd',
        time_format: 'HH:mm:ss',
    });
    /* eslint-enable camelcase, @typescript-eslint/camelcase */

    const mockStore = new UserPreferenceMockStore(preferences);
    const mockDatetimeFormatter = new DatetimeFormatter(mockStore, new FormControlUtils(), 'en_us');

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                DateTimeDetailFieldTestHostComponent,
                DateTimeDetailFieldComponent,
            ],
            imports: [
                CommonModule
            ],
            providers: [
                {provide: UserPreferenceStore, useValue: mockStore},
                {provide: DatetimeFormatter, useValue: mockDatetimeFormatter},

            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(DateTimeDetailFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have user preferences based formatted datetime', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase, @typescript-eslint/camelcase */
        preferences.next({
            date_format: 'yyyy-MM-dd',
            time_format: 'HH:mm:ss',
        });
        /* eslint-enable camelcase, @typescript-eslint/camelcase */

        testHostComponent.field.value = '2020-04-14 21:11:01';
        testHostFixture.detectChanges();

        testHostFixture.whenStable().then(() => {
            expect(testHostFixture.nativeElement.textContent).toContain('2020-04-14 21:11:01');
        });
    });

    it('should have user preferences based formatted datetime(MM.dd.yyyy HH.mm.ss)', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase, @typescript-eslint/camelcase */
        preferences.next({
            date_format: 'MM.dd.yyyy',
            time_format: 'HH.mm.ss',
        });
        /* eslint-enable camelcase, @typescript-eslint/camelcase */

        testHostComponent.field.value = '2021-05-16 10:12:15';
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostFixture.nativeElement.textContent).toContain('05.16.2021 10.12.15');
        });
    });

    it('should have user preferences based formatted datetime(MM-dd-yyyy hh.mm aaaaaa)', () => {

        expect(testHostComponent).toBeTruthy();

        /* eslint-disable camelcase, @typescript-eslint/camelcase */
        preferences.next({
            date_format: 'MM-dd-yyyy',
            time_format: 'hh.mm aaaaaa',
        });
        /* eslint-enable camelcase, @typescript-eslint/camelcase */

        testHostComponent.field.value = '2021-06-04 00:00:00';
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostFixture.nativeElement.textContent).toContain('06-04-2021 12.00 am');

        });
    });

});
