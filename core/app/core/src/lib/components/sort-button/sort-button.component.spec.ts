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

import {SortButtonComponent} from './sort-button.component';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {Component} from '@angular/core';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {By} from '@angular/platform-browser';
import {SortDirection} from '../../common/views/list/list-navigation.model';
import {take} from 'rxjs/operators';
import {themeImagesMockData} from '../../store/theme-images/theme-images.store.spec.mock';
import {ImageModule} from '../image/image.module';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {SortDirectionDataSource} from './sort-button.model';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

const sortDirectionSubject = new BehaviorSubject<SortDirection>(SortDirection.NONE);
let lastDirection = SortDirection.NONE;
const sortDirectionState: SortDirectionDataSource = {
    getSortDirection: (): Observable<SortDirection> => sortDirectionSubject.asObservable(),
    changeSortDirection: (direction: SortDirection): void => {
        sortDirectionSubject.next(direction);
        lastDirection = direction;
    }
} as SortDirectionDataSource;

@Component({
    selector: 'sort-button-test-host-component',
    template: '<scrm-sort-button [state]="state"></scrm-sort-button>'
})
class SortButtonTestHostComponent {
    state = sortDirectionState;
}

describe('SortButtonComponent', () => {
    let testHostComponent: SortButtonTestHostComponent;
    let testHostFixture: ComponentFixture<SortButtonTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [
        SortButtonTestHostComponent,
        SortButtonComponent,
    ],
    imports: [ImageModule,
        AngularSvgIconModule.forRoot()],
    providers: [
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting()
    ]
}).compileComponents();

        testHostFixture = TestBed.createComponent(SortButtonTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have sort button', () => {
        expect(testHostFixture.debugElement.query(By.css('.sort-button'))).toBeTruthy();
    });

    it('should have sort icon', () => {
        expect(testHostFixture.debugElement.query(By.css('.sort-icon'))).toBeTruthy();
    });

    it('should change selection to descending', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        sortDirectionSubject.next(SortDirection.NONE);
        lastDirection = SortDirection.NONE;

        const link = el.query(By.css('.sort-button'));

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            link.nativeElement.click();
            link.nativeElement.dispatchEvent(new Event('click'));
            testHostFixture.detectChanges();

            testHostFixture.whenStable().then(() => {
                expect(lastDirection).toEqual(SortDirection.DESC);
            });
        });


    }));

    it('should change selection to ascending', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastDirection = SortDirection.DESC;
        sortDirectionSubject.next(lastDirection);

        const link = el.query(By.css('.sort-button'));

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            link.nativeElement.click();
            link.nativeElement.dispatchEvent(new Event('click'));
            testHostFixture.detectChanges();

            testHostFixture.whenStable().then(() => {
                expect(lastDirection).toEqual(SortDirection.ASC);
            });
        });

    }));

    it('should change selection to none', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastDirection = SortDirection.ASC;
        sortDirectionSubject.next(SortDirection.ASC);

        const link = el.query(By.css('.sort-button'));

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            link.nativeElement.click();
            link.nativeElement.dispatchEvent(new Event('click'));
            testHostFixture.detectChanges();

            testHostFixture.whenStable().then(() => {
                expect(lastDirection).toEqual(SortDirection.NONE);
            });
        });


    }));
});
