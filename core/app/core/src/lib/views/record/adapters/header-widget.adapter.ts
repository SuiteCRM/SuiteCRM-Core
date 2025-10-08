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

import {Injectable} from '@angular/core';
import {combineLatestWith} from 'rxjs';
import {map} from 'rxjs/operators';
import {MetadataStore, RecordViewSectionMetadata} from '../../../store/metadata/metadata.store.service';
import {RecordViewStore} from '../store/record-view/record-view.store';
import {ObjectMap} from "../../../common/types/object-map";
import {ActiveFieldsChecker} from "../../../services/condition-operators/active-fields-checker.service";
import {Record} from "../../../common/record/record.model";

@Injectable()
export class HeaderWidgetAdapter {

    config$ = this.store.sectionMetadata$.pipe(
        combineLatestWith(this.store.showHeaderWidgets$, this.store.stagingRecord$),
        map(([metadata, show, record]: [RecordViewSectionMetadata, boolean, Record]) => {

            let filteredWidgets = [];

            if (metadata.headerWidgets && metadata.headerWidgets.length) {

                filteredWidgets = this.store.filterWidgetsByMode(metadata.headerWidgets);

                filteredWidgets = filteredWidgets.filter(widget => {
                    if (widget.activeOnFields && Object.keys(widget.activeOnFields).length) {
                        return this.isActive(widget.activeOnFields, record);
                    }
                    return true; // If no activeOnFields, consider it active
                });

                filteredWidgets.forEach(widget => {
                    if (widget && widget.refreshOn === 'data-update') {
                        widget.reload$ = this.store.record$.pipe(map(() => true));
                    }

                    if (widget) {
                        widget.subpanelReload$ = this.store.subpanelReload$;
                    }
                });

                if (!filteredWidgets.length) {
                    show = false;
                }
            }

            filteredWidgets = filteredWidgets || [];

            return {
                widgets: filteredWidgets,
                show: show && filteredWidgets.length > 0
            };
        })
    );

    constructor(
        protected store: RecordViewStore,
        protected metadata: MetadataStore,
        protected activeFieldsChecker: ActiveFieldsChecker
    ) {
    }

    protected isActive(activeOnFields: ObjectMap, record: Record): boolean {

        const fieldKeys = Object.keys(activeOnFields);

        if (!activeOnFields || !fieldKeys.length) {
            return true;
        }

        if (!record || !record?.fields || !Object.keys(record?.fields ?? {}).length) {
            return false;
        }

        return fieldKeys.every(fieldKey => {

            const field = record.fields[fieldKey];

            if (!field) {
                return true; // If field is not present, consider it active
            }

            const activeOn = activeOnFields[fieldKey] || null;


            return this.activeFieldsChecker.isValueActive(record, field, activeOn);

        });
    }

}
