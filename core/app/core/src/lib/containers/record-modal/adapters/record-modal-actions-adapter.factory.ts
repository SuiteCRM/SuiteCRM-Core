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
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {MetadataStore} from "../../../store/metadata/metadata.store.service";
import {LanguageStore} from "../../../store/language/language.store";
import {RecordModalStore} from "../store/record-modal/record-modal.store";
import {AsyncActionService} from "../../../services/process/processes/async-action/async-action";
import {ConfirmationModalService} from "../../../services/modals/confirmation-modal.service";
import {SelectModalService} from "../../../services/modals/select-modal.service";
import {MessageService} from "../../../services/message/message.service";
import {RecordActionDisplayTypeLogic} from "../../../views/record/action-logic/display-type/display-type.logic";
import {RecordModalActionManager} from "../actions/record-modal-action-manager.service";
import {RecordModalActionsAdapter} from "./record-modal-actions.adapter";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {RecordMapperRegistry} from "../../../common/record/record-mappers/record-mapper.registry";
import {BaseSaveRecordMapper} from "../../../store/record/record-mappers/base-save.record-mapper";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";
import {RecordManager} from "../../../services/record/record.manager";

@Injectable({
    providedIn: 'root',
})
export class RecordModalActionsAdapterFactory {

    constructor(
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
        protected baseMapper: BaseSaveRecordMapper,
        protected logic: FieldLogicManager,
        protected recordManager: RecordManager
    ) {
        recordMappers.register('default', baseMapper.getKey(), baseMapper);
    }

    create(store: RecordModalStore, activeModal: NgbActiveModal): RecordModalActionsAdapter {
        return new RecordModalActionsAdapter(
            store,
            activeModal,
            this.metadata,
            this.language,
            this.actionManager,
            this.asyncActionService,
            this.message,
            this.confirmation,
            this.selectModalService,
            this.displayTypeLogic,
            this.appMetadataStore,
            this.fieldModalService,
            this.recordMappers,
            this.logic,
            this.recordManager
        );
    }
}
