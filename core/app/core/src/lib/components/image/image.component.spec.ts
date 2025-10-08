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

import {ComponentFixture, TestBed} from '@angular/core/testing';

import {ImageComponent} from './image.component';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {Component} from '@angular/core';
import {themeImagesMockData} from '../../store/theme-images/theme-images.store.spec.mock';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'host-component',
    template: '<scrm-image [image]="image"></scrm-image>'
})
class TestHostComponent {
    private image = 'line';

    setImage(newImage: string): void {
        this.image = newImage;
    }
}

describe('ImageComponent', () => {
    let testHostComponent: TestHostComponent;
    let testHostFixture: ComponentFixture<TestHostComponent>;

    beforeEach(() => {

        TestBed.configureTestingModule({
    declarations: [ImageComponent, TestHostComponent],
    imports: [AngularSvgIconModule.forRoot()],
    providers: [
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();


    });

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(TestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have image with src', () => {
        expect(testHostComponent).toBeTruthy();
        const img = testHostFixture.nativeElement.querySelector('img');

        expect(img).toBeTruthy();
        expect(img.src).toContain(themeImagesMockData.line.path);
    });

    it('should have svg', () => {
        testHostComponent.setImage('download');
        testHostFixture.detectChanges();

        expect(testHostFixture.nativeElement.querySelector('svg-icon')).toBeTruthy();
    });
});
