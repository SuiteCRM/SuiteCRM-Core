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
import {LanguageStore} from '../../../store/language/language.store';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {RecordViewStore} from '../../../views/record/store/record-view/record-view.store';
import {RecordContentAdapter} from "./record-content.adapter";
import {RecordActionManager} from "../actions/record-action-manager.service";
import {PanelLogicManager} from "../../../components/panel-logic/panel-logic.manager";
import {Observable} from "rxjs";
import {Panel} from "../../../common/metadata/metadata.model";

@Injectable({
    providedIn: 'root',
})
export class RecordContentAdapterFactory {

    constructor(
        protected metadata: MetadataStore,
        protected language: LanguageStore,
        protected actions: RecordActionManager,
        protected logicManager: PanelLogicManager
    ) {
    }

    create(
        store: RecordViewStore,
        panels$: Observable<Panel[]>
    ): RecordContentAdapter {
        return new RecordContentAdapter(
            store,
            this.metadata,
            this.language,
            this.actions,
            this.logicManager,
            panels$
        );
    }
}
