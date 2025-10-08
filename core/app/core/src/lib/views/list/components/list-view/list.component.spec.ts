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
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {ListComponent} from './list.component';
import {DynamicModule} from 'ng-dynamic-component';
import {By} from '@angular/platform-browser';
import {DropdownButtonModule} from '../../../../components/dropdown-button/dropdown-button.module';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {ImageModule} from '../../../../components/image/image.module';
import {listStoreFactoryMock} from '../../../../store/record-list/record-list.store.spec.mock';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {ListContainerModule} from '../list-container/list-container.module';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {FieldModule} from '../../../../fields/field.module';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {ListHeaderModule} from '../list-header/list-header.module';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {SortButtonModule} from '../../../../components/sort-button/sort-button.module';
import {themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {listviewStoreMock} from '../../store/list-view/list-view.store.spec.mock';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {moduleNameMapperMock} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'list-test-host-component',
    template: '<scrm-list></scrm-list>'
})
class ListTestHostComponent {
}

describe('ListComponent', () => {
    let testHostComponent: ListTestHostComponent;
    let testHostFixture: ComponentFixture<ListTestHostComponent>;

    beforeEach(waitForAsync(() => {
        /* eslint-disable camelcase, @typescript-eslint/camelcase */
        TestBed.configureTestingModule({
    declarations: [ListComponent, ListTestHostComponent],
    imports: [ListHeaderModule,
        ListContainerModule,
        RouterTestingModule,
        NoopAnimationsModule,
        ImageModule,
        ApolloTestingModule,
        DynamicModule,
        FieldModule,
        DropdownButtonModule,
        DropdownButtonModule,
        RouterTestingModule,
        SortButtonModule],
    providers: [
        { provide: RecordListStoreFactory, useValue: listStoreFactoryMock },
        { provide: ListViewStore, useValue: listviewStoreMock },
        { provide: ThemeImagesStore, useValue: themeImagesStoreMock },
        { provide: SystemConfigStore, useValue: systemConfigStoreMock },
        { provide: UserPreferenceStore, useValue: userPreferenceStoreMock },
        { provide: NavigationStore, useValue: navigationMock },
        { provide: ModuleNavigation, useValue: mockModuleNavigation },
        { provide: LanguageStore, useValue: languageStoreMock },
        { provide: MetadataStore, useValue: metadataStoreMock },
        { provide: AppStateStore, useValue: appStateStoreMock },
        { provide: ModuleNameMapper, useValue: moduleNameMapperMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
        /* eslint-enable camelcase, @typescript-eslint/camelcase */

        testHostFixture = TestBed.createComponent(ListTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));


    it('should create', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();
    }));

    it('should have list header', waitForAsync(() => {

        const headerElement = testHostFixture.nativeElement.querySelector('scrm-list-header');

        expect(testHostComponent).toBeTruthy();
        expect(headerElement).toBeTruthy();
    }));

    it('should have list container', waitForAsync(() => {
        const listContainerElement = testHostFixture.nativeElement.querySelector('scrm-list-container');

        expect(testHostComponent).toBeTruthy();
        expect(listContainerElement).toBeTruthy();
    }));

    it('should have title', waitForAsync(() => {
        const element = testHostFixture.debugElement.query(By.css('.list-view-title')).nativeElement;

        expect(testHostFixture).toBeTruthy();
        expect(element).toBeTruthy();
        expect(element.textContent).toContain('ACCOUNTS');
    }));
});
