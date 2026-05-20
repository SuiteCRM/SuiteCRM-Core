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

import {Component} from '@angular/core';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {BaseEnumComponent} from '../../../base/base-enum.component';
import {Option} from '../../../../common/record/field.model';
import {LanguageListStringMap, LanguageStore} from '../../../../store/language/language.store';
import {UntypedFormGroup} from '@angular/forms';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

@Component({
    selector: 'scrm-radioenum-edit',
    templateUrl: './radioenum.component.html',
    styleUrls: []
})
export class RadioEnumEditFieldComponent extends BaseEnumComponent {
    formGroup: UntypedFormGroup;

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(languages, typeFormatter, logic, logicDisplay);
    }

    get displayDirection(): string {
        if (!this.field || !this.field.definition || !this.field.definition.displayDirection) {
            return '';
        }
        return this.field.definition.displayDirection;
    }

    ngOnInit(): void {
        this.checkAndInitAsDynamicEnum();

        super.ngOnInit();

        this.subscribeValueChanges();

        if (this.record && this.record.formGroup) {
            this.formGroup = this.record.formGroup
        } else {
            this.formGroup = new UntypedFormGroup({});
            this.formGroup.addControl(this.field.name, this.field.formControl);
        }

    }

    public getId(item: Option) {
        return this.field.name + '-' + item.value;
    }

    protected buildOptionsArray(appStrings: LanguageListStringMap): void {
        this.options = [];

        Object.keys(this.optionsMap).forEach(key => {

            this.options.push({
                value: key,
                label: this.optionsMap[key]
            } as Option);
        });

        if (this.isDynamicEnum) {
            this.buildDynamicEnumOptions(appStrings);
        }
    }
}
