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

import {ActionData} from '../../common/actions/action.model';
import {Field} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {LogicRuleValues} from '../../common/metadata/metadata.model';

export interface ConditionOperatorActionData extends ActionData {
    field: Field,
}

export abstract class ConditionOperatorActionHandler {

    abstract run(record: Record, field: Field, opsConfig: LogicRuleValues): boolean;

    protected getComparisonValues(opsConfig: LogicRuleValues, record: Record): string[] {
        if (this.isFieldComparison(opsConfig)) {
            return this.getFieldComparisonValues(record, opsConfig);
        }

        return this.getStaticComparisonValue(opsConfig);
    }

    protected getFieldComparisonValues(record: Record, opsConfig: LogicRuleValues): string[] {
        const compField = record.fields[opsConfig?.field ?? ''] ?? null;
        if (compField === null) {
            return null;
        }

        const compFieldValue = compField?.value ?? null;
        if (compFieldValue !== null) {
            return [compField.toString()];
        }

        return [];
    }

    protected getStaticComparisonValue(opsConfig: LogicRuleValues): string[] {
        if (Array.isArray(opsConfig.values)) {
            return opsConfig.values.filter(value => value !== undefined && value !== null).map(value => value.toString());
        }

        const value = opsConfig?.value ?? null
        if (value === null) {
            return [];
        }

        return [value].map(value => value.toString());
    }

    protected isFieldComparison(opsConfig: LogicRuleValues): boolean {
        return !!opsConfig?.field;
    }
}
