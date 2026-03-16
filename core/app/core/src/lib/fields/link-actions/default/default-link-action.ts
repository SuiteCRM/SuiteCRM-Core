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
import {DynamicLabelService} from '../../../services/language/dynamic-label.service';

@Injectable({
    providedIn: 'root'
})
export class DefaultLinkAction extends LinkActionHandler {

    key = 'default';
    modes = ['detail' as ViewMode, 'list' as ViewMode];

    constructor(
        protected navigation: ModuleNavigation,
        protected dynamicLabelService: DynamicLabelService
    ) {
        super();
    }

    isRouterLink(field: Field, record: Record, params?: any): boolean {
        return true;
    }

    getLink(field: Field, record: Record, params?: any): string | null {
        const fieldMetadata = field?.metadata ?? null;
        const linkRoute = fieldMetadata?.linkRoute ?? null;
        if (fieldMetadata && linkRoute) {
            return this.dynamicLabelService.parse(linkRoute, {}, record.fields, record.attributes);
        }

        return this.navigation.getRecordRouterLink(record.module, record.id);
    }

    run(data: LinkActionData): void {
        // No-op — router link handles navigation
    }

    shouldDisplay(data: LinkActionData): boolean {
        return true;
    }
}
