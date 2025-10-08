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
import {Action, ActionData, ActionHandler, ActionHandlerMap, ActionManager} from '../../common/actions/action.model';
import {ViewMode} from '../../common/views/view.model';

@Injectable({
    providedIn: 'root',
})
export class BaseActionManager<D extends ActionData> implements ActionManager<D> {

    actions: { [key: string]: ActionHandlerMap<D> } = {
        edit: {} as ActionHandlerMap<D>,
        create: {} as ActionHandlerMap<D>,
        list: {} as ActionHandlerMap<D>,
        detail: {} as ActionHandlerMap<D>,
        massupdate: {} as ActionHandlerMap<D>,
        filter: {} as ActionHandlerMap<D>
    };

    run(action: Action, mode: ViewMode, data: D): void {
        if (!this.actions || !this.actions[mode] || !this.actions[mode][action.key]) {
            return;
        }

        this.actions[mode][action.key].run(data, action);
    }

    getHandler(action: Action, mode: ViewMode): ActionHandler<D> {
        let handlerKey = action.key;

        if (action && action.asyncProcess) {
            handlerKey = 'async-process';
        }

        if (!this.actions || !this.actions[mode] || !this.actions[mode][handlerKey]) {
            return null;
        }

        return this.actions[mode][handlerKey];
    }

    addHandler(action: Action, mode: ViewMode, handler: ActionHandler<D>) {

        if (!this.actions[mode]) {
            this.actions[mode] = {} as ActionHandlerMap<D>;

        }

        this.actions[mode][action.key] = handler;
    }
}
