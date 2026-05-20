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

import {DropdownButtonComponent} from './dropdown-button.component';
import {Component} from '@angular/core';
import {DropdownButtonInterface} from '../../common/components/button/dropdown-button.model';
import {NgbDropdownModule, NgbTooltipModule} from '@ng-bootstrap/ng-bootstrap';
import {ButtonModule} from '../button/button.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';

@Component({
    selector: 'dropdown-button-test-host-component',
    template: '<scrm-dropdown-button [config]="button"></scrm-dropdown-button>'
})
class DropdownButtonTestHostComponent {
    clicked = 0;
    clickedItem1 = 0;
    clickedItem2 = 0;
    button: DropdownButtonInterface = {
        klass: {'some-class': true},
        onClick: () => {
            this.clicked++;
        },
        label: 'Test Label',
        items: [
            {
                label: 'Item 1',
                onClick: (): void => {
                    this.clickedItem1++;
                }
            },
            {
                label: 'Item 2',
                onClick: (): void => {
                    this.clickedItem2++;
                }
            }
        ]
    };
}

describe('DropdownButtonComponent', () => {
    let testHostComponent: DropdownButtonTestHostComponent;
    let testHostFixture: ComponentFixture<DropdownButtonTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                DropdownButtonTestHostComponent,
                DropdownButtonComponent,
            ],
            imports: [
                ButtonModule,
                NgbDropdownModule,
                NgbTooltipModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(DropdownButtonTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have class', () => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.className).toContain('some-class');
    });

    it('should be clickable', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');
        const divElement = testHostFixture.nativeElement.querySelector('div');

        testHostComponent.clicked = 0;

        expect(button).toBeTruthy();

        button.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            const links = divElement.getElementsByClassName('dropdown-item');
            links.item(0).click();
            links.item(1).click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                expect(testHostComponent.clickedItem1).toEqual(1);
                expect(testHostComponent.clickedItem2).toEqual(1);
            });
        });
    }));

});
