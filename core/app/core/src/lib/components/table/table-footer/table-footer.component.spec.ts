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
import {TableFooterComponent} from './table-footer.component';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {BulkActionMenuModule} from '../../bulk-action-menu/bulk-action-menu.module';
import {ListViewStore} from '../../../views/list/store/list-view/list-view.store';
import {languageStoreMock} from '../../../store/language/language.store.spec.mock';
import {themeImagesMockData} from '../../../store/theme-images/theme-images.store.spec.mock';
import {LanguageStore} from '../../../store/language/language.store';
import {PaginationModule} from '../../pagination/pagination.module';
import {ImageModule} from '../../image/image.module';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';
import {listviewStoreMock} from '../../../views/list/store/list-view/list-view.store.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

describe('TablefooterUiComponent', () => {
    let component: TableFooterComponent;
    let fixture: ComponentFixture<TableFooterComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [TableFooterComponent],
    imports: [PaginationModule,
        BulkActionMenuModule,
        AngularSvgIconModule.forRoot(),
        ApolloTestingModule,
        ImageModule],
    providers: [
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        { provide: LanguageStore, useValue: languageStoreMock },
        {
            provide: ListViewStore, useValue: listviewStoreMock
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(TableFooterComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
