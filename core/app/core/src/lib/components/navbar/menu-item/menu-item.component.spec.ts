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

import {MenuItemComponent} from './menu-item.component';
import {Component} from '@angular/core';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {RouterTestingModule} from '@angular/router/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {MenuItem} from '../../../common/menu/menu.model';
import {MenuItemLinkComponent} from '../menu-item-link/menu-item-link.component';
import {LanguageStrings} from '../../../store/language/language.store';
import {MenuRecentlyViewedComponent} from '../menu-recently-viewed/menu-recently-viewed.component';
import {languageMockData} from '../../../store/language/language.store.spec.mock';
import {themeImagesMockData} from '../../../store/theme-images/theme-images.store.spec.mock';
import {ImageModule} from '../../image/image.module';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

const mockMenuItem = {
    link: {
        url: '',
        label: 'Top Link Label',
        route: '/fake-module',
    },
    icon: '',
    submenu: [
        {
            link: {
                url: '',
                label: 'Sub Link 1',
                route: '/fake-module/edit',
            },
            icon: 'plus',
            submenu: []
        },
        {
            link: {
                url: '',
                label: 'Sub Link 2',
                route: '/fake-module/list',
            },
            icon: 'view',
            submenu: []
        }
    ],
    recentRecords: null
};

@Component({
    selector: 'menu-item-test-host-component',
    template: '<scrm-menu-item [item]="item"></scrm-menu-item>'
})
class MenuItemTestHostComponent {
    item: MenuItem = mockMenuItem;
    languages: LanguageStrings = {
        ...languageMockData,
        languageKey: 'en_us'
    };

    setItem(value: MenuItem): void {
        this.item = value;
    }

    setLanguages(value: LanguageStrings): void {
        this.languages = value;
    }
}

describe('ModuleMenuItemComponent', () => {
    let testHostComponent: MenuItemTestHostComponent;
    let testHostFixture: ComponentFixture<MenuItemTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [
        MenuItemComponent,
        MenuItemLinkComponent,
        MenuItemTestHostComponent,
        MenuRecentlyViewedComponent
    ],
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
        testHostFixture = TestBed.createComponent(MenuItemTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have top link', () => {


        const spanElement = testHostFixture.nativeElement.querySelector('span');
        const topLinks = spanElement.getElementsByClassName('top-nav-link');
        const topLink = topLinks[0];

        expect(testHostComponent).toBeTruthy();
        expect(spanElement).toBeTruthy();
        expect(topLinks.length).toEqual(1);
        expect(topLink.textContent).toContain(mockMenuItem.link.label);

    });

    it('should have sub links', () => {

        const divElement = testHostFixture.nativeElement.querySelector('div');
        const subLinks = divElement.getElementsByClassName('sub-nav-link');

        expect(testHostComponent).toBeTruthy();
        expect(divElement).toBeTruthy();
        expect(subLinks.length).toEqual(2);
    });
});
