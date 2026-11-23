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

import {ActivatedRoute, NavigationExtras, Router} from '@angular/router';
import {Injector, runInInjectionContext} from '@angular/core';
import {ngbModalMock} from '../../../services/modals/confirmation-modal.spec.mock';
import {moduleNameMapperMock} from '../../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {RecordCancelAction} from './cancel/record-cancel.action';
import {RecordActionManager} from './record-action-manager.service';
import {RecordSaveAction} from './save/record-save.action';
import {RecordToggleWidgetsAction} from './toggle-widgets/record-widget-action.service';
import {RecordEditAction} from './edit/record-edit.action';
import {messageServiceMock} from '../../../services/message/message.service.spec.mock';
import {RecordCreateAction} from './create/record-create.action';
import {CancelCreateAction} from './cancel-create/cancel-create.action';
import {RecordSaveNewAction} from './save-new/record-save-new.action';
import {actionNameMapperMock} from '../../../services/navigation/action-name-mapper/action-name-mapper.service.spec.mock';
import {mockModuleNavigation} from '../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {RecordSaveContinueAction} from './save-continue/record-save-continue.action';
import {RecordSaveScheduleAction} from './save-schedule/record-save-schedule.action';
import {AsyncProcessRecordAction} from './async-process/async-process.service';
import {ModalEditAction} from './modal-edit/modal-edit.action';
import {ModalCreateAction} from './modal-create/modal-create.action';
import {RecordPaginationService} from '../store/record-pagination/record-pagination.service';
import {localStorageServiceMock} from '../../../services/local-storage/local-storage.service.spec.mock';
import {userPreferenceStoreMock} from '../../../store/user-preference/user-preference.store.spec.mock';
import {appStateStoreMock} from '../../../store/app-state/app-state.store.spec.mock';
import {systemConfigStoreMock} from '../../../store/system-config/system-config.store.spec.mock';
import {ValidationManager} from '../../../services/record/validation/validation.manager';
import {validationManagerMock} from '../../../services/record/validation/validation.manager.spec.mock';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {metadataStoreMock} from '../../../store/metadata/metadata.store.spec.mock';
import {ValidationMessageService} from '../../../services/record/validation/validation-message.service';
import {languageStoreMock} from '../../../store/language/language.store.spec.mock';

const mockRouter = {
    navigate: (commands: any[], extras?: NavigationExtras) => {
        if (!extras || !commands) {
            return null;
        }

        return null;
    }
} as Router;

const route = new ActivatedRoute();
const pagination = new RecordPaginationService(localStorageServiceMock, userPreferenceStoreMock, appStateStoreMock, route);

const injector = Injector.create({
    providers: [
        {provide: ValidationManager, useValue: validationManagerMock},
        {provide: MetadataStore, useValue: metadataStoreMock},
        {provide: ValidationMessageService, useValue: new ValidationMessageService(messageServiceMock, languageStoreMock)}
    ]
});

export const recordActionsManagerMock = runInInjectionContext(injector, () => new RecordActionManager(
    new RecordEditAction(messageServiceMock),
    new RecordCreateAction(moduleNameMapperMock, mockRouter),
    new RecordToggleWidgetsAction(),
    new RecordCancelAction(ngbModalMock, mockModuleNavigation, mockRouter, pagination),
    new CancelCreateAction(ngbModalMock, mockRouter, moduleNameMapperMock, actionNameMapperMock),
    new RecordSaveAction(mockRouter, messageServiceMock, mockModuleNavigation, null, null, pagination),
    new RecordSaveNewAction(messageServiceMock, mockModuleNavigation),
    new RecordSaveContinueAction(messageServiceMock, mockModuleNavigation, null, systemConfigStoreMock, null, pagination),
    new RecordSaveScheduleAction(messageServiceMock, mockRouter, mockModuleNavigation, null, systemConfigStoreMock, pagination),
    new AsyncProcessRecordAction(),
    new ModalEditAction(ngbModalMock, route),
    new ModalCreateAction(ngbModalMock, appStateStoreMock)
));
