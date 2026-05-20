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
import {RecordActionData} from './record.action';
import {RecordCancelAction} from './cancel/record-cancel.action';
import {RecordSaveAction} from './save/record-save.action';
import {RecordToggleWidgetsAction} from './toggle-widgets/record-widget-action.service';
import {RecordEditAction} from './edit/record-edit.action';
import {RecordCreateAction} from './create/record-create.action';
import {RecordSaveNewAction} from './save-new/record-save-new.action';
import {CancelCreateAction} from './cancel-create/cancel-create.action';
import {BaseActionManager} from '../../../services/actions/base-action-manager.service';
import {AsyncProcessRecordAction} from './async-process/async-process.service';
import {RecordSaveContinueAction} from "./save-continue/record-save-continue.action";
import {RecordSaveScheduleAction} from "./save-schedule/record-save-schedule.action";
import {ModalEditAction} from "./modal-edit/modal-edit.action";
import {ModalCreateAction} from "./modal-create/modal-create.action";

@Injectable({
    providedIn: 'root',
})
export class RecordActionManager extends BaseActionManager<RecordActionData> {

    constructor(
        protected edit: RecordEditAction,
        protected create: RecordCreateAction,
        protected toggleWidgets: RecordToggleWidgetsAction,
        protected cancel: RecordCancelAction,
        protected cancelCreate: CancelCreateAction,
        protected save: RecordSaveAction,
        protected saveNew: RecordSaveNewAction,
        protected saveContinue: RecordSaveContinueAction,
        protected saveSchedule: RecordSaveScheduleAction,
        protected async: AsyncProcessRecordAction,
        protected recordModalEdit: ModalEditAction,
        protected recordModalCreate: ModalCreateAction,
    ) {
        super();
        edit.modes.forEach(mode => this.actions[mode][edit.key] = edit);
        create.modes.forEach(mode => this.actions[mode][create.key] = create);
        toggleWidgets.modes.forEach(mode => this.actions[mode][toggleWidgets.key] = toggleWidgets);
        cancel.modes.forEach(mode => this.actions[mode][cancel.key] = cancel);
        save.modes.forEach(mode => this.actions[mode][save.key] = save);
        saveNew.modes.forEach(mode => this.actions[mode][saveNew.key] = saveNew);
        saveSchedule.modes.forEach(mode => this.actions[mode][saveSchedule.key] = saveSchedule);
        saveContinue.modes.forEach(mode => this.actions[mode][saveContinue.key] = saveContinue);
        cancelCreate.modes.forEach(mode => this.actions[mode][cancelCreate.key] = cancelCreate);
        async.modes.forEach(mode => this.actions[mode][async.key] = async);
        recordModalEdit.modes.forEach(mode => this.actions[mode][recordModalEdit.key] = recordModalEdit);
        recordModalCreate.modes.forEach(mode => this.actions[mode][recordModalCreate.key] = recordModalCreate);
    }
}
