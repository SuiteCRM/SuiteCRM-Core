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

import {inject} from '@angular/core';
import {AsyncValidatorFn, UntypedFormGroup} from '@angular/forms';
import {Action, ActionHandler, RecordBasedActionData} from '../../../common/actions/action.model';
import {FieldMap} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ValidationManager} from '../../../services/record/validation/validation.manager';
import {RecordViewStore} from '../store/record-view/record-view.store';
import {ModuleNavigation} from '../../../services/navigation/module-navigation/module-navigation.service';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {ValidationMessageService} from '../../../services/record/validation/validation-message.service';
import {Params, Router} from "@angular/router";
import {RecordPaginationService} from "../store/record-pagination/record-pagination.service";

export interface RecordActionData extends RecordBasedActionData {
    store: RecordViewStore;
    action?: Action;
}

export abstract class RecordActionHandler extends ActionHandler<RecordActionData> {

    protected validationManager = inject(ValidationManager);
    protected metadataStore = inject(MetadataStore);
    protected validationMessage = inject(ValidationMessageService);

    abstract run(data: RecordActionData): void;

    abstract shouldDisplay(data: RecordActionData): boolean;

    setAsyncValidators(fields: FieldMap): void {
        Object.keys(fields).forEach(fieldKey => {
            const field = fields[fieldKey];

            field.asyncValidationErrors = null;

            if (field?.asyncValidators?.length) {
                field.formControl.setAsyncValidators(field?.asyncValidators);
                field.formControl.updateValueAndValidity();
            }
        });
    }

    clearAsyncValidators(fields: FieldMap): void {
        Object.keys(fields).forEach(fieldKey => {
            const field = fields[fieldKey];

            if (field?.asyncValidators?.length) {
                field.formControl.clearAsyncValidators();
                field.formControl.updateValueAndValidity();
            }
        });
    }

    protected setRecordAsyncValidators(record: Record, formGroup: UntypedFormGroup): void {
        const meta = this.metadataStore.get() || {};
        const viewMeta = meta.recordView || {};
        const validators: AsyncValidatorFn[] = this.validationManager.getAsyncRecordSaveValidations(record, viewMeta);

        if (validators.length) {
            formGroup.setAsyncValidators(validators);
            formGroup.updateValueAndValidity();
        }
    }

    protected clearRecordAsyncValidators(formGroup: UntypedFormGroup): void {
        formGroup.clearAsyncValidators();
        formGroup.updateValueAndValidity();
    }

    checkRecordAccess(data: RecordActionData, defaultAcls: string[] = []): boolean {

        const record = data.store.recordStore.getBaseRecord();
        const acls = record.acls ?? [];

        if (!acls || !acls.length) {
            return false;
        }

        const action = data.action ?? null;

        return this.checkAccess(action, acls, defaultAcls);
    }

    /**
     * Navigate back
     * @param navigation
     * @param params
     * @param id
     * @param moduleName
     * @param record
     */
    protected navigateBack(
        navigation: ModuleNavigation,
        params: { [p: string]: string },
        id: string,
        moduleName: string,
        record: Record
    ) {
        let returnModule = navigation.getReturnModule(params);
        let returnAction = navigation.getReturnAction(params);
        let returnId = navigation.getReturnId(params);

        if (id === returnId) {
            return;
        }

        if (returnModule === moduleName &&
            returnAction === 'record' &&
            returnId !== id
        ) {
            return;
        }

        if (!returnModule || !returnAction) {
            return;
        }

        navigation.navigateBack(record, moduleName, params);
    }

    protected navigateBackToDetail(
        navigation: ModuleNavigation,
        router: Router,
        recordPaginationService: RecordPaginationService,
        id: string,
        moduleName: string,
        params: { [p: string]: string }
    ) {
        const nextRoute = navigation.getRecordRouterLink(moduleName, id);
        const queryParams = { offset: recordPaginationService.getOffsetFromUrl() } as Params;

        const section = params?.record_section ?? '';
        if (section) {
            queryParams.section = section;
        }
        router.navigate([nextRoute], { queryParams});
    }

}
