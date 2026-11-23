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
import {Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {Component} from '@angular/core';
import {ActionGroupMenuModule} from './action-group-menu.module';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../store/system-config/system-config.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {Action, ActionDataSource} from '../../common/actions/action.model';

@Component({
    selector: 'action-group-menu-test-host-component',
    template: '<scrm-action-group-menu [config]="config"></scrm-action-group-menu>'
})
class ActionGroupMenuTestHostComponent {
    config: ActionDataSource = {

        getActions: (): Observable<Action[]> => {
            return of([
                {
                    key: 'new',
                    labelKey: 'LBL_NEW',
                    label: 'New',
                    klass: ['settings-button']
                } as Action,
                {
                    key: 'edit',
                    labelKey: 'LBL_EDIT',
                    label: 'Edit',
                    klass: ['settings-button']
                } as Action
            ]).pipe(shareReplay());
        },

        runAction: (): void => {
        },
        isActive: (): boolean => true,
    };
}

describe('ActionGroupMenuComponent', () => {

    let testHostComponent: ActionGroupMenuTestHostComponent;
    let testHostFixture: ComponentFixture<ActionGroupMenuTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [ActionGroupMenuTestHostComponent],
            imports: [ActionGroupMenuModule],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock}
            ]
        })
            .compileComponents();

        testHostFixture = TestBed.createComponent(ActionGroupMenuTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have buttons', () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            const element = testHostFixture.nativeElement;
            const buttons = element.getElementsByClassName('settings-button');

            expect(buttons).toBeTruthy();
            expect(buttons.length).toEqual(2);
            expect(buttons.item(0).textContent).toContain('New');
            expect(buttons.item(1).textContent).toContain('Edit');
        });
    });
});
