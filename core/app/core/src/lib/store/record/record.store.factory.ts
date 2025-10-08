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

import {Injectable} from '@angular/core';
import {RecordStore} from './record.store';
import {Observable} from 'rxjs';
import {RecordFetchGQL} from './graphql/api.record.get';
import {RecordSaveGQL} from './graphql/api.record.save';
import {MessageService} from '../../services/message/message.service';
import {RecordManager} from '../../services/record/record.manager';
import {BaseSaveRecordMapper} from './record-mappers/base-save.record-mapper';
import {RecordMapperRegistry} from '../../common/record/record-mappers/record-mapper.registry';
import {ViewFieldDefinition} from '../../common/metadata/metadata.model';
import {ObjectMap} from "../../common/types/object-map";

@Injectable({
    providedIn: 'root',
})
export class RecordStoreFactory {

    constructor(
        protected recordFetchGQL: RecordFetchGQL,
        protected recordSaveGQL: RecordSaveGQL,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected recordMappers: RecordMapperRegistry,
        protected baseMapper: BaseSaveRecordMapper,
    ) {
        recordMappers.register('default', baseMapper.getKey(), baseMapper);
    }

    create(definitions$: Observable<ViewFieldDefinition[]>, metadata$?: Observable<ObjectMap>, options: ObjectMap = null): RecordStore {
        return new RecordStore(
            definitions$,
            metadata$,
            this.recordSaveGQL,
            this.recordFetchGQL,
            this.message,
            this.recordManager,
            this.recordMappers,
            options
        );
    }
}
