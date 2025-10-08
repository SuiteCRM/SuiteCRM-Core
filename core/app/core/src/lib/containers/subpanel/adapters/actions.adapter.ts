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
import {Action, ActionContext} from '../../../common/actions/action.model';
import {SearchCriteria} from '../../../common/views/list/search-criteria.model';
import {SubPanelDefinition} from '../../../common/metadata/subpanel.metadata.model';
import {ViewMode} from '../../../common/views/view.model';
import {combineLatestWith, Observable} from 'rxjs';
import {map, take} from 'rxjs/operators';
import {AsyncActionInput, AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {LanguageStore, LanguageStrings} from '../../../store/language/language.store';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {BaseRecordActionsAdapter} from '../../../services/actions/base-record-action.adapter';
import {SelectModalService} from "../../../services/modals/select-modal.service";
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {SubpanelActionManager} from "../components/subpanel/action-manager.service";
import {SubpanelActionData} from "../actions/subpanel.action";
import {SubpanelStore} from "../store/subpanel/subpanel.store";
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {BaseActionsAdapter} from "../../../services/actions/base-action.adapter";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";

@Injectable()
export class SubpanelActionsAdapter extends BaseActionsAdapter<SubpanelActionData> {

    constructor(
        protected store: SubpanelStore,
        protected language: LanguageStore,
        protected actionManager: SubpanelActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
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

    getActions(context?: ActionContext): Observable<Action[]> {
        return this.store.metadata$.pipe(
            combineLatestWith(this.language.vm$, this.store.criteria$),
            map(([meta, language, criteria]: [SubPanelDefinition, LanguageStrings, SearchCriteria]) => {
                if (!meta) {
                    return [];
                }

                return this.parseModeActions(meta.top_buttons, "list" as ViewMode);
            })
        );
    }

    protected buildActionData(action: Action, context?: ActionContext): SubpanelActionData {
        return {
            subpanelMeta: this.store?.metadata,
            module: action.module || this.store.metadata?.module,
            parentModule: this.store.parentModule,
            parentId: this.store.parentId,
            store: this.store,
            action
        } as SubpanelActionData;
    }

    protected getMode(): ViewMode {
        return 'list';
    }

    protected getModuleName(context?: ActionContext): string {
        return this.store?.metadata?.module;
    }

    protected reload(action: Action, process: Process, context?: ActionContext): void {
        this.store.load(false).pipe(take(1)).subscribe();
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
        return {
            action: actionName,
            module: moduleName,
            id: (context && context.record && context.record.id) || '',
            params: (action && action.params) || [],
        } as AsyncActionInput;
    }
}
