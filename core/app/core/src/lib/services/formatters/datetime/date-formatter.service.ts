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
import {DatetimeFormatter} from './datetime-formatter.service';
import {FormatOptions} from '../formatter.model';
import {formatDate} from '@angular/common';

@Injectable({
    providedIn: 'root'
})
export class DateFormatter extends DatetimeFormatter {

    getInternalFormat(): string {
        return this.getInternalDateFormat();
    }

    getUserFormat(): string {
        return this.getDateFormat();
    }

    /**
     * Format User Date to Internal format. It assumes the date is always in GMT
     *
     * @param dateString
     * @param options
     */
    toInternalFormat(dateString: string, options?: FormatOptions): string {
        const fromFormat = (options && options.fromFormat) || this.getUserFormat();
        if (dateString) {

            let date = this.toDateTime(dateString, fromFormat);

            if (!date || !date.isValid) {
                date = this.toDateTime(dateString);
            }

            return formatDate(date.toJSDate(), this.getInternalFormat(), this.locale);
        }
        return '';
    }

    /**
     * Format Internal Date to User. It assumes internal date is in GMT/UTC
     *
     * @param dateString
     * @param options
     */
    toUserFormat(dateString: string, options?: FormatOptions): string {
        const fromFormat = (options && options.fromFormat) || this.getInternalFormat();
        const toFormat = (options && options.toFormat) || this.getUserFormat();
        if (dateString) {
            const dateTime = this.toDateTime(dateString, fromFormat);
            if (!dateTime.isValid) {
                return dateString;
            }
            return formatDate(dateTime.toJSDate(), toFormat, this.locale);
        }
        return '';
    }

}
