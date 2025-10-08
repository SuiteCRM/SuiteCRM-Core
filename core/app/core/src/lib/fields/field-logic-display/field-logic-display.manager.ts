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
import {FieldLogicDisplayActionData} from './field-logic-display.action';
import {Action, ActionContext} from '../../common/actions/action.model';
import {DisplayType, Field} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {ViewMode} from '../../common/views/view.model';
import {DisplayTypeAction} from './display-type/display-type.action';

@Injectable({
    providedIn: 'root'
})
export class FieldLogicDisplayManager extends BaseActionManager<FieldLogicDisplayActionData> {

    constructor(
        displayType: DisplayTypeAction,
    ) {
        super();
        displayType.modes.forEach(mode => this.actions[mode][displayType.key] = displayType);
    }

    runAll(field: Field, record: Record, mode: ViewMode): void {
        let toDisplay: DisplayType = 'show';

        if(!field.displayLogic) {
            return;
        }

        const validModeLogic = Object.values(field.displayLogic).filter(logic => {
            const allowedModes = logic['modes'] ?? [];
            return !!(allowedModes.length && allowedModes.includes(mode));
        });

        if (!validModeLogic || !validModeLogic.length) {
            field.display.set(toDisplay);
            return;
        }

        let defaultDisplay = field.defaultDisplay ?? 'show';

        let targetDisplay: DisplayType = 'none';

        if (defaultDisplay === 'none') {
            targetDisplay = 'show';
        }

        const context = {
            record,
            field,
            module: record.module
        } as ActionContext;


        const isActive = validModeLogic.some(logic => {
            const data: FieldLogicDisplayActionData = this.buildActionData(logic, context);
            return this.actions[mode][logic.key].run(data, logic);
        });

        if (isActive) {
            defaultDisplay = targetDisplay;

        }

        toDisplay = defaultDisplay as DisplayType;

        if (defaultDisplay === 'show') {
            toDisplay = 'show';
        }

        field.display.set(toDisplay);

    }

    protected buildActionData(action: Action, context?: ActionContext): FieldLogicDisplayActionData {
        return {
            field: context.field,
            record: (context && context.record) || null,
        } as FieldLogicDisplayActionData;
    }

}
