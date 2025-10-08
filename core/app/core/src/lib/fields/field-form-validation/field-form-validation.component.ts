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
import {Record} from "../../common/record/record.model";
import {StringMap} from "../../common/types/string-map";
import {Component, Input} from "@angular/core";
import {Field} from "../../common/record/field.model";
import {StandardValidationErrors} from "../../common/services/validators/validators.model";

@Component({
    selector: 'scrm-field-validation',
    templateUrl: './field-form-validation.component.html',
    styleUrls: []
})

export class FieldFormValidationComponent {

    @Input('field') field: Field;
    @Input('record') record: Record;
    @Input('errors') errors: StandardValidationErrors;

    hasLabels(item: any): boolean {
        return item?.message?.labels?.startLabelKey || item?.message?.labels?.endLabelKey;
    }

    getMessageStartLabelKey(item: any): string {
        return item?.message?.labels?.startLabelKey ?? '';
    }

    getValidationIcon(item: any): string {
        return item?.message?.labels?.icon ?? '';
    }

    getMessageEndLabelKey(item: any): string {
        return item?.message?.labels?.endLabelKey ?? '';
    }

    getMessageContext(item: any, record: Record): StringMap {
        const context = item && item.message && item.message.context || {};
        context.module = (record && record.module) || '';

        return context;
    }

    getMessageLabelKey(item: any): string {
        return (item && item.message && item.message.labelKey) || '';
    }
}
