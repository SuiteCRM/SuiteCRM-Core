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

const recentRecords = [
    {
        attributes: {item_summary: 'Module 1', module_name: 'Accounts', item_id: '1'}
    },
    {
        attributes: {item_summary: 'Module 2', module_name: 'Accounts', item_id: '2'}
    }
];

@Component({
    selector: 'menu-recently-viewed-test-host-component',
    template: '<scrm-menu-recently-viewed module="accounts"></scrm-menu-recently-viewed>'
})
class MenuRecentlyViewedTestHostComponent {
}

describe('MenuRecentlyViewedComponent', () => {
    let testHostComponent: MenuRecentlyViewedTestHostComponent;
    let testHostFixture: ComponentFixture<MenuRecentlyViewedTestHostComponent>;

    beforeEach(waitForAsync(() => configureNavbarItemTest(MenuRecentlyViewedTestHostComponent, {accounts: {recentlyViewed: recentRecords}})));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(MenuRecentlyViewedTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have label', () => {
        const title = testHostFixture.nativeElement.querySelector('h4');

        expect(title.textContent).toContain('Recently Viewed');
    });

    it('should have recently viewed record links', () => {

        const navItemLinks = testHostFixture.nativeElement.getElementsByClassName('nav-item');

        expect(navItemLinks.length).toEqual(2);

        let links = navItemLinks[0].getElementsByClassName('nav-link');

        expect(links.length).toEqual(1);
        expect(links[0].textContent).toContain('Module 1');
        expect(links[0].attributes.getNamedItem('href').value).toContain('/accounts/record/1');

        links = navItemLinks[1].getElementsByClassName('nav-link');

        expect(links.length).toEqual(1);
        expect(links[0].textContent).toContain('Module 2');
        expect(links[0].attributes.getNamedItem('href').value).toContain('/accounts/record/2');
    });
});
