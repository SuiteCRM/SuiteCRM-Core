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

import {HomeMenuItemComponent} from './home-menu-item.component';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {RouterTestingModule} from '@angular/router/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {Component} from '@angular/core';
import {MenuItemLinkComponent} from '../menu-item-link/menu-item-link.component';
import {themeImagesMockData} from '../../../store/theme-images/theme-images.store.spec.mock';
import {ImageModule} from '../../image/image.module';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@Component({
    selector: 'home-menu-item-test-host-component',
    template: '<scrm-home-menu-item [active]="active" [route]="route"></scrm-home-menu-item>'
})
class HomeMenuItemTestHostComponent {
    active = false;
    route = '';

    setActive(value: boolean): void {
        this.active = value;
    }

    setRoute(value: string): void {
        this.route = value;
    }
}

describe('HomeMenuItemComponent', () => {
    let testHostComponent: HomeMenuItemTestHostComponent;
    let testHostFixture: ComponentFixture<HomeMenuItemTestHostComponent>;

    beforeEach(waitForAsync(() => {

        TestBed.configureTestingModule({
    declarations: [HomeMenuItemComponent, MenuItemLinkComponent, HomeMenuItemTestHostComponent],
    imports: [AngularSvgIconModule.forRoot(),
        RouterTestingModule,
        ImageModule,
        NgbModule],
    providers: [
        {
            provide: ThemeImagesStore, useValue: {
                images$: of(themeImagesMockData).pipe(take(1))
            }
        },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
}).compileComponents();
    }));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(HomeMenuItemTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
        const linkElement = testHostFixture.nativeElement.querySelector('li');

        expect(linkElement).toBeTruthy();
        expect(linkElement.className).not.toContain('active');
    });

    it('should have image', () => {
        expect(testHostComponent).toBeTruthy();

        expect(testHostFixture.nativeElement.querySelector('svg-icon')).toBeTruthy();
    });

    it('should active link', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.setActive(true);
        testHostFixture.detectChanges();
        const linkElement = testHostFixture.nativeElement.querySelector('li');

        expect(linkElement).toBeTruthy();
        expect(linkElement.className).toContain('active');
    });

    it('should home link', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.setRoute('/home');
        testHostFixture.detectChanges();
        const linkElement = testHostFixture.nativeElement.querySelector('a');
        const url = '/home';

        expect(linkElement).toBeTruthy();
        expect(linkElement.href).toContain(url);
    });
});
