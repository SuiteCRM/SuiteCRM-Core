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

import {TableComponent} from './table.component';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {RouterTestingModule} from '@angular/router/testing';
import {Component} from '@angular/core';
import {LanguageStore} from '../../store/language/language.store';
import {themeImagesMockData} from '../../store/theme-images/theme-images.store.spec.mock';
import {TableBodyModule} from './table-body/table-body.module';
import {metadataStoreMock} from '../../store/metadata/metadata.store.spec.mock';
import {ImageModule} from '../image/image.module';
import {MetadataStore} from '../../store/metadata/metadata.store.service';
import {SortButtonModule} from '../sort-button/sort-button.module';
import {tableConfigMock} from './table.component.spec.mock';
import {TableFooterModule} from './table-footer/table-footer.module';
import {ListViewStore} from '../../views/list/store/list-view/list-view.store';
import {TableHeaderModule} from './table-header/table-header.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {listviewStoreMock} from '../../views/list/store/list-view/list-view.store.spec.mock';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'table-test-host-component',
    template: '<scrm-table [config]="tableConfig"></scrm-table>'
})
class TableTestHostComponent {
    tableConfig = tableConfigMock;
}

describe('TableComponent', () => {
    let component: TableTestHostComponent;
    let fixture: ComponentFixture<TableTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [TableComponent],
    imports: [TableHeaderModule,
        TableBodyModule,
        TableFooterModule,
        AngularSvgIconModule.forRoot(),
        ApolloTestingModule,
        ImageModule,
        SortButtonModule,
        RouterTestingModule],
    providers: [
        {
            provide: ListViewStore, useValue: listviewStoreMock
        },
        {
            provide: LanguageStore, useValue: languageStoreMock
        },
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        {
            provide: MetadataStore, useValue: metadataStoreMock
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(TableTestHostComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
