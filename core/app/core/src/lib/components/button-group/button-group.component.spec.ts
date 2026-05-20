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

import {ButtonGroupComponent} from './button-group.component';
import {Component} from '@angular/core';
import {NgbDropdownModule} from '@ng-bootstrap/ng-bootstrap';
import {ButtonGroupInterface} from '../../common/components/button/button-group.model';
import {shareReplay} from 'rxjs/operators';
import {Observable, of} from 'rxjs';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';

@Component({
    selector: 'dropdown-group-test-host-component',
    template: '<scrm-button-group [config$]="config"></scrm-button-group>'
})
class ButtonGroupTestHostComponent {
    clicked = 0;
    clickedItem1 = 0;
    clickedItem2 = 0;
    clickedItem3 = 0;
    clickedItem4 = 0;
    clickedItem5 = 0;
    clickedItem6 = 0;
    config: Observable<ButtonGroupInterface> = of({
        wrapperKlass: ['some-class'],
        dropdownLabel: 'Test Dropdown Label',
        buttons: [
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
            },
            {
                label: 'Item 3',
                onClick: (): void => {
                    this.clickedItem3++;
                }
            },
            {
                label: 'Item 4',
                onClick: (): void => {
                    this.clickedItem4++;
                }
            },
            {
                label: 'Item 5',
                onClick: (): void => {
                    this.clickedItem5++;
                }
            },
            {
                label: 'Item 6',
                onClick: (): void => {
                    this.clickedItem6++;
                }
            },
        ]
    }).pipe(shareReplay(1));
}

describe('ButtonGroupComponent', () => {

    let testHostComponent: ButtonGroupTestHostComponent;
    let testHostFixture: ComponentFixture<ButtonGroupTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                ButtonGroupTestHostComponent,
                ButtonGroupComponent,
            ],
            imports: [
                ButtonModule,
                NgbDropdownModule,
                DropdownButtonModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(ButtonGroupTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('buttons should be clickable', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();
        const buttons = testHostFixture.nativeElement.getElementsByClassName('button-group-button');

        expect(buttons).toBeTruthy();
        expect(buttons.length).toEqual(5);

        buttons.item(0).click();
        buttons.item(1).click();
        buttons.item(2).click();
        buttons.item(3).click();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent.clickedItem1).toEqual(1);
            expect(testHostComponent.clickedItem2).toEqual(1);
            expect(testHostComponent.clickedItem3).toEqual(1);
            expect(testHostComponent.clickedItem4).toEqual(1);
        });
    }));

    it('dropdown items should be clickable', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        const element = testHostFixture.nativeElement;
        const buttonWrapper = element.getElementsByClassName('button-group-dropdown').item(0);
        const button = buttonWrapper.getElementsByClassName('dropdown-toggle button-group-button').item(0);
        const divElement = element.querySelector('scrm-dropdown-button');

        testHostComponent.clicked = 0;

        expect(button).toBeTruthy();

        button.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            const links = divElement.getElementsByClassName('dropdown-item');

            expect(links.length).toEqual(2);
            links.item(0).click();
            links.item(1).click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                expect(testHostComponent.clickedItem5).toEqual(1);
                expect(testHostComponent.clickedItem6).toEqual(1);
            });
        });
    }));
});
