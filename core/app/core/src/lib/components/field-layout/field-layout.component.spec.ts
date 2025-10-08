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

import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';

import {FieldLayoutComponent} from './field-layout.component';
import {Component} from '@angular/core';
import {Panel} from '../../common/metadata/metadata.model';
import {FieldMap} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {deepClone} from '../../common/utils/object-utils';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {LayoutModule} from '@angular/cdk/layout';
import {CommonModule} from '@angular/common';
import {RouterTestingModule} from '@angular/router/testing';
import {By} from '@angular/platform-browser';
import {BehaviorSubject, Observable} from 'rxjs';
import {FieldModule} from '../../fields/field.module';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';
import {moduleNameMapperMock} from '../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {UserPreferenceStore} from '../../store/user-preference/user-preference.store';
import {FieldLayoutConfig, FieldLayoutDataSource} from './field-layout.model';
import {datetimeFormatterMock} from '../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../services/formatters/currency/currency-formatter.service';
import {userPreferenceStoreMock} from '../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../store/language/language.store';
import {numberFormatterMock} from '../../services/formatters/number/number-formatter.spec.mock';
import {ModuleNameMapper} from '../../services/navigation/module-name-mapper/module-name-mapper.service';
import {dateFormatterMock} from '../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../services/formatters/datetime/date-formatter.service';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {currencyFormatterMock} from '../../services/formatters/currency/currency-formatter.service.spec.mock';
import {DatetimeFormatter} from '../../services/formatters/datetime/datetime-formatter.service';
import {NumberFormatter} from '../../services/formatters/number/number-formatter.service';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
const mockConfigData: FieldLayoutConfig = {
    mode: 'detail',
    maxColumns: 2
} as FieldLayoutConfig;
const mockPanelData: Panel = {
    label: 'OVERVIEW',
    key: 'lbl_account_information',
    rows: [
        {
            cols: [
                {
                    name: 'name',
                    label: 'LBL_NAME',
                    comment: 'Name of the Company',
                    fieldDefinition: {
                        name: 'name',
                        type: 'name',
                        dbType: 'varchar',
                        vname: 'LBL_NAME',
                        len: 150,
                        comment: 'Name of the Company',
                        unified_search: true,
                        full_text_search: {boost: 3},
                        audited: true,
                        required: true,
                        importable: 'required',
                        merge_filter: 'selected'
                    },
                    type: 'name'
                },
                {
                    name: 'description',
                    label: 'LBL_DESCRIPTION',
                    comment: 'Full text of the note',
                    fieldDefinition: {
                        name: 'description',
                        vname: 'LBL_DESCRIPTION',
                        type: 'text',
                        comment: 'Full text of the note',
                        rows: 6,
                        cols: 80,
                        required: false
                    },
                    type: 'text'
                }
            ]
        },
        {
            cols: [
                {
                    name: 'assigned_user_name',
                    label: 'LBL_ASSIGNED_TO',
                    fieldDefinition: {
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
                        duplicate_merge: 'disabled',
                        required: false
                    },
                    type: 'relate'
                }
            ]
        }]
} as Panel;

const mockFieldsData = {
    name: {
        type: 'name',
        value: 'op 14',
        metadata: {},
        definition: {
            name: 'name',
            vname: 'LBL_OPPORTUNITY_NAME',
            type: 'name',
            dbType: 'varchar',
            len: '50',
            unified_search: true,
            full_text_search: {boost: 3},
            comment: 'Name of the opportunity',
            merge_filter: 'selected',
            importable: 'required',
            required: true
        },
        labelKey: 'LBL_OPPORTUNITY_NAME',
        label: 'Opportunity Name'
    },
    account_name: {
        type: 'relate',
        metadata: {},
        definition: {
            name: 'account_name',
            rname: 'name',
            id_name: 'account_id',
            vname: 'LBL_ACCOUNT_NAME',
            type: 'relate',
            table: 'accounts',
            join_name: 'accounts',
            isnull: 'true',
            module: 'Accounts',
            dbType: 'varchar',
            link: 'accounts',
            len: '255',
            source: 'non-db',
            unified_search: true,
            required: true,
            importable: 'required'
        },
        labelKey: 'LBL_ACCOUNT_NAME',
        label: 'Account Name'
    },
    amount: {
        type: 'currency',
        value: '8000',
        metadata: {},
        definition: {
            name: 'amount',
            vname: 'LBL_AMOUNT',
            type: 'currency',
            dbType: 'double',
            comment: 'Unconverted amount of the opportunity',
            importable: 'required',
            duplicate_merge: '1',
            required: true,
            options: 'numeric_range_search_dom',
            enable_range_search: true
        },
        labelKey: '{$MOD.LBL_AMOUNT} ({$CURRENCY})',
        label: ''
    },
    date_closed: {
        type: 'date',
        value: '2020-07-13',
        metadata: {},
        definition: {
            name: 'date_closed',
            vname: 'LBL_DATE_CLOSED',
            type: 'date',
            audited: true,
            comment: 'Expected or actual date the oppportunity will close',
            importable: 'required',
            required: true,
            enable_range_search: true,
            options: 'date_range_search_dom'
        },
        labelKey: 'LBL_DATE_CLOSED',
        label: 'Expected Close Date'
    },
    sales_stage: {
        type: 'enum',
        value: 'Qualification',
        metadata: {},
        definition: {
            name: 'sales_stage',
            vname: 'LBL_SALES_STAGE',
            type: 'enum',
            options: 'sales_stage_dom',
            len: '255',
            audited: true,
            comment: 'Indication of progression towards closure',
            merge_filter: 'enabled',
            importable: 'required',
            required: true
        },
        labelKey: 'LBL_SALES_STAGE',
        label: 'Sales Stage'
    },
    opportunity_type: {
        type: 'enum',
        value: '',
        metadata: {},
        definition: {
            name: 'opportunity_type',
            vname: 'LBL_TYPE',
            type: 'enum',
            options: 'opportunity_type_dom',
            len: '255',
            audited: true,
            comment: 'Type of opportunity (ex: Existing, New)',
            merge_filter: 'enabled',
            required: false
        },
        labelKey: 'LBL_TYPE',
        label: 'Type'
    },
    probability: {
        type: 'int',
        value: '20',
        metadata: {},
        definition: {
            name: 'probability',
            vname: 'LBL_PROBABILITY',
            type: 'int',
            dbType: 'double',
            audited: true,
            comment: 'The probability of closure',
            validation: {type: 'range', min: 0, max: 100},
            merge_filter: 'enabled',
            required: false
        },
        labelKey: 'LBL_PROBABILITY',
        label: 'Probability (%)'
    },
    lead_source: {
        type: 'enum',
        value: '',
        metadata: {},
        definition: {
            name: 'lead_source',
            vname: 'LBL_LEAD_SOURCE',
            type: 'enum',
            options: 'lead_source_dom',
            len: '50',
            comment: 'Source of the opportunity',
            merge_filter: 'enabled',
            required: false
        },
        labelKey: 'LBL_LEAD_SOURCE',
        label: 'Lead Source'
    },
    next_step: {
        type: 'varchar',
        value: '',
        metadata: {},
        definition: {
            name: 'next_step',
            vname: 'LBL_NEXT_STEP',
            type: 'varchar',
            len: '100',
            comment: 'The next step in the sales process',
            merge_filter: 'enabled',
            required: false
        },
        labelKey: 'LBL_NEXT_STEP',
        label: 'Next Step'
    },
    campaign_name: {
        type: 'relate',
        metadata: {},
        definition: {
            name: 'campaign_name',
            rname: 'name',
            id_name: 'campaign_id',
            vname: 'LBL_CAMPAIGN',
            type: 'relate',
            link: 'campaign_opportunities',
            isnull: 'true',
            table: 'campaigns',
            module: 'Campaigns',
            source: 'non-db',
            additionalFields: {id: 'campaign_id'},
            required: false
        },
        labelKey: 'LBL_CAMPAIGN',
        label: 'Campaign'
    },
    description: {
        type: 'text',
        value: 'desc 1',
        metadata: {},
        definition: {
            name: 'description',
            vname: 'LBL_DESCRIPTION',
            type: 'text',
            comment: 'Full text of the note',
            rows: 6,
            cols: 80,
            required: false
        },
        labelKey: 'LBL_DESCRIPTION',
        label: 'Description'
    },
    assigned_user_name: {
        type: 'relate',
        metadata: {},
        definition: {
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
            duplicate_merge: 'disabled',
            required: false
        },
        labelKey: 'LBL_ASSIGNED_TO',
        label: 'Assigned to'
    },
    date_modified: {
        type: 'datetime',
        value: '2020-07-31 14.31',
        metadata: {},
        definition: {
            name: 'date_modified',
            vname: 'LBL_DATE_MODIFIED',
            type: 'datetime',
            group: 'modified_by_name',
            comment: 'Date record last modified',
            enable_range_search: true,
            options: 'date_range_search_dom',
            inline_edit: false,
            required: false
        },
        labelKey: 'LBL_DATE_MODIFIED',
        label: 'Date Modified'
    },
    date_entered: {
        type: 'datetime',
        value: '2020-07-31 14.31',
        metadata: {},
        definition: {
            name: 'date_entered',
            vname: 'LBL_DATE_ENTERED',
            type: 'datetime',
            group: 'created_by_name',
            comment: 'Date record created',
            enable_range_search: true,
            options: 'date_range_search_dom',
            inline_edit: false,
            required: false
        },
        labelKey: 'LBL_DATE_ENTERED',
        label: 'Date Created'
    }
} as FieldMap;

const mockRecord = {
    type: 'Account',
    module: 'accounts',
    attributes: {
        date_entered: '2020-09-17 15.22',
        date_modified: '2020-09-17 15.22',
        modified_user_id: '1',
        assigned_user_id: '',
        annual_revenue: '',
        billing_address_street: '',
        billing_address_city: '',
        billing_address_state: '',
        billing_address_country: '',
        billing_address_postalcode: '',
        billing_address_street_2: '',
        billing_address_street_3: '',
        billing_address_street_4: '',
        description: '',
        email1: '',
        email2: null,
        email_opt_out: '',
        invalid_email: '',
        employees: '',
        id: '59418996-1880-8cc2-822a-5f637f84f3c5',
        industry: '',
        name: 'V8 Api test Account',
        ownership: '',
        parent_id: '',
        phone_alternate: '',
        phone_fax: '',
        phone_office: '',
        rating: '',
        shipping_address_street: '',
        shipping_address_city: '',
        shipping_address_state: '',
        shipping_address_country: '',
        shipping_address_postalcode: '',
        shipping_address_street_2: '',
        shipping_address_street_3: '',
        shipping_address_street_4: '',
        campaign_id: '',
        sic_code: '',
        ticker_symbol: '',
        account_type: '',
        website: '',
        custom_fields: [],
        created_by: '1',
        created_by_name: 'admin',
        modified_by_name: 'admin',
        opportunity_id: null,
        case_id: null,
        contact_id: null,
        task_id: null,
        note_id: null,
        meeting_id: null,
        call_id: null,
        email_id: null,
        member_id: null,
        parent_name: '',
        assigned_user_name: '',
        account_id: '',
        account_name: '',
        bug_id: '',
        module_dir: 'Accounts',
        emailAddress: {id: null},
        table_name: 'accounts',
        object_name: 'Account',
        importable: true,
        new_schema: true,
        push_billing: null,
        push_shipping: null,
        db: [],
        new_with_id: false,
        disable_vardefs: false,
        new_assigned_user_name: null,
        processed_dates_times: {
            id: '1',
            name: '1',
            date_entered: '1',
            date_modified: '1',
            modified_user_id: '1',
            modified_by_name: '1',
            created_by: '1',
            created_by_name: '1',
            description: '1',
            deleted: '1',
            created_by_link: '1',
            modified_user_link: '1',
            assigned_user_id: '1',
            assigned_user_name: '1',
            assigned_user_link: '1',
            SecurityGroups: '1',
            account_type: '1',
            industry: '1',
            annual_revenue: '1',
            phone_fax: '1',
            billing_address_street: '1',
            billing_address_street_2: '1',
            billing_address_street_3: '1',
            billing_address_street_4: '1',
            billing_address_city: '1',
            billing_address_state: '1',
            billing_address_postalcode: '1',
            billing_address_country: '1',
            rating: '1',
            phone_office: '1',
            phone_alternate: '1',
            website: '1',
            ownership: '1',
            employees: '1',
            ticker_symbol: '1',
            shipping_address_street: '1',
            shipping_address_street_2: '1',
            shipping_address_street_3: '1',
            shipping_address_street_4: '1',
            shipping_address_city: '1',
            shipping_address_state: '1',
            shipping_address_postalcode: '1',
            shipping_address_country: '1',
            email1: '1',
            email_addresses_primary: '1',
            email_addresses: '1',
            email_addresses_non_primary: '1',
            parent_id: '1',
            sic_code: '1',
            parent_name: '1',
            members: '1',
            member_of: '1',
            email_opt_out: '1',
            invalid_email: '1',
            cases: '1',
            email: '1',
            tasks: '1',
            notes: '1',
            meetings: '1',
            calls: '1',
            emails: '1',
            documents: '1',
            bugs: '1',
            contacts: '1',
            opportunities: '1',
            project: '1',
            leads: '1',
            campaigns: '1',
            campaign_accounts: '1',
            campaign_id: '1',
            campaign_name: '1',
            prospect_lists: '1',
            aos_quotes: '1',
            aos_invoices: '1',
            aos_contracts: '1',
            jjwg_maps_address_c: '1',
            jjwg_maps_geocode_status_c: '1',
            jjwg_maps_lat_c: '1',
            jjwg_maps_lng_c: '1'
        },
        process_save_dates: true,
        save_from_post: true,
        duplicates_found: false,
        deleted: '0',
        update_date_modified: true,
        update_modified_by: true,
        update_date_entered: false,
        set_created_by: true,
        ungreedy_count: false,
        module_name: 'Accounts',
        list_fields: [],
        current_notify_user: null,
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
        logicHookDepth: {before_retrieve: 0, after_retrieve: 0},
        '\u0000*\u0000max_logic_depth': 10,
        '\u0000*\u0000loaded_relationships': ['member_of', 'campaign_accounts'],
        '\u0000*\u0000is_updated_dependent_fields': false,
        acl_category: null,
        old_modified_by_name: null,
        required_fields: {id: 1, name: 1},
        acl_fields: true,
        jjwg_maps_lat_c: '0.00000000',
        jjwg_maps_lng_c: '0.00000000',
        created_by_link: '',
        modified_user_link: '',
        assigned_user_link: '',
        SecurityGroups: '',
        email_addresses_primary: '',
        email_addresses: '',
        email_addresses_non_primary: '',
        members: '',
        member_of: [],
        cases: '',
        email: '',
        tasks: '',
        notes: '',
        meetings: '',
        calls: '',
        emails: '',
        documents: '',
        bugs: '',
        contacts: '',
        opportunities: '',
        project: '',
        leads: '',
        campaigns: '',
        campaign_accounts: [],
        campaign_name: '',
        prospect_lists: '',
        aos_quotes: '',
        aos_invoices: '',
        aos_contracts: '',
        jjwg_maps_address_c: '',
        jjwg_maps_geocode_status_c: '',
        relDepth: 0,
        rel_fields_before_value: {
            opportunity_id: null,
            bug_id: '',
            case_id: null,
            contact_id: null,
            task_id: null,
            note_id: null,
            meeting_id: null,
            call_id: null,
            email_id: null,
            member_id: null,
            project_id: null
        },
        editable: true
    },
    id: '59418996-1880-8cc2-822a-5f637f84f3c5',
    fields: {
        name: {
            type: 'name',
            value: 'V8 Api test Account',
            metadata: {},
            definition: {
                name: 'name',
                type: 'name',
                dbType: 'varchar',
                vname: 'LBL_NAME',
                len: 150,
                comment: 'Name of the Company',
                unified_search: true,
                full_text_search: {boost: 3},
                audited: true,
                required: true,
                importable: 'required',
                merge_filter: 'selected'
            },
            labelKey: 'LBL_NAME',
            label: 'Name'
        },
        phone_office: {
            type: 'phone',
            value: '',
            metadata: {},
            definition: {
                name: 'phone_office',
                vname: 'LBL_PHONE_OFFICE',
                type: 'phone',
                dbType: 'varchar',
                len: 100,
                audited: true,
                unified_search: true,
                full_text_search: {boost: 1},
                comment: 'The office phone number',
                merge_filter: 'enabled',
                required: false
            },
            labelKey: 'LBL_PHONE_OFFICE',
            label: 'Office Phone'
        },
        website: {
            type: 'link',
            value: '',
            metadata: {},
            definition: {
                name: 'website',
                vname: 'LBL_WEBSITE',
                type: 'url',
                dbType: 'varchar',
                len: 255,
                comment: 'URL of website for the company',
                required: false
            },
            labelKey: 'LBL_WEBSITE',
            label: 'Website'
        },
        phone_fax: {
            type: 'phone',
            value: '',
            metadata: {},
            definition: {
                name: 'phone_fax',
                vname: 'LBL_FAX',
                type: 'phone',
                dbType: 'varchar',
                len: 100,
                unified_search: true,
                full_text_search: {boost: 1},
                comment: 'The fax phone number of this company',
                required: false
            },
            labelKey: 'LBL_FAX',
            label: 'Fax'
        },
        email1: {
            type: 'varchar',
            value: '',
            metadata: {},
            definition: {
                name: 'email1',
                vname: 'LBL_EMAIL',
                group: 'email1',
                type: 'varchar',
                function: {name: 'getEmailAddressWidget', returns: 'html'},
                source: 'non-db',
                studio: {editField: true, searchview: false},
                full_text_search: {boost: 3, analyzer: 'whitespace'},
                required: false
            },
            labelKey: 'LBL_EMAIL',
            label: 'Email Address'
        },
        billing_address_street: {
            type: 'address',
            value: '',
            metadata: {},
            definition: {
                name: 'billing_address_street',
                vname: 'LBL_BILLING_ADDRESS_STREET',
                type: 'varchar',
                len: '150',
                comment: 'The street address used for billing address',
                group: 'billing_address',
                merge_filter: 'enabled',
                required: false
            },
            labelKey: 'LBL_BILLING_ADDRESS',
            label: 'Billing Address'
        },
        shipping_address_street: {
            type: 'address',
            value: '',
            metadata: {},
            definition: {
                name: 'shipping_address_street',
                vname: 'LBL_SHIPPING_ADDRESS_STREET',
                type: 'varchar',
                len: 150,
                group: 'shipping_address',
                comment: 'The street address used for for shipping purposes',
                merge_filter: 'enabled',
                required: false
            },
            labelKey: 'LBL_SHIPPING_ADDRESS',
            label: 'Shipping Address'
        },
        description: {
            type: 'text',
            value: '',
            metadata: {},
            definition: {
                name: 'description',
                vname: 'LBL_DESCRIPTION',
                type: 'text',
                comment: 'Full text of the note',
                rows: 6,
                cols: 80,
                required: false
            },
            labelKey: 'LBL_DESCRIPTION',
            label: 'Description'
        },
        assigned_user_name: {
            type: 'relate',
            metadata: {},
            definition: {
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
                duplicate_merge: 'disabled',
                required: false
            },
            labelKey: 'LBL_ASSIGNED_TO',
            label: 'Assigned to'
        },
        account_type: {
            type: 'enum',
            value: '',
            metadata: {},
            definition: {
                name: 'account_type',
                vname: 'LBL_TYPE',
                type: 'enum',
                options: 'account_type_dom',
                len: 50,
                comment: 'The Company is of this type',
                required: false
            },
            labelKey: 'LBL_TYPE',
            label: 'Type'
        },
        industry: {
            type: 'enum',
            value: '',
            metadata: {},
            definition: {
                name: 'industry',
                vname: 'LBL_INDUSTRY',
                type: 'enum',
                options: 'industry_dom',
                len: 50,
                comment: 'The company belongs in this industry',
                merge_filter: 'enabled',
                required: false
            },
            labelKey: 'LBL_INDUSTRY',
            label: 'Industry'
        },
        annual_revenue: {
            type: 'varchar',
            value: '',
            metadata: {},
            definition: {
                name: 'annual_revenue',
                vname: 'LBL_ANNUAL_REVENUE',
                type: 'varchar',
                len: 100,
                comment: 'Annual revenue for this company',
                merge_filter: 'enabled',
                required: false
            },
            labelKey: 'LBL_ANNUAL_REVENUE',
            label: 'Annual Revenue'
        },
        employees: {
            type: 'varchar',
            value: '',
            metadata: {},
            definition: {
                name: 'employees',
                vname: 'LBL_EMPLOYEES',
                type: 'varchar',
                len: 10,
                comment: 'Number of employees, varchar to accomodate for both number (100) or range (50-100)',
                required: false
            },
            labelKey: 'LBL_EMPLOYEES',
            label: 'Employees'
        },
        parent_name: {
            type: 'relate',
            metadata: {},
            definition: {
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
                importable: 'true',
                required: false
            },
            labelKey: 'LBL_MEMBER_OF',
            label: 'Member of'
        },
        campaign_name: {
            type: 'relate',
            metadata: {},
            definition: {
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
                comment: 'The first campaign name for Account (Meta-data only)',
                required: false
            },
            labelKey: 'LBL_CAMPAIGN',
            label: 'Campaign'
        },
        date_entered: {
            type: 'datetime',
            value: '2020-09-17 15.22',
            metadata: {},
            definition: {
                name: 'date_entered',
                vname: 'LBL_DATE_ENTERED',
                type: 'datetime',
                group: 'created_by_name',
                comment: 'Date record created',
                enable_range_search: true,
                options: 'date_range_search_dom',
                inline_edit: false,
                required: false
            },
            labelKey: 'LBL_DATE_ENTERED',
            label: 'Date Created'
        },
        date_modified: {
            type: 'datetime',
            value: '2020-09-17 15.22',
            metadata: {},
            definition: {
                name: 'date_modified',
                vname: 'LBL_DATE_MODIFIED',
                type: 'datetime',
                group: 'modified_by_name',
                comment: 'Date record last modified',
                enable_range_search: true,
                options: 'date_range_search_dom',
                inline_edit: false,
                required: false
            },
            labelKey: 'LBL_DATE_MODIFIED',
            label: 'Date Modified'
        }
    }
};

const displayConfigSubject = new BehaviorSubject<FieldLayoutConfig>(mockConfigData);
const panelSubject = new BehaviorSubject<Panel>(mockPanelData);
const fieldSubject = new BehaviorSubject<FieldMap>(deepClone(mockFieldsData));
const recordSubject = new BehaviorSubject<Record>(deepClone(mockRecord));
const dataSource: FieldLayoutDataSource = {
    getConfig: (): Observable<FieldLayoutConfig> => displayConfigSubject.asObservable(),
    getLayout: (): Observable<Panel> => panelSubject.asObservable(),
    getFields: (): Observable<FieldMap> => fieldSubject.asObservable(),
    getRecord: (): Observable<Record> => recordSubject.asObservable()
} as FieldLayoutDataSource;


@Component({
    selector: 'field-grid-host-component',
    template: `
        <scrm-field-layout [dataSource]="dataSource" [actions]="true">

            <span class="float-right mt-4" field-grid-actions>
                <scrm-button *ngFor="let button of gridButtons" [config]="button"></scrm-button>
            </span>
        </scrm-field-layout>
    `
})
class FieldLayoutTestHostComponent {
    mode: 'edit';
    dataSource = dataSource;
    gridButtons = [
        {
            label: 'Clear',
            klass: ['clear-button', 'btn', 'btn-outline-danger', 'btn-sm']
        },
        {
            label: 'Some Button',
            klass: ['some-button', 'btn', 'btn-danger', 'btn-sm']
        }
    ];
}

describe('FieldLayoutComponent', () => {
    let testHostComponent: FieldLayoutTestHostComponent;
    let testHostFixture: ComponentFixture<FieldLayoutTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                FieldLayoutTestHostComponent,
                FieldLayoutComponent
            ],
            imports: [
                ButtonModule,
                DropdownButtonModule,
                BrowserDynamicTestingModule,
                LayoutModule,
                FieldModule,
                CommonModule,
                RouterTestingModule
            ],
            providers: [
                {provide: ModuleNameMapper, useValue: moduleNameMapperMock},
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {provide: CurrencyFormatter, useValue: currencyFormatterMock},
                {provide: LanguageStore, useValue: languageStoreMock}
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(FieldLayoutTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have field layout parent', waitForAsync(() => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('scrm-field-layout')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('form')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.clear-button')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.some-button')).nativeElement).toBeTruthy();
    }));

    it('should display fields using defined layout', waitForAsync(() => {

        expect(testHostComponent).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('scrm-field-layout')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('form')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.clear-button')).nativeElement).toBeTruthy();
        expect(testHostFixture.debugElement.query(By.css('.some-button')).nativeElement).toBeTruthy();

        const fieldLayout = testHostFixture.nativeElement.getElementsByClassName('field-layout');

        expect(fieldLayout).toBeTruthy();

        const rows = testHostFixture.nativeElement.getElementsByClassName('form-row');

        expect(rows).toBeTruthy();
        expect(rows.length).toEqual(2);

        const cols = testHostFixture.nativeElement.getElementsByClassName('col');

        expect(cols).toBeTruthy();
        expect(cols.length).toEqual(4);

        const firstRow = rows.item(0);
        const secondRow = rows.item(1);

        const nameCol = firstRow.getElementsByClassName('col').item(0);
        const descriptionCol = firstRow.getElementsByClassName('col').item(1);

        expect(nameCol).toBeTruthy();
        expect(nameCol.textContent).toContain('OPPORTUNITY NAME');
        expect(nameCol.textContent).toContain('op 14');

        expect(descriptionCol).toBeTruthy();
        expect(descriptionCol.textContent).toContain('DESCRIPTION');
        expect(descriptionCol.textContent).toContain('desc 1');

        const assignedToCol = secondRow.getElementsByClassName('col').item(0);
        const buttonsCol = secondRow.getElementsByClassName('col').item(1);

        expect(assignedToCol).toBeTruthy();
        expect(assignedToCol.textContent).toContain('ASSIGNED TO');

        expect(buttonsCol).toBeTruthy();
        expect(buttonsCol.textContent).toContain('Clear');
        expect(buttonsCol.textContent).toContain('Some Button');

        const clearButton = secondRow.getElementsByClassName('btn').item(0);
        const someButton = secondRow.getElementsByClassName('btn').item(1);

        expect(clearButton).toBeTruthy();
        expect(clearButton.textContent).toContain('Clear');

        expect(someButton).toBeTruthy();
        expect(someButton.textContent).toContain('Some Button');
    }));
});
