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
import {RecordContentComponent} from './record-content.component';
import {BehaviorSubject, Observable} from 'rxjs';
import {Component} from '@angular/core';
import {NgbDropdownModule, NgbModule, NgbNavModule} from '@ng-bootstrap/ng-bootstrap';
import {Panel, TabDefinitions} from '../../common/metadata/metadata.model';
import {Record} from '../../common/record/record.model';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';
import {CloseButtonModule} from '../close-button/close-button.module';
import {PanelModule} from '../panel/panel.module';
import {LanguageStore} from '../../store/language/language.store';
import {RecordContentConfig, RecordContentDataSource} from './record-content.model';
import {metadataStoreMock} from '../../store/metadata/metadata.store.spec.mock';
import {MetadataStore} from '../../store/metadata/metadata.store.service';
import {themeImagesStoreMock} from '../../store/theme-images/theme-images.store.spec.mock';
import {MinimiseButtonModule} from '../minimise-button/minimise-button.module';
import {languageStoreMock} from '../../store/language/language.store.spec.mock';
import {ThemeImagesStore} from '../../store/theme-images/theme-images.store';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
const mockDisplayConfigData: RecordContentConfig = {
    layout: 'panels',
    mode: 'detail',
    maxColumns: 2,
    tabDefs: {
        LBL_ACCOUNT_INFORMATION: {newTab: true, panelDefault: 'expanded'},
        LBL_PANEL_ADVANCED: {newTab: true, panelDefault: 'expanded'},
        LBL_PANEL_ASSIGNMENT: {newTab: true, panelDefault: 'expanded'}
    } as TabDefinitions
} as RecordContentConfig;
const mockPanelsData: Panel[] = [
    {
        label: 'OVERVIEW',
        key: 'lbl_account_information',
        rows: [{
            cols: [{
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
            }, {
                name: 'phone_office',
                label: 'LBL_PHONE_OFFICE',
                comment: 'The office phone number',
                fieldDefinition: {
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
                type: 'phone'
            }]
        }, {
            cols: [{
                name: 'website',
                label: 'LBL_WEBSITE',
                type: 'link',
                displayParams: {link_target: '_blank'},
                fieldDefinition: {
                    name: 'website',
                    vname: 'LBL_WEBSITE',
                    type: 'url',
                    dbType: 'varchar',
                    len: 255,
                    comment: 'URL of website for the company',
                    required: false
                }
            }, {
                name: 'phone_fax',
                label: 'LBL_FAX',
                comment: 'The fax phone number of this company',
                fieldDefinition: {
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
                type: 'phone'
            }]
        }, {
            cols: [{
                name: 'email1',
                label: 'LBL_EMAIL',
                studio: 'false',
                fieldDefinition: {
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
                type: 'varchar'
            }]
        }, {
            cols: [{
                name: 'billing_address_street',
                label: 'LBL_BILLING_ADDRESS',
                type: 'address',
                displayParams: {key: 'billing'},
                fieldDefinition: {
                    name: 'billing_address_street',
                    vname: 'LBL_BILLING_ADDRESS_STREET',
                    type: 'varchar',
                    len: '150',
                    comment: 'The street address used for billing address',
                    group: 'billing_address',
                    merge_filter: 'enabled',
                    required: false
                }
            }, {
                name: 'shipping_address_street',
                label: 'LBL_SHIPPING_ADDRESS',
                type: 'address',
                displayParams: {key: 'shipping'},
                fieldDefinition: {
                    name: 'shipping_address_street',
                    vname: 'LBL_SHIPPING_ADDRESS_STREET',
                    type: 'varchar',
                    len: 150,
                    group: 'shipping_address',
                    comment: 'The street address used for for shipping purposes',
                    merge_filter: 'enabled',
                    required: false
                }
            }]
        }, {
            cols: [{
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
            }]
        }, {
            cols: [{
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
            }]
        }]
    },
    {
        label: 'MORE INFORMATION',
        key: 'LBL_PANEL_ADVANCED',
        rows: [{
            cols: [{
                name: 'account_type',
                label: 'LBL_TYPE',
                comment: 'The Company is of this type',
                fieldDefinition: {
                    name: 'account_type',
                    vname: 'LBL_TYPE',
                    type: 'enum',
                    options: 'account_type_dom',
                    len: 50,
                    comment: 'The Company is of this type',
                    required: false
                },
                type: 'enum'
            }, {
                name: 'industry',
                label: 'LBL_INDUSTRY',
                comment: 'The company belongs in this industry',
                fieldDefinition: {
                    name: 'industry',
                    vname: 'LBL_INDUSTRY',
                    type: 'enum',
                    options: 'industry_dom',
                    len: 50,
                    comment: 'The company belongs in this industry',
                    merge_filter: 'enabled',
                    required: false
                },
                type: 'enum'
            }]
        }, {
            cols: [{
                name: 'annual_revenue',
                label: 'LBL_ANNUAL_REVENUE',
                comment: 'Annual revenue for this company',
                fieldDefinition: {
                    name: 'annual_revenue',
                    vname: 'LBL_ANNUAL_REVENUE',
                    type: 'varchar',
                    len: 100,
                    comment: 'Annual revenue for this company',
                    merge_filter: 'enabled',
                    required: false
                },
                type: 'varchar'
            }, {
                name: 'employees',
                label: 'LBL_EMPLOYEES',
                comment: 'Number of employees, varchar to accomodate for both number (100) or range (50-100)',
                fieldDefinition: {
                    name: 'employees',
                    vname: 'LBL_EMPLOYEES',
                    type: 'varchar',
                    len: 10,
                    comment: 'Number of employees, varchar to accomodate for both number (100) or range (50-100)',
                    required: false
                },
                type: 'varchar'
            }]
        }, {
            cols: [{
                name: 'parent_name',
                label: 'LBL_MEMBER_OF',
                fieldDefinition: {
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
                type: 'relate'
            }]
        }, {
            cols: [{
                name: 'campaign_name',
                label: 'LBL_CAMPAIGN',
                fieldDefinition: {
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
                type: 'relate'
            }]
        }]
    },
    {
        label: 'OTHER',
        key: 'LBL_PANEL_ASSIGNMENT', rows: [{
            cols: [{
                name: 'date_entered',
                label: 'LBL_DATE_ENTERED',
                customCode: '{$fields.date_entered.value} {$APP.LBL_BY} {$fields.created_by_name.value}',
                fieldDefinition: {
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
                type: 'datetime'
            }, {
                name: 'date_modified',
                label: 'LBL_DATE_MODIFIED',
                customCode: '{$fields.date_modified.value} {$APP.LBL_BY} {$fields.modified_by_name.value}',
                fieldDefinition: {
                    name: 'date_modified',
                    vname: 'LBL_DATE_MODIFIED',
                    type: 'datetime',
                    group: 'modified_by_name',
                    comment: 'Date record last modified',
                    enable_range_search: true,
                    options: 'date_range_search_dom', inline_edit: false, required: false
                },
                type: 'datetime'
            }]
        }]
    }
] as Panel[];

const record = {
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

const displayConfigSubject = new BehaviorSubject<RecordContentConfig>(mockDisplayConfigData);
const panelsSubject = new BehaviorSubject<Panel[]>(mockPanelsData);
const recordSubject = new BehaviorSubject<Record>(record);
const dataSource: RecordContentDataSource = {
    getDisplayConfig: (): Observable<RecordContentConfig> => displayConfigSubject.asObservable(),

    getPanels: () => panelsSubject.asObservable(),
    getRecord: (): Observable<Record> => recordSubject.asObservable()
} as RecordContentDataSource;

/* eslint-enable camelcase, @typescript-eslint/camelcase */

@Component({
    selector: 'record-content-test-host-component',
    template: '<scrm-record-content [dataSource]="state"></scrm-record-content>'
})
class RecordContentComponentTestHostComponent {
    state = dataSource;
}

describe('RecordContentComponent', () => {
    let testHostComponent: RecordContentComponentTestHostComponent;
    let testHostFixture: ComponentFixture<RecordContentComponentTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                RecordContentComponentTestHostComponent,
                RecordContentComponent,
            ],
            imports: [
                DropdownButtonModule,
                ButtonModule,
                NgbDropdownModule,
                PanelModule,
                CloseButtonModule,
                MinimiseButtonModule,
                NgbModule,
                NgbNavModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: MetadataStore, useValue: metadataStoreMock},
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(RecordContentComponentTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have panels', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        displayConfigSubject.next({...mockDisplayConfigData});

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(testHostComponent).toBeTruthy();
            const panels = testHostFixture.nativeElement.getElementsByClassName('panel-card');

            expect(panels).toBeTruthy();
            expect(panels.length).toEqual(3);
        });

    }));

    it('should have correct panel titles', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        displayConfigSubject.next({...mockDisplayConfigData});


        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {


            const panels = testHostFixture.nativeElement.getElementsByClassName('panel-card');

            expect(panels).toBeTruthy();
            expect(panels.length).toEqual(3);

            const accountInfoPanel = panels.item(0);
            const accountInfoPanelHeader = accountInfoPanel.getElementsByClassName('card-header').item(0);
            const advancedPanel = panels.item(1);
            const advancedPanelHeader = advancedPanel.getElementsByClassName('card-header').item(0);
            const assignmentPanel = panels.item(2);
            const assignmentPanelHeader = assignmentPanel.getElementsByClassName('card-header').item(0);

            expect(accountInfoPanelHeader).toBeTruthy();
            expect(accountInfoPanelHeader.textContent).toContain('OVERVIEW');
            expect(advancedPanelHeader).toBeTruthy();
            expect(advancedPanelHeader.textContent).toContain('MORE INFORMATION');
            expect(assignmentPanelHeader).toBeTruthy();
            expect(assignmentPanelHeader.textContent).toContain('OTHER');

        });


    }));

    it('panels should be collapsible', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        displayConfigSubject.next({...mockDisplayConfigData});

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent).toBeTruthy();
            const panels = testHostFixture.nativeElement.getElementsByClassName('panel-card');

            expect(panels).toBeTruthy();
            expect(panels.length).toEqual(3);

            const accountInfoPanel = panels.item(0);
            const accountInfoPanelButton = accountInfoPanel.getElementsByClassName('minimise-button').item(0);
            const advancedPanel = panels.item(1);
            const advancedPanelButton = advancedPanel.getElementsByClassName('minimise-button').item(0);
            const assignmentPanel = panels.item(2);
            const assignmentPanelButton = assignmentPanel.getElementsByClassName('minimise-button').item(0);

            expect(accountInfoPanelButton).toBeTruthy();
            expect(advancedPanelButton).toBeTruthy();
            expect(assignmentPanelButton).toBeTruthy();

            accountInfoPanelButton.click();
            advancedPanelButton.click();
            assignmentPanelButton.click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {

                const accountInfoPanelBody = accountInfoPanel.getElementsByClassName('card-body').item(0);
                const advancedPanelBody = advancedPanel.getElementsByClassName('card-body').item(0);
                const assignmentPanelBody = assignmentPanel.getElementsByClassName('card-body').item(0);

                expect(accountInfoPanelBody).toBeTruthy();
                expect(accountInfoPanelBody.className).not.toContain('show');
                expect(advancedPanelBody).toBeTruthy();
                expect(advancedPanelBody.className).not.toContain('show');
                expect(assignmentPanelBody).toBeTruthy();
                expect(assignmentPanelBody.className).not.toContain('show');
            });
        });
    }));

    it('should have tabs', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        const tabsDisplayConfig = {...mockDisplayConfigData};
        tabsDisplayConfig.layout = 'tabs';
        displayConfigSubject.next(tabsDisplayConfig);

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent).toBeTruthy();
            const tabsContainer = testHostFixture.nativeElement.getElementsByClassName('nav-tabs');
            const tabs = testHostFixture.nativeElement.getElementsByClassName('tab');

            expect(tabsContainer).toBeTruthy();
            expect(tabs).toBeTruthy();
            expect(tabs.length).toEqual(3);
            expect(tabsContainer.length).toEqual(1);
        });
    }));

    it('should have correct tabs titles', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        const tabsDisplayConfig = {...mockDisplayConfigData};
        tabsDisplayConfig.layout = 'tabs';
        displayConfigSubject.next(tabsDisplayConfig);

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent).toBeTruthy();
            const tabs = testHostFixture.nativeElement.getElementsByClassName('tab');

            expect(tabs).toBeTruthy();
            expect(tabs.length).toEqual(3);

            const accountInfoTab = tabs.item(0);
            const accountInfoLink = accountInfoTab.getElementsByClassName('tab-link').item(0);
            const advancedTab = tabs.item(1);
            const advancedLink = advancedTab.getElementsByClassName('tab-link').item(0);
            const assignmentTab = tabs.item(2);
            const assignmentLink = assignmentTab.getElementsByClassName('tab-link').item(0);

            expect(accountInfoLink).toBeTruthy();
            expect(accountInfoLink.textContent).toContain('OVERVIEW');
            expect(advancedLink).toBeTruthy();
            expect(advancedLink.textContent).toContain('MORE INFORMATION');
            expect(assignmentLink).toBeTruthy();
            expect(assignmentLink.textContent).toContain('OTHER');

        });


    }));

    it('tabs should be toggleable', waitForAsync(() => {
        expect(testHostComponent).toBeTruthy();

        const tabsDisplayConfig = {...mockDisplayConfigData};
        tabsDisplayConfig.layout = 'tabs';
        displayConfigSubject.next(tabsDisplayConfig);

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(testHostComponent).toBeTruthy();
            const tabs = testHostFixture.nativeElement.getElementsByClassName('tab');
            const tabContent = testHostFixture.nativeElement.getElementsByClassName('tab-content').item(0);

            expect(tabs).toBeTruthy();
            expect(tabContent).toBeTruthy();
            expect(tabs.length).toEqual(3);

            const accountInfoTab = tabs.item(0);
            const accountInfoLink = accountInfoTab.getElementsByClassName('tab-link').item(0);
            const advancedTab = tabs.item(1);
            const advancedLink = advancedTab.getElementsByClassName('tab-link').item(0);
            const assignmentTab = tabs.item(2);
            const assignmentLink = assignmentTab.getElementsByClassName('tab-link').item(0);

            expect(accountInfoLink).toBeTruthy();
            expect(accountInfoLink.textContent).toContain('OVERVIEW');
            accountInfoLink.click();

            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                let displayedTab = tabContent.getElementsByClassName('tab-lbl_account_information');

                expect(displayedTab).toBeTruthy();
                expect(displayedTab.length).toEqual(1);

                expect(advancedLink).toBeTruthy();
                expect(advancedLink.textContent).toContain('MORE INFORMATION');
                advancedLink.click();

                testHostFixture.detectChanges();
                testHostFixture.whenStable().then(() => {
                    displayedTab = tabContent.getElementsByClassName('tab-LBL_PANEL_ADVANCED');

                    expect(displayedTab).toBeTruthy();
                    expect(displayedTab.length).toEqual(1);

                    expect(assignmentLink).toBeTruthy();
                    expect(assignmentLink.textContent).toContain('OTHER');

                    assignmentLink.click();
                    testHostFixture.detectChanges();
                    testHostFixture.whenStable().then(() => {
                        displayedTab = tabContent.getElementsByClassName('tab-LBL_PANEL_ASSIGNMENT');

                        expect(displayedTab).toBeTruthy();
                        expect(displayedTab.length).toEqual(1);
                    });
                });
            });
        });
    }));
});
