/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {Component, OnInit, signal, WritableSignal} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {ActiveFieldsChecker} from '../../../../services/condition-operators/active-fields-checker.service';

@Component({
    selector: 'scrm-texttemplate-detail',
    templateUrl: './texttemplate.component.html',
    styleUrls: []
})
export class TextTemplateDetailFieldComponent extends BaseFieldComponent implements OnInit {

    activeTemplateKey: WritableSignal<string> = signal('');

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected activeFieldsChecker: ActiveFieldsChecker
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.initActiveTemplateKey();
        this.subscribeToConditionalFieldChanges();
    }

    protected subscribeToConditionalFieldChanges(): void {
        const conditionalTemplates = this.field?.definition?.metadata?.conditionalTemplates ?? [];

        conditionalTemplates.forEach((template: any) => {
            const refField = template?.fieldName ? (this.record?.fields?.[template.fieldName] ?? null) : null;
            if (refField?.valueChanges$) {
                this.subs.push(refField.valueChanges$.subscribe(() => this.initActiveTemplateKey()));
            }
        });
    }

    protected initActiveTemplateKey(): void {
        const conditionalTemplates = this.field?.definition?.metadata?.conditionalTemplates ?? [];

        for (const template of conditionalTemplates) {
            const displayModes = template?.displayModes ?? [];
            if (displayModes.length && !displayModes.includes(this.mode)) {
                continue;
            }

            const activeOn = template?.activeOn ?? [];
            if (!activeOn.length) {
                this.activeTemplateKey.set(template?.templateLabelKey);
                return;
            }

            const checkField = template?.fieldName ? (this.record?.fields?.[template?.fieldName] ?? null) : this.field;

            if (!this.record || !checkField) {
                continue;
            }

            if (this.activeFieldsChecker.isValueActive(this.record, checkField, activeOn)) {
                this.activeTemplateKey.set(template?.templateLabelKey);
                return;
            }
        }

        this.activeTemplateKey.set(this.field?.definition?.metadata?.templateLabelKey ?? '');
    }
}
