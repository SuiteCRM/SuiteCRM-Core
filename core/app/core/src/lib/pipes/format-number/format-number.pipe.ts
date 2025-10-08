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

import {Inject, LOCALE_ID, Pipe} from '@angular/core';
import {DecimalPipe} from '@angular/common';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';

@Pipe({
    name: 'formatNumber'
})
export class FormatNumberPipe extends DecimalPipe {

    constructor(
        protected formatter: NumberFormatter,
        @Inject(LOCALE_ID) public locale: string
    ) {
        super(locale);
    }

    transform(value: number | string, digitsInfo?: string, locale?: string): string | null;
    // eslint-disable-next-line no-dupe-class-members
    transform(value: null | undefined, digitsInfo?: string, locale?: string): null;
    // eslint-disable-next-line no-dupe-class-members,@typescript-eslint/no-unused-vars
    transform(value: number | string | null | undefined, digitsInfo?: string, locale?: string): string | null {

        if (!value) {
            return null;
        }

        return this.formatter.toUserFormat('' + value);
    }
}
