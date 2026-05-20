/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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
import {LinkActionHandler} from '../link-action.handler';
import {LinkActionData} from '../link-action.model';
import {Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {ModuleNavigation} from '../../../services/navigation/module-navigation/module-navigation.service';
import {ModuleNameMapper} from '../../../services/navigation/module-name-mapper/module-name-mapper.service';

/**
 * Link action handler that navigates to a record in a module determined by field values.
 *
 * Params:
 *   moduleField — attribute name containing the legacy module name (e.g. 'item_module')
 *   recordField — attribute name containing the record ID (e.g. 'item_key')
 */
@Injectable({
    providedIn: 'root'
})
export class RecordLinkAction extends LinkActionHandler {

    key = 'record-link';
    modes = ['detail' as ViewMode, 'list' as ViewMode];

    constructor(
        protected navigation: ModuleNavigation,
        protected moduleNameMapper: ModuleNameMapper
    ) {
        super();
    }

    isRouterLink(field: Field, record: Record, params?: any): boolean {
        const module = this.resolveModule(record, params);
        const recordId = this.resolveRecordId(record, params);

        return !!(module && recordId);
    }

    getLink(field: Field, record: Record, params?: any): string | null {
        const module = this.resolveModule(record, params);
        const recordId = this.resolveRecordId(record, params);

        if (!module || !recordId) {
            return null;
        }

        return this.navigation.getRecordRouterLink(module, recordId);
    }

    run(data: LinkActionData): void {
        // No-op — router link handles navigation
    }

    shouldDisplay(data: LinkActionData): boolean {
        return true;
    }

    protected resolveModule(record: Record, params?: any): string {
        const moduleField = params?.moduleField ?? '';
        if (!moduleField) {
            return '';
        }

        const legacyModule = record?.attributes?.[moduleField] ?? '';
        if (!legacyModule) {
            return '';
        }

        return this.moduleNameMapper.toFrontend(legacyModule);
    }

    protected resolveRecordId(record: Record, params?: any): string {
        const recordField = params?.recordField ?? '';
        if (!recordField) {
            return '';
        }

        return record?.attributes?.[recordField] ?? '';
    }
}
