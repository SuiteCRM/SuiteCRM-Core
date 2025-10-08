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
import {TableBodyComponent} from './table-body.component';
import {CdkTableModule} from '@angular/cdk/table';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {Component} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';
import {FieldModule} from '../../../fields/field.module';
import {SortButtonModule} from '../../sort-button/sort-button.module';
import {themeImagesStoreMock} from '../../../store/theme-images/theme-images.store.spec.mock';
import {tableConfigMock} from '../table.component.spec.mock';
import {languageStoreMock} from '../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../store/language/language.store';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';

@Component({
    selector: 'table-body-test-host-component',
    template: '<scrm-table-body [config]="tableConfig"></scrm-table-body>'
})
class TableBodyTestHostComponent {
    tableConfig = tableConfigMock;
}

describe('TableBodyComponent', () => {
    let testHostComponent: TableBodyTestHostComponent;
    let testHostFixture: ComponentFixture<TableBodyTestHostComponent>;

    beforeEach(() => {
        TestBed.configureTestingModule({
            imports: [
                CdkTableModule,
                ApolloTestingModule,
                FieldModule,
                SortButtonModule,
                RouterTestingModule
            ],
            declarations: [TableBodyComponent, TableBodyTestHostComponent],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
            ],
        })
            .compileComponents();
    });

    beforeEach(waitForAsync(() => {
        testHostFixture = TestBed.createComponent(TableBodyTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', waitForAsync(() => {
        testHostFixture.whenStable().then(() => {
            expect(testHostComponent).toBeTruthy();
        });
    }));

    it('should have table body', waitForAsync(() => {

        testHostFixture.whenStable().then(() => {
            const tableBodyElement = testHostFixture.nativeElement.querySelector('table');

            expect(testHostComponent).toBeTruthy();
            expect(tableBodyElement).toBeTruthy();
            expect(tableBodyElement.outerHTML).toContain('aria-describedby="table-body"');
        });
    }));
});
