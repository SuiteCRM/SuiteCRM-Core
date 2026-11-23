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

import {provideHttpClient} from '@angular/common/http';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {Type} from '@angular/core';
import {TestBed} from '@angular/core/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {of} from 'rxjs';
import {ModuleNameMapper} from '../../services/navigation/module-name-mapper/module-name-mapper.service';
import {moduleNameMapperMock} from '../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {ModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service';
import {mockModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {AsyncActionService} from '../../services/process/processes/async-action/async-action';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {LanguageStore} from '../../store/language/language.store';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {MetadataStore} from '../../store/metadata/metadata.store.service';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {themeImagesStoreMock} from '../../store/theme-images/theme-images.store.spec.mock';
import {registerTestIcons} from '../image/svg-icons.spec.mock';
import {NavbarUiModule} from './navbar.module';

export async function configureNavbarItemTest(host: Type<unknown>, moduleMetadata = {}): Promise<void> {
    const language = {
        vm$: languageStoreMock.vm$,
        getFieldLabel: (key: string, module?: string) =>
            key === 'LBL_MORE' ? 'More' : languageStoreMock.getFieldLabel(key, module)
    };
    await TestBed.configureTestingModule({
        declarations: [host],
        imports: [NavbarUiModule, RouterTestingModule, AngularSvgIconModule.forRoot()],
        providers: [
            provideHttpClient(),
            provideHttpClientTesting(),
            {provide: LanguageStore, useValue: language},
            {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
            {provide: AppStateStore, useValue: {activeNavbarDropdown$: of(0)}},
            {provide: AsyncActionService, useValue: jasmine.createSpyObj('AsyncActionService', ['run'])},
            {provide: ModuleNavigation, useValue: mockModuleNavigation},
            {provide: ModuleNameMapper, useValue: moduleNameMapperMock},
            {provide: MetadataStore, useValue: {allModuleMetadata$: of(moduleMetadata)}},
            {provide: SystemConfigStore, useValue: {
                getUi: () => ({minLength: 20, mediumLength: 25, maxLength: 30}),
                getConfigValue: () => ({navigation_max_module_recently_viewed: '5'})
            }}
        ]
    }).compileComponents();
    registerTestIcons('plus', 'view', 'home');
}
