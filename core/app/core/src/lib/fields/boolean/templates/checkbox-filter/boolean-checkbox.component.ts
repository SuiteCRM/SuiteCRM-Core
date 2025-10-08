/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {BaseEnumComponent} from '../../../base/base-enum.component';
import {LanguageStore} from '../../../../store/language/language.store';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

@Component({
    selector: 'scrm-boolean-checkbox-filter',
    templateUrl: './boolean-checkbox.component.html',
    styleUrls: []
})
export class BooleanCheckboxFilterFieldComponent extends BaseEnumComponent implements OnInit {

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(languages, typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        this.field.value = '';

        if (this.field.criteria.values && this.field.criteria.values.length > 0) {
            this.field.value = this.field.criteria.values[0];
        }

    }

    toggle(): void {
        let newValue = 'true';

        if (this.field.value) {
            newValue = '';
        }

        this.field.value = newValue;
        this.field.formControl.setValue(newValue);
        this.field.formControl.markAsDirty();
        this.field.criteria.operator = '=';
        this.field.criteria.values = [newValue];
    }
}
