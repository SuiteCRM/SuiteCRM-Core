/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {Injectable, signal} from '@angular/core';
import {Observable, of} from 'rxjs';
import {RecordThreadModalActionData} from "../actions/header-actions/record-thread-modal-header.action";
import {RecordThreadModalStore} from "../../../store/record-thread-modal/record-thread-modal.store";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {MetadataStore} from "../../../store/metadata/metadata.store.service";
import {LanguageStore} from "../../../store/language/language.store";
import {
    RecordThreadModalHeaderActionManager
} from "../actions/header-actions/record-thread-modal-header-action-manager.service";
import {AsyncActionInput, AsyncActionService} from "../../../services/process/processes/async-action/async-action";
import {MessageService} from "../../../services/message/message.service";
import {ConfirmationModalService} from "../../../services/modals/confirmation-modal.service";
import {SelectModalService} from "../../../services/modals/select-modal.service";
import {RecordActionDisplayTypeLogic} from "../../../views/record/action-logic/display-type/display-type.logic";
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";
import {RecordManager} from "../../../services/record/record.manager";
import {Action, ActionContext, ActionHandler, ModeActions} from "../../../common/actions/action.model";
import {ViewMode} from "../../../common/views/view.model";
import {AfterActionLogicDefinitions, LogicDefinitions} from "../../../common/metadata/metadata.model";
import {Process} from "../../../services/process/process.service";
import {isTrue} from "../../../common/utils/value-utils";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {BaseRecordThreadModalActionAdapter} from "../../../services/actions/base-record-thread-modal-action.adapter";

@Injectable()
export class RecordThreadModalHeaderActionsAdapter extends BaseRecordThreadModalActionAdapter<RecordThreadModalActionData> {

    constructor(
        protected store: RecordThreadModalStore,
        protected activeModal: NgbActiveModal,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actionManager: RecordThreadModalHeaderActionManager,
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
        return of(this.parseModeActions(this.store?.config?.modalConfig?.recordModalOptions?.headerActions?.actions, this.getMode(), this.store.getRecordThreadStore().getViewContext()))
    }

    protected buildActionData(action: Action, context?: ActionContext): RecordThreadModalActionData {
        return {
            store: this.store,
            action,
        } as RecordThreadModalActionData;
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldReload(process: Process): boolean {
        return !!(process.data && process.data.reloadThread);
    }

    protected getMode(): ViewMode {
        return 'detail';
    }

    protected getModuleName(context?: ActionContext): string {
        return '';
    }

    protected reload(action: Action, process: Process, context?: ActionContext): void {

        const reloadThread = process?.data?.reloadThread ?? false;

        if (isTrue(reloadThread)) {
            this.store.reloadRecordThread();
        }
    }

    protected shouldDisplay(actionHandler: ActionHandler<RecordThreadModalActionData>, data: RecordThreadModalActionData): boolean {

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
        actionData: RecordThreadModalActionData,
        context: ActionContext,
        afterActionLogic: AfterActionLogicDefinitions = null
    ): void {
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
     * Parse mode actions
     * @param declaredActions
     * @param mode
     * @param context
     */
    protected parseModeActions(declaredActions: Action[], mode: ViewMode, context: ActionContext = null): any[] {
        if (!declaredActions) {
            return [];
        }

        const availableActions = {
            list: [],
            detail: [],
            edit: [],
            create: [],
            massupdate: [],
        } as ModeActions;

        if (declaredActions && declaredActions.length) {
            declaredActions.forEach(action => {
                if (!action.modes || !action.modes.length) {
                    return;
                }

                action.modes.forEach(actionMode => {
                    if (!availableActions[actionMode] && !action.asyncProcess) {
                        return;
                    }
                    availableActions[actionMode].push(action);
                });
            });
        }

        availableActions.detail = availableActions.detail.concat(this.defaultActions.detail ?? []);
        availableActions.list = availableActions.list.concat(this.defaultActions.list ?? []);
        availableActions.edit = availableActions.edit.concat(this.defaultActions.edit ?? []);
        availableActions.create = availableActions.create.concat(this.defaultActions.create ?? []);
        availableActions.massupdate = availableActions.massupdate.concat(this.defaultActions.massupdate ?? []);

        const actions = [];
        availableActions[mode].forEach(action => {

            const actionHandler = this.actionManager.getHandler(action, mode);

            if (actionHandler) {
                const data: RecordThreadModalActionData = this.buildActionData(action, context);

                if (!this.shouldDisplay(actionHandler, data)) {
                    return;
                }

                action.status = actionHandler.getStatus(data) || '';
            }

            if (!actionHandler && !action?.asyncProcess) {
                return;
            }

            const module = (context && context.module) || '';
            const label = this.language.getFieldLabel(action.labelKey, module);
            const newAction = {...action, label};
            if (isTrue(action?.params?.closeModal ?? false)) {
                newAction.preActionOnClick = () => {
                    this.activeModal.close();
                }
            }
            newAction.isRunning = signal(false);
            actions.push(newAction);
        });

        return actions;
    }

    /**
     * Should reload page
     * @param process
     */
    protected shouldCloseModal(process: Process): boolean {
        return !!(process.data && process.data.closeModal);
    }
}
