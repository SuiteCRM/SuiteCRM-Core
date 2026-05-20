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
import {Injectable} from '@angular/core';
import {Observable} from 'rxjs';
import {Record} from '../../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {ViewMode} from '../../../../common/views/view.model';
import {map} from 'rxjs/operators';
import {BaseRecordContainerStore} from '../../../../store/record-container/base-record-container.store';
import {RecordPanelMetadata} from './record-panel.store.model';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {MessageService} from '../../../../services/message/message.service';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';

@Injectable()
export class RecordPanelStore extends BaseRecordContainerStore<RecordPanelMetadata> {

    constructor(
        protected appStateStore: AppStateStore,
        protected meta: MetadataStore,
        protected message: MessageService,
        protected fieldManager: FieldManager,
        protected language: LanguageStore,
        protected storeFactory: RecordStoreFactory
    ) {

        super(
            appStateStore,
            meta,
            message,
            fieldManager,
            language,
            storeFactory
        );
    }

    /**
     * Get view fields observable
     *
     * @returns {object} Observable<ViewFieldDefinition[]>
     */
    public getViewFields$(): Observable<ViewFieldDefinition[]> {
        return this.meta$.pipe(map((meta: RecordPanelMetadata) => {
            return meta.fields;
        }));
    }

    /**
     * Get view fields keys observable
     *
     * @returns {object} Observable<string[]>
     */
    public getViewFieldsKeys$(): Observable<string[]> {
        return this.meta$.pipe(map((meta: RecordPanelMetadata) => {
            if (!meta.fields || !meta.fields.length) {
                return [];
            }

            const keys = [];

            meta.fields.forEach(field => {
                keys.push(field.name);
            });

            return keys;
        }));
    }

    /**
     * Init record
     *
     * @param {object} record to use
     * @param {string} mode to use
     * @param {boolean} loadMetadata to use
     * @returns {object} Observable<any>
     */
    public initRecord(record: Record, mode: ViewMode = 'detail' as ViewMode, loadMetadata = true): void {

        super.initRecord(record, mode, loadMetadata);
        this.setRecord(record);
    }

}
