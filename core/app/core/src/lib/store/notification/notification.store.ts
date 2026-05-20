/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

import {Injectable} from '@angular/core';
import {BehaviorSubject, Observable, of, timer} from 'rxjs';
import {distinctUntilChanged, map, shareReplay, take, tap} from 'rxjs/operators';
import {StateStore} from '../state';
import {deepClone} from '../../common/utils/object-utils';
import {RecordThreadStore} from '../../containers/record-thread/store/record-thread/record-thread.store';
import {NotificationsService} from './notifications.service';
import {AppStateStore} from '../app-state/app-state.store';
import {Process} from '../../services/process/process.service';
import {SystemConfigStore} from '../system-config/system-config.store';

export interface NotificationState {
    notificationsEnabled?: boolean;
    notificationsTotal?: number;
    notificationsUnreadTotal?: number;
}

const initialState: NotificationState = {
    notificationsEnabled: false,
    notificationsTotal: 0,
    notificationsUnreadTotal: 0
};

let internalState: NotificationState = deepClone(initialState);

let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class NotificationStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    notificationsUnreadTotal$: Observable<number>;
    notificationsTotal$: Observable<number>;
    notificationsEnabled$: Observable<boolean>;

    protected store = new BehaviorSubject<NotificationState>(internalState);
    protected state$ = this.store.asObservable();

    protected notificationStore: RecordThreadStore;
    private notificationPageSize: number = 0;

    constructor(
        protected appStateStore: AppStateStore,
        protected configs: SystemConfigStore,
        protected notificationService: NotificationsService
    ) {
        this.notificationsUnreadTotal$ = this.state$.pipe(map(state => state.notificationsUnreadTotal), distinctUntilChanged());
        this.notificationsTotal$ = this.state$.pipe(map(state => state.notificationsTotal), distinctUntilChanged());
        this.notificationsEnabled$ = this.state$.pipe(map(state => state.notificationsEnabled), distinctUntilChanged());
    }

    /**
     * Clear state
     */
    public clear(): void {
        cache$ = null;
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
        this.clear();
        this.notificationStore.clear();
        this.notificationStore = null;
    }

    public init(): void {
        if (this.appStateStore.isLoggedIn()) {
            this.initNotifications();
        }
    }

    /**
     * Initialize notifications
     */
    public initNotifications() {
        if (this.notificationStore) {
            return;
        }
        this.notificationStore = this.notificationService.initStore();
    }

    /**
     * Enable notifications
     */
    public enableNotifications(): void {
        this.initNotifications();
        this.updateState({...internalState, notificationsEnabled: true});
    }

    /**
     * Disable notifications
     */
    public disableNotifications(): void {
        this.disableNotificationAutoRefresh();
        this.updateState({
            ...internalState,
            notificationsEnabled: false,
            notificationsTotal: 0,
            notificationsUnreadTotal: 0
        });
    }

    /**
     * Check if notifications are enabled
     */
    public areNotificationsEnabled(): boolean {
        return internalState.notificationsEnabled;
    }

    /**
     * Call notification refresh
     */
    public refreshNotifications(): void {
        if (!this.areNotificationsEnabled()) {
            return;
        }
        this.notificationStore.load(false).pipe(take(1)).subscribe(() => {
            this.notificationService.onRefresh(this.notificationStore, this);
        });
    }

    /**
     * Mark current notifications as read
     */
    public markNotificationsAsRead(): void {

        if (!this.areNotificationsEnabled()) {
            return;
        }

        this.notificationStore.getRecordList().pagination$.pipe(
            take(1),
            tap(data => this.notificationPageSize = data.pageSize),
            tap(data => this.setNotificationsTotal(data.total)),
        ).subscribe();

        let unreadCountFromRecords = this.notificationStore.getRecordList().records.filter(item => item.attributes.is_read === false).length;

        let readCount = this.getNotificationsTotal() - this.getNotificationsUnreadTotal();

        timer(500).pipe(take(1))
            .subscribe(() => {
                if (this.getNotificationsUnreadTotal() > 0 && (this.notificationPageSize > readCount || unreadCountFromRecords > 0)) {
                    this.notificationService.markNotificationsAsRead(this.notificationStore)
                        .subscribe((process: Process) => {
                            const unreadCount = process?.data?.unreadCount ?? 0;
                            this.setNotificationsUnreadTotal(unreadCount);
                            this.setRecordAsReadTrue();
                        });
                }
            });
    }

    /**
     * Run conditional navigation auto-refresh
     * @param view current view
     */
    public conditionalNotificationRefresh(view: string = ''): void {

        if (!this.areNotificationsEnabled()) {
            return;
        }

        const reloadActions = this.configs.getUi('notifications_reload_actions') ?? null;
        const previousModule = this.getModule();

        if (!view) {
            view = this.getView();
        }


        if (!reloadActions || !previousModule) {
            return;
        }

        const actions: string[] = reloadActions[previousModule];

        if (!actions || !actions.length) {
            return;
        }

        const reload = actions.some(action => {
            return action === 'any' || action === view;
        });

        if (reload) {
            this.refreshNotifications();
        }
    }

    /**
     * Disable notifications auto-refresh
     */
    public disableNotificationAutoRefresh(): void {
        this.notificationStore.disableAutoRefresh();
    }

    /**
     * Mark record as read
     */
    public setRecordAsReadTrue(): void {
        this.notificationStore.getRecordList().records.forEach(record => {
            if (!record.attributes.is_read) {
                record.attributes.is_read = true;
            }
        });
    }

    /**
     * Set notification as unread
     * @param notificationsUnreadTotal
     */
    public setNotificationsUnreadTotal(notificationsUnreadTotal: number): void {
        this.updateState({...internalState, notificationsUnreadTotal});
    }

    /**
     * Set notification as total
     * @param notificationsTotal
     */
    public setNotificationsTotal(notificationsTotal: number): void {
        this.updateState({...internalState, notificationsTotal});
    }

    /**
     * Get notification total
     *
     * @returns number
     */
    public getNotificationsTotal(): number {
        return internalState.notificationsTotal;
    }

    /**
     * Get unread notification count
     *
     * @returns number
     */
    public getNotificationsUnreadTotal(): number {
        return internalState.notificationsUnreadTotal;
    }

    /**
     * Get Notification store
     *
     * @returns {object}
     */
    public getNotificationStore(): RecordThreadStore {
        return this.notificationStore;
    }

    /**
     * Get the current module
     *
     * @returns {string} current view
     */
    public getModule(): string {
        return this.appStateStore.getModule();
    }

    /**
     * Get the current view
     *
     * @returns {string} current view
     */
    public getView(): string {
        return this.appStateStore.getView();
    }

    /**
     * On login handlers
     * @protected
     */
    protected onLogin(): void {
    }

    /**
     * On logout handlers
     * @protected
     */
    protected onLogout(): void {
        this.disableNotifications();
        this.clearAuthBased();
    }

    /**
     * Check if loaded
     */
    public isCached(): boolean {
        return cache$ !== null;
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: NotificationState): void {
        this.store.next(internalState = state);
    }

    /**
     * Set pre-loaded adminMetadata and cache
     */
    public set(state: NotificationState): void {
        cache$ = of(state).pipe(shareReplay(1));
        this.updateState(state);

    }

}
