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
import {ViewContext} from '../../../../common/views/view.model';
import {WidgetMetadata} from '../../../../common/metadata/widget.metadata';
import {CommonModule} from '@angular/common';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {FieldModule} from '../../../../fields/field.module';
import {ChartDataStoreFactory} from '../../../../store/chart-data/chart-data.store.factory';
import {SidebarWidgetModule} from './sidebar-widget.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {chartDataStoreFactoryMock} from '../../../../store/chart-data/chart-data.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';


@Component({
    selector: 'sidebar-widget-test-host-component',
    template: '<scrm-sidebar-widget [type]="type" [context]="context" [config]="config"></scrm-sidebar-widget>'
})
class SidebarWidgetHostComponent {
    type = 'chart';

    context: ViewContext = {
        module: 'accounts',
        id: '123'
    };

    config: WidgetMetadata = {
        type: 'chart',
        options: {
            toggle: false,
            headerTitle: true,
            charts: [
                {
                    chartKey: 'accounts-past-years-closed-opportunity-amounts',
                    chartType: 'line-chart',
                    statisticsType: 'accounts-past-years-closed-opportunity-amounts',
                    labelKey: 'LBL_CLOSED_PER_YEAR',
                    chartOptions: []
                },
            ],
        }
    };
}

describe('SideBarWidgetComponent', () => {
    let testHostComponent: SidebarWidgetHostComponent;
    let testHostFixture: ComponentFixture<SidebarWidgetHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                SidebarWidgetHostComponent,
            ],
            imports: [
                SidebarWidgetModule,
                BrowserDynamicTestingModule,
                FieldModule,
                CommonModule,
                RouterTestingModule,
                ApolloTestingModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: ChartDataStoreFactory, useValue: chartDataStoreFactoryMock},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(SidebarWidgetHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have statistics widget', () => {
        expect(testHostComponent).toBeTruthy();

        const topWidget = testHostFixture.nativeElement.getElementsByTagName('scrm-sidebar-widget')[0];

        expect(topWidget).toBeTruthy();
    });
});
