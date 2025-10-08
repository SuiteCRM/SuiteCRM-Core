/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {combineLatestWith, Observable, Subscription} from 'rxjs';
import {Injectable} from '@angular/core';
import {map} from 'rxjs/operators';
import {MetadataStore, RecordViewMetadata} from '../../../store/metadata/metadata.store.service';
import {RecordContentConfig, RecordContentDataSource} from "../../../components/record-content/record-content.model";
import {RecordValidationHandler} from "../../../services/record/validation/record-validation.handler";
import {RecordModalStore} from "../store/record-modal/record-modal.store";
import {LanguageStore} from "../../../store/language/language.store";
import {RecordModalActionManager} from "../actions/record-modal-action-manager.service";
import {Action} from "../../../common/actions/action.model";
import {ViewMode} from "../../../common/views/view.model";
import {Panel} from "../../../common/metadata/metadata.model";
import {Record} from "../../../common/record/record.model";

export class RecordModalContentAdapter implements RecordContentDataSource {
    inlineEdit: true;

    protected fieldSubs: Subscription[] = [];

    constructor(
        protected store: RecordModalStore,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actions: RecordModalActionManager,
        protected recordValidationHandler: RecordValidationHandler
    ) {
    }

    getEditAction(): void {
        const data = {
            store: this.store
        } as any;

        const action = {
            key: 'edit'
        } as Action;

        this.actions.run(action, this.store.getMode(), data);
    }

    getDisplayConfig(): Observable<RecordContentConfig> {
        return this.store.recordViewMetadata$.pipe(
            combineLatestWith(this.store.mode$),
            map(([meta, mode]: [RecordViewMetadata, ViewMode]) => {
                const layout = this.getLayout(meta);
                const maxColumns = meta.templateMeta.maxColumns || 2;
                const colClasses = meta?.templateMeta?.colClasses ?? [];
                const tabDefs = meta.templateMeta.tabDefs;

                return {
                    layout,
                    mode,
                    maxColumns,
                    tabDefs,
                    colClasses
                } as RecordContentConfig;
            })
        );
    }

    getPanels(): Observable<Panel[]> {
        return this.store.panels$;
    }

    getRecord(): Observable<Record> {
        return this.store.stagingRecord$.pipe(
            combineLatestWith(this.store.mode$),
            map(([record, mode]: [Record, ViewMode]) => {
                if (mode === 'edit' || mode === 'create') {
                    this.recordValidationHandler.initValidators(record);
                } else {
                    this.recordValidationHandler.resetValidators(record);
                }

                if (record.formGroup) {
                    record.formGroup.enable();
                }

                return record;
            })
        );

    }

    protected getLayout(recordMeta: RecordViewMetadata): string {
        let layout = 'panels';
        if (recordMeta.templateMeta.useTabs) {
            layout = 'tabs';
        }

        return layout;
    }

    clean(): void {
        this.fieldSubs.forEach(sub => sub.unsubscribe());
    }
}
