/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
import {Injectable} from "@angular/core";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {Record} from "../../common/record/record.model";
import {FieldDefinitionMap} from "../../common/record/field.model";
import {isTrue} from "../../common/utils/value-utils";
import {ProcessService} from "../process/process.service";
import {Observable} from "rxjs";
import {map} from "rxjs/operators";

@Injectable({
    providedIn: 'root'
})
export class RecordDuplicateService {

    constructor(
        protected systemConfigStore: SystemConfigStore,
        protected processService: ProcessService
    ) {
    }

    public duplicateParse(record: Record, vardefs: FieldDefinitionMap): Record {

        record.id = '';
        record.attributes.id = '';
        record.attributes.date_entered = '';

        const excludedFields = this.systemConfigStore.getConfigValue('duplicate_ignore') ?? [];
        const module = record.module ?? '';

        const moduleExcludedFields = [...excludedFields['default'] ?? [], excludedFields[module] ?? []];

        moduleExcludedFields.forEach(field => {
            record.attributes[field] = '';
        });

        Object.keys(vardefs).forEach((fieldName: string) => {
            const fieldDef = vardefs[fieldName];
            const allowDuplicate = isTrue(fieldDef?.metadata?.allow_duplicate ?? true);
            if (!allowDuplicate) {
                record.attributes[fieldName] = '';
            }
        })

        return record;
    }

    public getDuplicateRecord(module: string, id: string): Observable<Record> {
        return this.processService.submit('get-duplicate-record', {module, id}).pipe(
            map(process => {
                const record: Record = {
                    type: '',
                    module: '',
                    attributes: {},
                    acls: []
                } as Record;

                const data = process?.data?.record ?? null;
                if (!data) {
                    return record;
                }

                record.id = data.id ?? '';
                record.module = data.module ?? module;
                record.type = data.type ?? '';
                record.attributes = data.attributes ?? {};
                record.acls = data.acls ?? [];
                record.favorite = data.favorite ?? false;

                return record;
            })
        );
    }

}
