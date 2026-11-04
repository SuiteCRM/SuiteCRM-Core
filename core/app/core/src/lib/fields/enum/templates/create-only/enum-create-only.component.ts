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
 * A dropdown chosen when the record is created and read-only afterwards (e.g. the grant type of
 * an OAuth2 client, which the legacy forms post as a hidden value once the client exists).
 *
 * Display type `create-only` of `enum` fields, edit mode (the detail mode is the enum one).
 */
@Component({
    selector: 'scrm-enum-create-only-edit',
    templateUrl: './enum-create-only.component.html',
    styleUrls: []
})
export class EnumCreateOnlyEditFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    options: { value: string; label: string }[] = [];

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    get created(): boolean {
        return !!(this.record?.id || this.record?.attributes?.id);
    }

    ngOnInit(): void {
        super.ngOnInit();
        const list = this.language.getAppListString(this.field?.definition?.options ?? '') as any;
        this.options = Object.keys(list ?? {}).map(value => ({value, label: `${list[value]}`}));
        this.subscribeValueChanges();
    }

    ngOnDestroy(): void {
        this.unsubscribeAll();
    }

    optionLabel(value: string): string {
        return this.options.find(option => option.value === value)?.label ?? value ?? '';
    }
}
