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

import {deepClone} from '../../../../common/utils/object-utils';
import {ColumnDefinition, SearchMetaField, SearchMetaFieldMap} from '../../../../common/metadata/list.metadata.model';
import {FieldMap, FieldMetadata, Option} from '../../../../common/record/field.model';
import {Record} from '../../../../common/record/record.model';
import {RecordMapperRegistry} from '../../../../common/record/record-mappers/record-mapper.registry';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {shareReplay, tap} from 'rxjs/operators';
import {RecordStore} from '../../../../store/record/record.store';
import {SavedFilter, SavedFilterAttributeMap} from '../../../../store/saved-filters/saved-filter.model';
import {UntypedFormGroup} from '@angular/forms';
import {RecordSaveGQL} from '../../../../store/record/graphql/api.record.save';
import {MessageService} from '../../../../services/message/message.service';
import {RecordFetchGQL} from '../../../../store/record/graphql/api.record.get';
import {RecordManager} from '../../../../services/record/record.manager';
import {FieldManager} from '../../../../services/record/field/field.manager';
import {LanguageStore} from '../../../../store/language/language.store';
import {signal} from "@angular/core";
import {ObjectMap} from "../../../../common/types/object-map";

const initialState = {
    id: '',
    type: '',
    module: '',
    attributes: {}
} as Record;

export class SavedFilterRecordStore extends RecordStore {

    state$: Observable<SavedFilter>;
    staging$: Observable<SavedFilter>;
    protected internalState: SavedFilter = deepClone(initialState);
    protected stagingState: SavedFilter = deepClone(initialState);
    protected store = new BehaviorSubject<SavedFilter>(this.internalState);
    protected staging = new BehaviorSubject<SavedFilter>(this.stagingState);
    protected searchFields: SearchMetaFieldMap = {};
    protected listColumns: ColumnDefinition[] = [];

    constructor(
        protected definitions$: Observable<ViewFieldDefinition[]>,
        protected metadata$: Observable<ObjectMap>,
        protected recordSaveGQL: RecordSaveGQL,
        protected recordFetchGQL: RecordFetchGQL,
        protected message: MessageService,
        protected recordManager: RecordManager,
        protected recordMappers: RecordMapperRegistry,
        protected fieldManager: FieldManager,
        protected language: LanguageStore
    ) {
        super(
            definitions$,
            metadata$,
            recordSaveGQL,
            recordFetchGQL,
            message,
            recordManager,
            recordMappers
        );

        this.state$ = this.store.asObservable().pipe(
            tap(record => {
                this.updateStaging(record);
            })
        );
        this.staging$ = this.staging.asObservable();
    }

    /**
     * Get search fields metadata
     * @returns SearchMetaFieldMap
     */
    public getSearchFields(): SearchMetaFieldMap {
        return this.searchFields;
    }

    /**
     * Set search fields metadata
     * @param {object} searchFields SearchMetaFieldMap
     */
    public setSearchFields(searchFields: SearchMetaFieldMap): void {
        this.searchFields = searchFields;
    }

    /**
     * Get list fields metadata
     * @returns SearchMetaFieldMap
     */
    public getListColumns(): ColumnDefinition[] {
        return this.listColumns;
    }

    /**
     * Set list fields metadata
     * @param {object} listColumns SearchMetaFieldMap
     */
    public setListColumns(listColumns: ColumnDefinition[]): void {
        this.listColumns = listColumns;
    }

    /**
     * Get record
     *
     * @returns {object} Record
     */
    getBaseRecord(): SavedFilter {
        if (!this.stagingState) {
            return null;
        }

        this.mapStagingFields();

        return deepClone({
            id: this.stagingState.id,
            type: this.stagingState.type,
            module: this.stagingState.module,
            key: this.stagingState.key,
            searchModule: this.stagingState.searchModule,
            criteria: this.stagingState.criteria,
            attributes: this.stagingState.attributes,
        } as SavedFilter);
    }

    /**
     * Extract base record
     *
     * @returns {object} Record
     */
    extractBaseRecord(record: SavedFilter): Record {
        if (!record) {
            return null;
        }

        let criteria = record.criteria ?? {};
        if (Array.isArray(criteria) && !criteria.length) {
            criteria = {};
        }

        return deepClone({
            id: record.id,
            type: record.type,
            module: record.module,
            key: record.key,
            searchModule: record.searchModule,
            criteria: criteria,
            attributes: record.attributes,
        } as SavedFilter);
    }

    /**
     * Init record fields
     *
     * @param {object} record Record
     */
    protected initRecord(record: SavedFilter): void {

        if (this.metadata) {
            record.metadata = this.metadata;
        }

        if (!record?.validationTriggered) {
            record.validationTriggered = signal(false);
        }

        record.attributes = record.attributes || {} as SavedFilterAttributeMap;
        record.attributes.search_module = record.searchModule;
        const filters = record?.attributes?.contents?.filters ?? {};
        record.attributes.contents = record.attributes.contents || {filters: {}} as SearchCriteria;

        if (Array.isArray(filters) && !filters.length) {
            record.attributes.contents.filters = {};
        } else {
            record.attributes.contents.filters = filters;
        }

        record.criteria = this.getCriteria(record);

        this.initCriteriaFields(record, this.getSearchFields());

        if (record.module && this.definitions && this.definitions.length > 0) {
            record.fields = this.recordManager.initFields(record, this.definitions);
        }

        this.initOrderByOptions(record);
    }

    /**
     * Init Order by options using list view columns set as default
     * @param record
     */
    protected initOrderByOptions(record: SavedFilter): void {
        if (!record.fields || !record.fields.orderBy) {
            return
        }

        record.fields.orderBy.metadata = record.fields.orderBy.metadata || {} as FieldMetadata;

        const options = [] as Option[];
        this.getListColumns().forEach(column => {

            if (!column.default || column.default !== true) {
                return;
            }

            const labelKey = column.label || column.fieldDefinition.vname || '';
            const label = this.language.getFieldLabel(labelKey, record.searchModule);

            options.push({
                value: column.fieldDefinition.name || column.name,
                label
            })
        });

        record.fields.orderBy.metadata.options$ = of(options).pipe(shareReplay());
    }

    /**
     * Get criteria from filter
     * @param filter
     */
    protected getCriteria(filter: SavedFilter): SearchCriteria {

        if (!filter || !filter.criteria) {
            return {filters: {}};
        }

        if (!filter.criteria.filters) {
            return {...filter.criteria, filters: {}};
        }

        if (Array.isArray(filter.criteria.filters) && !filter.criteria.filters.length) {
            return {...filter.criteria, filters: {}};
        }

        return deepClone(filter.criteria);
    }

    /**
     * Initialize criteria fields
     *
     * @param {object} record to use
     * @param {object} searchFields to use
     */
    protected initCriteriaFields(record: SavedFilter, searchFields: SearchMetaFieldMap): void {

        record.criteriaFields = record.criteriaFields || {} as FieldMap;
        if (!record.criteriaFormGroup) {
            record.criteriaFormGroup = new UntypedFormGroup({});
        }

        if (!searchFields) {
            return;
        }

        Object.keys(searchFields).forEach(key => {
            this.buildField(record, searchFields[key]);
        });
    }

    /**
     * Build filter field according to Field interface
     *
     * @param {object} record SavedFilter
     * @param {object} fieldMeta to use
     */
    protected buildField(record: SavedFilter, fieldMeta: SearchMetaField): void {
        const fieldName = fieldMeta.name;
        const type = fieldMeta.type;

        const definition = {
            name: fieldMeta.name,
            label: fieldMeta.label,
            vardefBased: fieldMeta?.vardefBased ?? false,
            readonly: fieldMeta?.readonly ?? false,
            display: fieldMeta?.display ?? 'default',
            type,
            fieldDefinition: {}
        } as ViewFieldDefinition;

        if (fieldMeta.fieldDefinition) {
            definition.fieldDefinition = fieldMeta.fieldDefinition;
        }

        if (type === 'bool' || type === 'boolean') {
            definition.fieldDefinition.options = 'dom_int_bool';
        }

        this.fieldManager.addFilterField(record, definition, this.language);

    }
}
