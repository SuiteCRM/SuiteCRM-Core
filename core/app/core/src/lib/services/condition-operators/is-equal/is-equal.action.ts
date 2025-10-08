/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {ConditionOperatorActionHandler} from '../condition-operator.action';
import {Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {LogicRuleValues} from '../../../common/metadata/metadata.model';
import {ConditionOperatorModel} from '../condition-operator.model';

@Injectable({
    providedIn: 'root'
})
export class IsEqualAction extends ConditionOperatorActionHandler implements ConditionOperatorModel {

    key = 'is-equal';

    constructor() {
        super();
    }

    run(record: Record, field: Field, opsConfig: LogicRuleValues): boolean {
        let comparisonValue = null;

        if (this.compareToField(opsConfig)) {
            comparisonValue = this.getFieldComparisonValue(record, opsConfig);
        } else {
            comparisonValue = this.getStaticComparisonValue(opsConfig);
        }

        if (comparisonValue) {
            return comparisonValue.includes(field.value.toString());
        }
        return false;
    }

    protected getFieldComparisonValue(record: Record, opsConfig: LogicRuleValues): string[] {
        return [record.fields[opsConfig.field]?.value];
    }

    protected getStaticComparisonValue(opsConfig: LogicRuleValues): any[] {
        if (Array.isArray(opsConfig.values)) {
            return opsConfig.values.map(value => value?.toString());
        }

        return [opsConfig.value].map(value => value?.toString());
    }

    protected compareToField(opsConfig: LogicRuleValues): boolean {
        if (opsConfig?.field){
            return true;
        }
        return false;
    }
}
