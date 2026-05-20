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
import {BehaviorSubject, Observable, of} from 'rxjs';
import {take} from 'rxjs/operators';
import {Component} from '@angular/core';

import {PaginationComponent} from './pagination.component';
import {PaginationDataSource} from '../../common/components/pagination/pagination.model';
import {PageSelection, PaginationCount} from '../../common/views/list/list-navigation.model';
import {By} from '@angular/platform-browser';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {LanguageStore} from '../../store/language/language.store';
import {languageMockData} from '../../store/language/language.store.spec.mock';
import {themeImagesMockData} from '../../store/theme-images/theme-images.store.spec.mock';
import {ImageModule} from '../image/image.module';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

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
    declarations: [
        PaginationTestHostComponent,
        PaginationComponent,
    ],
    imports: [ImageModule,
        AngularSvgIconModule.forRoot()],
    providers: [
        {
            provide: LanguageStore, useValue: {
                appListStrings$: of(languageMockData.appListStrings).pipe(take(1)),
                appStrings$: of(languageMockData.appStrings).pipe(take(1))
            }
        },
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
}).compileComponents();

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
        expect(testHostFixture.debugElement.query(By.css('.pagination-count'))).toBeTruthy();
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
