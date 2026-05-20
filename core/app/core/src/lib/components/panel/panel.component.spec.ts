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
import {PanelComponent} from './panel.component';
import {By} from '@angular/platform-browser';
import {DropdownButtonInterface} from '../../common/components/button/dropdown-button.model';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';
import {ListViewStore} from '../../views/list/store/list-view/list-view.store';
import {CloseButtonModule} from '../close-button/close-button.module';
import {MinimiseButtonModule} from '../minimise-button/minimise-button.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {listviewStoreMock} from '../../views/list/store/list-view/list-view.store.spec.mock';

@Component({
    selector: 'panel-test-host-component',
    template: `
        <scrm-panel [title]="title" [close]="close">
            <span panel-header-button>
                <scrm-button *ngIf="button1" [config]="button1"></scrm-button>
                <scrm-button *ngIf="button2" [config]="button2"></scrm-button>
                <scrm-dropdown-button *ngIf="dropdown" [config]="dropdown"></scrm-dropdown-button>
            </span>
            <h1 panel-body>test panel body</h1>
        </scrm-panel>
    `
})
class PanelTestHostComponent {
    closeClicked = 0;
    button1Clicked = 0;
    button2Clicked = 0;
    title = 'Test Title';
    button1 = {
        label: 'Test button 1',
        onClick: (): void => {
            this.button1Clicked++;
        }
    };

    button2 = {
        label: 'Test button 2',
        onClick: (): void => {
            this.button2Clicked++;
        }
    };

    dropdown: DropdownButtonInterface = {
        klass: {'some-class': true},
        label: 'Test Label',
        items: []
    };

    close = {
        onClick: (): void => {
            this.closeClicked++;
        }
    };
}

describe('PanelComponent', () => {
    let testHostComponent: PanelTestHostComponent;
    let testHostFixture: ComponentFixture<PanelTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                PanelTestHostComponent,
                PanelComponent,
            ],
            imports: [
                CloseButtonModule,
                ButtonModule,
                DropdownButtonModule,
                MinimiseButtonModule
            ],
            providers: [
                {provide: ListViewStore, useValue: listviewStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(PanelTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have header', () => {
        expect(testHostFixture.debugElement.query(By.css('.card-header')).nativeElement).toBeTruthy();
    });

    it('should have body', () => {
        expect(testHostFixture.debugElement.query(By.css('.card-body')).nativeElement).toBeTruthy();
    });

    it('should have header title', () => {

        const element = testHostFixture.debugElement.query(By.css('.card-header'));

        expect(element.nativeElement.textContent).toContain(('Test Title'));
    });

    it('should have close icon', waitForAsync(() => {
        testHostComponent.closeClicked = 0;

        expect(testHostComponent).toBeTruthy();
        const element = testHostFixture.debugElement.query(By.css('.card-header'));
        const button = element.nativeElement.querySelector('button');

        expect(button).toBeTruthy();
        button.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostComponent.closeClicked).toEqual(1);
        });
    }));

    it('should have configurable buttons', waitForAsync(() => {
        testHostComponent.closeClicked = 0;
        testHostComponent.button1Clicked = 0;
        testHostComponent.button2Clicked = 0;

        expect(testHostComponent).toBeTruthy();
        const element = testHostFixture.debugElement.query(By.css('.panel-buttons'));
        const buttons = element.nativeElement.querySelectorAll('button');

        expect(buttons).toBeTruthy();
        expect(buttons.length).toEqual(3);

        buttons.forEach((btn) => {
            btn.click();
        });
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostComponent.button1Clicked).toEqual(1);
            expect(testHostComponent.button2Clicked).toEqual(1);
        });


    }));

    it('should have projected body', () => {
        expect(testHostComponent).toBeTruthy();
        const element = testHostFixture.debugElement.query(By.css('.card-body'));

        expect(element.nativeElement).toBeTruthy();

        const body = element.nativeElement.querySelector('h1');

        expect(body).toBeTruthy();
        expect(body.textContent).toContain('test panel body');
    });

});
