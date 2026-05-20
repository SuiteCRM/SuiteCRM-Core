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

import {Inject, Injectable, LOCALE_ID} from '@angular/core';
import {UserPreferenceStore} from '../../../store/user-preference/user-preference.store';
import {Observable} from 'rxjs';
import {map} from 'rxjs/operators';
import {formatDate} from '@angular/common';
import {NgbDateStruct, NgbTimeStruct} from '@ng-bootstrap/ng-bootstrap';
import {DateTime, IANAZone} from 'luxon';
import {FormatOptions, Formatter} from '../formatter.model';
import {FormControlUtils} from '../../record/field/form-control.utils';
import {DateTimeOptions} from "luxon/src/datetime";

export interface DatetimeFormats {
    date: string;
    time: string;
}

export interface DateTimeStruct extends NgbDateStruct, NgbTimeStruct {
}

@Injectable({
    providedIn: 'root'
})
export class DatetimeFormatter implements Formatter {

    format$: Observable<DatetimeFormats> = this.preferences.userPreferences$.pipe(
        map(() => {

            const date = this.getDateFormat();
            const time = this.getTimeFormat();

            return {date, time};
        })
    );

    constructor(
        protected preferences: UserPreferenceStore,
        protected formUtils: FormControlUtils,
        @Inject(LOCALE_ID) public locale: string
    ) {

    }

    getDateFormat(): string {
        const dateFormatPreference = this.preferences.getUserPreference('date_format');

        if (dateFormatPreference) {
            return dateFormatPreference;
        }

        return this.getInternalDateFormat();
    }

    getTimeFormat(map = true): string {

        const timeFormatPreference = this.preferences.getUserPreference('time_format');

        if (timeFormatPreference) {
            let format: string = timeFormatPreference;

            if (format.includes('aaaaaa') && map) {
                format = format.replace('aaaaaa', 'a');
            }

            return format;
        }

        return this.getInternalTimeFormat();
    }

    getDateTimeFormat(): string {
        const dateFormat = this.getDateFormat();
        const timeFormat = this.getTimeFormat();
        return dateFormat + ' ' + timeFormat;
    }

    getInternalDateFormat(): string {
        return 'yyyy-MM-dd';
    }

    getInternalTimeFormat(): string {
        return 'HH:mm:ss';
    }

    getInternalDateTimeFormat(): string {
        const dateFormat = this.getInternalDateFormat();
        const timeFormat = this.getInternalTimeFormat();
        return dateFormat + ' ' + timeFormat;
    }

    getInternalFormat(): string {
        return this.getInternalDateTimeFormat();
    }

    getUserFormat(): string {
        return this.getDateTimeFormat();
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
            const dateTime = this.toDateTime(dateString, fromFormat,  {
                zone: 'GMT'
            });

            if (!dateTime.isValid) {
                return dateString;
            }

            let date = DateTime.fromJSDate(dateTime.toJSDate(), {zone: this.preferences.getUserPreference('timezone')}).toFormat(toFormat, {locale: this.locale})
            if (this.getTimeFormat(false).includes('aaaaaa')) {
                date = date.replace('A', 'a');
                date = date.replace('P', 'p');
                date = date.replace('M', 'm');
            }
            return date;
        }
        return '';
    }

    /**
     * Format User Date to Internal format. It assumes the date is in the user timezone
     *
     * @param dateString
     * @param options
     */
    toInternalFormat(dateString: string, options?: FormatOptions): string {
        const fromFormat = (options && options.fromFormat) || this.getUserFormat();
        if (dateString) {

            let date = this.toDateTime(dateString, fromFormat, {
                zone: this.preferences.getUserPreference('timezone')
            });

            return  DateTime.fromJSDate(date.toJSDate(), {zone: 'GMT'}).toFormat(this.getInternalFormat(), {locale: this.locale})
        }
        return '';
    }

    formatDateTime(dateString: string, format: string, fromFormat = '', locale = this.locale, timezone = ''): string {
        const dateTime = this.toDateTime(dateString, fromFormat);

        if (!dateTime.isValid) {
            return dateString;
        }
        return formatDate(dateTime.toJSDate(), format, locale, timezone);
    }

    toDateTime(datetimeString: string, fromFormat = '', options?: DateTimeOptions): DateTime {
        if (!datetimeString) {
            return DateTime.invalid('empty');
        }

        if (fromFormat) {
            fromFormat = fromFormat.replace('aaaaa\'m\'', 'a');
            return DateTime.fromFormat(datetimeString, fromFormat, options);
        }

        let dateTime = this.fromUserFormat(datetimeString, options);
        if (!dateTime.isValid) {
            dateTime = this.fromInternalFormat(datetimeString, options);
        }

        return dateTime;
    }

    userDateTimeFormatToStruct(datetime: string): { date: NgbDateStruct; time: NgbTimeStruct } {
        if (!datetime) {
            return null;
        }

        const dateTime = this.toDateTime(datetime, '',  {
            zone: this.preferences.getUserPreference('timezone')
        });

        if (!dateTime.isValid) {
            return null;
        }

        return {
            date: {
                day: dateTime.day,
                month: dateTime.month,
                year: dateTime.year
            } as NgbDateStruct,
            time: {
                hour: dateTime.hour,
                minute: dateTime.minute,
                second: dateTime.second,
            } as NgbTimeStruct
        };
    }

    internalDateTimeFormatToStruct(datetime: string): { date: NgbDateStruct; time: NgbTimeStruct } {
        if (!datetime) {
            return null;
        }

        const dateTime = this.toDateTime(datetime, this.getInternalDateTimeFormat(), {
            zone: 'GMT'
        });

        const rezoned = dateTime.setZone(this.preferences.getUserPreference('timezone'));

        if (!dateTime.isValid) {
            return null;
        }

        return {
            date: {
                day: rezoned.day,
                month: rezoned.month,
                year: rezoned.year
            } as NgbDateStruct,
            time: {
                hour: rezoned.hour,
                minute: rezoned.minute,
                second: rezoned.second,
            } as NgbTimeStruct
        };
    }

    userDateFormatToStruct(datetime: string): NgbDateStruct {
        if (!datetime) {
            return null;
        }

        const dateTime = this.toDateTime(datetime, '', {
            zone: this.preferences.getUserPreference('timezone')
        });

        if (!dateTime.isValid) {
            return null;
        }

        return {
            day: dateTime.day,
            month: dateTime.month,
            year: dateTime.year
        } as NgbDateStruct;
    }

    dateFormatToStruct(datetime: string, fromFormat = ''): NgbDateStruct {
        if (!datetime) {
            return null;
        }

        const dateTime = this.toDateTime(datetime, fromFormat);
        if (!dateTime.isValid) {
            return null;
        }

        return {
            day: dateTime.day,
            month: dateTime.month,
            year: dateTime.year
        } as NgbDateStruct;
    }

    userTimeFormatToStruct(datetime: string): NgbTimeStruct {
        if (!datetime) {
            return null;
        }

        const dateTime = this.toDateTime(datetime, '', {
            zone: this.preferences.getUserPreference('timezone')
        });

        if (!dateTime.isValid) {
            return null;
        }

        return {
            hour: dateTime.hour,
            minute: dateTime.minute,
            second: dateTime.second
        } as NgbTimeStruct;
    }

    fromUserFormat(datetime: string, options?: DateTimeOptions, formatOptions?: FormatOptions): DateTime {
        // ensure datetime is in user format.
        datetime = this.toUserFormat(datetime, formatOptions);
        datetime = datetime.toString();
        datetime = datetime.replace('a', 'A');
        datetime = datetime.replace('p', 'P');
        datetime = datetime.replace('m', 'M');

        let format = this.getUserFormat();
        format = format.replace('aaaaa\'m\'', 'a');
        return DateTime.fromFormat(datetime, format, options);
    }

    fromInternalFormat(datetime: string, options?: DateTimeOptions): DateTime {
        const format = this.getInternalFormat();
        return DateTime.fromFormat(datetime.toString(), format, options);
    }

    validateUserFormat(inputValue: any, userFormat: string = ''): boolean {

        const trimmedInputValue = this.formUtils.getTrimmedInputValue(inputValue);
        if (this.formUtils.isEmptyInputValue(trimmedInputValue)) {
            return false;
        }
        const dateTime = this.fromUserFormat(trimmedInputValue, {},{fromFormat: userFormat});
        return !dateTime.isValid;
    }

    userTimeZone(): string {
        let userTZ = this.preferences.getUserPreference('timezone') ?? 'GMT';
        if (!userTZ) {
            userTZ = 'GMT';
        }
        const milliseconds = DateTime.now().setZone(userTZ).toMillis();
        return IANAZone.create(userTZ).formatOffset(milliseconds, 'techie');
    }


}
