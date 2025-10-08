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
import {DynamicModule} from 'ng-dynamic-component';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {DropdownButtonModule} from '../../../../components/dropdown-button/dropdown-button.module';
import {RecordViewStore} from '../../store/record-view/record-view.store';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {ImageModule} from '../../../../components/image/image.module';
import {RecordComponent} from './record.component';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {StatusBarModule} from '../../../../components/status-bar/status-bar.module';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {FieldModule} from '../../../../fields/field.module';
import {RecordContainerModule} from '../record-container/record-container.module';
import {RecordHeaderModule} from '../record-header/record-header.module';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {recordviewStoreMock} from '../../store/record-view/record-view.store.spec.mock';
import {themeImagesMockData} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'record-test-host-component',
    template: '<scrm-record></scrm-record>'
})
class RecordTestHostComponent {
}

describe('RecordComponent', () => {
    let testHostComponent: RecordTestHostComponent;
    let testHostFixture: ComponentFixture<RecordTestHostComponent>;

    beforeEach(waitForAsync(() => {
        /* eslint-disable camelcase, @typescript-eslint/camelcase */
        TestBed.configureTestingModule({
    declarations: [RecordComponent, RecordTestHostComponent],
    imports: [RouterTestingModule,
        NoopAnimationsModule,
        ImageModule,
        ApolloTestingModule,
        DynamicModule,
        FieldModule,
        DropdownButtonModule,
        RecordContainerModule,
        RecordHeaderModule,
        StatusBarModule,
        AngularSvgIconModule.forRoot()],
    providers: [
        { provide: RecordViewStore, useValue: recordviewStoreMock },
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        { provide: ModuleNavigation, useValue: mockModuleNavigation },
        { provide: SystemConfigStore, useValue: systemConfigStoreMock },
        { provide: UserPreferenceStore, useValue: userPreferenceStoreMock },
        { provide: NavigationStore, useValue: navigationMock },
        { provide: ModuleNavigation, useValue: mockModuleNavigation },
        { provide: LanguageStore, useValue: languageStoreMock },
        { provide: MetadataStore, useValue: metadataStoreMock },
        { provide: AppStateStore, useValue: appStateStoreMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
        /* eslint-enable camelcase, @typescript-eslint/camelcase */
    }));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(RecordTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });
});
