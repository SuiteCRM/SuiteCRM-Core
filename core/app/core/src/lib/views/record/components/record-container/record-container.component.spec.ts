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
import {ApolloTestingModule} from 'apollo-angular/testing';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {RecordContainerComponent} from './record-container.component';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {Router} from '@angular/router';
import {WidgetPanelModule} from '../../../../components/widget-panel/widget-panel.module';
import {RecordViewStore} from '../../store/record-view/record-view.store';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {NavigationStore} from '../../../../store/navigation/navigation.store';
import {recordviewStoreMock} from '../../store/record-view/record-view.store.spec.mock';
import {themeImagesMockData} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {SubpanelModule} from '../../../../containers/subpanel/components/subpanel/subpanel.module';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {
    mockModuleNavigation,
    mockRouter
} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('RecordContainerComponent', () => {
    let component: RecordContainerComponent;
    let fixture: ComponentFixture<RecordContainerComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [RecordContainerComponent],
    imports: [WidgetPanelModule,
        AngularSvgIconModule.forRoot(),
        NoopAnimationsModule,
        ApolloTestingModule,
        SubpanelModule],
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
        { provide: Router, useValue: mockRouter },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(RecordContainerComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
