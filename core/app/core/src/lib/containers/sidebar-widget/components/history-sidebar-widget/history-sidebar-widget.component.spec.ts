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

import {ComponentFixture, TestBed} from '@angular/core/testing';
import {provideRouter} from '@angular/router';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {of} from 'rxjs';
import {HistorySidebarWidgetComponent} from './history-sidebar-widget.component';
import {HistorySidebarWidgetModule} from './history-sidebar-widget.module';
import {HistoryTimelineAdapter} from './history-timeline.adapter.service';
import {HistoryTimelineAdapterFactory} from './history-timeline.adapter.factory';
import {LanguageStore} from '../../../../store/language/language.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';

describe('HistoryTimelineWidgetComponent', () => {
    let fixture: ComponentFixture<HistorySidebarWidgetComponent>;
    let adapter: HistoryTimelineAdapter;
    const context = {module: 'accounts', id: 'test-record'};

    beforeEach(async () => {
        adapter = new HistoryTimelineAdapter(null, mockModuleNavigation);
        spyOn(adapter, 'init');
        spyOn(adapter, 'fetchTimelineEntries').and.returnValue(adapter.dataStream$);
        await TestBed.configureTestingModule({
            imports: [HistorySidebarWidgetModule, NoopAnimationsModule],
            providers: [
                provideRouter([]),
                {provide: HistoryTimelineAdapterFactory, useValue: {create: () => adapter}},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: ModuleNavigation, useValue: mockModuleNavigation}
            ]
        }).compileComponents();

        fixture = TestBed.createComponent(HistorySidebarWidgetComponent);
        fixture.componentRef.setInput('context', context);
        fixture.componentRef.setInput('config', {
            key: 'history',
            type: 'history',
            reload$: of(false),
            subpanelReload$: of({})
        });
    });

    it('initializes the timeline for the active record', () => {
        fixture.detectChanges();

        expect(adapter.init).toHaveBeenCalledOnceWith(context);
        expect(fixture.nativeElement.querySelector('.history-timeline')).not.toBeNull();
    });

    it('requests the next page without resetting the timeline', () => {
        fixture.detectChanges();
        fixture.componentInstance.getLoadMoreButton().onClick();

        expect(adapter.fetchTimelineEntries).toHaveBeenCalledOnceWith(false);
    });
});
