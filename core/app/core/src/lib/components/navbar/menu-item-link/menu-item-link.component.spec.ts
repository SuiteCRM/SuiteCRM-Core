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

import {Component} from '@angular/core';
import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';
import {provideHttpClientTesting} from '@angular/common/http/testing';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';
import {RouterTestingModule} from '@angular/router/testing';
import {of} from 'rxjs';
import {take} from 'rxjs/operators';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {MenuItemLinkComponent} from './menu-item-link.component';
import {MenuItemLink} from '../../../common/menu/menu.model';
import {themeImagesMockData} from '../../../store/theme-images/theme-images.store.spec.mock';
import {ImageModule} from '../../image/image.module';
import {ThemeImagesStore} from '../../../store/theme-images/theme-images.store';

const mockLink = {
    label: 'Test Link Label',
    url: '/fake-module/edit?return_module=Opportunities&return_action=DetailView',
    route: '/fake-module/edit',
    params: {
        'return-module': 'FakeModule',
        'return-action': 'DetailView',
    }
};

@Component({
    selector: 'menu-item-link-test-host-component',
    template: '<scrm-menu-item-link [class]="class" [link]="link" [icon]="icon"></scrm-menu-item-link>'
})
class MenuItemLinkTestHostComponent {
    class = '';
    link: MenuItemLink = mockLink;
    icon = '';

    setClass(value: string): void {
        this.class = value;
    }

    setLink(value: MenuItemLink): void {
        this.link = value;
    }

    setIcon(value: string): void {
        this.icon = value;
    }
}


describe('MenuItemActionLinkComponent', () => {
    let testHostComponent: MenuItemLinkTestHostComponent;
    let testHostFixture: ComponentFixture<MenuItemLinkTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
    declarations: [MenuItemLinkComponent, MenuItemLinkTestHostComponent],
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
        testHostFixture = TestBed.createComponent(MenuItemLinkTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
        const linkElement = testHostFixture.nativeElement.querySelector('a');

        expect(linkElement).toBeTruthy();
        expect(linkElement.className).toEqual('');
        expect(testHostFixture.nativeElement.querySelector('svg-icon')).toBeFalsy();
    });

    it('should have image', () => {
        expect(testHostComponent).toBeTruthy();

        testHostComponent.setIcon('plus');
        testHostFixture.detectChanges();

        expect(testHostFixture.nativeElement.querySelector('svg-icon')).toBeTruthy();
    });

    it('should use route', () => {

        expect(testHostComponent).toBeTruthy();

        const linkElement = testHostFixture.nativeElement.querySelector('a');
        const url = '/fake-module/edit?return-module=FakeModule&return-action=DetailView';

        expect(linkElement).toBeTruthy();
        expect(linkElement.href).toContain(url);
        expect(linkElement.text).toContain(mockLink.label);
    });

    it('should use href', () => {
        expect(testHostComponent).toBeTruthy();

        const link = {
            label: 'Test Link Label',
            url: './fake-module/edit?return_module=FakeModule&return_action=DetailView',
            route: null,
            params: null
        };

        testHostComponent.setLink(link);
        testHostFixture.detectChanges();


        const linkElement = testHostFixture.nativeElement.querySelector('a');
        const url = '/fake-module/edit?return_module=FakeModule&return_action=DetailView';

        expect(linkElement).toBeTruthy();
        expect(linkElement.href).toContain(url);
        expect(linkElement.text).toContain(mockLink.label);
    });

    it('should have class', () => {
        expect(testHostComponent).toBeTruthy();

        const testClass = 'test-class';
        testHostComponent.setClass(testClass);
        testHostFixture.detectChanges();


        const linkElement = testHostFixture.nativeElement.querySelector('a');

        expect(linkElement).toBeTruthy();
        expect(linkElement.className).toContain(testClass);
    });

});
