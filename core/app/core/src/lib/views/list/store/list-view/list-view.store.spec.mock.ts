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

import {take} from 'rxjs/operators';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {listStoreFactoryMock} from '../../../../store/record-list/record-list.store.spec.mock';
import {ListViewStore} from './list-view.store';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {localStorageServiceMock} from '../../../../services/local-storage/local-storage.service.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {messageServiceMock} from '../../../../services/message/message.service.spec.mock';
import {filterListStoreFactoryMock} from '../../../../store/saved-filters/filter-list.store.spec.mock';
import {
    confirmationModalServiceMock,
    ngbModalMock
} from '../../../../services/modals/confirmation-modal.spec.mock';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const listviewMockData = {
    recordList: {
        id: '/docroot/api/records/accounts',
        meta: {
            offsets: {
                current: 10,
                next: 20,
                prev: 0,
                end: 80,
                total: '83',
                totalCounted: true
            },
            ordering: {
                orderBy: 'date_entered',
                sortOrder: 'ASC'
            }
        },
        records: [{
            id: '29319818-dc26-f57d-03e1-5ed77dedd691',
            relationships: [],
            type: 'Account',
            attributes: {
                account_type: '',
                annual_revenue: '',
                aos_contracts: '',
                aos_invoices: '',
                aos_quotes: '',
                assigned_user_id: '',
                assigned_user_link: '',
                assigned_user_name: '',
                billing_address_city: '',
                billing_address_country: '',
                billing_address_postalcode: '',
                billing_address_state: '',
                billing_address_street: '',
                billing_address_street_2: '',
                billing_address_street_3: '',
                billing_address_street_4: '',
                bugs: '',
                calls: '',
                campaign_accounts: '',
                campaign_id: '',
                campaign_name: '',
                campaigns: '',
                cases: '',
                city: '',
                contacts: '',
                created_by: '1',
                created_by_link: '',
                created_by_name: 'admin',
                date_entered: '06/03/2020 10:37',
                date_modified: '06/03/2020 10:37',
                deleted: '0',
                description: '',
                documents: '',
                email: '',
                email1: '',
                email_addresses: '',
                email_addresses_non_primary: '',
                email_addresses_primary: '',
                email_opt_out: '',
                emails: '',
                employees: '',
                encoded_name: 'V8 Api test Account',
                industry: '',
                invalid_email: '',
                jjwg_maps_address_c: '',
                jjwg_maps_geocode_status_c: '',
                jjwg_maps_lat_c: '0.00000000',
                jjwg_maps_lng_c: '0.00000000',
                leads: '',
                meetings: '',
                member_of: '',
                members: '',
                modified_by_name: 'admin',
                modified_user_id: '1',
                modified_user_link: '',
                name: 'V8 Api test Account',
                notes: '',
                opportunities: '',
                ownership: '',
                parent_id: '',
                parent_name: '',
                phone_alternate: '',
                phone_fax: '',
                phone_office: '',
                project: '',
                prospect_lists: '',
                rating: '',
                securitygroups: '',
                shipping_address_city: '',
                shipping_address_country: '',
                shipping_address_postalcode: '',
                shipping_address_state: '',
                shipping_address_street: '',
                shipping_address_street_2: '',
                shipping_address_street_3: '',
                shipping_address_street_4: '',
                sic_code: '',
                tasks: '',
                ticker_symbol: '',
                website: '',
            }
        }]
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

export const listviewStoreMock = new ListViewStore(
    appStateStoreMock,
    languageStoreMock,
    navigationMock,
    mockModuleNavigation,
    metadataStoreMock,
    localStorageServiceMock,
    messageServiceMock,
    listStoreFactoryMock,
    ngbModalMock,
    filterListStoreFactoryMock,
    confirmationModalServiceMock
);

listviewStoreMock.init('accounts').pipe(take(1)).subscribe();
listviewStoreMock.vm$.pipe(take(1)).subscribe();
