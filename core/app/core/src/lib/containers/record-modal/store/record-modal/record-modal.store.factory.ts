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
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {RecordModalStore} from './record-modal.store';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {RecordStoreFactory} from "../../../../store/record/record.store.factory";
import {LanguageStore} from "../../../../store/language/language.store";
import {PanelLogicManager} from "../../../../components/panel-logic/panel-logic.manager";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {MessageService} from "../../../../services/message/message.service";
import {NavigationStore} from "../../../../store/navigation/navigation.store";
import {ModuleNavigation} from "../../../../services/navigation/module-navigation/module-navigation.service";
import {RecordValidationHandler} from "../../../../services/record/validation/record-validation.handler";
import {RecordModalFieldActionsAdapterFactory} from "../../adapters/record-modal-field-actions.adapter.factory";
import {RecordManager} from "../../../../services/record/record.manager";

@Injectable({
    providedIn: 'root',
})
export class RecordModalStoreFactory {

    constructor(
        protected storeFactory: RecordStoreFactory,
        protected appStateStore: AppStateStore,
        protected metadataStore: MetadataStore,
        protected preferences: UserPreferenceStore,
        protected languageStore: LanguageStore,
        protected panelLogicManager: PanelLogicManager,
        protected message: MessageService,
        protected navigationStore: NavigationStore,
        protected moduleNavigation: ModuleNavigation,
        protected fieldActionAdaptorFactory: RecordModalFieldActionsAdapterFactory,
        protected recordValidationHandler: RecordValidationHandler,
        protected recordManager: RecordManager
    ) {
    }

    create(metadataView: string): RecordModalStore {
        return new RecordModalStore(
            metadataView,
            this.storeFactory,
            this.appStateStore,
            this.preferences,
            this.metadataStore,
            this.languageStore,
            this.panelLogicManager,
            this.message,
            this.navigationStore,
            this.moduleNavigation,
            this.fieldActionAdaptorFactory,
            this.recordValidationHandler,
            this.recordManager
        );
    }
}
