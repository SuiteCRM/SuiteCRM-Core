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
import {Action, ActionContext} from '../../../common/actions/action.model';
import {ViewMode} from '../../../common/views/view.model';
import {combineLatestWith, Observable, of} from 'rxjs';
import {map, shareReplay, take} from 'rxjs/operators';
import {AsyncActionInput, AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../store/language/language.store';
import {SubpanelLineActionData} from '../line-actions/line.action';
import {SubpanelStore} from '../store/subpanel/subpanel.store';
import {SubpanelLineActionManager} from '../line-actions/line-action-manager.service';
import {SelectModalService} from "../../../services/modals/select-modal.service";
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {BaseActionsAdapter} from "../../../services/actions/base-action.adapter";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";

@Injectable({
    providedIn: 'root',
})
export class SubpanelLineActionsAdapter extends BaseActionsAdapter<SubpanelLineActionData> {

    constructor(
        protected store: SubpanelStore,
        protected actionManager: SubpanelLineActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected language: LanguageStore,
        protected selectModalService: SelectModalService,
        protected fieldModalService: FieldModalService,
        protected metadata: MetadataStore,
        protected appMetadataStore: AppMetadataStore,
        protected logic: FieldLogicManager,
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
            logic
        )
    }

    /**
     * Get action name
     * @param action
     */
    protected getActionName(action: Action) {
        return `record-${action.key}`;
    }

    getActions(context: ActionContext = null): Observable<Action[]> {

        return this.store.metadata$.pipe(map(metadata => metadata.lineActions)).pipe(
            combineLatestWith(of('list' as ViewMode).pipe(shareReplay())),
            map(([actions, mode]: [Action[], ViewMode]) => {
                return this.parseModeActions(actions, mode, context);
            })
        );
    }


    protected buildActionData(action: Action, context?: ActionContext): SubpanelLineActionData {
        return {
            record: (context && context.record) || null,
            store: this.store,
            action: action
        } as SubpanelLineActionData;
    }

    protected getMode(): ViewMode {
        return 'list' as ViewMode;
    }

    protected getModuleName(context?: ActionContext): string {
        return this.store.metadata.module;
    }

    protected reload(action: Action, process: Process, context?: ActionContext): void {
        this.store.load(false).pipe(take(1)).subscribe();
        this.store.loadAllStatistics(false).pipe(take(1)).subscribe();
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

        const metadata = this.store.metadata;
        const collectionList = metadata.collection_list || null;

        const module = (context && context.module) || moduleName;

        let linkField: string = metadata.get_subpanel_data;

        if (collectionList && collectionList[module] && collectionList[module].get_subpanel_data) {
            linkField = collectionList[module].get_subpanel_data;
        }

        if (linkField && action && action.params && action.params.linkFieldMapping) {
            Object.keys(action.params.linkFieldMapping).some(key => {
                if (linkField.includes(key)) {
                    linkField = action.params.linkFieldMapping[key];
                    return true;
                }
            })
        }

        return {
            action: actionName,
            module: moduleName,
            id: (context && context.record && context.record.id) || '',
            payload: {
                baseModule: this.store.parentModule,
                baseRecordId: this.store.parentId,
                linkField,
                recordModule: module,
                relateModule: this.store.metadata.module,
                relateRecordId: (context && context.record && context.record.id) || '',
            }
        } as AsyncActionInput;
    }
}
