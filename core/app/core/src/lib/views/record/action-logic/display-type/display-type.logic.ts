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

import {LogicDefinitions} from '../../../../common/metadata/metadata.model';
import {Action, ActionData} from '../../../../common/actions/action.model';
import {StringArrayMap} from '../../../../common/types/string-map';
import {ViewMode} from '../../../../common/views/view.model';
import {Injectable} from '@angular/core';
import {RecordActionData} from '../../actions/record.action';
import {ActionLogicHandler} from '../../../../services/actions/action-logic-handler';
import {ActiveFieldsChecker} from "../../../../services/condition-operators/active-fields-checker.service";
import {ObjectArrayMatrix} from "../../../../common/types/object-map";

@Injectable({
    providedIn: 'root'
})
export class RecordActionDisplayTypeLogic extends ActionLogicHandler<RecordActionData> {

    key = 'displayType';
    modes = ['edit', 'detail', 'list', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor(protected activeFieldsChecker: ActiveFieldsChecker) {
        super();
    }

    runAll(displayLogic: LogicDefinitions, data: ActionData): boolean {
        let toDisplay = true;

        const validModeLogic = Object.values(displayLogic).filter(logic => {
            const allowedModes = logic.modes ?? [];
            return !!(allowedModes.length && allowedModes.includes(data.store.getMode()));
        });

        if (!validModeLogic || !validModeLogic.length) {
            return toDisplay;
        }

        let defaultDisplay = data?.action?.display ?? 'show';
        let targetDisplay = 'hide';
        if (defaultDisplay === 'hide') {
            targetDisplay = 'show';
        }

        const isActive = validModeLogic.some(logic => this.run(data, logic as Action));

        if (isActive) {
            defaultDisplay = targetDisplay;
        }

        toDisplay = (defaultDisplay === 'show');

        return toDisplay;
    }

    run(data: ActionData, logic: Action): boolean {

        const record = data.store.recordStore.getStaging();
        if (!record || !logic) {
            return true;
        }

        const activeOnFields: StringArrayMap = (logic.params && logic.params.activeOnFields) || {} as StringArrayMap;
        const relatedFields: string[] = Object.keys(activeOnFields);

        const activeOnAttributes: ObjectArrayMatrix = (logic.params && logic.params.activeOnAttributes) || {} as ObjectArrayMatrix;
        const relatedAttributesFields: string[] = Object.keys(activeOnAttributes);

        if (!relatedFields.length && !relatedAttributesFields.length) {
            return true;
        }

        return this.activeFieldsChecker.isActive(relatedFields, record, activeOnFields, relatedAttributesFields, activeOnAttributes);
    }
}
