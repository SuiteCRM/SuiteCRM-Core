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

import {Injectable} from '@angular/core';
import {Action} from '../../../common/actions/action.model';
import {Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {CurrencyService} from '../../../services/currency/currency.service';
import {CurrencyFormatter} from "../../../services/formatters/currency/currency-formatter.service";

@Injectable({
    providedIn: 'root'
})
export class UpdateCurrencyAction extends FieldLogicActionHandler {

    key = 'update-currency';
    modes = ['edit', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor(protected currencyService: CurrencyService, protected currencyFormatter: CurrencyFormatter) {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;
        const isBaseCurrency = field?.definition?.metadata?.isBaseCurrency ?? false;

        if (!record || !field || isBaseCurrency) {
            return;
        }

        const currencyIdFieldName = action.params.currencyIdField ?? 'currency_id';
        const baseCurrencyFieldName = action.params.baseCurrencyField ?? 'amount_usdollar';

        const currencyId = record?.fields[currencyIdFieldName]?.value ?? null;
        let value = parseFloat(field?.value ?? null);
        let baseValue = parseFloat(record?.fields[baseCurrencyFieldName]?.value ?? null);

        if (!isFinite(value) || !isFinite(baseValue) || currencyId === null) {
            return;
        }

        const newValue = this.currencyService.baseToCurrency(currencyId, baseValue);

        if (!isFinite(newValue)) {
            return;
        }

        this.updateValue(field, newValue, record);
    }

    protected updateValue(field: Field, value: number, record: Record): void {

        const options = {
            mode: 'edit' as ViewMode
        }

        const formattedValue = this.currencyFormatter.toUserFormat(value.toString(), options);
        field.value = formattedValue;
        field.formControl.setValue(formattedValue);
        // re-validate the parent form-control after value update
        record.formGroup.updateValueAndValidity({onlySelf: true, emitEvent: true});
    }

    getTriggeringStatus(): string[] {
        return ['onAnyLogic'];
    }
}
