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

import {BehaviorSubject, Observable, of} from 'rxjs';
import {distinctUntilChanged, shareReplay, take} from 'rxjs/operators';
import {CollectionGQL} from '../../services/api/graphql-api/api.collection.get';
import {UserPreferenceStore} from './user-preference.store';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const userPreferenceMockData = {
    userPreferences: {
        global: {
            id: '/api/user-preferences/global',
            _id: 'global',
            value: null,
            items: {
                calendar_publish_key: 'cc360e7b-c31c-5a12-4716-5e692263f2c8',
                swap_shortcuts: '',
                navigation_paradigm: 'gm',
                sort_modules_by_name: '',
                subpanel_tabs: '',
                count_collapsed_subpanels: '',
                module_favicon: '',
                no_opps: 'off',
                timezone: 'UTC',
                ut: '1',
                mail_smtpserver: '',
                mail_smtpport: '25',
                mail_smtpuser: '',
                mail_smtppass: '',
                use_real_names: 'off',
                mail_smtpauth_req: '',
                mail_smtpssl: 0,
                email_show_counts: 0,
                user_theme: 'SuiteP',
                editor_type: 'mozaik',
                reminder_time: '60',
                email_reminder_time: '60',
                reminder_checked: '0',
                email_reminder_checked: '0',
                currency: {id: '1', name: 'US Dollar', symbol: '$', iso4217: 'USD'},
                default_currency_significant_digits: '2',
                num_grp_sep: ',',
                dec_sep: '.',
                fdow: '0',
                datef: 'm/d/Y',
                timef: 'H:i',
                default_locale_name_format: 's f l',
                export_delimiter: ',',
                default_export_charset: 'UTF-8',
                email_link_type: 'sugar',
                subtheme: 'Dawn',
                list_max_entries_per_page: 20,
                date_format: 'dd.MM.yyyy',
                time_format: 'HH.mm.ss',
            }
        }
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class UserPreferenceRecordGQLSpy extends CollectionGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetchAll(module: string, metadata: { fields: string[] }): Observable<any> {
        const data = {
            data: {
                userPreferences: {
                    edges: []
                }
            }
        };

        Object.keys(userPreferenceMockData.userPreferences).forEach(key => {
            data.data.userPreferences.edges.push({
                node: userPreferenceMockData.userPreferences[key]
            });
        });

        return of(data).pipe(shareReplay());
    }
}

export const userPreferenceStoreMock = new UserPreferenceStore(new UserPreferenceRecordGQLSpy());
userPreferenceStoreMock.load().pipe(take(1)).subscribe();

export class UserPreferenceMockStore extends UserPreferenceStore {

    constructor(
        protected mockData: BehaviorSubject<any>
    ) {
        super(new UserPreferenceRecordGQLSpy());

        this.userPreferences$ = this.mockData.asObservable().pipe(distinctUntilChanged());
    }

    getUserPreference(key: string): any {

        if (!this.mockData.value || !this.mockData.value[key]) {
            return null;
        }

        return this.mockData.value[key];
    }

}
