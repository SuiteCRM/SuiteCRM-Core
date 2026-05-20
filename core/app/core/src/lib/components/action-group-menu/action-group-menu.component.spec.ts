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
import {ActionGroupMenuComponent} from './action-group-menu.component';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {Observable, of} from 'rxjs';
import {shareReplay, take} from 'rxjs/operators';
import {Component} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';
import {ButtonModule} from '../button/button.module';
import {ActionGroupMenuModule} from './action-group-menu.module';
import {RecordViewStore} from '../../views/record/store/record-view/record-view.store';
import {recordviewStoreMock} from '../../views/record/store/record-view/record-view.store.spec.mock';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {themeImagesMockData} from '../../store/theme-images/theme-images.store.spec.mock';
import {ModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service';
import {mockModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../store/system-config/system-config.store.spec.mock';
import {userPreferenceStoreMock} from '../../store/user-preference/user-preference.store.spec.mock';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {navigationMock} from '../../store/navigation/navigation.store.spec.mock';
import {NavigationStore} from '../../store/navigation/navigation.store';
import {LanguageStore} from '../../store/language/language.store';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {metadataStoreMock} from '../../store/metadata/metadata.store.spec.mock';
import {appStateStoreMock} from '../../store/app-state/app-state.store.spec.mock';
import {MetadataStore} from '../../store/metadata/metadata.store.service';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {recordActionsMock} from '../../views/record/adapters/actions.adapter.spec.mock';
import {RecordActionsAdapter} from '../../views/record/adapters/actions.adapter';
import {ImageModule} from '../image/image.module';
import {Action, ActionDataSource} from '../../common/actions/action.model';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'action-group-menu-test-host-component',
    template: '<scrm-action-group-menu [config]="config"></scrm-action-group-menu>'
})
class ActionGroupMenuTestHostComponent {
    config = {

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
                    labelKey: 'LBL_Edit',
                    label: 'Edit',
                    klass: ['settings-button']
                } as Action
            ]).pipe(shareReplay());
        },

        runAction: (): void => {
        }

    } as ActionDataSource;
}

describe('ActionGroupMenuComponent', () => {

    let testHostComponent: ActionGroupMenuTestHostComponent;
    let testHostFixture: ComponentFixture<ActionGroupMenuTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [ActionGroupMenuComponent, ActionGroupMenuTestHostComponent],
    imports: [ApolloTestingModule,
        ImageModule,
        ButtonModule,
        ActionGroupMenuModule,
        RouterTestingModule],
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
        { provide: RecordActionsAdapter, useValue: recordActionsMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
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
