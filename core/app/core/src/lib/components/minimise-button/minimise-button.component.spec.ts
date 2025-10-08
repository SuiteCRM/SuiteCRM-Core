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
import {Component} from '@angular/core';
import {MinimiseButtonComponent} from './minimise-button.component';
import {ButtonInterface} from '../../common/components/button/button.model';
import {themeImagesStoreMock} from '../../store/theme-images/theme-images.store.spec.mock';
import {ButtonModule} from '../button/button.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';

@Component({
    selector: 'minimise-button-test-host-component',
    template: '<scrm-minimise-button [config]="button"></scrm-minimise-button>'
})
class MinimiseButtonTestHostComponent {
    clicked = 0;
    button: ButtonInterface = {
        klass: {'some-class': true},
        onClick: () => {
            this.clicked++;
        }
    };
}

describe('MinimiseButtonComponent', () => {
    let testHostComponent: MinimiseButtonTestHostComponent;
    let testHostFixture: ComponentFixture<MinimiseButtonTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                MinimiseButtonTestHostComponent,
                MinimiseButtonComponent,
            ],
            imports: [
                ButtonModule
            ],
            providers: [
                {
                    provide: ThemeImagesStore, useValue: themeImagesStoreMock
                },
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(MinimiseButtonTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have minimise icon', () => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');
        const svg = testHostFixture.nativeElement.querySelector('scrm-image');

        expect(button).toBeTruthy();
        expect(svg).toBeTruthy();
        expect(svg.attributes['ng-reflect-image'] && svg.attributes['ng-reflect-image'].value).toBeTruthy();
        expect(svg.attributes['ng-reflect-image'].value).toEqual('minimise');
    });

    it('should have class', () => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        expect(button.className).toContain('some-class');
        expect(button.className).toContain('minimise-button');
    });

    it('should be clickable', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');

        testHostComponent.clicked = 0;

        expect(button).toBeTruthy();

        button.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostComponent.clicked).toEqual(1);
            const svg = testHostFixture.nativeElement.querySelector('scrm-image');

            expect(svg.attributes['ng-reflect-image'] && svg.attributes['ng-reflect-image'].value).toBeTruthy();
            expect(svg.attributes['ng-reflect-image'].value).toEqual('plus_thin');
        });
    }));

    it('should toggle icon', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();
        const button = testHostFixture.nativeElement.querySelector('button');

        testHostComponent.clicked = 0;

        expect(button).toBeTruthy();

        button.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent.clicked).toEqual(1);
            let svg = testHostFixture.nativeElement.querySelector('scrm-image');

            expect(svg.attributes['ng-reflect-image'] && svg.attributes['ng-reflect-image'].value).toBeTruthy();
            expect(svg.attributes['ng-reflect-image'].value).toEqual('plus_thin');

            button.click();
            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {

                expect(testHostComponent.clicked).toEqual(2);
                svg = testHostFixture.nativeElement.querySelector('scrm-image');

                expect(svg.attributes['ng-reflect-image'] && svg.attributes['ng-reflect-image'].value).toBeTruthy();
                expect(svg.attributes['ng-reflect-image'].value).toEqual('minimise');

            });

        });
    }));

});
