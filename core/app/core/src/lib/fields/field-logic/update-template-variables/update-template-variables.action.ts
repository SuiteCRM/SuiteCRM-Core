/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {Injectable} from "@angular/core";
import {FieldLogicActionData, FieldLogicActionHandler} from "../field-logic.action";
import {ViewMode} from "../../../common/views/view.model";
import {ProcessService} from "../../../services/process/process.service";
import {MessageService} from "../../../services/message/message.service";
import {RecordManager} from "../../../services/record/record.manager";
import {Action} from "../../../common/actions/action.model";
import {AsyncActionInput} from "../../../services/process/processes/async-action/async-action";
import {take} from "rxjs/operators";


@Injectable({
    providedIn: 'root'
})
export class UpdateTemplateVariablesAction extends FieldLogicActionHandler {

    key = 'update-template-variables';
    modes = ['edit', 'create'] as ViewMode[];

    constructor(
        protected processService: ProcessService,
        protected messages: MessageService,
        protected recordManager: RecordManager,
    ) {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const baseRecord = this.recordManager.getBaseRecord(record);

        let showOnlyModules = [];

        if (Array.isArray(action?.params?.showOnlyModules ?? []) && action?.params?.showOnlyModules?.length > 0) {
            showOnlyModules = action.params.showOnlyModules;
        }

        if (baseRecord.attributes['parent_type']) {
            showOnlyModules.push(baseRecord.attributes['parent_type']);
        }

        const options = {
            baseModule: record.module ?? '',
            action: this.key,
            record: baseRecord
        } as AsyncActionInput;

        if (showOnlyModules.length > 0) {
            options['showOnlyModules'] = showOnlyModules;
        }

        field.loading.set(true)

        this.processService.submit(this.key, options).pipe(take(1)).subscribe({
            next: (result) => {

                const modules  = result?.data?.modules ?? [];
                const fieldDefs = result?.data?.fieldDefs ?? {};

                if (!field?.metadata?.squire) {
                    field.metadata.squire = {};
                }

                field.metadata.squire.variables = {};
                field.metadata.squire.variables.modules = modules;
                field.metadata.squire.variables.fieldDefs = fieldDefs;

                field.loading.set(false);
            },
            error: () => {
                field.loading.set(false)
                this.messages.addDangerMessageByKey("LBL_FAILED_TO_UPDATE_TEMPLATE_VARIABLES");
            }
        });
    }
    getTriggeringStatus(): string[] {
        return ['onDependencyChange', 'onFieldInitialize'];
    }
}
