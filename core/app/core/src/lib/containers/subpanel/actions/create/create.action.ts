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
import {Params, Router} from '@angular/router';
import {ModuleNameMapper,} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {ViewMode} from '../../../../common/views/view.model';
import {AttributeMap} from '../../../../common/record/record.model';
import {isVoid} from '../../../../common/utils/value-utils';
import get from 'lodash-es/get';
import {SubpanelActionData, SubpanelActionHandler} from '../subpanel.action';


@Injectable({
    providedIn: 'root'
})
export class SubpanelCreateAction extends SubpanelActionHandler {
    key = 'create';
    modes = ['list' as ViewMode];

    constructor(
        protected moduleNameMapper: ModuleNameMapper,
        protected router: Router
    ) {
        super();
    }

    run(data: SubpanelActionData): void {

        const moduleName = data.module;
        const moduleAction = data?.action?.moduleAction ?? 'edit';

        const parentId = data?.parentId ?? '';
        const parentModule = data.parentModule ?? '';

        const route = `/${moduleName}/${moduleAction}`;

        let returnModule = parentModule;
        let returnId = parentId;

        if ((data.action?.params?.redirect ?? true) === false) {
            returnModule = moduleName;
            returnId = '';
        }

        const queryParams = {
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            return_module: this.moduleNameMapper.toLegacy(returnModule),
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            return_action: 'DetailView',
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            return_id: returnId,
            relate_to: parentModule,
            relate_id: parentId,
        } as Params;

        this.addAdditionalFields(data, queryParams);
        this.addParams(data, queryParams);

        this.router.navigate([route], {
            queryParams
        }).then();
    }

    shouldDisplay(): boolean {
        return true;
    }

    /**
     * Add additional record fields
     *
     * @param {object} data SubpanelActionData
     * @param {object} queryParams Params map
     */
    protected addAdditionalFields(data: SubpanelActionData, queryParams: Params): void {
        const parentAttributes = (data.store.parentRecord && data.store.parentRecord.attributes) || {} as AttributeMap;

        if (!parentAttributes && !Object.keys(parentAttributes).length) {
            return;
        }

        const additionalFields = data.action.additionalFields ?? {} as { [key: string]: string };
        const additionalFieldKeys = Object.keys(additionalFields) || [];

        additionalFieldKeys.forEach(additionalFieldKey => {
            if (!additionalFieldKey || !additionalFields[additionalFieldKey]) {
                return;
            }

            const parentAttribute = additionalFields[additionalFieldKey];
            const attribute = get(parentAttributes, parentAttribute, null);

            if (isVoid(attribute)) {
                return;
            }

            queryParams[additionalFieldKey] = attribute;
        });
    }

    /**
     * Add configuration defined params
     *
     * @param {object} data SubpanelActionData
     * @param {object} queryParams Params map
     */
    protected addParams(data: SubpanelActionData, queryParams: Params): void {

        const params = data.action.extraParams ?? {} as { [key: string]: string };
        const paramKeys = Object.keys(params) || [];

        paramKeys.forEach(paramKey => {
            if (!paramKey || !params[paramKey]) {
                return;
            }

            queryParams[paramKey] = params[paramKey];
        });
    }
}
