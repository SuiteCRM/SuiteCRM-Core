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
import {configureNavbarItemTest} from '../navbar-item.spec.mock';
import {Component} from '@angular/core';

const mockMenuItems = [
    {
        link: {
            url: '',
            label: 'Menu Item 1',
            route: '/fake-module-1',
        },
        icon: '',
        submenu: [],
        recentRecords: null
    },
    {
        link: {
            url: '',
            label: 'Menu Item 2',
            route: '/fake-module-2',
        },
        icon: '',
        submenu: [],
        recentRecords: null
    }
];

@Component({
    selector: 'menu-item-list-test-host-component',
    template: '<scrm-menu-items-list [items]="items" [labelKey]="label"></scrm-menu-items-list>'
})
class MenuItemListTestHostComponent {
    items = mockMenuItems;
    label = 'LBL_MORE';
}

describe('MenuItemsListComponent', () => {
    let testHostComponent: MenuItemListTestHostComponent;
    let testHostFixture: ComponentFixture<MenuItemListTestHostComponent>;

    beforeEach(waitForAsync(() => configureNavbarItemTest(MenuItemListTestHostComponent)));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(MenuItemListTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have label', () => {

        const navItemLink = testHostFixture.nativeElement.querySelector('a');

        expect(navItemLink.text).toContain('More');
    });

    it('should have menu items', () => {

        const navItemLink = testHostFixture.nativeElement.querySelector('div');
        const links = navItemLink.getElementsByClassName('action-link');

        expect(links.length).toEqual(2);
        expect(links[0].textContent).toContain('Menu Item 1');
        expect(links[0].attributes.getNamedItem('href').value).toContain('/fake-module-1');
        expect(links[1].textContent).toContain('Menu Item 2');
        expect(links[1].attributes.getNamedItem('href').value).toContain('/fake-module-2');
    });
});
