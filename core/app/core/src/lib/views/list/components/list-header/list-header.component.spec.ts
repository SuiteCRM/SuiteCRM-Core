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
import {take} from 'rxjs/operators';
import {of} from 'rxjs';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {ListHeaderModule} from './list-header.module';
import {QuickFiltersService} from '../../services/quick-filters.service';
import {RouterTestingModule} from '@angular/router/testing';
import {AngularSvgIconModule, SvgIconRegistryService} from 'angular-svg-icon';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {registerThemeSvgFixtures, themeImagesMockData} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {listviewStoreMock} from '../../store/list-view/list-view.store.spec.mock';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'list-header-test-host-component',
    template: '<scrm-list-header></scrm-list-header>'
})
class ListHeaderTestHostComponent {
}

describe('ListHeaderComponent', () => {
    let testHostComponent: ListHeaderTestHostComponent;
    let testHostFixture: ComponentFixture<ListHeaderTestHostComponent>;

    beforeEach(() => {
        TestBed.configureTestingModule({
    declarations: [ListHeaderTestHostComponent],
    imports: [ListHeaderModule,
        ApolloTestingModule,
        RouterTestingModule,
        AngularSvgIconModule.forRoot()],
    providers: [
        {provide: QuickFiltersService, useValue: {
            breakdown$: of(true),
            enabled$: of(true),
            init: () => {},
            getBreakpoint: () => 2,
            areConfigEnabled: () => true,
            config$: of({buttons: [{label: 'My accounts'}]})
        }},
        { provide: ListViewStore, useValue: listviewStoreMock },
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        {
            provide: ModuleNavigation, useValue: mockModuleNavigation
        },
        {
            provide: SystemConfigStore, useValue: systemConfigStoreMock
        },
        {
            provide: UserPreferenceStore, useValue: userPreferenceStoreMock
        },
        {
            provide: NavigationStore, useValue: navigationMock
        },
        {
            provide: ModuleNavigation, useValue: mockModuleNavigation
        },
        {
            provide: LanguageStore, useValue: languageStoreMock
        },
        {
            provide: MetadataStore, useValue: metadataStoreMock
        },
        {
            provide: AppStateStore, useValue: appStateStoreMock
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    });

    beforeEach(() => {
        registerThemeSvgFixtures(TestBed.inject(SvgIconRegistryService));
        testHostFixture = TestBed.createComponent(ListHeaderTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should render configured quick filter buttons', () => {
        const filters = testHostFixture.nativeElement.querySelector('scrm-button-group .quick-filter-button');
        expect(filters).not.toBeNull();
        expect(filters.querySelector('button').textContent).toContain('My accounts');
    });

    it('should have title', () => {

        const titleElement = testHostFixture.nativeElement.querySelector('scrm-module-title');

        expect(testHostComponent).toBeTruthy();
        expect(titleElement).toBeTruthy();
        expect(titleElement.innerHTML).toContain('ACCOUNTS');
    });

    it('should have settings', () => {
        const settingsMenuElement = testHostFixture.nativeElement.querySelector('scrm-settings-menu');
        const settingsButtons = settingsMenuElement.getElementsByClassName('settings-button');

        expect(testHostComponent).toBeTruthy();
        expect(settingsMenuElement).toBeTruthy();
        expect(settingsButtons).toBeTruthy();
        expect(settingsButtons.length).toBeGreaterThan(0);
    });
});
