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

import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {Observable, of} from 'rxjs';
import {shareReplay, take} from 'rxjs/operators';
import {LanguageStore} from './language.store';
import {appStateStoreMock} from '../app-state/app-state.store.spec.mock';
import {localStorageServiceMock} from '../../services/local-storage/local-storage.service.spec.mock';

export const languageMockData = {
    appStrings: {
        LBL_NEW: 'New',
        LBL_EDIT: 'Edit',
        LBL_HISTORY: 'History',
        LBL_SAVE_BUTTON_LABEL: 'Save',
        LBL_CANCEL: 'Cancel',
        LBL_OK: 'OK',
        LBL_SEARCH_RESULTS_TITLE: 'Results',
        ERR_SEARCH_INVALID_QUERY: 'An error has occurred while performing the search. Your query syntax might not be valid.',
        ERR_SEARCH_NO_RESULTS: 'No results matching your search criteria. Try broadening your search.',
        LBL_SEARCH_PERFORMED_IN: 'Search performed in',
        LBL_EMAIL_CODE: 'Email Code:',
        LBL_SEND: 'Send',
        LBL_LOGOUT: 'Logout',
        LBL_TOUR_NEXT: 'Next',
        LBL_LAST_VIEWED: 'Recently Viewed',
        LBL_LISTVIEW_OPTION_ENTIRE: 'Select All',
        LBL_LISTVIEW_OPTION_CURRENT: 'Select This page',
        LBL_LISTVIEW_NONE: 'Deselect All',
        LBL_LIST_OF: 'of',
        LNK_ADVANCED_FILTER: 'Advanced Filter',
        LBL_CLEAR_BUTTON_LABEL: 'Clear',
        LBL_SEARCH_BUTTON_LABEL: 'Search',
        LBL_SAVED_FILTER_SHORTCUT: 'My Filters',
        LBL_BASIC_FILTER: 'Basic Filter',
        LBL_QUICK: 'Quick',
        LBL_DELETE: 'Delete',
        LBL_EXPORT: 'Export',
        LBL_MERGE_DUPLICATES: 'Merge',
        LBL_MASS_UPDATE: 'Mass Update',
        ANNUAL_REVENUE_BY_ACCOUNTS: 'Annual Revenue By Accounts',
        PIPELINE_BY_SALES_STAGE: 'Pipeline By Sales Stage',
        LEADS_BY_SOURCE: 'Leads By Source',
        LBL_QUICK_CHARTS_EMPTY: '',
        LBL_PANEL_ASSIGNMENT: 'OTHER',
        // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
        lbl_account_information: 'OVERVIEW',
        LBL_PANEL_ADVANCED: 'MORE INFORMATION',
        LBL_AVERAGE_CLOSED_WON_PER_YEAR: 'Average Closed Won Per Year',
        LBL_OPPORTUNITIES_TOTAL: 'Total Opportunity Value',
        LBL_INSIGHTS: 'Insights',
        LBL_NO_DATA: 'No Data',
        WARN_UNSAVED_CHANGES: 'Are you sure you want to navigate away from this record?',
    },
    appListStrings: {
        // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
        language_pack_name: 'US English',
        moduleList: {
            Home: 'Home',
            Accounts: 'Accounts',
        },
        // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
        account_type_dom: {
            '': '',
            _analyst: 'Analyst',
            _competitor: 'Competitor',
            _customer: 'Customer',
            _integrator: 'Integrator',
            _investor: 'Investor',
            _partner: 'Partner',
            _press: 'Press',
            _prospect: 'Prospect',
            _reseller: 'Reseller',
            _other: 'Other'
        },
        // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
        dom_int_bool: {
            1: 'Yes',
            0: 'No',
        },
    },
    modStrings: {
        home: {
            LBL_MODULE_NAME: 'Home',
            LBL_NEW_FORM_TITLE: 'New Contact',
            LBL_FIRST_NAME: 'First Name:',
            LBL_LAST_NAME: 'Last Name:',
            LBL_LIST_LAST_NAME: 'Last Name',
            LBL_PHONE: 'Phone:',
            LBL_EMAIL_ADDRESS: 'Email Address:',
            LBL_MY_PIPELINE_FORM_TITLE: 'My Pipeline',
            LBL_PIPELINE_FORM_TITLE: 'Pipeline By Sales Stage',
            // eslint-disable-next-line camelcase,@typescript-eslint/camelcase
            LBL_RGraph_PIPELINE_FORM_TITLE: 'Pipeline By Sales Stage',
            LNK_NEW_CONTACT: 'Create Contact',
            LNK_NEW_ACCOUNT: 'Create Account',
            LNK_NEW_OPPORTUNITY: 'Create Opportunity',
            LNK_NEW_LEAD: 'Create Lead',
            LNK_NEW_CASE: 'Create Case',
            LNK_NEW_NOTE: 'Create Note or Attachment',
            LNK_NEW_CALL: 'Log Call',
            LNK_NEW_EMAIL: 'Archive Email',
            LNK_NEW_MEETING: 'Schedule Meeting',
            LNK_NEW_TASK: 'Create Task',
            LNK_NEW_BUG: 'Report Bug',
            LNK_NEW_SEND_EMAIL: 'Compose Email',
            LBL_NO_ACCESS: 'You do not have access'
        },
        accounts: {
            LBL_ID: 'ID',
            LBL_NAME: 'Name:',
            LBL_LIST_NAME: 'Name',
            LNK_ACCOUNT_LIST: 'View Accounts',
            LNK_NEW_ACCOUNT: 'Create Account',
            LBL_MODULE_NAME: 'Accounts',
            LBL_MODULE_TITLE: 'Accounts: Home',
            LBL_MODULE_ID: 'Accounts',
            LBL_NEW_FORM_TITLE: 'New Account',
            LNK_IMPORT_ACCOUNTS: 'Import Accounts',
            LBL_WEBSITE: 'WebSite',
            LBL_ANY_PHONE: 'Phone',
            LBL_ANY_EMAIL: 'Email',
            LBL_ANY_ADDRESS: 'Address',
            LBL_CITY: 'City',
            LBL_STATE: 'State',
            LBL_POSTAL_CODE: 'Postal Code',
            LBL_COUNTRY: 'Country',
            LBL_TYPE: 'Type',
            LBL_INDUSTRY: 'Industry',
            LBL_ASSIGNED_TO: 'Assigned To',
            LBL_NEW: 'New',
            LBL_EDIT: 'Edit',
            LBL_HISTORY: 'History',
            LBL_SAVE_BUTTON_LABEL: 'Save',
            LBL_CANCEL: 'Cancel',
            LBL_OUT_OF: 'Out of',
            LBL_POSITION: 'Position',
            LBL_SIZE_ANALYSIS: 'Size Analysis',
            LBL_AMOUNT: 'Amount'
        },
    }
};


class LanguageRecordGQLSpy extends EntityGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, id: string, metadata: { fields: string[] }): Observable<any> {
        if (module === 'appStrings') {

            return of({
                data: {
                    appStrings: {
                        _id: 'en_us',
                        items: languageMockData.appStrings
                    }
                }
            }).pipe(shareReplay());
        }

        if (module === 'appListStrings') {
            return of({
                data: {
                    appListStrings: {
                        _id: 'en_us',
                        items: languageMockData.appListStrings
                    }
                }
            }).pipe(shareReplay());
        }

        if (module === 'modStrings') {
            return of({
                data: {
                    modStrings: {
                        _id: 'en_us',
                        items: languageMockData.modStrings
                    }
                }
            }).pipe(shareReplay());
        }
    }
}

export const languageStoreMock = new LanguageStore(new LanguageRecordGQLSpy(), appStateStoreMock, localStorageServiceMock);
languageStoreMock.load('en_us', languageStoreMock.getAvailableStringsTypes()).pipe(take(1)).subscribe();

