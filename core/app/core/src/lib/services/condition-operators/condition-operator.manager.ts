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
import {GreaterThanAction} from './greater-than/greater-than.action';
import {LessThanAction} from './less-than/less-than.action';
import {NotEmptyAction} from './not-empty/not-empty.action';
import {ConditionOperatorModel} from './condition-operator.model';
import {IsEmptyAction} from './is-empty/is-empty.action';
import {IsEqualAction} from './is-equal/is-equal.action';
import {NotEqualAction} from './not-equal/not-equal.action';
import {AnyValueAction} from "./any-value/any-value.action";

@Injectable({
    providedIn: 'root'
})
export class ConditionOperatorManager {

    constructor(
        public greaterThanAction: GreaterThanAction,
        public lessThanAction: LessThanAction,
        public notEmptyAction: NotEmptyAction,
        public isEmptyAction: IsEmptyAction,
        public isEqualAction: IsEqualAction,
        public notEqualAction: NotEqualAction,
        public anyValueAction: AnyValueAction
    ) {
    }

    get(key: string): ConditionOperatorModel {
        const operatorMap: { [key: string]: ConditionOperatorModel } = {
            'greater-than': this.greaterThanAction,
            'less-than': this.lessThanAction,
            'not-empty': this.notEmptyAction,
            'is-empty': this.isEmptyAction,
            'is-equal': this.isEqualAction,
            'not-equal': this.notEqualAction,
            'any-value': this.anyValueAction
        };

        return operatorMap[key];
    }

}
