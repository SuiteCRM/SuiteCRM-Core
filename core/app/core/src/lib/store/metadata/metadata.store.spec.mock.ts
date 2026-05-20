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
import {appStateStoreMock} from '../app-state/app-state.store.spec.mock';
import {MetadataStore} from './metadata.store.service';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const metadataMockData = {
    search: {
        layout: {
            basic: {
                name: {
                    name: 'name',
                    label: 'LBL_NAME',
                    fieldType: 'name',
                    default: true,
                    width: '10%'
                },
                current_user_only: {
                    name: 'current_user_only',
                    label: 'LBL_CURRENT_USER_FILTER',
                    type: 'bool',
                    default: true,
                    width: '10%'
                },
                favorites_only: {
                    name: 'favorites_only',
                    label: 'LBL_FAVORITES_FILTER',
                    type: 'bool'
                }
            },
            advanced: {
                name: {
                    name: 'name',
                    label: 'LBL_NAME',
                    fieldType: 'name',
                    default: true,
                    width: '10%'
                },
                website: {
                    name: 'website',
                    label: 'LBL_WEBSITE',
                    fieldType: 'url',
                    default: true,
                    width: '10%'
                },
                phone: {
                    name: 'phone',
                    label: 'LBL_ANY_PHONE',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                email: {
                    name: 'email',
                    label: 'LBL_ANY_EMAIL',
                    fieldType: 'email',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                address_street: {
                    name: 'address_street',
                    label: 'LBL_ANY_ADDRESS',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                address_city: {
                    name: 'address_city',
                    label: 'LBL_CITY',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                address_state: {
                    name: 'address_state',
                    label: 'LBL_STATE',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                address_postalcode: {
                    name: 'address_postalcode',
                    label: 'LBL_POSTAL_CODE',
                    type: 'name',
                    default: true,
                    width: '10%'
                },
                billing_address_country: {
                    name: 'billing_address_country',
                    label: 'LBL_COUNTRY',
                    fieldType: 'varchar',
                    type: 'name',
                    options: 'countries_dom',
                    default: true,
                    width: '10%'
                },
                account_type: {
                    name: 'account_type',
                    options: 'account_type_dom',
                    label: 'LBL_TYPE',
                    fieldType: 'enum',
                    default: true,
                    width: '10%'
                },
                industry: {
                    name: 'industry',
                    options: 'industry_dom',
                    label: 'LBL_INDUSTRY',
                    fieldType: 'enum',
                    default: true,
                    width: '10%'
                },
                assigned_user_id: {
                    name: 'assigned_user_id',
                    label: 'LBL_ASSIGNED_TO',
                    fieldType: 'relate',
                    type: 'enum',
                    function: {
                        name: 'get_user_array',
                        params: [
                            false
                        ]
                    },
                    default: true,
                    width: '10%'
                }
            }
        }
    },
    listView: {
        bulkActions: {
            delete: {
                key: 'delete',
                labelKey: 'LBL_DELETE',
                params: {
                    min: 1,
                    max: 5
                },
                acl: [
                    'delete'
                ]
            },
            export: {
                key: 'export',
                labelKey: 'LBL_EXPORT',
                params: {
                    min: 1,
                    max: 5
                },
                acl: [
                    'export'
                ]
            },
            merge: {
                key: 'merge',
                labelKey: 'LBL_MERGE_DUPLICATES',
                params: {
                    min: 1,
                    max: 5
                },
                acl: [
                    'edit',
                    'delete'
                ]
            },
            massupdate: {
                key: 'massupdate',
                labelKey: 'LBL_MASS_UPDATE',
                params: {
                    min: 1,
                    max: 5
                },
                acl: [
                    'massupdate'
                ]
            }
        },
        chartTypes: {
            key: 'annual_revenue',
            labelKey: 'ANNUAL_REVENUE_BY_ACCOUNTS',
            type: 'line'
        },
        columns: [
            {
                name: 'name',
                width: '20%',
                label: 'LBL_LIST_ACCOUNT_NAME',
                link: true,
                default: true,
                module: '',
                id: '',
                sortable: false
            },
            {
                name: 'billing_address_city',
                width: '10%',
                label: 'LBL_LIST_CITY',
                link: false,
                default: true,
                module: '',
                id: '',
                sortable: false
            },
            {
                name: 'billing_address_country',
                width: '10%',
                label: 'LBL_BILLING_ADDRESS_COUNTRY',
                link: false,
                default: true,
                module: '',
                id: '',
                sortable: false
            },
            {
                name: 'phone_office',
                width: '10%',
                label: 'LBL_LIST_PHONE',
                link: false,
                default: true,
                module: '',
                id: '',
                sortable: false
            },
            {
                name: 'assigned_user_name',
                width: '10%',
                label: 'LBL_LIST_ASSIGNED_USER',
                link: false,
                default: true,
                module: 'Employees',
                id: 'ASSIGNED_USER_ID',
                sortable: false
            },
            {
                name: 'email1',
                width: '15%',
                label: 'LBL_EMAIL_ADDRESS',
                link: true,
                default: true,
                module: '',
                id: '',
                sortable: false,
                customCode: '{$EMAIL1_LINK}'
            },
            {
                name: 'date_entered',
                width: '5%',
                label: 'LBL_DATE_ENTERED',
                link: false,
                default: true,
                module: '',
                id: '',
                sortable: false
            }
        ]
    },
    recordView: {
        templateMeta: {
            maxColumns: 2,
            useTabs: false,
            tabDefs: {
                LBL_ACCOUNT_INFORMATION: {
                    newTab: true,
                    panelDefault: 'expanded'
                },
                LBL_PANEL_ADVANCED: {
                    newTab: true,
                    panelDefault: 'expanded'
                },
                LBL_PANEL_ASSIGNMENT: {
                    newTab: true,
                    panelDefault: 'expanded'
                }
            }
        },
        actions: [
            {
                key: 'create',
                labelKey: 'LBL_NEW',
                modes: ['detail'],
                acl: ['edit'],
                params: {
                    expanded: true
                },
            },
            {
                key: 'edit',
                labelKey: 'LBL_EDIT',
                modes: ['detail'],
                acl: ['edit'],
                params: {
                    expanded: true
                },
            },
            {
                key: 'save',
                labelKey: 'LBL_SAVE_BUTTON_LABEL',
                modes: ['edit'],
                acl: ['edit'],
                params: {
                    expanded: true
                },
            },
        ],
        panels: [
            {
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
                                    full_text_search: {
                                        boost: 3
                                    },
                                    audited: true,
                                    required: true,
                                    importable: 'required',
                                    merge_filter: 'selected'
                                },
                                type: 'name'
                            },
                            {
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
                                    full_text_search: {
                                        boost: 1
                                    },
                                    comment: 'The office phone number',
                                    merge_filter: 'enabled',
                                    required: false
                                },
                                type: 'phone'
                            }
                        ]
                    },
                    {
                        cols: [
                            {
                                name: 'website',
                                label: 'LBL_WEBSITE',
                                type: 'link',
                                displayParams: {
                                    link_target: '_blank'
                                },
                                fieldDefinition: {
                                    name: 'website',
                                    vname: 'LBL_WEBSITE',
                                    type: 'url',
                                    dbType: 'varchar',
                                    len: 255,
                                    comment: 'URL of website for the company',
                                    required: false
                                }
                            },
                            {
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
                                    full_text_search: {
                                        boost: 1
                                    },
                                    comment: 'The fax phone number of this company',
                                    required: false
                                },
                                type: 'phone'
                            }
                        ]
                    },
                    {
                        cols: [
                            {
                                name: 'email1',
                                label: 'LBL_EMAIL',
                                studio: 'false',
                                fieldDefinition: {
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
                                    },
                                    required: false
                                },
                                type: 'varchar'
                            }
                        ]
                    },
                    {
                        cols: [
                            {
                                name: 'billing_address_street',
                                label: 'LBL_BILLING_ADDRESS',
                                type: 'address',
                                displayParams: {
                                    key: 'billing'
                                },
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
                            },
                            {
                                name: 'shipping_address_street',
                                label: 'LBL_SHIPPING_ADDRESS',
                                type: 'address',
                                displayParams: {
                                    key: 'shipping'
                                },
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
                            }
                        ]
                    },
                    {
                        cols: [
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
                    }
                ]
            },
            {
                key: 'LBL_PANEL_ADVANCED',
                rows: [
                    {
                        cols: [
                            {
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
                            },
                            {
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
                            }
                        ]
                    },
                    {
                        cols: [
                            {
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
                            },
                            {
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
                            }
                        ]
                    },
                    {
                        cols: [
                            {
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
                            }
                        ]
                    },
                    {
                        cols: [
                            {
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
                            }
                        ]
                    }
                ]
            },
            {
                key: 'LBL_PANEL_ASSIGNMENT',
                rows: [
                    {
                        cols: [
                            {
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
                            },
                            {
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
                                    options: 'date_range_search_dom',
                                    inline_edit: false,
                                    required: false
                                },
                                type: 'datetime'
                            }
                        ]
                    }
                ]
            }
        ]
    },
    subPanel: {
        activities: {
            order: 10,
            sort_order: 'desc',
            sort_by: 'date_due',
            title_key: 'LBL_ACTIVITIES_SUBPANEL_TITLE',
            type: 'collection',
            subpanel_name: 'activities',
            header_definition_from_subpanel: 'meetings',
            module: 'Activities',
            top_buttons: [
                {
                    key: 'create',
                    labelKey: 'LBL_QUICK_CREATE'
                }
            ],
            collection_list: {
                tasks: {
                    module: 'Tasks',
                    subpanel_name: 'ForActivities',
                    get_subpanel_data: 'tasks'
                },
                meetings: {
                    module: 'Meetings',
                    subpanel_name: 'ForActivities',
                    get_subpanel_data: 'meetings'
                },
                calls: {
                    module: 'Calls',
                    subpanel_name: 'ForActivities',
                    get_subpanel_data: 'calls'
                }
            },
            icon: 'graph'
        },
        history: {
            order: 20,
            sort_order: 'desc',
            sort_by: 'date_entered',
            title_key: 'LBL_HISTORY_SUBPANEL_TITLE',
            type: 'collection',
            subpanel_name: 'history',
            header_definition_from_subpanel: 'meetings',
            module: 'History',
            top_buttons: [
                {
                    key: 'create',
                    labelKey: 'LBL_QUICK_CREATE'
                }
            ],
            collection_list: {
                tasks: {
                    module: 'Tasks',
                    subpanel_name: 'ForHistory',
                    get_subpanel_data: 'tasks'
                },
                meetings: {
                    module: 'Meetings',
                    subpanel_name: 'ForHistory',
                    get_subpanel_data: 'meetings'
                },
                calls: {
                    module: 'Calls',
                    subpanel_name: 'ForHistory',
                    get_subpanel_data: 'calls'
                },
                notes: {
                    module: 'Notes',
                    subpanel_name: 'ForHistory',
                    get_subpanel_data: 'notes'
                },
                emails: {
                    module: 'Emails',
                    subpanel_name: 'ForUnlinkedEmailHistory',
                    get_subpanel_data: 'function:get_emails_by_assign_or_link',
                    function_parameters: {
                        import_function_file: 'include/utils.php',
                        link: 'contacts'
                    },
                    generate_select: true,
                    get_distinct_data: true
                }
            },
            searchdefs: {
                collection: {
                    name: 'collection',
                    label: 'LBL_COLLECTION_TYPE',
                    type: 'enum',
                    options: {
                        Tasks: 'Tasks',
                        Meetings: 'Meetings',
                        Calls: 'Calls',
                        Notes: 'Notes',
                        Emails: 'Emails'
                    },
                    default: true,
                    width: '10%'
                },
                name: {
                    name: 'name',
                    default: true,
                    width: '10%'
                },
                current_user_only: {
                    name: 'current_user_only',
                    label: 'LBL_CURRENT_USER_FILTER',
                    type: 'bool',
                    default: true,
                    width: '10%'
                },
                date_modified: {
                    name: 'date_modified',
                    default: true,
                    width: '10%'
                }
            },
            icon: 'clock'
        },
        documents: {
            order: 25,
            module: 'Documents',
            subpanel_name: 'default',
            sort_order: 'asc',
            sort_by: 'id',
            title_key: 'LBL_DOCUMENTS_SUBPANEL_TITLE',
            get_subpanel_data: 'documents',
            top_buttons: [
                {
                    key: 'create',
                    labelKey: 'LBL_QUICK_CREATE'
                }
            ],
            icon: 'download'
        },
        contacts: {
            order: 30,
            module: 'Contacts',
            sort_order: 'asc',
            sort_by: 'last_name, first_name',
            subpanel_name: 'ForAccounts',
            get_subpanel_data: 'contacts',
            add_subpanel_data: 'contact_id',
            title_key: 'LBL_CONTACTS_SUBPANEL_TITLE',
            top_buttons: [
                {
                    key: 'create',
                    labelKey: 'LBL_QUICK_CREATE'
                }
            ],
            icon: 'person'
        }
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */


class MetadataRecordGQLSpy extends EntityGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, id: string, metadata: { fields: string[] }): Observable<any> {

        return of({
            data: {
                viewDefinition: metadataMockData
            }
        }).pipe(shareReplay(1));
    }
}

export const metadataStoreMock = new MetadataStore(new MetadataRecordGQLSpy(), appStateStoreMock);
metadataStoreMock.load('accounts', metadataStoreMock.getMetadataTypes()).pipe(take(1)).subscribe();
