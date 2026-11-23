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
import {ApolloTestingModule} from 'apollo-angular/testing';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';

import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';

import {RecordListModalComponent} from './record-list-modal.component';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {RouterTestingModule} from '@angular/router/testing';
import {registerThemeSvgFixtures, themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {AngularSvgIconModule, SvgIconRegistryService} from 'angular-svg-icon';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {RecordListModalStoreFactory} from '../../store/record-list-modal/record-list-modal.store.factory';
import {recordlistModalStoreFactoryMock} from '../../store/record-list-modal/record-list-modal.store.spec.mock';
import {RecordListModalModule} from './record-list-modal.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';

describe('RecordListModalComponent', () => {
    let fixture: ComponentFixture<RecordListModalComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            imports: [ApolloTestingModule, AngularSvgIconModule.forRoot(), RecordListModalModule,
                NoopAnimationsModule, RouterTestingModule],
            providers: [
                provideHttpClient(),
                provideHttpClientTesting(),
                NgbActiveModal,
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: RecordListModalStoreFactory, useValue: recordlistModalStoreFactoryMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: ModuleNavigation, useValue: mockModuleNavigation},
            ],
        }).compileComponents();

        registerThemeSvgFixtures(TestBed.inject(SvgIconRegistryService));
        fixture = TestBed.createComponent(RecordListModalComponent);
        fixture.componentRef.setInput('module', 'accounts');
        // Attach the modal before checking bindings, as NgbModal does.
        fixture.detectChanges(false);
        fixture.detectChanges();
    }));

    it('should have a table', () => {
        expect(fixture.nativeElement.querySelector('scrm-table')).not.toBeNull();
    });

    it('should have a filter panel', () => {
        expect(fixture.nativeElement.querySelector('scrm-list-filter')).not.toBeNull();
    });
});
