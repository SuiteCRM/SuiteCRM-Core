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
import {BehaviorSubject, Observable} from 'rxjs';
import {Component} from '@angular/core';
import {provideHttpClient} from '@angular/common/http';
import {provideHttpClientTesting} from '@angular/common/http/testing';

import {PaginationDataSource} from '../../common/components/pagination/pagination.model';
import {PageSelection, PaginationCount} from '../../common/views/list/list-navigation.model';
import {By} from '@angular/platform-browser';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {registerTestIcons} from '../image/svg-icons.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {themeImagesStoreMock} from '../../store/theme-images/theme-images.store.spec.mock';
import {PaginationModule} from './pagination.module';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';

const pageSubject = new BehaviorSubject<PageSelection>(PageSelection.LAST);
const countSubject = new BehaviorSubject<PaginationCount>({
    pageFirst: 0, pageLast: 0, total: 0
});
let lastStatus = PageSelection.LAST;
const pageState: PaginationDataSource = {
    getPaginationCount: (): Observable<PaginationCount> => countSubject.asObservable(),
    changePage: (page: PageSelection): void => {
        pageSubject.next(page);
        lastStatus = page;
    }

} as PaginationDataSource;

@Component({
    selector: 'pagination-test-host-component',
    template: '<scrm-pagination [state]="state"></scrm-pagination>'
})
class PaginationTestHostComponent {
    state = pageState;
}

describe('PaginationComponent', () => {
    let testHostComponent: PaginationTestHostComponent;
    let testHostFixture: ComponentFixture<PaginationTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [PaginationTestHostComponent],
            imports: [
                PaginationModule,
                AngularSvgIconModule.forRoot()
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                provideHttpClient(),
                provideHttpClientTesting()
            ]
        }).compileComponents();

        registerTestIcons('paginate_first', 'paginate_previous', 'paginate_next', 'paginate_last');
        testHostFixture = TestBed.createComponent(PaginationTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have previous page button', () => {
        expect(testHostFixture.debugElement.query(By.css('.pagination-previous'))).toBeTruthy();
    });

    it('should have first page button', () => {
        expect(testHostFixture.debugElement.query(By.css('.pagination-first'))).toBeTruthy();
    });

    it('should have last page button', () => {
        expect(testHostFixture.debugElement.query(By.css('.pagination-last'))).toBeTruthy();
    });

    it('should have next page button', () => {
        expect(testHostFixture.debugElement.query(By.css('.pagination-next'))).toBeTruthy();
    });

    it('should have page count', () => {
        countSubject.next({pageFirst: 1, pageLast: 20, total: 40});
        testHostFixture.detectChanges();
        const count = testHostFixture.debugElement.query(By.css('.pagination-count'));
        expect(count).toBeTruthy();
        expect(count.nativeElement.textContent).toContain('40');
    });

    it('should trigger page change to previous page', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = PageSelection.LAST;

        const link = el.query(By.css('.pagination-previous'));

        link.nativeElement.dispatchEvent(new Event('click'));
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(PageSelection.PREVIOUS);
        });
    }));

    it('should trigger page change to first page', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = PageSelection.LAST;

        const link = el.query(By.css('.pagination-first'));

        link.nativeElement.dispatchEvent(new Event('click'));
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(PageSelection.FIRST);
        });
    }));

    it('should trigger page change to next page', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = PageSelection.LAST;

        const link = el.query(By.css('.pagination-next'));

        link.nativeElement.dispatchEvent(new Event('click'));
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(PageSelection.NEXT);
        });
    }));

    it('should trigger page change to last page', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = PageSelection.FIRST;

        const link = el.query(By.css('.pagination-last'));

        link.nativeElement.dispatchEvent(new Event('click'));
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(PageSelection.LAST);
        });
    }));
});
