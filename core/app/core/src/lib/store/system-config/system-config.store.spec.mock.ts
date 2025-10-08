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
import {SystemConfigStore} from './system-config.store';
import {CollectionGQL} from '../../services/api/graphql-api/api.collection.get';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const systemConfigMockData = {
    systemConfigs: {
        list_max_entries_per_page: {
            id: '/docroot/api/system-configs/list_max_entries_per_page',
            _id: 'list_max_entries_per_page',
            value: '20',
            items: []
        },
        default_language: {
            id: '/docroot/api/system-configs/default_language',
            _id: 'default_language',
            value: 'en_us',
            items: []
        },
        default_number_grouping_seperator: {
            id: '/docroot/api/system-configs/default_number_grouping_seperator',
            _id: 'default_number_grouping_seperator',
            value: ';',
            items: []
        },
        default_decimal_seperator: {
            id: '/docroot/api/system-configs/default_decimal_seperator',
            _id: 'default_decimal_seperator',
            value: ',',
            items: []
        },
        passwordsetting: {
            id: '/docroot/api/system-configs/passwordsetting',
            _id: 'passwordsetting',
            value: null,
            items: {
                forgotpasswordON: false
            }
        },
        languages: {
            id: '/docroot/api/system-configs/languages',
            _id: 'languages',
            value: null,
            items: {
                en_us: 'English (US)',
                pt_PT: 'Português (Portugal) - pt-PT'
            }
        },
        listview_column_limits: {
            id: '/docroot/api/system-configs/listview_column_limits',
            _id: 'listview_column_limits',
            value: null,
            items: {
                with_sidebar: {
                    XSmall: 2,
                    Small: 3,
                    Medium: 4,
                    Large: 6,
                    XLarge: 7
                },
                without_sidebar: {
                    XSmall: 2,
                    Small: 3,
                    Medium: 5,
                    Large: 7,
                    XLarge: 8
                }
            }
        },
        listview_settings_limits: {
            id: '/docroot/api/system-configs/listview_settings_limits',
            _id: 'listview_settings_limits',
            value: null,
            items: {
                XSmall: 2,
                Small: 3,
                Medium: 4,
                Large: 6,
                XLarge: 7
            }
        },
        listview_actions_limits: {
            id: '/docroot/api/system-configs/listview_actions_limits',
            _id: 'listview_actions_limits',
            value: null,
            items: {
                XSmall: 2,
                Small: 3,
                Medium: 4,
                Large: 6,
                XLarge: 7
            }
        },
        recordview_actions_limits: {
            id: '/docroot/api/system-configs/recordview_actions_limits',
            _id: 'recordview_actions_limits',
            value: null,
            items: {
                XSmall: 3,
                Small: 3,
                Medium: 3,
                Large: 3,
                XLarge: 3
            }
        },
        ui: {
            id: '/docroot/api/system-configs/ui',
            _id: 'ui',
            value: null,
            items: {
                alert_timeout: 3,
                modal_buttons_collapse_breakpoint: 4,
            }
        },
        date_format: {
            id: '/docroot/api/system-configs/date_format',
            _id: 'date_format',
            value: 'dd.MM.yyyy',
            items: []
        },
        time_format: {
            id: '/docroot/api/system-configs/time_format',
            _id: 'time_format',
            value: 'HH.mm.ss',
            items: []
        }
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class SystemConfigRecordGQLSpy extends CollectionGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetchAll(module: string, metadata: { fields: string[] }): Observable<any> {
        const data = {
            data: {
                systemConfigs: {
                    edges: []
                }
            }
        };

        Object.keys(systemConfigMockData.systemConfigs).forEach(key => {
            data.data.systemConfigs.edges.push({
                node: systemConfigMockData.systemConfigs[key]
            });
        });

        return of(data).pipe(shareReplay());
    }
}

export const systemConfigStoreMock = new SystemConfigStore(new SystemConfigRecordGQLSpy());
systemConfigStoreMock.load().pipe(take(1)).subscribe();
