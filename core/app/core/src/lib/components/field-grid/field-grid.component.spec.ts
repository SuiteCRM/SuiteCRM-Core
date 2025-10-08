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
import {FieldGridComponent} from './field-grid.component';
import {Component} from '@angular/core';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {LayoutModule} from '@angular/cdk/layout';
import {By} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {RouterTestingModule} from '@angular/router/testing';
import {Field} from '../../common/record/field.model';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {FieldModule} from '../../fields/field.module';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';

@Component({
    selector: 'field-grid-host-component',
    template: `
        <scrm-field-grid [fieldMode]="mode" [fields]="fields" [actions]="true" [special]="special.length > 0">

            <div class="float-right mt-4" *ngIf="special.length > 0" field-grid-special>
                <div class="d-inline-block form-check mb-2 mr-sm-2" *ngFor="let item of special ">
                    <input class="form-check-input" type="checkbox" [value]="item.value">
                    <label class="form-check-label">{{item.label}}</label>
                </div>
            </div>

            <span class="float-right mt-4" field-grid-actions>
                <scrm-button *ngFor="let button of gridButtons" [config]="button"></scrm-button>
            </span>
        </scrm-field-grid>
    `
})
class FieldGridTestHostComponent {
    mode: 'edit';
    fields = [
        {
            name: 'first_name',
            label: 'First Name',
            type: 'varchar',
            value: '',
        },
        {
            name: 'last_name',
            label: 'Last Name',
            type: 'varchar',
            value: '',
        },
        {
            name: 'phone',
            label: 'Phone',
            type: 'varchar',
            value: '',
        },
    ];
    special = [
        {
            name: 'my_filters_only',
            label: 'My Filters',
            type: 'bool'
        } as Field,
        {
            name: 'favorites_only',
            label: 'Favorites',
            type: 'bool'
        } as Field,
    ];
    gridButtons = [
        {
            label: 'Clear',
            klass: ['clear-filters-button', 'btn', 'btn-outline-danger', 'btn-sm']
        },
        {
            label: 'Search',
            klass: ['filter-button', 'btn', 'btn-danger', 'btn-sm']
        }
    ];
}

describe('FieldGridComponent', () => {
    let testHostComponent: FieldGridTestHostComponent;
    let testHostFixture: ComponentFixture<FieldGridTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                FieldGridTestHostComponent,
                FieldGridComponent
            ],
            imports: [
                ButtonModule,
                DropdownButtonModule,
                BrowserDynamicTestingModule,
                LayoutModule,
                FieldModule,
                CommonModule,
                RouterTestingModule,
                ApolloTestingModule
            ],
            providers: [],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(FieldGridTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have grid list', waitForAsync(() => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('scrm-field-grid')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('form')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.clear-filters-button')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.filter-button')).nativeElement).toBeTruthy();
    }));
});
