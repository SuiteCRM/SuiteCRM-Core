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
import {ListContainerComponent} from './list-container.component';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {RouterTestingModule} from '@angular/router/testing';
import {WidgetPanelModule} from '../../../../components/widget-panel/widget-panel.module';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {themeImagesMockData} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {listviewStoreMock} from '../../store/list-view/list-view.store.spec.mock';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {TableModule} from '../../../../components/table/table.module';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('ListcontainerUiComponent', () => {
    let component: ListContainerComponent;
    let fixture: ComponentFixture<ListContainerComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [ListContainerComponent],
    imports: [TableModule,
        WidgetPanelModule,
        AngularSvgIconModule.forRoot(),
        NoopAnimationsModule,
        ApolloTestingModule,
        RouterTestingModule],
    providers: [
        {
            provide: ListViewStore, useValue: listviewStoreMock
        },
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        {
            provide: LanguageStore, useValue: languageStoreMock
        },
        { provide: MetadataStore, useValue: metadataStoreMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(ListContainerComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
