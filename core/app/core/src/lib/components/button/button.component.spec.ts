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

import {ButtonComponent} from './button.component';
import {Component} from '@angular/core';
import {ButtonInterface} from '../../common/components/button/button.model';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {ImageModule} from '../image/image.module';


@Component({
    selector: 'button-test-host-component',
    template: '<scrm-button [config]="config"></scrm-button>'
})
class ButtonTestHostComponent {
    clicked = 0;
    config: ButtonInterface = {
        klass: 'button-test',
        onClick: () => {
            this.clicked++;
        },
        label: 'Button label'
    };
}

describe('ButtonComponent', () => {
    let testHostComponent: ButtonTestHostComponent;
    let testHostFixture: ComponentFixture<ButtonTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                ButtonComponent,
                ButtonTestHostComponent,
            ],
            imports: [
                ImageModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();
    }));

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(ButtonTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should render', () => {
        expect(testHostComponent).toBeTruthy();
    });


    it('should have label', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(buttonElement.textContent).toContain('Button label');
    });

    it('should have class', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(buttonElement.className).toContain('button-test');
    });

    it('should support click', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');
        buttonElement.click();

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(testHostComponent.clicked).toEqual(1);
    });

    it('should support no label', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');
        testHostComponent.config.label = null;

        testHostFixture.detectChanges();

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(buttonElement.textContent).not.toContain('Button label');
    });

    it('should support no class', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');
        testHostComponent.config.klass = null;

        testHostFixture.detectChanges();

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(buttonElement.className).not.toContain('button-class');
    });

    it('should support no click callback', () => {

        const buttonElement = testHostFixture.nativeElement.querySelector('button');

        testHostComponent.config.onClick = null;
        testHostFixture.detectChanges();

        buttonElement.click();

        expect(testHostComponent).toBeTruthy();
        expect(buttonElement).toBeTruthy();
        expect(testHostComponent.clicked).toEqual(0);
    });
});
