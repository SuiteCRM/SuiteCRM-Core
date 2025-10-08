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
import {ChartMessageAreaModule} from './chart-message-area.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';


@Component({
    selector: 'chart-messages-area-host-component',
    template: '<scrm-chart-message-area [labelKey]="label"></scrm-chart-message-area>'
})
class ChartMessageAreaTestHostComponent {
    label = 'LBL_NO_DATA';
}


describe('ChartMessageAreaComponent', () => {
    let component: ChartMessageAreaTestHostComponent;
    let fixture: ComponentFixture<ChartMessageAreaTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [ChartMessageAreaTestHostComponent],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
            ],
            imports: [ChartMessageAreaModule]
        })
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(ChartMessageAreaTestHostComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should have value', () => {
        expect(component).toBeTruthy();
        expect(fixture.nativeElement.textContent).toContain('No Data');
    });
});
