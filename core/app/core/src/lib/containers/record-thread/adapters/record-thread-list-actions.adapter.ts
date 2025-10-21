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
import {isTrue} from '../../../common/utils/value-utils';
import {combineLatestWith, Observable, of} from 'rxjs';
import {map} from 'rxjs/operators';
import {Action, ActionContext, ModeActions} from '../../../common/actions/action.model';
import {ViewMode} from '../../../common/views/view.model';
import {AsyncActionInput, AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {LanguageStore} from '../../../store/language/language.store';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {BaseRecordActionsAdapter} from '../../../services/actions/base-record-action.adapter';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {RecordThreadStore} from '../store/record-thread/record-thread.store';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {RecordThreadListActionData} from "../actions/list-actions/record-thread-list.action";
import {RecordThreadListActionManager} from "../actions/list-actions/record-thread-list-action-manager.service";
import {RecordThreadListMetadata} from '../store/record-thread/record-thread-list.store.model';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";
import {RecordManager} from "../../../services/record/record.manager";

@Injectable()
export class RecordThreadListActionsAdapter extends BaseRecordActionsAdapter<RecordThreadListActionData> {

    defaultActions: ModeActions = {
        detail: [],
        edit: [],
        create: [],
        list: []
    };
    collapseButtons = false;

    constructor(
        protected threadStore: RecordThreadStore,
        protected language: LanguageStore,
        protected actionManager: RecordThreadListActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected selectModalService: SelectModalService,
        protected fieldModalService: FieldModalService,
        protected metadata: MetadataStore,
        protected appMetadataStore: AppMetadataStore,
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
        return of(this.threadStore.getListMetadata()).pipe(
            combineLatestWith(of('list' as ViewMode)),
            map(([meta, mode]: [RecordThreadListMetadata, ViewMode]) => {

                if (!mode || !meta) {
                    return [];
                }
                return this.parseModeActions(meta.actions, mode, this.threadStore.getViewContext());
            }),
        );
    }

    /**
     * Get action name
     * @param action
     */
    protected getActionName(action: Action) {
        return `record-thread-list-${action.key}`;
    }

    protected buildActionData(action: Action, context?: ActionContext): RecordThreadListActionData {
        return {
            threadStore: this.threadStore,
            action: action
        } as RecordThreadListActionData;
    }

    /**
     * Build backend process input
     *
     * @param action
     * @param actionName
     * @param moduleName
     * @param context
     */
    protected buildActionInput(action: Action, actionName: string, moduleName: string, context: ActionContext = null): AsyncActionInput {

        this.message.removeMessages();

        return {
            action: actionName,
            module: this.threadStore.module,
            ids: this.threadStore.getRecordIds(),
            params: (action && action.params) || []
        } as AsyncActionInput;
    }

    protected getMode(): ViewMode {
        return 'list';
    }

    protected getModuleName(context?: ActionContext): string {
        return this.threadStore.module;
    }

    protected reload(action: Action, process: Process, context?: ActionContext): void {

        const reloadThread = process?.data?.reloadThread ?? false;

        if (isTrue(reloadThread)) {
            this.threadStore.reload();
        }
    }

    /**
     * @inheritDoc
     */
    protected shouldReload(process: Process): boolean {
        const reloadThread = process?.data?.reloadThread ?? false;
        return isTrue(reloadThread);
    }
}
