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
import {RecordThreadStore} from '../../containers/record-thread/store/record-thread/record-thread.store';
import {SystemConfigStore} from '../system-config/system-config.store';
import {deepClone} from '../../common/utils/object-utils';
import {Record} from '../../common/record/record.model';
import {Field} from '../../common/record/field.model';
import {SearchCriteria} from '../../common/views/list/search-criteria.model';
import {RecordThreadItemMetadata} from '../../containers/record-thread/store/record-thread/record-thread-item.store.model';
import {
    RecordThreadConfig,
    ThreadItemMetadataConfig
} from '../../containers/record-thread/components/record-thread/record-thread.model';
import {RecordThreadStoreFactory} from '../../containers/record-thread/store/record-thread/record-thread.store.factory';
import {RecordThreadItemConfig} from '../../containers/record-thread/components/record-thread-item/record-thread-item.model';
import {Process, ProcessService} from '../../services/process/process.service';
import {catchError, take, tap} from 'rxjs/operators';
import {AsyncActionInput} from '../../services/process/processes/async-action/async-action';
import {MessageService} from '../../services/message/message.service';
import {Observable, timer} from 'rxjs';
import {LanguageStore} from '../language/language.store';
import {DynamicLabelService} from '../../services/language/dynamic-label.service';
import {NotificationStore} from './notification.store';

@Injectable({
    providedIn: 'root',
})
export class NotificationsService {

    constructor(
        protected systemConfig: SystemConfigStore,
        protected storeFactory: RecordThreadStoreFactory,
        protected processService: ProcessService,
        protected messages: MessageService,
        protected language: LanguageStore,
        protected dynamicLabels: DynamicLabelService
    ) {
    }

    getOptions(): any {
        const ui = this.systemConfig.getConfigValue('ui');
        const options = ui?.notifications ?? null;
        return options;
    }

    initStore(): RecordThreadStore {
        const options = this.getOptions();

        const config = {} as RecordThreadConfig;
        this.setupListActions(config, options);
        this.setupItemConfig(config, options);

        const store = this.storeFactory.create();
        store.setItemMetadata(config.itemConfig.metadata);
        store.setListMetadata({actions: config.listActions});

        const filters = {
            orderBy: options?.filters?.orderBy ?? 'date_entered',
            sortOrder: options?.filters?.sortOrder ?? 'asc',
            preset: {type: 'alerts'}
        } as SearchCriteria;

        store.init(options.module, false, options?.pageSize ?? null);
        store.setFilters(filters).pipe(take(1)).subscribe();

        return store;
    }

    setupListActions(config: RecordThreadConfig, options): void {
        config.listActions = options?.listActions ?? [];

        if ((options?.collapseListActions ?? null) !== null) {
            config.collapseListActions = options.collapseListActions;
        }
    }

    setupItemConfig(config: RecordThreadConfig, options): void {
        config.itemConfig = {
            collapsible: options?.item?.collapsible ?? false,
            collapseLimit: options?.item?.collapseLimit ?? null,
            klass: options?.item?.itemClass ?? '',
            buttonClass: options?.item?.buttonClass ?? '',
            buttonGroupClass: options?.item?.buttonGroupClass ?? '',
            dynamicClass: options?.item?.dynamicClass ?? [],
            containerClass: options?.item?.containerClass ?? '',
            flexDirection: options?.item?.flexDirection ?? '',
            metadata: {} as RecordThreadItemMetadata,
        } as RecordThreadItemConfig;

        this.setupItemMetadata(config.itemConfig.metadata, options.item.layout, options);
    }

    setupItemMetadata(metadata: RecordThreadItemMetadata, layout: ThreadItemMetadataConfig, options): void {
        if (layout && layout.header) {
            metadata.headerLayout = deepClone(layout.header);
        }

        if (layout && layout.body) {
            metadata.bodyLayout = deepClone(layout.body);
        }

        if (layout && layout.actions) {
            metadata.actions = deepClone(layout.actions);
        }

        if (options?.item && options?.item?.fields) {
            metadata.fields = deepClone(options.item.fields);
        }

        if ((options?.item?.collapseActions ?? null) !== null) {
            metadata.collapseActions = options?.item?.collapseActions;
        }
    }

    /**
     * Send notification mark-as-read request
     *
     * @param {object} store to use
     * @returns {object} Observable<Process>
     */
    markNotificationsAsRead(store: RecordThreadStore): Observable<Process> {
        const options = {
            action: 'record-thread-list-mark-as-read',
            module: store.module ?? 'alerts',
            ids: store.getRecordIds(),
        } as AsyncActionInput;


        return this.processService
            .submit('record-thread-list-mark-as-read', options)
            .pipe(
                tap((process: Process) => {
                    let handler = 'addSuccessMessageByKey';
                    if (process.status === 'error') {
                        handler = 'addDangerMessageByKey';
                    }

                    if (process.messages) {
                        process.messages.forEach(message => {
                            this.messages[handler](message);
                        });
                    }

                    store.getItemStores().forEach(notification => {
                        const staging = notification?.recordStore?.getStaging() ?? {} as Record;
                        const field = staging?.fields['is_read'] ?? null as Field;
                        if (field == null) {
                            return;
                        }
                        field.value = 'true';
                    });
                }),
                catchError(err => {
                    this.messages.addDangerMessageByKey('ERR_NOTIFICATIONS_MARK_AS_READ');
                    throw err;
                }),
            );
    }

    onLoadMore(notificationStore: NotificationStore): void {
        timer(1500).pipe(take(1)).subscribe(() => {
            notificationStore.markNotificationsAsRead();
        });
    }

    onRefresh(store: RecordThreadStore, notificationStore: NotificationStore): void {
        const count = store.getRecordList().getMeta().unreadCount as number;
        let appStateCount = notificationStore.getNotificationsUnreadTotal();
        if (count > appStateCount) {
            let unreadCount = (count - appStateCount).toString();
            const labelTemplate = this.language.getFieldLabel('LBL_NEW_NOTIFICATION');
            const parsedLabel = this.dynamicLabels.parse(labelTemplate, {unread: unreadCount}, {});
            this.messages.addSuccessMessage(parsedLabel);
        }
        notificationStore.setNotificationsUnreadTotal(count);
    }
}
