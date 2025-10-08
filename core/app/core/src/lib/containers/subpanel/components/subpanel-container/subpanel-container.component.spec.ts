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

import {ComponentFixture, TestBed} from '@angular/core/testing';
import {Component} from '@angular/core';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {SubpanelContainerComponent} from './subpanel-container.component';
import {RouterTestingModule} from '@angular/router/testing';
import {ButtonModule} from '../../../../components/button/button.module';
import {ListFilterModule} from '../../../list-filter/components/list-filter/list-filter.module';
import {ActionMenuModule} from '../../../../views/list/components/action-menu/action-menu.module';
import {ImageModule} from '../../../../components/image/image.module';
import {ModuleTitleModule} from '../../../../components/module-title/module-title.module';
import {SettingsMenuModule} from '../../../../views/list/components/settings-menu/settings-menu.module';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'subpanel-test-host-component',
    template: '<scrm-subpanel></scrm-subpanel>'
})
class SubpanelContainerComponentTestHostComponent {
}

describe('SubpanelContainerComponent', () => {
    let testHostComponent: SubpanelContainerComponentTestHostComponent;
    let testHostFixture: ComponentFixture<SubpanelContainerComponentTestHostComponent>;

    beforeEach(() => {
        TestBed.configureTestingModule({
    declarations: [SubpanelContainerComponent, SubpanelContainerComponentTestHostComponent],
    imports: [ModuleTitleModule,
        ActionMenuModule,
        ButtonModule,
        SettingsMenuModule,
        ApolloTestingModule,
        ImageModule,
        ListFilterModule,
        RouterTestingModule],
    providers: [provideHttpClient(withInterceptorsFromDi()), provideHttpClientTesting()]
})
            .compileComponents();
    });

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(SubpanelContainerComponentTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });
});
