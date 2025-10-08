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

import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {ListGQL} from '../../../../store/record-list/graphql/api.list.get';
import {appStateStoreMock} from '../../../../store/app-state/app-state.store.spec.mock';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {messageServiceMock} from '../../../../services/message/message.service.spec.mock';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {Observable, of} from 'rxjs';
import {deepClone} from '../../../../common/utils/object-utils';
import {take} from 'rxjs/operators';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const timelineRecordListMockData = {
    recordList: {
        "id": "/suite8_beta/public/api/record-list/History",
        "_id": "History",
        "meta": {
            "offsets": {
                "current": 10,
                "next": 20,
                "prev": 0,
                "end": 20,
                "total": 21,
                "totalCounted": true
            },
            "ordering": {
                "orderBy": "date_entered",
                "sortOrder": "DESC"
            }
        },
        "records": [
            {
                "id": "c7c6ef45-3dd7-9c8b-d919-60c86b63efec",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "c7c6ef45-3dd7-9c8b-d919-60c86b63efec",
                    "date_entered": "2021-06-15 08:56:18",
                    "date_modified": "2021-06-15 08:56:18",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-10",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "21f707ba-5f5d-6363-9cd8-60c86b2bd633",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "21f707ba-5f5d-6363-9cd8-60c86b2bd633",
                    "date_entered": "2021-06-15 08:55:58",
                    "date_modified": "2021-06-15 08:55:58",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-7",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "22218890-8412-1fa7-04c9-60c86b5dca54",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "22218890-8412-1fa7-04c9-60c86b5dca54",
                    "date_entered": "2021-06-15 08:55:46",
                    "date_modified": "2021-06-15 08:55:46",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-6",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "2877722a-e5b1-6643-5dff-60c86bbf60a0",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "2877722a-e5b1-6643-5dff-60c86bbf60a0",
                    "date_entered": "2021-06-15 08:55:34",
                    "date_modified": "2021-06-15 08:55:34",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-5",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "4ebd6d2e-e611-e140-247a-60c86a3f5f65",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "4ebd6d2e-e611-e140-247a-60c86a3f5f65",
                    "date_entered": "2021-06-15 08:55:19",
                    "date_modified": "2021-06-15 08:55:19",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-4",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "bb3abd40-64c0-3f99-cadb-60c86a9f346d",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "bb3abd40-64c0-3f99-cadb-60c86a9f346d",
                    "date_entered": "2021-06-15 08:54:51",
                    "date_modified": "2021-06-15 08:54:51",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-3",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "1f5a4842-1937-78b1-b092-60c858977635",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "1f5a4842-1937-78b1-b092-60c858977635",
                    "date_entered": "2021-06-15 07:36:50",
                    "date_modified": "2021-06-15 07:36:50",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "Note-2",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "59c9a8bd-e5eb-3fd7-a0ee-60c83b35f6ba",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "59c9a8bd-e5eb-3fd7-a0ee-60c83b35f6ba",
                    "date_entered": "2021-06-15 05:31:11",
                    "date_modified": "2021-06-15 05:31:11",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "My Note 1",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "a99008c6-d3cc-cd0a-a21d-5f4e612340c4",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "Abbie Brandenburg",
                        "id": "a99008c6-d3cc-cd0a-a21d-5f4e612340c4"
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            },
            {
                "id": "15fcb425-aeae-6194-519c-5f4e618c4c2a",
                "module": "meetings",
                "type": "Meeting",
                "attributes": {
                    "module_name": "Meetings",
                    "object_name": "Meeting",
                    "id": "15fcb425-aeae-6194-519c-5f4e618c4c2a",
                    "name": "Follow-up on proposal",
                    "date_entered": "2020-09-01 14:58:38",
                    "date_modified": "2021-06-15 05:28:06",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "description": "",
                    "deleted": "",
                    "assigned_user_id": "895d9256-bc80-f86f-7255-5e7237689fd1",
                    "assigned_user_name": {
                        "user_name": "c.raposo",
                        "id": "895d9256-bc80-f86f-7255-5e7237689fd1"
                    },
                    "accept_status": "",
                    "set_accept_links": "",
                    "location": "",
                    "password": "",
                    "join_url": "",
                    "host_url": "",
                    "displayed_url": "",
                    "creator": "",
                    "external_id": "",
                    "duration_hours": "",
                    "duration_minutes": "",
                    "date_start": "",
                    "date_end": "",
                    "parent_type": "Accounts",
                    "status": "Not Held",
                    "type": "",
                    "direction": "",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "reminder_checked": "",
                    "reminder_time": "",
                    "email_reminder_checked": "",
                    "email_reminder_time": "",
                    "email_reminder_sent": "",
                    "reminders": "",
                    "outlook_id": "",
                    "sequence": "",
                    "contact_name": {
                        "last_name": ""
                    },
                    "parent_name": "",
                    "contact_id": "",
                    "repeat_type": "",
                    "repeat_interval": "",
                    "repeat_dow": "",
                    "repeat_until": "",
                    "repeat_count": "",
                    "repeat_parent_id": "",
                    "recurring_source": "",
                    "duration": "",
                    "gsync_id": "",
                    "gsync_lastsync": "",
                    "jjwg_maps_address_c": "",
                    "jjwg_maps_geocode_status_c": "",
                    "jjwg_maps_lat_c": "",
                    "jjwg_maps_lng_c": ""
                }
            },
            {
                "id": "5b847f0c-27ba-6e7b-12b1-5f4e61d213e5",
                "module": "notes",
                "type": "Note",
                "attributes": {
                    "module_name": "Notes",
                    "object_name": "Note",
                    "assigned_user_id": "1",
                    "assigned_user_name": {
                        "user_name": "admin",
                        "id": "1"
                    },
                    "id": "5b847f0c-27ba-6e7b-12b1-5f4e61d213e5",
                    "date_entered": "2020-09-01 14:58:38",
                    "date_modified": "2021-06-15 05:29:27",
                    "modified_user_id": "",
                    "modified_by_name": {
                        "user_name": "",
                        "id": ""
                    },
                    "created_by": "1",
                    "created_by_name": {
                        "user_name": "",
                        "id": "1"
                    },
                    "name": "More Account Information",
                    "file_mime_type": "",
                    "file_url": "",
                    "filename": "",
                    "parent_type": "Accounts",
                    "parent_id": "2fde31e3-ba6a-c29a-96f3-5f4e61c51bd6",
                    "contact_id": "",
                    "portal_flag": "",
                    "embed_flag": "",
                    "description": "",
                    "deleted": "",
                    "filecontents": "",
                    "parent_name": "",
                    "show_preview": "",
                    "contact_name": {
                        "name": "",
                        "id": ""
                    },
                    "contact_phone": "",
                    "contact_email": "",
                    "account_id": "",
                    "opportunity_id": "",
                    "campaign_id": "",
                    "acase_id": "",
                    "lead_id": ""
                }
            }
        ],
        "__typename": "RecordList"
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class TimelineRecordListGQLSpy extends ListGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(
        module: string,
        limit: number,
        offset: number,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        criteria: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        sort: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        metadata: { fields: string[] }
    ): Observable<any> {

        const data = {
            data: {
                recordList: deepClone(timelineRecordListMockData.recordList)
            }
        };

        data.data.recordList.meta.offsets = {
            current: offset,
            next: (offset + limit) || 0,
            prev: (offset - limit) || 0,
            total: 200,
            end: 180
        };

        return of(data);
    }
}

export const timelineRecordListStoreFactoryMock = new RecordListStoreFactory(
    new TimelineRecordListGQLSpy(),
    systemConfigStoreMock,
    userPreferenceStoreMock,
    appStateStoreMock,
    languageStoreMock,
    messageServiceMock,
);

timelineRecordListStoreFactoryMock.create().init('history', true).pipe(take(1)).subscribe();
