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
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {Action} from '../../../common/actions/action.model';
import {Record} from '../../../common/record/record.model';
import {Field} from '../../../common/record/field.model';
import {ViewMode} from '../../../common/views/view.model';

@Injectable({
    providedIn: 'root'
})
export class UpdateEmailSignatureAction extends FieldLogicActionHandler {

    key = 'updateEmailSignature';
    modes = ['edit', 'create'] as ViewMode[];

    constructor() {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const fromFieldName = action.params.fromField ?? 'outbound_email_account';
        const fromField = record?.fields[fromFieldName] ?? {} as Field;
        const signatureAttributeName = action.params.signatureAttribute ?? 'signature';
        const valueObject = fromField?.valueObject ?? {};
        const outboundId = valueObject['id'] ?? '';
        const signatureAttribute = valueObject[signatureAttributeName] ?? null;

        if (!fromField || signatureAttribute === null) {
            return;
        }

        const currentValue = field.value || '';
        this.replaceSignaturePlaceholder(field, outboundId, signatureAttribute, currentValue, record);
    }

    getTriggeringStatus(): string[] {
        return ['onDependencyChange', 'onFieldInitialize'];
    }

    private replaceSignaturePlaceholder(field: Field, outboundId: string, signatureString: string, body: string, record: Record): void {
        const placeholderClass = 'scrm-signature-placeholder';
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = body;

        let signatureElem = tempDiv.getElementsByClassName(placeholderClass)[0] as HTMLElement || undefined;

        if (signatureElem && signatureElem?.classList?.contains('signature-' + outboundId)) {
            return;
        }

        if (!signatureElem) {
            // No placeholder found, append signature at the end
            const newSignature = document.createElement('div');
            newSignature.className = placeholderClass + ' signature-' + outboundId;
            newSignature.innerHTML = signatureString;
            tempDiv.appendChild(document.createElement('div'));
            tempDiv.appendChild(newSignature);
        } else {
            const newSignature = document.createElement('div');
            newSignature.className = placeholderClass + ' signature-' + outboundId;
            newSignature.innerHTML = signatureString;
            tempDiv.replaceChild(newSignature, signatureElem);
        }

        field.value = tempDiv.innerHTML;
        field.formControl.setValue(field.value);
        field.formControl.updateValueAndValidity({onlySelf: true, emitEvent: true})
        // re-validate the parent form-control after value update
        record?.formGroup?.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

}
