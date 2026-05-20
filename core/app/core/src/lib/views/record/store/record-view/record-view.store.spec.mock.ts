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

import {Observable, of} from 'rxjs';
import {shareReplay, take} from 'rxjs/operators';
import {deepClone} from '../../../../common/utils/object-utils';
import {Record} from '../../../../common/record/record.model';
import {RecordMapperRegistry} from '../../../../common/record/record-mappers/record-mapper.registry';
import {StatisticsMap, StatisticsQueryMap} from '../../../../common/statistics/statistics.model';
import {navigationMock} from '../../../../store/navigation/navigation.store.spec.mock';
import {RecordViewStore} from './record-view.store';
import {mockModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {localStorageServiceMock} from '../../../../services/local-storage/local-storage.service.spec.mock';
import {subpanelFactoryMock} from '../../../../containers/subpanel/store/subpanel/subpanel.store.spec.mock';
import {recordManagerMock} from '../../../../services/record/record.manager.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {messageServiceMock} from '../../../../services/message/message.service.spec.mock';
import {StatisticsBatch} from '../../../../store/statistics/statistics-batch.service';
import {StatisticsFetchGQL} from '../../../../store/statistics/graphql/api.statistics.get';
import {RecordStoreFactory} from '../../../../store/record/record.store.factory';
import {BaseSaveRecordMapper} from '../../../../store/record/record-mappers/base-save.record-mapper';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const recordViewMockData = {
    getRecordView: {
        id: '/docroot/api/record/c4da5f04-2d4a-7a14-35ff-5f242b8f8a52',
        record: {
            date_entered: null,
            date_modified: null,
            modified_user_id: null,
            assigned_user_id: null,
            annual_revenue: null,
            billing_address_street: null,
            billing_address_city: null,
            billing_address_state: null,
            billing_address_country: null,
            billing_address_postalcode: null,
            billing_address_street_2: null,
            billing_address_street_3: null,
            billing_address_street_4: null,
            description: null,
            email1: null,
            email2: null,
            email_opt_out: null,
            invalid_email: null,
            employees: null,
            id: 'c4da5f04-2d4a-7a14-35ff-5f242b8f8a52',
            industry: null,
            name: 'Some name',
            ownership: null,
            parent_id: null,
            phone_alternate: null,
            phone_fax: null,
            phone_office: null,
            rating: null,
            shipping_address_street: null,
            shipping_address_city: null,
            shipping_address_state: null,
            shipping_address_country: null,
            shipping_address_postalcode: null,
            shipping_address_street_2: null,
            shipping_address_street_3: null,
            shipping_address_street_4: null,
            campaign_id: null,
            sic_code: null,
            ticker_symbol: null,
            account_type: null,
            website: null,
            custom_fields: [],
            created_by: null,
            created_by_name: null,
            modified_by_name: null,
            opportunity_id: null,
            case_id: null,
            contact_id: null,
            task_id: null,
            note_id: null,
            meeting_id: null,
            call_id: null,
            email_id: null,
            member_id: null,
            parent_name: null,
            assigned_user_name: null,
            account_id: '',
            account_name: '',
            bug_id: '',
            module_dir: 'Accounts',
            emailAddress: {
                id: null
            },
            table_name: 'accounts',
            object_name: 'Account',
            importable: true,
            new_schema: true,
            additional_column_fields: [
                'assigned_user_name',
                'assigned_user_id',
                'opportunity_id',
                'bug_id',
                'case_id',
                'contact_id',
                'task_id',
                'note_id',
                'meeting_id',
                'call_id',
                'email_id',
                'parent_name',
                'member_id'
            ],
            relationship_fields: {
                opportunity_id: 'opportunities',
                bug_id: 'bugs',
                case_id: 'cases',
                contact_id: 'contacts',
                task_id: 'tasks',
                note_id: 'notes',
                meeting_id: 'meetings',
                call_id: 'calls',
                email_id: 'emails',
                member_id: 'members',
                project_id: 'project'
            },
            push_billing: null,
            push_shipping: null,
            db: [],
            new_with_id: false,
            disable_vardefs: false,
            new_assigned_user_name: null,
            processed_dates_times: [],
            process_save_dates: true,
            save_from_post: true,
            duplicates_found: false,
            deleted: 0,
            update_date_modified: true,
            update_modified_by: true,
            update_date_entered: false,
            set_created_by: true,
            ungreedy_count: false,
            module_name: 'Accounts',
            field_defs: {
                id: {
                    name: 'id',
                    vname: 'LBL_ID',
                    type: 'id',
                    required: true,
                    reportable: true,
                    comment: 'Unique identifier',
                    inline_edit: false
                },
                name: {
                    name: 'name',
                    type: 'name',
                    dbType: 'varchar',
                    vname: 'LBL_NAME',
                    len: 150,
                    comment: 'Name of the Company',
                    unified_search: true,
                    full_text_search: {
                        boost: 3
                    },
                    audited: true,
                    required: true,
                    importable: 'required',
                    merge_filter: 'selected'
                },
                date_entered: {
                    name: 'date_entered',
                    vname: 'LBL_DATE_ENTERED',
                    type: 'datetime',
                    group: 'created_by_name',
                    comment: 'Date record created',
                    enable_range_search: true,
                    options: 'date_range_search_dom',
                    inline_edit: false
                },
                date_modified: {
                    name: 'date_modified',
                    vname: 'LBL_DATE_MODIFIED',
                    type: 'datetime',
                    group: 'modified_by_name',
                    comment: 'Date record last modified',
                    enable_range_search: true,
                    options: 'date_range_search_dom',
                    inline_edit: false
                },
                modified_user_id: {
                    name: 'modified_user_id',
                    rname: 'user_name',
                    id_name: 'modified_user_id',
                    vname: 'LBL_MODIFIED',
                    type: 'assigned_user_name',
                    table: 'users',
                    isnull: 'false',
                    group: 'modified_by_name',
                    dbType: 'id',
                    reportable: true,
                    comment: 'User who last modified record',
                    massupdate: false,
                    inline_edit: false
                },
                modified_by_name: {
                    name: 'modified_by_name',
                    vname: 'LBL_MODIFIED_NAME',
                    type: 'relate',
                    reportable: false,
                    source: 'non-db',
                    rname: 'user_name',
                    table: 'users',
                    id_name: 'modified_user_id',
                    module: 'Users',
                    link: 'modified_user_link',
                    duplicate_merge: 'disabled',
                    massupdate: false,
                    inline_edit: false
                },
                created_by: {
                    name: 'created_by',
                    rname: 'user_name',
                    id_name: 'modified_user_id',
                    vname: 'LBL_CREATED',
                    type: 'assigned_user_name',
                    table: 'users',
                    isnull: 'false',
                    dbType: 'id',
                    group: 'created_by_name',
                    comment: 'User who created record',
                    massupdate: false,
                    inline_edit: false
                },
                created_by_name: {
                    name: 'created_by_name',
                    vname: 'LBL_CREATED',
                    type: 'relate',
                    reportable: false,
                    link: 'created_by_link',
                    rname: 'user_name',
                    source: 'non-db',
                    table: 'users',
                    id_name: 'created_by',
                    module: 'Users',
                    duplicate_merge: 'disabled',
                    importable: 'false',
                    massupdate: false,
                    inline_edit: false
                },
                description: {
                    name: 'description',
                    vname: 'LBL_DESCRIPTION',
                    type: 'text',
                    comment: 'Full text of the note',
                    rows: 6,
                    cols: 80
                },
                deleted: {
                    name: 'deleted',
                    vname: 'LBL_DELETED',
                    type: 'bool',
                    default: '0',
                    reportable: false,
                    comment: 'Record deletion indicator'
                },
                created_by_link: {
                    name: 'created_by_link',
                    type: 'link',
                    relationship: 'accounts_created_by',
                    vname: 'LBL_CREATED_BY_USER',
                    link_type: 'one',
                    module: 'Users',
                    bean_name: 'User',
                    source: 'non-db'
                },
                modified_user_link: {
                    name: 'modified_user_link',
                    type: 'link',
                    relationship: 'accounts_modified_user',
                    vname: 'LBL_MODIFIED_BY_USER',
                    link_type: 'one',
                    module: 'Users',
                    bean_name: 'User',
                    source: 'non-db'
                },
                assigned_user_id: {
                    name: 'assigned_user_id',
                    rname: 'user_name',
                    id_name: 'assigned_user_id',
                    vname: 'LBL_ASSIGNED_TO_ID',
                    group: 'assigned_user_name',
                    type: 'relate',
                    table: 'users',
                    module: 'Users',
                    reportable: true,
                    isnull: 'false',
                    dbType: 'id',
                    audited: true,
                    comment: 'User ID assigned to record',
                    duplicate_merge: 'disabled'
                },
                assigned_user_name: {
                    name: 'assigned_user_name',
                    link: 'assigned_user_link',
                    vname: 'LBL_ASSIGNED_TO_NAME',
                    rname: 'user_name',
                    type: 'relate',
                    reportable: false,
                    source: 'non-db',
                    table: 'users',
                    id_name: 'assigned_user_id',
                    module: 'Users',
                    duplicate_merge: 'disabled'
                },
                assigned_user_link: {
                    name: 'assigned_user_link',
                    type: 'link',
                    relationship: 'accounts_assigned_user',
                    vname: 'LBL_ASSIGNED_TO_USER',
                    link_type: 'one',
                    module: 'Users',
                    bean_name: 'User',
                    source: 'non-db',
                    duplicate_merge: 'enabled',
                    rname: 'user_name',
                    id_name: 'assigned_user_id',
                    table: 'users'
                },
                SecurityGroups: {
                    name: 'SecurityGroups',
                    type: 'link',
                    relationship: 'securitygroups_accounts',
                    module: 'SecurityGroups',
                    bean_name: 'SecurityGroup',
                    source: 'non-db',
                    vname: 'LBL_SECURITYGROUPS'
                },
                account_type: {
                    name: 'account_type',
                    vname: 'LBL_TYPE',
                    type: 'enum',
                    options: 'account_type_dom',
                    len: 50,
                    comment: 'The Company is of this type'
                },
                industry: {
                    name: 'industry',
                    vname: 'LBL_INDUSTRY',
                    type: 'enum',
                    options: 'industry_dom',
                    len: 50,
                    comment: 'The company belongs in this industry',
                    merge_filter: 'enabled'
                },
                annual_revenue: {
                    name: 'annual_revenue',
                    vname: 'LBL_ANNUAL_REVENUE',
                    type: 'varchar',
                    len: 100,
                    comment: 'Annual revenue for this company',
                    merge_filter: 'enabled'
                },
                phone_fax: {
                    name: 'phone_fax',
                    vname: 'LBL_FAX',
                    type: 'phone',
                    dbType: 'varchar',
                    len: 100,
                    unified_search: true,
                    full_text_search: {
                        boost: 1
                    },
                    comment: 'The fax phone number of this company'
                },
                billing_address_street: {
                    name: 'billing_address_street',
                    vname: 'LBL_BILLING_ADDRESS_STREET',
                    type: 'varchar',
                    len: '150',
                    comment: 'The street address used for billing address',
                    group: 'billing_address',
                    merge_filter: 'enabled'
                },
                billing_address_street_2: {
                    name: 'billing_address_street_2',
                    vname: 'LBL_BILLING_ADDRESS_STREET_2',
                    type: 'varchar',
                    len: '150',
                    source: 'non-db'
                },
                billing_address_street_3: {
                    name: 'billing_address_street_3',
                    vname: 'LBL_BILLING_ADDRESS_STREET_3',
                    type: 'varchar',
                    len: '150',
                    source: 'non-db'
                },
                billing_address_street_4: {
                    name: 'billing_address_street_4',
                    vname: 'LBL_BILLING_ADDRESS_STREET_4',
                    type: 'varchar',
                    len: '150',
                    source: 'non-db'
                },
                billing_address_city: {
                    name: 'billing_address_city',
                    vname: 'LBL_BILLING_ADDRESS_CITY',
                    type: 'varchar',
                    len: '100',
                    comment: 'The city used for billing address',
                    group: 'billing_address',
                    merge_filter: 'enabled'
                },
                billing_address_state: {
                    name: 'billing_address_state',
                    vname: 'LBL_BILLING_ADDRESS_STATE',
                    type: 'varchar',
                    len: '100',
                    group: 'billing_address',
                    comment: 'The state used for billing address',
                    merge_filter: 'enabled'
                },
                billing_address_postalcode: {
                    name: 'billing_address_postalcode',
                    vname: 'LBL_BILLING_ADDRESS_POSTALCODE',
                    type: 'varchar',
                    len: '20',
                    group: 'billing_address',
                    comment: 'The postal code used for billing address',
                    merge_filter: 'enabled'
                },
                billing_address_country: {
                    name: 'billing_address_country',
                    vname: 'LBL_BILLING_ADDRESS_COUNTRY',
                    type: 'varchar',
                    group: 'billing_address',
                    comment: 'The country used for the billing address',
                    merge_filter: 'enabled'
                },
                rating: {
                    name: 'rating',
                    vname: 'LBL_RATING',
                    type: 'varchar',
                    len: 100,
                    comment: 'An arbitrary rating for this company for use in comparisons with others'
                },
                phone_office: {
                    name: 'phone_office',
                    vname: 'LBL_PHONE_OFFICE',
                    type: 'phone',
                    dbType: 'varchar',
                    len: 100,
                    audited: true,
                    unified_search: true,
                    full_text_search: {
                        boost: 1
                    },
                    comment: 'The office phone number',
                    merge_filter: 'enabled'
                },
                phone_alternate: {
                    name: 'phone_alternate',
                    vname: 'LBL_PHONE_ALT',
                    type: 'phone',
                    group: 'phone_office',
                    dbType: 'varchar',
                    len: 100,
                    unified_search: true,
                    full_text_search: {
                        boost: 1
                    },
                    comment: 'An alternate phone number',
                    merge_filter: 'enabled'
                },
                website: {
                    name: 'website',
                    vname: 'LBL_WEBSITE',
                    type: 'url',
                    dbType: 'varchar',
                    len: 255,
                    comment: 'URL of website for the company'
                },
                ownership: {
                    name: 'ownership',
                    vname: 'LBL_OWNERSHIP',
                    type: 'varchar',
                    len: 100,
                    comment: ''
                },
                employees: {
                    name: 'employees',
                    vname: 'LBL_EMPLOYEES',
                    type: 'varchar',
                    len: 10,
                    comment: 'Number of employees, varchar to accomodate for both number (100) or range (50-100)'
                },
                ticker_symbol: {
                    name: 'ticker_symbol',
                    vname: 'LBL_TICKER_SYMBOL',
                    type: 'varchar',
                    len: 10,
                    comment: 'The stock trading (ticker) symbol for the company',
                    merge_filter: 'enabled'
                },
                shipping_address_street: {
                    name: 'shipping_address_street',
                    vname: 'LBL_SHIPPING_ADDRESS_STREET',
                    type: 'varchar',
                    len: 150,
                    group: 'shipping_address',
                    comment: 'The street address used for for shipping purposes',
                    merge_filter: 'enabled'
                },
                shipping_address_street_2: {
                    name: 'shipping_address_street_2',
                    vname: 'LBL_SHIPPING_ADDRESS_STREET_2',
                    type: 'varchar',
                    len: 150,
                    source: 'non-db'
                },
                shipping_address_street_3: {
                    name: 'shipping_address_street_3',
                    vname: 'LBL_SHIPPING_ADDRESS_STREET_3',
                    type: 'varchar',
                    len: 150,
                    source: 'non-db'
                },
                shipping_address_street_4: {
                    name: 'shipping_address_street_4',
                    vname: 'LBL_SHIPPING_ADDRESS_STREET_4',
                    type: 'varchar',
                    len: 150,
                    source: 'non-db'
                },
                shipping_address_city: {
                    name: 'shipping_address_city',
                    vname: 'LBL_SHIPPING_ADDRESS_CITY',
                    type: 'varchar',
                    len: 100,
                    group: 'shipping_address',
                    comment: 'The city used for the shipping address',
                    merge_filter: 'enabled'
                },
                shipping_address_state: {
                    name: 'shipping_address_state',
                    vname: 'LBL_SHIPPING_ADDRESS_STATE',
                    type: 'varchar',
                    len: 100,
                    group: 'shipping_address',
                    comment: 'The state used for the shipping address',
                    merge_filter: 'enabled'
                },
                shipping_address_postalcode: {
                    name: 'shipping_address_postalcode',
                    vname: 'LBL_SHIPPING_ADDRESS_POSTALCODE',
                    type: 'varchar',
                    len: 20,
                    group: 'shipping_address',
                    comment: 'The zip code used for the shipping address',
                    merge_filter: 'enabled'
                },
                shipping_address_country: {
                    name: 'shipping_address_country',
                    vname: 'LBL_SHIPPING_ADDRESS_COUNTRY',
                    type: 'varchar',
                    group: 'shipping_address',
                    comment: 'The country used for the shipping address',
                    merge_filter: 'enabled'
                },
                email1: {
                    name: 'email1',
                    vname: 'LBL_EMAIL',
                    group: 'email1',
                    type: 'varchar',
                    function: {
                        name: 'getEmailAddressWidget',
                        returns: 'html'
                    },
                    source: 'non-db',
                    studio: {
                        editField: true,
                        searchview: false
                    },
                    full_text_search: {
                        boost: 3,
                        analyzer: 'whitespace'
                    }
                },
                email_addresses_primary: {
                    name: 'email_addresses_primary',
                    type: 'link',
                    relationship: 'accounts_email_addresses_primary',
                    source: 'non-db',
                    vname: 'LBL_EMAIL_ADDRESS_PRIMARY',
                    duplicate_merge: 'disabled',
                    studio: {
                        formula: false
                    }
                },
                email_addresses: {
                    name: 'email_addresses',
                    type: 'link',
                    relationship: 'accounts_email_addresses',
                    module: 'EmailAddress',
                    source: 'non-db',
                    vname: 'LBL_EMAIL_ADDRESSES',
                    reportable: false,
                    unified_search: true,
                    rel_fields: {
                        primary_address: {
                            type: 'bool'
                        }
                    },
                    studio: {
                        formula: false
                    }
                },
                email_addresses_non_primary: {
                    name: 'email_addresses_non_primary',
                    type: 'email',
                    source: 'non-db',
                    vname: 'LBL_EMAIL_NON_PRIMARY',
                    studio: false,
                    reportable: false,
                    massupdate: false
                },
                parent_id: {
                    name: 'parent_id',
                    vname: 'LBL_PARENT_ACCOUNT_ID',
                    type: 'id',
                    required: false,
                    reportable: false,
                    audited: true,
                    comment: 'Account ID of the parent of this account'
                },
                sic_code: {
                    name: 'sic_code',
                    vname: 'LBL_SIC_CODE',
                    type: 'varchar',
                    len: 10,
                    comment: 'SIC code of the account'
                },
                parent_name: {
                    name: 'parent_name',
                    rname: 'name',
                    id_name: 'parent_id',
                    vname: 'LBL_MEMBER_OF',
                    type: 'relate',
                    isnull: 'true',
                    module: 'Accounts',
                    table: 'accounts',
                    massupdate: false,
                    source: 'non-db',
                    len: 36,
                    link: 'member_of',
                    unified_search: true,
                    importable: 'true'
                },
                members: {
                    name: 'members',
                    type: 'link',
                    relationship: 'member_accounts',
                    module: 'Accounts',
                    bean_name: 'Account',
                    source: 'non-db',
                    vname: 'LBL_MEMBERS'
                },
                member_of: {
                    name: 'member_of',
                    type: 'link',
                    relationship: 'member_accounts',
                    module: 'Accounts',
                    bean_name: 'Account',
                    link_type: 'one',
                    source: 'non-db',
                    vname: 'LBL_MEMBER_OF',
                    side: 'right'
                },
                email_opt_out: {
                    name: 'email_opt_out',
                    vname: 'LBL_EMAIL_OPT_OUT',
                    source: 'non-db',
                    type: 'bool',
                    massupdate: false,
                    studio: 'false'
                },
                invalid_email: {
                    name: 'invalid_email',
                    vname: 'LBL_INVALID_EMAIL',
                    source: 'non-db',
                    type: 'bool',
                    massupdate: false,
                    studio: 'false'
                },
                cases: {
                    name: 'cases',
                    type: 'link',
                    relationship: 'account_cases',
                    module: 'Cases',
                    bean_name: 'aCase',
                    source: 'non-db',
                    vname: 'LBL_CASES'
                },
                email: {
                    name: 'email',
                    type: 'email',
                    query_type: 'default',
                    source: 'non-db',
                    operator: 'subquery',
                    db_field: [
                        'id'
                    ],
                    vname: 'LBL_ANY_EMAIL',
                    studio: {
                        visible: false,
                        searchview: true
                    },
                    importable: false
                },
                tasks: {
                    name: 'tasks',
                    type: 'link',
                    relationship: 'account_tasks',
                    module: 'Tasks',
                    bean_name: 'Task',
                    source: 'non-db',
                    vname: 'LBL_TASKS'
                },
                notes: {
                    name: 'notes',
                    type: 'link',
                    relationship: 'account_notes',
                    module: 'Notes',
                    bean_name: 'Note',
                    source: 'non-db',
                    vname: 'LBL_NOTES'
                },
                meetings: {
                    name: 'meetings',
                    type: 'link',
                    relationship: 'account_meetings',
                    module: 'Meetings',
                    bean_name: 'Meeting',
                    source: 'non-db',
                    vname: 'LBL_MEETINGS'
                },
                calls: {
                    name: 'calls',
                    type: 'link',
                    relationship: 'account_calls',
                    module: 'Calls',
                    bean_name: 'Call',
                    source: 'non-db',
                    vname: 'LBL_CALLS'
                },
                emails: {
                    name: 'emails',
                    type: 'link',
                    relationship: 'emails_accounts_rel',
                    module: 'Emails',
                    bean_name: 'Email',
                    source: 'non-db',
                    vname: 'LBL_EMAILS',
                    studio: {
                        formula: false
                    }
                },
                documents: {
                    name: 'documents',
                    type: 'link',
                    relationship: 'documents_accounts',
                    source: 'non-db',
                    vname: 'LBL_DOCUMENTS_SUBPANEL_TITLE'
                },
                bugs: {
                    name: 'bugs',
                    type: 'link',
                    relationship: 'accounts_bugs',
                    module: 'Bugs',
                    bean_name: 'Bug',
                    source: 'non-db',
                    vname: 'LBL_BUGS'
                },
                contacts: {
                    name: 'contacts',
                    type: 'link',
                    relationship: 'accounts_contacts',
                    module: 'Contacts',
                    bean_name: 'Contact',
                    source: 'non-db',
                    vname: 'LBL_CONTACTS'
                },
                opportunities: {
                    name: 'opportunities',
                    type: 'link',
                    relationship: 'accounts_opportunities',
                    module: 'Opportunities',
                    bean_name: 'Opportunity',
                    source: 'non-db',
                    vname: 'LBL_OPPORTUNITY'
                },
                project: {
                    name: 'project',
                    type: 'link',
                    relationship: 'projects_accounts',
                    module: 'Project',
                    bean_name: 'Project',
                    source: 'non-db',
                    vname: 'LBL_PROJECTS'
                },
                leads: {
                    name: 'leads',
                    type: 'link',
                    relationship: 'account_leads',
                    module: 'Leads',
                    bean_name: 'Lead',
                    source: 'non-db',
                    vname: 'LBL_LEADS'
                },
                campaigns: {
                    name: 'campaigns',
                    type: 'link',
                    relationship: 'account_campaign_log',
                    module: 'CampaignLog',
                    bean_name: 'CampaignLog',
                    source: 'non-db',
                    vname: 'LBL_CAMPAIGNLOG',
                    studio: {
                        formula: false
                    }
                },
                campaign_accounts: {
                    name: 'campaign_accounts',
                    type: 'link',
                    vname: 'LBL_CAMPAIGNS',
                    relationship: 'campaign_accounts',
                    source: 'non-db'
                },
                campaign_id: {
                    name: 'campaign_id',
                    comment: 'Campaign that generated Account',
                    vname: 'LBL_CAMPAIGN_ID',
                    rname: 'id',
                    id_name: 'campaign_id',
                    type: 'id',
                    table: 'campaigns',
                    isnull: 'true',
                    module: 'Campaigns',
                    reportable: false,
                    massupdate: false,
                    duplicate_merge: 'disabled'
                },
                campaign_name: {
                    name: 'campaign_name',
                    rname: 'name',
                    vname: 'LBL_CAMPAIGN',
                    type: 'relate',
                    reportable: false,
                    source: 'non-db',
                    table: 'campaigns',
                    id_name: 'campaign_id',
                    link: 'campaign_accounts',
                    module: 'Campaigns',
                    duplicate_merge: 'disabled',
                    comment: 'The first campaign name for Account (Meta-data only)'
                },
                prospect_lists: {
                    name: 'prospect_lists',
                    type: 'link',
                    relationship: 'prospect_list_accounts',
                    module: 'ProspectLists',
                    source: 'non-db',
                    vname: 'LBL_PROSPECT_LIST'
                },
                aos_quotes: {
                    name: 'aos_quotes',
                    vname: 'LBL_AOS_QUOTES',
                    type: 'link',
                    relationship: 'account_aos_quotes',
                    module: 'AOS_Quotes',
                    bean_name: 'AOS_Quotes',
                    source: 'non-db'
                },
                aos_invoices: {
                    name: 'aos_invoices',
                    vname: 'LBL_AOS_INVOICES',
                    type: 'link',
                    relationship: 'account_aos_invoices',
                    module: 'AOS_Invoices',
                    bean_name: 'AOS_Invoices',
                    source: 'non-db'
                },
                aos_contracts: {
                    name: 'aos_contracts',
                    vname: 'LBL_AOS_CONTRACTS',
                    type: 'link',
                    relationship: 'account_aos_contracts',
                    module: 'AOS_Contracts',
                    bean_name: 'AOS_Contracts',
                    source: 'non-db'
                },
                jjwg_maps_address_c: {
                    required: false,
                    source: 'custom_fields',
                    name: 'jjwg_maps_address_c',
                    vname: 'LBL_JJWG_MAPS_ADDRESS',
                    type: 'varchar',
                    massupdate: '0',
                    default: null,
                    no_default: false,
                    comments: 'Address',
                    help: 'Address',
                    importable: 'true',
                    duplicate_merge: 'disabled',
                    duplicate_merge_dom_value: '0',
                    audited: false,
                    inline_edit: true,
                    reportable: true,
                    unified_search: false,
                    merge_filter: 'disabled',
                    len: '255',
                    size: '20',
                    id: 'Accountsjjwg_maps_address_c',
                    custom_module: 'Accounts'
                },
                jjwg_maps_geocode_status_c: {
                    required: false,
                    source: 'custom_fields',
                    name: 'jjwg_maps_geocode_status_c',
                    vname: 'LBL_JJWG_MAPS_GEOCODE_STATUS',
                    type: 'varchar',
                    massupdate: '0',
                    default: null,
                    no_default: false,
                    comments: 'Geocode Status',
                    help: 'Geocode Status',
                    importable: 'true',
                    duplicate_merge: 'disabled',
                    duplicate_merge_dom_value: '0',
                    audited: false,
                    inline_edit: true,
                    reportable: true,
                    unified_search: false,
                    merge_filter: 'disabled',
                    len: '255',
                    size: '20',
                    id: 'Accountsjjwg_maps_geocode_status_c',
                    custom_module: 'Accounts'
                },
                jjwg_maps_lat_c: {
                    required: false,
                    source: 'custom_fields',
                    name: 'jjwg_maps_lat_c',
                    vname: 'LBL_JJWG_MAPS_LAT',
                    type: 'float',
                    massupdate: '0',
                    default: '0.00000000',
                    no_default: false,
                    comments: '',
                    help: 'Latitude',
                    importable: 'true',
                    duplicate_merge: 'disabled',
                    duplicate_merge_dom_value: '0',
                    audited: false,
                    inline_edit: true,
                    reportable: true,
                    unified_search: false,
                    merge_filter: 'disabled',
                    len: '10',
                    size: '20',
                    enable_range_search: false,
                    precision: '8',
                    id: 'Accountsjjwg_maps_lat_c',
                    custom_module: 'Accounts'
                },
                jjwg_maps_lng_c: {
                    required: false,
                    source: 'custom_fields',
                    name: 'jjwg_maps_lng_c',
                    vname: 'LBL_JJWG_MAPS_LNG',
                    type: 'float',
                    massupdate: '0',
                    default: '0.00000000',
                    no_default: false,
                    comments: '',
                    help: 'Longitude',
                    importable: 'true',
                    duplicate_merge: 'disabled',
                    duplicate_merge_dom_value: '0',
                    audited: false,
                    inline_edit: true,
                    reportable: true,
                    unified_search: false,
                    merge_filter: 'disabled',
                    len: '11',
                    size: '20',
                    enable_range_search: false,
                    precision: '8',
                    id: 'Accountsjjwg_maps_lng_c',
                    custom_module: 'Accounts'
                }
            },
            column_fields: [
                'id',
                'name',
                'date_entered',
                'date_modified',
                'modified_user_id',
                'modified_by_name',
                'created_by',
                'created_by_name',
                'description',
                'deleted',
                'created_by_link',
                'modified_user_link',
                'assigned_user_id',
                'assigned_user_name',
                'assigned_user_link',
                'SecurityGroups',
                'account_type',
                'industry',
                'annual_revenue',
                'phone_fax',
                'billing_address_street',
                'billing_address_street_2',
                'billing_address_street_3',
                'billing_address_street_4',
                'billing_address_city',
                'billing_address_state',
                'billing_address_postalcode',
                'billing_address_country',
                'rating',
                'phone_office',
                'phone_alternate',
                'website',
                'ownership',
                'employees',
                'ticker_symbol',
                'shipping_address_street',
                'shipping_address_street_2',
                'shipping_address_street_3',
                'shipping_address_street_4',
                'shipping_address_city',
                'shipping_address_state',
                'shipping_address_postalcode',
                'shipping_address_country',
                'email1',
                'email_addresses_primary',
                'email_addresses',
                'email_addresses_non_primary',
                'parent_id',
                'sic_code',
                'parent_name',
                'members',
                'member_of',
                'email_opt_out',
                'invalid_email',
                'cases',
                'email',
                'tasks',
                'notes',
                'meetings',
                'calls',
                'emails',
                'documents',
                'bugs',
                'contacts',
                'opportunities',
                'project',
                'leads',
                'campaigns',
                'campaign_accounts',
                'campaign_id',
                'campaign_name',
                'prospect_lists',
                'aos_quotes',
                'aos_invoices',
                'aos_contracts',
                'jjwg_maps_address_c',
                'jjwg_maps_geocode_status_c',
                'jjwg_maps_lat_c',
                'jjwg_maps_lng_c'
            ],
            list_fields: [],
            current_notify_user: null,
            fetched_row: false,
            fetched_rel_row: [],
            layout_def: null,
            force_load_details: false,
            optimistic_lock: true,
            disable_custom_fields: false,
            number_formatting_done: false,
            process_field_encrypted: false,
            acltype: 'module',
            additional_meta_fields: [],
            notify_inworkflow: null,
            special_notification: false,
            in_workflow: false,
            tracker_visibility: true,
            listview_inner_join: [],
            in_import: false,
            in_save: null,
            logicHookDepth: null,
            '\u0000*\u0000max_logic_depth': 10,
            '\u0000*\u0000loaded_relationships': [],
            '\u0000*\u0000is_updated_dependent_fields': false,
            acl_category: null,
            old_modified_by_name: null,
            required_fields: {
                id: 1,
                name: 1
            },
            added_custom_field_defs: true,
            acl_fields: true,
            jjwg_maps_lat_c: '0.00000000',
            jjwg_maps_lng_c: '0.00000000',
            editable: true
        },
        _id: 'c4da5f04-2d4a-7a14-35ff-5f242b8f8a52'
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class RecordViewGQLSpy extends RecordFetchGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, record: string, metadata: { fields: string[] }): Observable<any> {

        const data = {
            getRecordView: deepClone(recordViewMockData.getRecordView)
        };

        return of(data);
    }
}

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class RecordSaveGQLSpy extends RecordSaveGQL {

    constructor() {
        super(null);
    }

    public save(record: Record): Observable<any> {

        return of({
            id: record.id,
            module: record.module,
            attributes: record.attributes
        });
    }
}

class StatisticsFetchGQLSpy extends StatisticsFetchGQL {
    constructor() {
        super(null);
    }

    public fetch(
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        module: string,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        queries: StatisticsQueryMap,
    ): Observable<StatisticsMap> {
        return of({
            history: {
                id: 'history',
                data: {
                    type: 'date',
                    value: '2020-09-23'
                }
            }
        }).pipe(shareReplay(1));
    }
}

const mockRecordStoreFactory = new RecordStoreFactory(
    new RecordViewGQLSpy(),
    new RecordSaveGQLSpy(),
    messageServiceMock,
    recordManagerMock,
    new RecordMapperRegistry(),
    new BaseSaveRecordMapper()
);

export const recordviewStoreMock = new RecordViewStore(
    new RecordViewGQLSpy(),
    new RecordSaveGQLSpy(),
    appStateStoreMock,
    languageStoreMock,
    navigationMock,
    mockModuleNavigation,
    metadataStoreMock,
    localStorageServiceMock,
    messageServiceMock,
    subpanelFactoryMock,
    recordManagerMock,
    new StatisticsBatch(new StatisticsFetchGQLSpy()),
    mockRecordStoreFactory
);

recordviewStoreMock.init('accounts', 'c4da5f04-2d4a-7a14-35ff-5f242b8f8a52').pipe(take(1)).subscribe();
recordviewStoreMock.module$.pipe(take(1)).subscribe();
recordviewStoreMock.appData$.pipe(take(1)).subscribe();
recordviewStoreMock.vm$.pipe(take(1)).subscribe();
