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

import {NavigationStore} from './navigation.store';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {Observable, of} from 'rxjs';
import {shareReplay, take} from 'rxjs/operators';

export const navigationMockData = {
    navbar: {
        groupedTabs: [
            {
                name: 'LBL_TABGROUP_SALES',
                labelKey: 'LBL_TABGROUP_SALES',
                modules: [
                    'accounts',
                    'home',
                ]
            },
            {
                name: 'LBL_TABGROUP_MARKETING',
                labelKey: 'LBL_TABGROUP_MARKETING',
                modules: [
                    'accounts',
                    'home',
                ]
            },
            {
                name: 'LBL_TABGROUP_SUPPORT',
                labelKey: 'LBL_TABGROUP_SUPPORT',
                modules: [
                    'accounts',
                    'home'
                ]
            },
        ],
        tabs: [
            'home',
            'accounts',
        ],
        userActionMenu: [
            {
                name: 'profile',
                labelKey: 'LBL_PROFILE_EDIT',
                url: 'index.php?module=Users&action=EditView&record=1',
                icon: ''
            },
            {
                name: 'employees',
                labelKey: 'LBL_EMPLOYEES',
                url: 'index.php?module=Employees&action=index',
                icon: ''
            },
            {
                name: 'training',
                labelKey: 'LBL_TRAINING',
                url: 'https://community.suitecrm.com',
                icon: ''
            },
            {
                name: 'about',
                labelKey: 'LNK_ABOUT',
                url: 'index.php?module=Home&action=About',
                icon: ''
            },
            {
                name: 'logout',
                labelKey: 'LBL_LOGOUT',
                url: 'index.php?module=Users&action=Logout',
                icon: ''
            }
        ],
        modules: {
            home: {
                path: 'home',
                defaultRoute: './#/home/index',
                name: 'home',
                labelKey: 'Home',
                menu: []
            },
            accounts: {
                path: 'accounts',
                defaultRoute: './#/accounts/index',
                name: 'accounts',
                labelKey: 'Accounts',
                menu: [
                    {
                        name: 'Create',
                        labelKey: 'LNK_NEW_ACCOUNT',
                        url: './#/accounts/edit',
                        icon: 'plus'
                    },
                    {
                        name: 'List',
                        labelKey: 'LNK_ACCOUNT_LIST',
                        url: './#/accounts/index',
                        icon: 'view'
                    },
                    {
                        name: 'Import',
                        labelKey: 'LNK_IMPORT_ACCOUNTS',
                        url: './#/import/step1',
                        icon: 'download'
                    }
                ]
            },
            contacts: {
                path: 'contacts',
                defaultRoute: './#/contacts/index',
                name: 'contacts',
                labelKey: 'Contacts',
                menu: [
                    {
                        name: 'Create',
                        labelKey: 'LNK_NEW_CONTACT',
                        url: './#/contacts/edit',
                        icon: 'plus'
                    },
                    {
                        name: 'Create_Contact_Vcard',
                        labelKey: 'LNK_IMPORT_VCARD',
                        url: './#/contacts/importvcard',
                        icon: 'plus'
                    },
                    {
                        name: 'List',
                        labelKey: 'LNK_CONTACT_LIST',
                        url: './#/contacts/index',
                        icon: 'view'
                    },
                    {
                        name: 'Import',
                        labelKey: 'LNK_IMPORT_CONTACTS',
                        url: './#/import/step1',
                        icon: 'download'
                    }
                ]
            },
            opportunities: {
                path: 'opportunities',
                defaultRoute: './#/opportunities/index',
                name: 'opportunities',
                labelKey: 'Opportunities',
                menu: [
                    {
                        name: 'Create',
                        labelKey: 'LNK_NEW_OPPORTUNITY',
                        url: './#/opportunities/edit',
                        icon: 'plus'
                    },
                    {
                        name: 'List',
                        labelKey: 'LNK_OPPORTUNITY_LIST',
                        url: './#/opportunities/index',
                        icon: 'view'
                    },
                    {
                        name: 'Import',
                        labelKey: 'LNK_IMPORT_OPPORTUNITIES',
                        url: './#/import/step1',
                        icon: 'download'
                    }
                ]
            },
            leads: {
                path: 'leads',
                defaultRoute: './#/leads/index',
                name: 'leads',
                labelKey: 'Leads',
                menu: [
                    {
                        name: 'Create',
                        labelKey: 'LNK_NEW_LEAD',
                        url: './#/leads/edit',
                        icon: 'plus'
                    },
                    {
                        name: 'Create_Lead_Vcard',
                        labelKey: 'LNK_IMPORT_VCARD',
                        url: './#/leads/importvcard',
                        icon: 'plus'
                    },
                    {
                        name: 'List',
                        labelKey: 'LNK_LEAD_LIST',
                        url: './#/leads/index',
                        icon: 'view'
                    },
                    {
                        name: 'Import',
                        labelKey: 'LNK_IMPORT_LEADS',
                        url: './#/import/step1',
                        icon: 'download'
                    }
                ]
            },
        }
    }
};

class NavigationRecordGQLSpy extends EntityGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, id: string, metadata: { fields: string[] }): Observable<any> {

        return of({
            data: {
                navbar: navigationMockData.navbar
            }
        }).pipe(shareReplay());
    }
}

export const navigationMock = new NavigationStore(new NavigationRecordGQLSpy());
navigationMock.load().pipe(take(1)).subscribe();
navigationMock.vm$.pipe(take(1)).subscribe();
