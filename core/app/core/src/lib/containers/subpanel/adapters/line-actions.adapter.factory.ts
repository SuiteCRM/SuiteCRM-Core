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
import {AsyncActionService} from '../../../services/process/processes/async-action/async-action';
import {MessageService} from '../../../services/message/message.service';
import {ConfirmationModalService} from '../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../store/language/language.store';
import {SubpanelStore} from '../store/subpanel/subpanel.store';
import {SubpanelLineActionsAdapter} from './line-actions.adapter';
import {SubpanelLineActionManager} from '../line-actions/line-action-manager.service';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {AppMetadataStore} from "../../../store/app-metadata/app-metadata.store.service";
import {FieldModalService} from "../../../services/modals/field-modal.service";
import {FieldLogicManager} from "../../../fields/field-logic/field-logic.manager";


@Injectable({
    providedIn: 'root',
})
export class SubpanelLineActionsAdapterFactory {

    constructor(
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
    }

    create(store: SubpanelStore): SubpanelLineActionsAdapter {
        return new SubpanelLineActionsAdapter(
            store,
            this.actionManager,
            this.asyncActionService,
            this.message,
            this.confirmation,
            this.language,
            this.selectModalService,
            this.fieldModalService,
            this.metadata,
            this.appMetadataStore,
            this.logic
        );
    }
}
