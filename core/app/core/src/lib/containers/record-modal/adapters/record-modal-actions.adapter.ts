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

import {Injectable} from '@angular/core';
import {combineLatestWith, Observable} from 'rxjs';
import {map, take} from 'rxjs/operators';
import {MetadataStore, RecordViewMetadata} from '../../../store/metadata/metadata.store.service';
import {AsyncActionInput, AsyncActionService,} from '../../../services/process/processes/async-action/async-action';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {LanguageStore} from '../../../store/language/language.store';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {BaseRecordActionsAdapter} from '../../../services/actions/base-record-action.adapter';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {RecordModalStore} from "../store/record-modal/record-modal.store";
import {RecordActionDisplayTypeLogic} from "../../../views/record/action-logic/display-type/display-type.logic";
import {RecordModalActionManager} from "../actions/record-modal-action-manager.service";
import {RecordModalActionData} from "../actions/record-modal.action";
import {Action, ActionContext, ActionHandler} from "../../../common/actions/action.model";
import {ViewMode} from "../../../common/views/view.model";
import {
    LogicDefinitions,
    Panel,
    AfterActionLogicDefinitions
} from "../../../common/metadata/metadata.model";
import {Record} from "../../../common/record/record.model";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";
import {RecordManager} from "../../../services/record/record.manager";

@Injectable()
export class RecordModalActionsAdapter extends BaseRecordActionsAdapter<RecordModalActionData> {

    constructor(
        protected store: RecordModalStore,
        protected activeModal: NgbActiveModal,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actionManager: RecordModalActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected selectModalService: SelectModalService,
        protected displayTypeLogic: RecordActionDisplayTypeLogic,
        protected appMetadataStore: AppMetadataStore,
        protected fieldModalService: FieldModalService,
        protected recordMappers: RecordMapperRegistry,
        protected logic: FieldLogicManager,
        protected recordManager: RecordManager
    ) {
        super(
            actionManager,
            asyncActionService,
            message,
            confirmation,
            language,
            selectModalService,
            fieldModalService,
            metadata,
            appMetadataStore,
            recordMappers,
            logic,
            recordManager
        );
    }

    getActions(context?: ActionContext): Observable<Action[]> {
        return this.store.recordViewMetadata$.pipe(
            combineLatestWith(this.store.mode$, this.store.record$, this.store.panels$),
            map(([meta, mode]: [RecordViewMetadata, ViewMode, Record, Panel[]]) => {
                if (!mode || !meta) {
                    return [];
                }
                return this.parseModeActions(meta.actions, mode, this.store.getViewContext());
            })
        );
    }

    protected buildActionData(action: Action, context?: ActionContext): RecordModalActionData {
        return {
            store: this.store,
            action,
        } as RecordModalActionData;
    }

    protected getMode(): ViewMode {
        return this.store.getMode();
    }

    protected getModuleName(context?: ActionContext): string {
        return this.store.getModuleName();
    }

    protected reload(action: Action, process: Process, context?: ActionContext): void {
        this.store.load(false).pipe(take(1)).subscribe();
    }

    protected shouldDisplay(actionHandler: ActionHandler<RecordModalActionData>, data: RecordModalActionData): boolean {

        const displayLogic: LogicDefinitions | null = data?.action?.displayLogic ?? null;
        let toDisplay = true;

        if (displayLogic && Object.keys(displayLogic).length) {
            toDisplay = this.displayTypeLogic.runAll(displayLogic, data);
        }

        if (!toDisplay) {
            return false;
        }

        return actionHandler && actionHandler.shouldDisplay(data);
    }

    /**
     * Run after async action handlers
     * @param actionName
     * @param moduleName
     * @param asyncData
     * @param process
     * @param action
     * @param actionData
     * @param context
     * @param afterActionLogic
     * @protected
     */
    protected afterAsyncAction(
        actionName: string,
        moduleName: string,
        asyncData: AsyncActionInput,
        process: Process,
        action: Action,
        actionData: RecordModalActionData,
        context: ActionContext,
        afterActionLogic: AfterActionLogicDefinitions = null
    ) {
        super.afterAsyncAction(
            actionName,
            moduleName,
            asyncData,
            process,
            action,
            actionData,
            context,
            afterActionLogic
        );
        if (this.shouldCloseModal(process)) {
            this.activeModal.close();
        }
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldCloseModal(process: Process): boolean {
        return !!(process.data && process.data.closeModal);
    }
}
