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

import {CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {ComponentFixture, fakeAsync, TestBed, tick} from '@angular/core/testing';
import {Event, NavigationCancel, NavigationEnd, NavigationError, NavigationStart, Router} from '@angular/router';
import {
    AppState,
    AppStateStore,
    DraftsService,
    NotificationStore,
    RecentlyViewedService,
    StateManager,
    SystemConfigStore
} from 'core';
import {BehaviorSubject, Subject} from 'rxjs';
import {AppComponent} from './app.component';

describe('AppComponent', () => {
    let fixture: ComponentFixture<AppComponent>;
    let routerEvents: Subject<Event>;
    let appState$: BehaviorSubject<AppState>;
    let appState: jasmine.SpyObj<AppStateStore>;
    let stateManager: jasmine.SpyObj<StateManager>;
    let configs: jasmine.SpyObj<SystemConfigStore>;
    let notifications: jasmine.SpyObj<NotificationStore>;
    let recentlyViewed: jasmine.SpyObj<RecentlyViewedService>;
    let draftsCount$: Subject<number>;
    let drafts: jasmine.SpyObj<DraftsService>;

    beforeEach(async () => {
        routerEvents = new Subject<Event>();
        appState$ = new BehaviorSubject<AppState>({loading: false, initialAppLoading: true});
        appState = jasmine.createSpyObj<AppStateStore>(
            'AppStateStore',
            ['getModule', 'getView', 'setRouteUrl', 'updateInitialAppLoading', 'updateLoading'],
            {vm$: appState$}
        );
        stateManager = jasmine.createSpyObj<StateManager>('StateManager', ['clearBackendCacheable']);
        configs = jasmine.createSpyObj<SystemConfigStore>('SystemConfigStore', ['getConfigValue']);
        configs.getConfigValue.and.returnValue(null);
        notifications = jasmine.createSpyObj<NotificationStore>('NotificationStore', ['conditionalNotificationRefresh']);
        recentlyViewed = jasmine.createSpyObj<RecentlyViewedService>('RecentlyViewedService', ['conditionalGlobalRefresh']);
        draftsCount$ = new Subject<number>();
        drafts = jasmine.createSpyObj<DraftsService>(
            'DraftsService',
            ['closeModal', 'draftsCount'],
            {draftsCount$}
        );
        drafts.draftsCount.and.returnValue(0);

        await TestBed.configureTestingModule({
            schemas: [CUSTOM_ELEMENTS_SCHEMA],
            declarations: [AppComponent],
            providers: [
                {provide: Router, useValue: {events: routerEvents}},
                {provide: AppStateStore, useValue: appState},
                {provide: StateManager, useValue: stateManager},
                {provide: SystemConfigStore, useValue: configs},
                {provide: NotificationStore, useValue: notifications},
                {provide: RecentlyViewedService, useValue: recentlyViewed},
                {provide: DraftsService, useValue: drafts}
            ]
        }).compileComponents();
        fixture = TestBed.createComponent(AppComponent);
    });

    afterEach(() => {
        routerEvents.complete();
        appState$.complete();
        draftsCount$.complete();
        fixture?.destroy();
    });

    it('renders the spinner during initial and subsequent loading only', fakeAsync(() => {
        const spinner = () => fixture.nativeElement.querySelector('app-full-page-spinner');
        fixture.detectChanges();
        tick();
        fixture.detectChanges();
        expect(spinner()).not.toBeNull();

        appState$.next({loading: false, initialAppLoading: false});
        tick();
        fixture.detectChanges();
        expect(spinner()).toBeNull();

        appState$.next({loading: true, initialAppLoading: false});
        tick();
        fixture.detectChanges();
        expect(spinner()).not.toBeNull();
    }));

    it('marks navigation as loading, clears configured caches and refreshes after the delay', fakeAsync(() => {
        appState.getModule.and.returnValue('Accounts');
        appState.getView.and.returnValue('detail');
        configs.getConfigValue.withArgs('cache_reset_actions').and.returnValue({Accounts: ['detail']});

        routerEvents.next(new NavigationStart(1, '/contacts'));

        expect(appState.updateLoading).toHaveBeenCalledOnceWith('router-navigation', true);
        expect(stateManager.clearBackendCacheable).toHaveBeenCalledTimes(1);
        tick(499);
        expect(notifications.conditionalNotificationRefresh).not.toHaveBeenCalled();
        expect(recentlyViewed.conditionalGlobalRefresh).not.toHaveBeenCalled();
        tick(1);
        expect(notifications.conditionalNotificationRefresh).toHaveBeenCalledTimes(1);
        expect(recentlyViewed.conditionalGlobalRefresh).toHaveBeenCalledTimes(1);
    }));

    it('records the route, resets scroll and clears loading when navigation completes', () => {
        const scrollTo = spyOn(window, 'scrollTo');
        routerEvents.next(new NavigationEnd(1, '/contacts', '/contacts'));

        expect(scrollTo).toHaveBeenCalledOnceWith(0, 0);
        expect(appState.setRouteUrl).toHaveBeenCalledOnceWith('/contacts');
        expect(appState.updateLoading).toHaveBeenCalledOnceWith('router-navigation', false);
        expect(appState.updateInitialAppLoading).toHaveBeenCalledOnceWith(false);
    });

    for (const [outcome, event] of [
        ['cancelled', new NavigationCancel(1, '/contacts', 'cancelled')],
        ['failed', new NavigationError(1, '/contacts', new Error('navigation failed'))]
    ] as const) {
        it(`clears loading without updating the route when navigation is ${outcome}`, () => {
            routerEvents.next(event);

            expect(appState.updateLoading).toHaveBeenCalledOnceWith('router-navigation', false);
            expect(appState.updateInitialAppLoading).toHaveBeenCalledOnceWith(false);
            expect(appState.setRouteUrl).not.toHaveBeenCalled();
        });
    }

    it('shows the drafts button for a positive count and removes it after the last draft closes', fakeAsync(() => {
        const button = () => fixture.nativeElement.querySelector('#drafts-container scrm-button');
        fixture.detectChanges();
        expect(button()).toBeNull();

        drafts.draftsCount.and.returnValue(11);
        draftsCount$.next(11);
        fixture.detectChanges();
        expect(button()).not.toBeNull();
        expect(fixture.componentInstance.draftsButton()).toEqual(jasmine.objectContaining({
            dynamicLabelFields: {count: {value: '10+', type: 'varchar'}}
        }));

        drafts.draftsCount.and.returnValue(0);
        draftsCount$.next(0);
        expect(drafts.closeModal).toHaveBeenCalledTimes(1);
        tick(250);
        fixture.detectChanges();
        expect(button()).toBeNull();
    }));
});
