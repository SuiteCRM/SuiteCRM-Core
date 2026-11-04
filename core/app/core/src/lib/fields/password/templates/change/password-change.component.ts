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

import {Component, OnDestroy, OnInit} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../../../store/language/language.store';

/**
 * A password input to change a secret: empty (the secret is not sent to the page), with the
 * placeholder and the help text of the field metadata (label keys of the module), as the legacy
 * forms (e.g. "Leave blank unless changing").
 *
 * Display type `change` of `password` fields, edit mode.
 */
@Component({
    selector: 'scrm-password-change-edit',
    templateUrl: './password-change.component.html',
    styleUrls: []
})
export class PasswordChangeEditFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    placeholder = '';
    helpText = '';

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.placeholder = this.label(this.field?.metadata?.placeholder ?? '');
        this.helpText = this.label(this.field?.metadata?.helpText ?? '');
        this.subscribeValueChanges();
    }

    ngOnDestroy(): void {
        this.unsubscribeAll();
    }

    protected label(key: string): string {
        if (!key) {
            return '';
        }

        const module = this.record?.module ?? this.parent?.module ?? null;

        return this.language.getFieldLabel(key, module) || key;
    }
}
