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
import {CommonModule} from '@angular/common';
import {ReactiveFormsModule} from '@angular/forms';
import {BaseFieldComponent} from '../../fields/base/base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {FieldLogicDisplayManager} from '../../fields/field-logic-display/field-logic-display.manager';
import {FieldLogicManager} from '../../fields/field-logic/field-logic.manager';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';

/**
 * A coordinate (internal format, '.' decimal) in the user format with all its decimals: the
 * decimal symbol of the user, no grouping.
 */
function userCoordinate(value: unknown, numberFormatter: NumberFormatter): string {
    const text = `${value ?? ''}`.trim();
    if (text === '' || !Number.isFinite(Number(text))) {
        return text;
    }

    return text.replace('.', numberFormatter.getDecimalsSymbol() || '.');
}

/**
 * A latitude or longitude of the maps modules shown with all its decimals (8,
 * as the legacy pages) and the decimal symbol of the user: the core number format keeps 3 decimals
 * (about 100 m).
 *
 * Registered on the coordinates of address-cache and markers in detail and list modes
 * (FieldRegistry.registerField, see extension.module.ts).
 */
@Component({
    selector: 'scrm-coordinate-field',
    standalone: true,
    template: `{{ getValue() }}`
})
export class CoordinateFieldComponent extends BaseFieldComponent {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected numberFormatter: NumberFormatter
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    getValue(): string {
        return userCoordinate(this.field?.value, this.numberFormatter);
    }
}

/**
 * The edit of a coordinate of the maps modules with all its decimals: the core
 * edit view starts from the number format of the user, which keeps 3 decimals and would be saved.
 * The value typed goes back to the internal format as in the core edit (decimal symbol of the
 * user).
 *
 * Registered on the coordinates of address-cache and markers in edit mode.
 */
@Component({
    selector: 'scrm-coordinate-edit-field',
    standalone: true,
    imports: [CommonModule, ReactiveFormsModule],
    template: `
        <input type="text" class="form-control form-control-sm" [formControl]="field.formControl"
               [class.is-invalid]="field.formControl.invalid && field.formControl.touched">
    `
})
export class CoordinateEditFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected numberFormatter: NumberFormatter
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.field?.formControl?.setValue(userCoordinate(this.field.value, this.numberFormatter), {emitEvent: false});
        this.subscribeValueChanges();
    }

    ngOnDestroy(): void {
        this.unsubscribeAll();
    }
}
