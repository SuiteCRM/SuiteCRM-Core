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

import {Component, OnInit} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {RecordModalOptions} from "../../../../services/modals/record-modal.model";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {ObjectMap} from "../../../../common/types/object-map";

@Component({
    selector: 'scrm-email-detail',
    templateUrl: './email.component.html',
    styleUrls: []
})
export class EmailDetailFieldsComponent extends BaseFieldComponent implements OnInit {

    linkType: string;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected appStateStore: AppStateStore,
        protected preferences: UserPreferenceStore,
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        this.linkType = this.preferences.getUserPreference('email_link_type') || 'sugar';
    }

    openEmailModal() {

        const options = {
            mapFields: this.getMappedFields(),
            record: this.parent,
            parentId: this.parent.id,
            parentModule: this.parent.module,
            module: 'emails',
            metadataView: 'modalComposeView',
            closeConfirmationModal: true,
            closeConfirmationLabel: 'LBL_CLOSE_EMAIL_MODAL',
            detached: true,
            headerClass: 'left-aligned-title',
            dynamicTitleKey: 'LBL_EMAIL_MODAL_DYNAMIC_TITLE',
            modalOptions: {
                size: 'lg',
                scrollable: false
            }
        } as RecordModalOptions;

        const selectedEmail = this.field?.value ?? '';
        const primaryEmail = this.getPrimaryEmail()
        if (selectedEmail !== primaryEmail){
            options.mapFields = this.getMappedFields(selectedEmail);
        }

        this.appStateStore.openRecordModal(options);
    }

    getPrimaryEmail(): string {
        return this.parent?.fields['email1']?.value ?? this.parent?.fields['email']?.value ?? '';
    }

    getMappedFields(email = null) {
        return {
            default: {
                'parent_id': 'id',
                'parent_name': 'fields.name',
                'parent_type': 'attributes.module_name',
                'to_addrs_names': [
                    {
                        'id': email ?? 'id',
                        'name': email ?? 'fields.name',
                        'email1': email ?? 'attributes.email1',
                        'module_name': 'attributes.module_name'
                    }
                ],
            }
        } as ObjectMap;
    }
}
