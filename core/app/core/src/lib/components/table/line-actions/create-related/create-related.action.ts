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
import {Router} from '@angular/router';
import {Action} from '../../../../common/actions/action.model';
import {ViewMode} from '../../../../common/views/view.model';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {LineActionActionHandler, LineActionData} from '../line.action';

@Injectable({
    providedIn: 'root'
})
export class CreateRelatedLineAction extends LineActionActionHandler {
    key = 'create';
    modes = ['list' as ViewMode];

    constructor(protected moduleNameMapper: ModuleNameMapper, protected router: Router) {
        super();
    }

    run(data: LineActionData, action: Action = null): void {

        const configs = action.params['create'] || {} as any;

        const params: { [key: string]: any } = {};
        /* eslint-disable camelcase,@typescript-eslint/camelcase*/
        params.return_module = configs.legacyModuleName;
        params.return_action = configs.returnAction;
        params.return_id = data.record.id;
        /* eslint-enable camelcase,@typescript-eslint/camelcase */
        params[configs.mapping.moduleName] = configs.legacyModuleName;

        params[configs.mapping.name] = data.record.attributes.name;
        params[configs.mapping.id] = data.record.id;

        const route = '/' + configs.module + '/' + configs.action;

        this.router.navigate([route], {
            queryParams: params
        }).then();
    }

    shouldDisplay(data: LineActionData): boolean {
        return true;
    }
}
