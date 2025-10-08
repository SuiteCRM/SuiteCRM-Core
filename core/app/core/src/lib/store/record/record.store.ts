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

import {BehaviorSubject, Observable, Subscription, throwError} from 'rxjs';
import {catchError, distinctUntilChanged, filter, map, shareReplay, startWith, take, tap} from 'rxjs/operators';
import {RecordFetchGQL} from './graphql/api.record.get';
import {RecordSaveGQL} from './graphql/api.record.save';
import {MessageService} from '../../services/message/message.service';
import {RecordManager} from '../../services/record/record.manager';
import {deepClone} from '../../common/utils/object-utils';
import {Record} from '../../common/record/record.model';
import {RecordMapper} from '../../common/record/record-mappers/record-mapper.model';
import {RecordMapperRegistry} from '../../common/record/record-mappers/record-mapper.registry';
import {ViewFieldDefinition} from '../../common/metadata/metadata.model';
import {MapEntry} from '../../common/types/overridable-map';
import {signal} from "@angular/core";
import {ObjectMap} from "../../common/types/object-map";

const initialState = {
    id: '',
    type: '',
    module: '',
    attributes: {},
    acls: []
} as Record;

export class RecordStore {

    state$: Observable<Record>;
    staging$: Observable<Record>;
    initFieldDefaults: boolean = false;
    fieldDefaultsInitialized: boolean = false;
    protected cache$: Observable<any> = null;
    protected internalState: Record = deepClone(initialState);
    protected stagingState: Record = deepClone(initialState);
    protected store = new BehaviorSubject<Record>(this.internalState);
    protected staging = new BehaviorSubject<Record>(this.stagingState);
    protected definitions: ViewFieldDefinition[] = [];
    protected metadata: ObjectMap = null;
    protected subs: Subscription[] = [];
    protected fieldsMetadata = {
        fields: [
            '_id',
            'id',
            'attributes',
            'module',
            'type',
            'acls',
            'favorite'
        ]
    };

    constructor(
        protected definitions$: Observable<ViewFieldDefinition[]>,
        protected metadata$: Observable<ObjectMap>,
        protected recordSaveGQL: RecordSaveGQL,
        protected recordFetchGQL: RecordFetchGQL,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected recordMappers: RecordMapperRegistry,
        protected options: ObjectMap = null
    ) {


        this.state$ = this.store.asObservable().pipe(distinctUntilChanged());
        this.staging$ = this.staging.asObservable();

        this.subs.push(this.state$.subscribe(record => {
            this.updateStaging(record);
        }));

        this.subs.push(definitions$.subscribe(definitions => {
            this.definitions = definitions;
            this.init(this.internalState);
        }));

        if (metadata$) {
            this.subs.push(metadata$.subscribe(metadata => {
                this.setMetadata(metadata);
            }));
        }

    }

    init(record: Record, initDefaultValues = false): void {
        const newRecord = {
            ...record,
        };

        this.initFieldDefaults = initDefaultValues;

        this.initRecord(newRecord, initDefaultValues);

        this.updateState(newRecord);
    }

    getStaging(): Record {
        if (!this.stagingState) {
            return null;
        }
        return this.stagingState;
    }

    setStaging(record: Record): void {

        this.initRecord(record, false, this?.options ?? null);

        this.staging.next(this.stagingState = record);
    }

    setRecord(record: Record, initDefaultValues: boolean = false): void {

        this.initFieldDefaults = initDefaultValues;
        this.initRecord(record);

        this.updateState(record);
    }

    save(): Observable<Record> {

        this.mapStagingFields();

        return this.recordSaveGQL.save(this.stagingState).pipe(
            tap((record: Record) => {
                this.initRecord(record);
                this.updateState(record);
            })
        );
    }

    validate(): Observable<boolean> {

        this.stagingState.formGroup.markAllAsTouched();
        this.stagingState?.validationTriggered.set(true);
        return this.stagingState.formGroup.statusChanges.pipe(
            startWith(this.stagingState.formGroup.status),
            filter(status => status !== 'PENDING'),
            take(1),
            map(status => status === 'VALID')
        );
    }

    resetStaging(): void {
        this.updateStaging(this.internalState);
    }

    destroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    /**
     * Get record
     *
     * @returns {object} Record
     */
    getBaseRecord(): Record {
        if (!this.internalState) {
            return null;
        }

        const baseRecord = {
            id: this.internalState.id,
            type: this.internalState.type,
            module: this.internalState.module,
            attributes: this.internalState.attributes,
            acls: this.internalState.acls
        } as Record;

        return deepClone(baseRecord);
    }

    /**
     * Get record
     *
     * @returns {object} Record
     */
    getBaseStaging(): Record {
        if (!this.stagingState) {
            return null;
        }

        this.mapStagingFields();

        const baseRecord = {
            id: this.stagingState.id,
            type: this.stagingState.type,
            module: this.stagingState.module,
            attributes: this.stagingState.attributes,
            acls: this.stagingState.acls
        } as Record;

        return deepClone(baseRecord);
    }

    /**
     * Extract base record
     *
     * @returns {object} Record
     */
    extractBaseRecord(record: Record): Record {
        const {fields, formGroup, ...base} = record;

        return {...base}
    }

    /**
     * Is staging record dirty
     *
     * @returns {object} Record
     */
    isDirty(): boolean {
        if (!this.stagingState || !this.stagingState.formGroup) {
            return false;
        }

        return this.stagingState.formGroup.dirty;
    }

    /**
     * Get record cached Observable or call the backend
     *
     * @param {string} module to use
     * @param {string} recordId to use
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<any>
     */
    public retrieveRecord(
        module: string,
        recordId: string,
        useCache = true
    ): Observable<Record> {
        if (this.cache$ == null || useCache === false) {
            this.cache$ = this.fetch(module, recordId).pipe(
                tap(record => this.init(record)),
                shareReplay(1)
            );
        }
        return this.cache$;
    }

    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: Record): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Update the staging
     *
     * @param {object} state to set
     */
    protected updateStaging(state: Record): void {

        const newState = deepClone(this.extractBaseRecord(state));
        this.initRecord(newState, this.initFieldDefaults, this?.options ?? null);

        this.staging.next(this.stagingState = newState);
    }

    /**
     * Map staging fields
     */
    protected mapStagingFields(): void {
        const mappers: MapEntry<RecordMapper> = this.recordMappers.get(this.stagingState.module);

        Object.keys(mappers).forEach(key => {
            const mapper = mappers[key];
            mapper.map(this.stagingState);
        });
    }

    /**
     * Init record fields
     *
     * @param {object} record Record
     * @param {boolean} initDefaultValues
     * @param options
     */
    protected initRecord(record: Record, initDefaultValues: boolean = false, options: ObjectMap = null): void {

        if (this.metadata) {
            record.metadata = this.metadata;
        }

        if (!record?.validationTriggered) {
            record.validationTriggered = signal(false);
        }

        if (record.module && this.definitions && this.definitions.length > 0) {
            record.fields = this.recordManager.initFields(record, this.definitions);
        }

        if (initDefaultValues) {
            this.recordManager.initFieldDefaults(record);
            this.fieldDefaultsInitialized = true;
        }

        if (options?.initVardefBasedFieldActions && options?.buildFieldActionAdapter) {
            this.recordManager.initVardefBasedFieldActions(record, options.buildFieldActionAdapter);
        }
    }

    /**
     * Fetch the record from the backend
     *
     * @param {string} module to use
     * @param {string} recordID to use
     * @returns {object} Observable<any>
     */
    protected fetch(module: string, recordID: string): Observable<Record> {
        return this.recordFetchGQL.fetch(module, recordID, this.fieldsMetadata)
            .pipe(
                map(({data}) => {

                    const record: Record = {
                        type: '',
                        module: '',
                        attributes: {},
                        acls: []
                    } as Record;

                    if (!data) {
                        return record;
                    }

                    const id = data.record.attributes.id;
                    if (!id) {
                        this.message.addDangerMessageByKey('LBL_RECORD_DOES_NOT_EXIST');
                        return record;
                    }

                    record.id = id;
                    record.module = module;
                    record.type = data.record.attributes && data.record.attributes.object_name;
                    record.attributes = data.record.attributes;
                    record.acls = data.record.acls;
                    record.favorite = data?.record?.favorite ?? false;

                    return record;
                }),
                catchError(err => throwError(err)),
            );
    }

    public setMetadata(metadata: ObjectMap): void {
        if (!metadata) {
            return;
        }
        this.metadata = metadata;
    }
}
