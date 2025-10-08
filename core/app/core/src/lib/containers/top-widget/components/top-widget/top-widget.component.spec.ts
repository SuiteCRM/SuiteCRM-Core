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
import {topWidgetStatisticsFactoryMock} from '../statistics-top-widget/statistics-top-widget.component.spec.mock';
import {CommonModule} from '@angular/common';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {FieldModule} from '../../../../fields/field.module';
import {SingleValueStatisticsStoreFactory} from '../../../../store/single-value-statistics/single-value-statistics.store.factory';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {TopWidgetModule} from './top-widget.module';


@Component({
    selector: 'top-widget-test-host-component',
    template: '<scrm-top-widget [type]="type" [context]="context" [config]="config"></scrm-top-widget>'
})
class TopWidgetHostComponent {
    type = 'statistics';

    context: ViewContext = {
        module: 'accounts',
        id: '123'
    };

    config: WidgetMetadata = {
        type: 'statistics',
        options: {
            statistics: [
                {
                    labelKey: 'LBL_AVERAGE_CLOSED_WON_PER_YEAR',
                    type: 'accounts-won-opportunity-amount-by-year'
                },
                {
                    labelKey: 'LBL_OPPORTUNITIES_TOTAL',
                    type: 'opportunities'
                },
            ],
        }
    };
}

describe('TopWidgetComponent', () => {
    let testHostComponent: TopWidgetHostComponent;
    let testHostFixture: ComponentFixture<TopWidgetHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                TopWidgetHostComponent,
            ],
            imports: [
                TopWidgetModule,
                BrowserDynamicTestingModule,
                FieldModule,
                CommonModule,
                RouterTestingModule,
                ApolloTestingModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: SingleValueStatisticsStoreFactory, useValue: topWidgetStatisticsFactoryMock},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(TopWidgetHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have statistics widget', () => {
        expect(testHostComponent).toBeTruthy();

        const topWidget = testHostFixture.nativeElement.getElementsByTagName('scrm-top-widget')[0];

        expect(topWidget).toBeTruthy();

        const widget = topWidget.getElementsByTagName('scrm-statistics-top-widget').item(0);

        expect(widget).toBeTruthy();

        const widgetBar = widget.getElementsByClassName('widget-bar').item(0);

        expect(widgetBar).toBeTruthy();

        const entries = widgetBar.getElementsByClassName('widget-bar-entry');

        expect(entries).toBeTruthy();
        expect(entries.length).toEqual(2);

        const averageSpend = entries.item(0);

        expect(averageSpend).toBeTruthy();

        const averageSpendLabel = averageSpend.getElementsByClassName('widget-bar-entry-label').item(0);
        const averageSpendValue = averageSpend.getElementsByClassName('widget-bar-entry-value').item(0);
        const averageSpendField = averageSpendValue.getElementsByTagName('scrm-field').item(0);
        const averageSpendCurrency = averageSpendField.getElementsByTagName('scrm-currency-detail').item(0);

        expect(averageSpendLabel).toBeTruthy();
        expect(averageSpendLabel.textContent).toContain('AVERAGE CLOSED WON PER YEAR:');
        expect(averageSpendValue).toBeTruthy();
        expect(averageSpendField).toBeTruthy();
        expect(averageSpendCurrency).toBeTruthy();
        expect(averageSpendCurrency.textContent).toContain('$1,467');

        const oppTotal = entries.item(1);

        expect(oppTotal).toBeTruthy();

        const oppTotalLabel = oppTotal.getElementsByClassName('widget-bar-entry-label').item(0);
        const oppTotalValue = oppTotal.getElementsByClassName('widget-bar-entry-value').item(0);
        const oppTotalField = oppTotalValue.getElementsByTagName('scrm-field').item(0);
        const oppTotalCurrency = oppTotalField.getElementsByTagName('scrm-currency-detail').item(0);

        expect(oppTotalLabel).toBeTruthy();
        expect(oppTotalLabel.textContent).toContain('TOTAL OPPORTUNITY VALUE:');
        expect(oppTotalValue).toBeTruthy();
        expect(oppTotalField).toBeTruthy();
        expect(oppTotalCurrency).toBeTruthy();
        expect(oppTotalCurrency.textContent).toContain('$5,400');
    });
});
