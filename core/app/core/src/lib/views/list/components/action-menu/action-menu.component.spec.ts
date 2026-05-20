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
import {ActionMenuComponent} from './action-menu.component';
import {RouterTestingModule} from '@angular/router/testing';
import {ButtonGroupModule} from '../../../../components/button-group/button-group.module';
import {ButtonModule} from '../../../../components/button/button.module';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {listviewStoreMock} from '../../store/list-view/list-view.store.spec.mock';

@Component({
    selector: 'action-menu-test-host-component',
    template: '<scrm-action-menu></scrm-action-menu>'
})
class ActionMenuTestHostComponent {
}

describe('ActionMenuComponent', () => {
    let testHostComponent: ActionMenuTestHostComponent;
    let testHostFixture: ComponentFixture<ActionMenuTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                ActionMenuComponent,
                ActionMenuTestHostComponent,
            ],
            imports: [
                RouterTestingModule,
                ButtonModule,
                ButtonGroupModule
            ],
            providers: [
                {provide: ListViewStore, useValue: listviewStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {
                    provide: ModuleNavigation, useValue: mockModuleNavigation
                },
                {provide: LanguageStore, useValue: languageStoreMock},
            ],
        }).compileComponents();
    }));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(ActionMenuTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should render', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have list', () => {
        expect(testHostComponent).toBeTruthy();

        const divElement = testHostFixture.nativeElement.querySelector('div');

        expect(divElement.className).toContain('list-view-actions');

    });

    it('should have action buttons', () => {
        const divElement = testHostFixture.nativeElement.querySelector('div');
        const actionButtons = divElement.getElementsByClassName('action-button');

        const createButton = actionButtons.item(0);
        const importButton = actionButtons.item(1);

        expect(testHostComponent).toBeTruthy();
        expect(divElement).toBeTruthy();
        expect(actionButtons).toBeTruthy();
        expect(actionButtons.length).toEqual(2);

        expect(createButton.textContent).toContain('Create Account');
        expect(importButton.textContent).toContain('Import Accounts');
    });
});
