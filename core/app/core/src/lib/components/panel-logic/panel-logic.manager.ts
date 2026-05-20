/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {BaseActionManager} from '../../services/actions/base-action-manager.service';
import {PanelLogicActionData} from './panel-logic.action';
import {Action, ActionContext} from '../../common/actions/action.model';
import {Field} from '../../common/record/field.model';
import {Panel} from '../../common/metadata/metadata.model';
import {Record} from '../../common/record/record.model';
import {ViewMode} from '../../common/views/view.model';
import {PanelLogicDisplayTypeAction} from './display-type/panel-logic-display-type-action.service';

@Injectable({
    providedIn: 'root'
})
export class PanelLogicManager extends BaseActionManager<PanelLogicActionData> {

    constructor(
        displayType: PanelLogicDisplayTypeAction,
    ) {
        super();
        displayType.modes.forEach(mode => this.actions[mode][displayType.key] = displayType);
    }

    /**
     * Run logic for the given field
     * @param {string} logicType
     * @param {object} field
     * @param {object} panel
     * @param {object} record
     * @param {object} mode
     */
    runLogic(logicType: string, field: Field, panel: Panel, record: Record, mode: ViewMode) {
        let toDisplay = true;

        const validModeLogic = Object.values(panel.meta.displayLogic).filter(logic => {
            const allowedModes = logic['modes'] ?? [];
            return !!(allowedModes.length && allowedModes.includes(mode));
        });

        if (!validModeLogic || !validModeLogic.length) {
            return toDisplay;
        }

        let defaultDisplay = panel.meta.display ?? 'show';
        let targetDisplay = 'hide';
        if (defaultDisplay === 'hide') {
            targetDisplay = 'show';
        }

        const context = {
            panel,
            record,
            field,
            module: record.module
        } as ActionContext;

        const isActive = validModeLogic.some(logic => {
            const data: PanelLogicActionData = this.buildActionData(logic, context);
            return this.actions[mode][logic.key].run(data, logic);
        });

        if (isActive) {
            defaultDisplay  = targetDisplay;
        }

        toDisplay = (defaultDisplay === 'show');

        panel.displayState.next(toDisplay);
    }

    protected buildActionData(action: Action, context?: ActionContext): PanelLogicActionData {
        return {
            field: context.field,
            record: (context && context.record) || null,
            panel: context.panel || null,
        } as PanelLogicActionData;
    }

}
