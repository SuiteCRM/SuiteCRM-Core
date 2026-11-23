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
import {ApolloTestingModule} from 'apollo-angular/testing';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {of} from 'rxjs';
import {SubpanelContainerComponent} from './subpanel-container.component';
import {SubpanelContainerModule} from './subpanel-container.module';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {recordviewStoreMock} from '../../../../views/record/store/record-view/record-view.store.spec.mock';
import {provideHttpClient} from '@angular/common/http';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {RouterTestingModule} from '@angular/router/testing';

describe('SubpanelContainerComponent', () => {
    let fixture: ComponentFixture<SubpanelContainerComponent>;

    beforeEach(() => {
        TestBed.configureTestingModule({
            imports: [SubpanelContainerModule, AngularSvgIconModule.forRoot(), ApolloTestingModule, RouterTestingModule],
            providers: [
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                provideHttpClient(),
                provideHttpClientTesting(),
            ]
        });
        fixture = TestBed.createComponent(SubpanelContainerComponent);
        fixture.componentRef.setInput('config', {
            parentModule: 'accounts',
            subpanels$: of(recordviewStoreMock.getSubpanels()),
            sidebarActive$: of(false)
        });
        fixture.detectChanges();
    });

    it('should render a header for configured subpanels', () => {
        expect(fixture.componentInstance).toBeTruthy();
        expect(fixture.nativeElement.querySelector('.sub-panel-banner-header')).not.toBeNull();
        expect(fixture.componentInstance.headerSubpanels()).toContain('activities');
    });
});
