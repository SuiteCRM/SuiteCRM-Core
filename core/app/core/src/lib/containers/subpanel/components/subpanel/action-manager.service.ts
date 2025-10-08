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
import {SubpanelCreateAction} from '../../actions/create/create.action';
import {SubpanelActionData} from '../../actions/subpanel.action';
import {SubpanelSelectAction} from "../../actions/select/select.action";
import {AsyncProcessSubpanelAction} from '../../actions/async-process/async-process.action';
import {SubpanelShowFilterAction} from "../../actions/show-filter/show-filter.action";
import {BaseActionManager} from "../../../../services/actions/base-action-manager.service";
import {SubpanelClearFilterAction} from "../../actions/clear-filter/clear-filter.action";
import {SubpanelModalCreateAction} from "../../actions/modal-create/modal-create.action";

@Injectable({
    providedIn: 'root',
})
export class SubpanelActionManager extends BaseActionManager<SubpanelActionData> {

    constructor(
        protected create: SubpanelCreateAction,
        protected select: SubpanelSelectAction,
        protected async: AsyncProcessSubpanelAction,
        protected showFilter: SubpanelShowFilterAction,
        protected clearFilter: SubpanelClearFilterAction,
        protected modalCreate: SubpanelModalCreateAction
    ) {
        super();
        async.modes.forEach(mode => this.actions[mode][async.key] = async);
        create.modes.forEach(mode => this.actions[mode][create.key] = create);
        select.modes.forEach(mode => this.actions[mode][select.key] = select);
        showFilter.modes.forEach(mode => this.actions[mode][showFilter.key] = showFilter);
        clearFilter.modes.forEach(mode => this.actions[mode][clearFilter.key] = clearFilter);
        clearFilter.modes.forEach(mode => this.actions[mode][clearFilter.key] = clearFilter);
        modalCreate.modes.forEach(mode => this.actions[mode][modalCreate.key] = modalCreate);
    }
}
