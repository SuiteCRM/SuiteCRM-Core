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
import {Formatter} from '../formatter.model';
import {FormControlUtils} from '../../record/field/form-control.utils';
import {SystemConfigStore} from "../../../store/system-config/system-config.store";

@Injectable({
    providedIn: 'root'
})
export class EmailFormatter implements Formatter {

    constructor(
        protected formUtils: FormControlUtils,
        protected configs: SystemConfigStore
    ) {
    }

    toUserFormat(value: string): string {
        return value;
    }

    toInternalFormat(value: string): string {
        return value;
    }

    getUserFormatPattern(): string {
        const validations = this.configs.getUi('validations');
        let defaultRegex = validations?.regex?.email || '';

        if (!defaultRegex) {
            // eslint-disable-next-line max-len
            defaultRegex = "^(?:[\\.\\-\\+&#!\\$\\*=\\?\\^_`\\{\\}~\\/\\w]+)@(?:(?:\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3})|\\w+(?:[\\.-]*\\w+)*(?:\\.[\\w-]{2,})+)$";
        }

        return defaultRegex;
    }

    validateUserFormat(inputValue: any, validationRegexPattern: string = ''): boolean {

        const trimmedInputValue = this.formUtils.getTrimmedInputValue(inputValue);
        if (this.formUtils.isEmptyInputValue(trimmedInputValue)) {
            return false;
        }
        const regex = new RegExp(validationRegexPattern);
        return !regex.test(trimmedInputValue);

    }

}
