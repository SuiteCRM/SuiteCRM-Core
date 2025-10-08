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
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {Action} from '../../../common/actions/action.model';
import {Field} from '../../../common/record/field.model';
import {ViewMode} from '../../../common/views/view.model';
import {isTrue} from '../../../common/utils/value-utils';

@Injectable({
    providedIn: 'root'
})
export class EmailPrimarySelectAction extends FieldLogicActionHandler {

    key = 'emailPrimarySelect';
    modes = ['edit', 'create', 'massupdate'] as ViewMode[];

    constructor() {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const items = field.items;

        if (!field || !items || !items.length) {
            return;
        }

        const activeItems = items.filter(item => !(item && item.attributes && item.attributes.deleted));

        // Auto-select the primary, only when the number of displayed rows equal to one;
        // This logic applies either via Add or Remove
        if (activeItems && activeItems.length !== 1) {
            return;
        }

        const item = activeItems[0];
        const emailField = (item.fields && item.fields['email-fields']) || {} as Field;
        const primary = (emailField.attributes && emailField.attributes['primary_address']) || null;

        if (primary && !isTrue(primary.value)) {
            primary.value = 'true';
            primary.formControl.setValue('true');
            // re-validate the parent form-control after value update
            emailField.formControl.updateValueAndValidity({onlySelf: true, emitEvent: true});
        }
    }

    getTriggeringStatus(): string[] {
        return ['onFieldInitialize'];
    }

}
