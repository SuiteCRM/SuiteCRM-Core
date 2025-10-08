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
import {HistorySidebarWidgetComponent} from './history-sidebar-widget.component';
import {CollectionViewer, ListRange} from '@angular/cdk/collections';
import {of} from 'rxjs';
import {CommonModule} from '@angular/common';
import {ScrollingModule} from '@angular/cdk/scrolling';
import {Router} from '@angular/router';
import {RouterTestingModule} from '@angular/router/testing';
import {take} from 'rxjs/operators';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {FieldModule} from '../../../../fields/field.module';
import {themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {RecordViewStore} from '../../../../views/record/store/record-view/record-view.store';
import {mockRouter} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {recordviewStoreMock} from '../../../../views/record/store/record-view/record-view.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {ImageModule} from '../../../../components/image/image.module';
/*

import {HistoryTimelineAdapterFactory} from './history-timeline.adapter.factory';
import {timelineStoreFactoryMock} from '../../store/history-timeline/history-timeline.store.spec.mock';

const timelineAdapterFactoryMock = new HistoryTimelineAdapterFactory(
    timelineStoreFactoryMock
);
*/

/* eslint-enable camelcase, @typescript-eslint/camelcase */

describe('HistoryTimelineWidgetComponent', () => {
    let component: HistorySidebarWidgetComponent;
    let fixture: ComponentFixture<HistorySidebarWidgetComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                HistorySidebarWidgetComponent
            ],
            providers: [
                {provide: RecordViewStore, useValue: recordviewStoreMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: Router, useValue: mockRouter},
            ],
            imports: [
                CommonModule,
                ScrollingModule,
                ImageModule,
                FieldModule,
                ApolloTestingModule,
                RouterTestingModule,
                NoopAnimationsModule
            ],
        }).compileComponents();

        fixture = TestBed.createComponent(HistorySidebarWidgetComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
        component.adapter.connect({
            viewChange: of({start: 1, end: 5} as ListRange)
        } as CollectionViewer).pipe(take(1)).subscribe();
        fixture.detectChanges();
    }));
    /*
    it('should create', () => {
        expect(component).toBeTruthy();

        const timeline = fixture.nativeElement.getElementsByClassName('history-timeline');

        expect(timeline).toBeTruthy();
        expect(timeline.length).toEqual(1);

        const infiniteScroll = timeline.item(0).getElementsByTagName('cdk-virtual-scroll-viewport');

        expect(infiniteScroll).toBeTruthy();
        expect(infiniteScroll.length).toEqual(1);

    });

        it('should fetch records in range 1-5', () => {
            expect(component).toBeTruthy();

            const timeline = fixture.nativeElement.getElementsByClassName('history-timeline');

            expect(timeline).toBeTruthy();
            expect(timeline.length).toEqual(1);

            const infiniteScroll = timeline.item(0).getElementsByTagName('cdk-virtual-scroll-viewport');

            expect(infiniteScroll).toBeTruthy();
            expect(infiniteScroll.length).toEqual(1);

            const context = {
                module: 'accounts',
                id: '2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6'
            } as ViewContext;

            // initialize the timeline adapter
            timelineAdapterFactoryMock.create().init(context);

            fixture.detectChanges();
            fixture.whenStable().then(() => {
                // check expected timeline-data length
            });

        });*/
});
