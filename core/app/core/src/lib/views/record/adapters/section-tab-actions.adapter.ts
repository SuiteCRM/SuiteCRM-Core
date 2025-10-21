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

import {combineLatestWith, Observable} from 'rxjs';
import {map, take} from 'rxjs/operators';
import {Injectable} from '@angular/core';
import {Action, ActionContext, ActionHandler, ModeActions} from '../../../common/actions/action.model';
import {LogicDefinitions} from '../../../common/metadata/metadata.model';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {Metadata, MetadataStore, RecordViewSectionMetadataMap} from '../../../store/metadata/metadata.store.service';
import {RecordViewStore} from '../store/record-view/record-view.store';
import {AsyncActionInput, AsyncActionService,} from '../../../services/process/processes/async-action/async-action';
import {LanguageStore, LanguageStrings} from '../../../store/language/language.store';
import {MessageService} from '../../../services/message/message.service';
import {Process} from '../../../services/process/process.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {BaseRecordActionsAdapter} from '../../../services/actions/base-record-action.adapter';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {RecordSectionTabActionData} from "../actions/section-tab-actions/section-tab.action";
import {deepClone} from "../../../common/utils/object-utils";
import {
    RecordSectionTabActionDisplayTypeLogic
} from "../actions/section-tab-actions/action-logic/display-type/display-type.logic";
import {RecordSectionTabActionManager} from "../actions/section-tab-actions/section-tab-action-manager.service";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";
import {RecordManager} from "../../../services/record/record.manager";

@Injectable()
export class RecordSectionTabActionsAdapter extends BaseRecordActionsAdapter<RecordSectionTabActionData> {

    defaultActions: ModeActions = {
        detail: [],
        edit: [],
    };

    constructor(
        protected store: RecordViewStore,
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actionManager: RecordSectionTabActionManager,
        protected asyncActionService: AsyncActionService,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected selectModalService: SelectModalService,
        protected fieldModalService: FieldModalService,
        protected displayTypeLogic: RecordSectionTabActionDisplayTypeLogic,
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

        return this.store.metadata$.pipe(
            combineLatestWith(this.store.mode$, this.store.record$, this.store.language$, this.store.widgets$, this.store.section$),
            map(([meta, mode]: [Metadata, ViewMode, Record, LanguageStrings, boolean, string]) => {
                const recordViewMetadata = meta?.recordView;


                const sections = recordViewMetadata?.sections ?? {};
                const sectionKeys = Object.keys(sections);

                if (!recordViewMetadata) {
                    return [];
                }


                if (!sectionKeys.length) {
                    return [];
                }

                const orderedSectionKeys = this.orderSectionKeys(sectionKeys, sections);
                const actions = this.getTabActionsFromSections(orderedSectionKeys, sections);

                return this.parseModeActions(actions, mode, this.store.getViewContext());
            })
        );

    }

    protected buildActionData(action: Action, context?: ActionContext): RecordSectionTabActionData {
        return {
            store: this.store,
            action,
        } as RecordSectionTabActionData;
    }

    /**
     * Build backend process input
     *
     * @param {Action} action Action
     * @param {string} actionName Action Name
     * @param {string} moduleName Module Name
     * @param {ActionContext|null} context Context
     * @returns {AsyncActionInput} Built backend process input
     */
    protected buildActionInput(action: Action, actionName: string, moduleName: string, context: ActionContext = null): AsyncActionInput {
        const baseRecord = this.store.getBaseRecord();

        this.message.removeMessages();

        return {
            action: actionName,
            module: baseRecord.module,
            id: baseRecord.id,
            params: (action && action.params) || []
        } as AsyncActionInput;
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

    protected shouldDisplay(actionHandler: ActionHandler<RecordSectionTabActionData>, data: RecordSectionTabActionData): boolean {

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

    protected getTabActionsFromSections(orderedSectionKeys: string[], sections: RecordViewSectionMetadataMap): Action[] {
        const actions = [];
        orderedSectionKeys.forEach((sectionKey: string) => {
            const section = sections[sectionKey];

            if (!section?.tabAction) {
                return;
            }

            const action = deepClone(section.tabAction)

            if (!action?.key) {
                action.key = 'toggle';
            }

            if (!action?.labelKey) {
                action.labelKey = sectionKey;
            }

            if (!action?.params) {
                action.params = {} as { [key: string]: any };
                action.params.expanded = true;
            }

            action.params.sectionKey = sectionKey;

            if (!action?.modes) {
                action.modes = ['detail', 'edit'];
            }

            actions.push(action);
        });
        return actions;
    }

    protected orderSectionKeys(sectionKeys: string[], sections: RecordViewSectionMetadataMap): string[] {
        return sectionKeys.sort((a, b) => {
            return (sections[a]?.order ?? 0) - (sections[b]?.order ?? 0);
        });
    }
}
