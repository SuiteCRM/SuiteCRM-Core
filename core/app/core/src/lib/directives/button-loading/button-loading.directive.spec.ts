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

import {ButtonLoadingDirective} from './button-loading.directive';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {Component} from '@angular/core';
import {ComponentFixture, TestBed} from '@angular/core/testing';

@Component({
    selector: 'host-component',
    template: '<button [scrm-button-loading]="loading"></button>'
})
class TestHostComponent {
    private loading = false;

    setLoading(loading: boolean): void {
        this.loading = loading;
    }
}

describe('ButtonLoadingDirective', () => {
    let testHostComponent: TestHostComponent;
    let testHostFixture: ComponentFixture<TestHostComponent>;
    const appState = new AppStateStore();

    beforeEach(() => {

        TestBed.configureTestingModule({
            declarations: [ButtonLoadingDirective, TestHostComponent],
            imports: [],
            providers: [
                {
                    provide: AppStateStore, useValue: appState
                },
            ],
        }).compileComponents();
    });

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(TestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('button should get disabled when app loading is active', () => {

        expect(testHostComponent).toBeTruthy();
        appState.updateLoading('button-loading',true);
        testHostFixture.detectChanges();
        let button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.disabled).toEqual(true);

        appState.updateLoading('button-loading',false);

        testHostFixture.detectChanges();
        button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.disabled).toEqual(false);
    });

    it('button should get disabled when loading input is active', () => {

        expect(testHostComponent).toBeTruthy();
        appState.updateLoading('button-loading',false);
        testHostComponent.setLoading(true);
        testHostFixture.detectChanges();
        let button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.disabled).toEqual(true);

        testHostComponent.setLoading(false);
        testHostFixture.detectChanges();
        button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.disabled).toEqual(false);
    });
});
