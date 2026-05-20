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
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {BehaviorSubject, of} from 'rxjs';
import {CommonModule} from '@angular/common';
import {Field} from '../../../../common/record/field.model';
import {DateEditFieldComponent} from './date.component';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';

@Component({
    selector: 'date-edit-field-test-host-component',
    template: '<scrm-date-edit></scrm-date-edit>'
})
class DateEditFieldTestHostComponent {
    field: Field = {
        type: 'data',
        value: '2020-05-01'
    };
}

describe('DateEditFieldComponent', () => {
    let testHostComponent: DateEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<DateEditFieldTestHostComponent>;

    const preferences = new BehaviorSubject({
        // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
        date_format: 'yyyy-MM-dd',
    });


    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                DateEditFieldTestHostComponent,
                DateEditFieldComponent,
            ],
            imports: [
                CommonModule,
                NgbModule
            ],
            providers: [
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {
                    provide: SystemConfigStore, useValue: {
                        configs$: of({
                            // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
                            date_format: {
                                id: '/docroot/api/system-configs/date_format',
                                _id: 'date_format',
                                value: 'dd.MM.yyyy',
                                items: []
                            }
                        })
                    }
                }
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(DateEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));
});
