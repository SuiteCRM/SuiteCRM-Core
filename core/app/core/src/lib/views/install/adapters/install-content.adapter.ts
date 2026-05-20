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

import {combineLatestWith, Observable, of} from 'rxjs';
import {Injectable} from '@angular/core';
import {map, shareReplay} from 'rxjs/operators';
import {Panel, PanelRow} from '../../../common/metadata/metadata.model';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {RecordContentConfig, RecordContentDataSource} from '../../../components/record-content/record-content.model';
import {LanguageStore} from '../../../store/language/language.store';
import {InstallViewStore} from '../store/install-view/install-view.store';
import {InstallActionManager} from '../actions/install-action-manager.service';
import {InstallViewMetadata} from '../store/install-view/install-view.store.model';

@Injectable()
export class InstallContentAdapter implements RecordContentDataSource {
    inlineEdit: true;

    constructor(
        protected store: InstallViewStore,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actions: InstallActionManager
    ) {
    }

    getEditAction(): void {
    }

    getDisplayConfig(): Observable<RecordContentConfig> {
        return this.store.getMetadata$().pipe(
            combineLatestWith(this.store.mode$),
            map(([meta, mode]: [InstallViewMetadata, ViewMode]) => {
                const layout = this.getLayout(meta);
                const maxColumns = meta.templateMeta.maxColumns || 2;
                const tabDefs = meta.templateMeta.tabDefs;

                return {
                    layout,
                    mode,
                    maxColumns,
                    tabDefs
                } as RecordContentConfig;
            })
        );
    }

    getPanels(): Observable<Panel[]> {
        return this.store.getMetadata$().pipe(
            combineLatestWith(this.store.stagingRecord$, this.language.vm$),
            map(([meta, record, languages]) => {

                const panels = [];
                const module = (record && record.module) || '';

                meta.panels.forEach(panelDefinition => {
                    const label = this.language.getFieldLabel(panelDefinition.key.toUpperCase(), module, languages);
                    const panel = {
                        label,
                        key: panelDefinition.key,
                        display$: panelDefinition?.display$ ?? of(true).pipe(shareReplay(1)),
                        rows: []
                    } as Panel;

                    panelDefinition.rows.forEach(rowDefinition => {
                        const row = {cols: []} as PanelRow;
                        rowDefinition.cols.forEach(cellDefinition => {
                            row.cols.push({...cellDefinition});
                        });
                        panel.rows.push(row);
                    });

                    panels.push(panel);
                });

                return panels;
            })
        );
    }

    getRecord(): Observable<Record> {
        return this.store.stagingRecord$;
    }

    protected getLayout(meta: InstallViewMetadata): string {
        let layout = 'panels';
        if (meta.templateMeta.useTabs) {
            layout = 'tabs';
        }

        return layout;
    }
}
