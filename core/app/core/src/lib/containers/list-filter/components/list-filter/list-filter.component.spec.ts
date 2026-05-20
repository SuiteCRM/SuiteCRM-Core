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
import {By} from '@angular/platform-browser';
import {RouterTestingModule} from '@angular/router/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {SearchMetaFieldMap} from '../../../../common/metadata/list.metadata.model';
import {map} from 'rxjs/operators';
import {DropdownButtonModule} from '../../../../components/dropdown-button/dropdown-button.module';
import {ButtonModule} from '../../../../components/button/button.module';
import {CloseButtonModule} from '../../../../components/close-button/close-button.module';
import {Metadata, MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {PanelModule} from '../../../../components/panel/panel.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {FieldGridModule} from '../../../../components/field-grid/field-grid.module';
import {ListViewStore} from '../../../../views/list/store/list-view/list-view.store';
import {ListFilterComponent} from './list-filter.component';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {FilterConfig} from './list-filter.model';
import {ListFilterModule} from './list-filter.module';
import {listviewStoreMock} from '../../../../views/list/store/list-view/list-view.store.spec.mock';
import {SavedFilter, SavedFilterMap} from '../../../../store/saved-filters/saved-filter.model';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {moduleNameMapperMock} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {ListFilterStoreFactory} from '../../store/list-filter/list-filter.store.factory';
import {SavedFilterActionAdapterFactory} from '../../adapters/actions.adapter.factory';
import {savedFilterActionAdapterFactoryMock} from '../../adapters/actions.adapter.factory.spec.mock';
import {listFilterStoreFactoryMock} from '../../store/list-filter/list-filter.store.spec.mock';

describe('ListFilterComponent', () => {
    let testHostComponent: ListFilterComponent;
    let testHostFixture: ComponentFixture<ListFilterComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                ListFilterComponent,
            ],
            imports: [
                CloseButtonModule,
                ButtonModule,
                PanelModule,
                DropdownButtonModule,
                FieldGridModule,
                RouterTestingModule,
                ApolloTestingModule,
                NoopAnimationsModule,
                ListFilterModule
            ],
            providers: [
                {provide: ListViewStore, useValue: listviewStoreMock},
                {provide: MetadataStore, useValue: metadataStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: ListFilterStoreFactory, useValue: listFilterStoreFactoryMock},
                {provide: SavedFilterActionAdapterFactory, useValue: savedFilterActionAdapterFactoryMock},
                {provide: ModuleNameMapper, useValue: moduleNameMapperMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(ListFilterComponent);
        testHostComponent = testHostFixture.componentInstance;

        testHostComponent.config = {

            module: listviewStoreMock.getModuleName(),
            filter$: listviewStoreMock.openFilter$,
            savedFilters$: listviewStoreMock.filterList.records$,
            listFields: listviewStoreMock.metadata.listView.fields,
            searchFields$: listviewStoreMock.metadata$.pipe(
                map((meta: Metadata) => {

                    if (!meta || !meta.search) {
                        return {} as SearchMetaFieldMap;
                    }

                    const searchMeta = meta.search;

                    let type = 'advanced';
                    if (!searchMeta.layout.advanced) {
                        type = 'basic';
                    }

                    return searchMeta.layout[type];
                })
            ),

            onClose: (): void => {
                listviewStoreMock.showFilters = false;
            },

            onSearch: (): void => {
                listviewStoreMock.showFilters = false;
            },

            updateFilter: (filter: SavedFilter, reload = true): void => {

                const filters = {} as SavedFilterMap;
                filters[filter.key] = filter;
                listviewStoreMock.setFilters(filters, reload);
            },

            resetFilter: (reload?: boolean): void => {
                listviewStoreMock.resetFilters(reload);
            },

            addSavedFilter: (filter: SavedFilter): void => {
                listviewStoreMock.addSavedFilter(filter);
            },

            removeSavedFilter: (filter: SavedFilter): void => {
                listviewStoreMock.removeSavedFilter(filter);
            },

            setOpenFilter: (filter: SavedFilter): void => {
                listviewStoreMock.setOpenFilter(filter);
            }
        } as FilterConfig;

        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have header', () => {
        expect(testHostFixture.debugElement.query(By.css('.card-header')).nativeElement).toBeTruthy();
    });

    it('should have body', () => {
        expect(testHostFixture.debugElement.query(By.css('.card-body')).nativeElement).toBeTruthy();
    });

    it('should have header title', () => {

        const element = testHostFixture.debugElement.query(By.css('.card-header'));

        expect(element.nativeElement.textContent).toContain('Basic Filter');
    });

    it('should have close icon', () => {

        expect(testHostComponent).toBeTruthy();
        const element = testHostFixture.debugElement.query(By.css('.card-header'));
        const button = element.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
    });

    it('should have configurable buttons', () => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.filter-select')).nativeElement).toBeTruthy();
    });

    it('should have grid list', () => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('scrm-field-grid')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('form')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.clear-filters-button')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.filter-button')).nativeElement).toBeTruthy();
    });

    it('should allow to insert filter value', waitForAsync(() => {

        expect(testHostFixture.debugElement.query(By.css('scrm-field-grid')).nativeElement).toBeTruthy();

        const input = testHostFixture.debugElement.query(By.css('input')).nativeElement;
        input.value = 'test';
        input.dispatchEvent(new Event('input'));

        expect(input).toBeTruthy();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            const currentFilter = testHostComponent.store.filterStore.getBaseRecord();

            expect(currentFilter.criteria.filters.name).toBeTruthy();
            expect(currentFilter.criteria.filters.name.operator).toEqual('=');
            expect(currentFilter.criteria.filters.name.values).toEqual(['test']);
        });
    }));

    it('should allow to applyFilter', waitForAsync(() => {

        expect(testHostFixture.debugElement.query(By.css('scrm-field-grid')).nativeElement).toBeTruthy();

        const input = testHostFixture.debugElement.query(By.css('input')).nativeElement;
        const filterButton = testHostFixture.debugElement.query(By.css('.filter-button')).nativeElement;

        input.value = 'test';
        input.dispatchEvent(new Event('input'));

        expect(input).toBeTruthy();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            filterButton.click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {

                const currentFilter = testHostComponent.store.filterStore.getBaseRecord();

                expect(currentFilter.criteria.filters.name).toBeTruthy();
                expect(currentFilter.criteria.filters.name.operator).toEqual('=');
                expect(currentFilter.criteria.filters.name.values).toEqual(['test']);

                expect(listviewStoreMock.showFilters).toEqual(false);
                expect(listviewStoreMock.recordList.criteria.filters.name.operator).toEqual('=');
                expect(listviewStoreMock.recordList.criteria.filters.name.values).toEqual(['test']);
            });
        });
    }));

    it('should allow to clear filter', waitForAsync(() => {

        expect(testHostFixture.debugElement.query(By.css('scrm-field-grid')).nativeElement).toBeTruthy();

        const input = testHostFixture.debugElement.query(By.css('input')).nativeElement;
        const filterButton = testHostFixture.debugElement.query(By.css('.filter-button')).nativeElement;
        const clearButton = testHostFixture.debugElement.query(By.css('.clear-filters-button')).nativeElement;

        input.value = 'test';
        input.dispatchEvent(new Event('input'));

        expect(input).toBeTruthy();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            filterButton.click();
            clearButton.click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {

                const currentFilter = testHostComponent.store.filterStore.getBaseRecord();

                expect(currentFilter.criteria).toBeTruthy();
                expect(currentFilter.criteria.filters.name.operator).toEqual('');
                expect(currentFilter.criteria.filters.name.values).toEqual([]);

                expect(listviewStoreMock.recordList.criteria).toEqual({name: 'default', filters: {}});
            });
        });
    }));
});
