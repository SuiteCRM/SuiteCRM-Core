/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {Component} from '@angular/core';
import {RelateService} from '../../../../services/record/relate/relate.service';
import {RelateFilterFieldComponent} from '../../../relate/templates/filter/relate.component';
import {LanguageStore} from '../../../../store/language/language.store';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {
    RecordListModalComponent
} from '../../../../containers/record-list-modal/components/record-list-modal/record-list-modal.component';
import {RecordListModalResult} from '../../../../containers/record-list-modal/components/record-list-modal/record-list-modal.model';
import {deepClone} from '../../../../common/utils/object-utils';
import {SearchCriteria} from '../../../../common/views/list/search-criteria.model';
import {ObjectMap} from '../../../../common/types/object-map';
import {isArray} from 'lodash-es';
import {map, take} from 'rxjs/operators';

@Component({
    selector: 'scrm-multirelate-filter',
    templateUrl: './multirelate.component.html',
    styleUrls: [],
    providers: [RelateService]
})
export class MultiRelateFilterFieldComponent extends RelateFilterFieldComponent {

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected relateService: RelateService,
        protected moduleNameMapper: ModuleNameMapper,
        protected modalService: NgbModal,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected config: SystemConfigStore
    ) {
        super(languages, typeFormatter, relateService, moduleNameMapper, modalService, logic, logicDisplay, config);
    }

    /**
     * Multirelate fields have no `id_name` vardef entry, so the base RelateFilterFieldComponent's
     * idField mechanism never kicks in (see BaseRelateComponent.getRelateIdField()) and its default
     * updateFieldValues() would send display names (option[rname]) as the search value instead of ids.
     * The backend subquery built for multirelate filtering (SearchForm2::getMultirelateSubquery())
     * matches against the related record's id column, so send ids here directly.
     */
    protected override updateFieldValues(): void {
        const value = this?.selectedValues?.map(option => option.id) ?? [];

        this.field.valueList = value;
        this.field.valueObjectArray = deepClone(this.selectedValues ?? []);

        this.updateSearchCriteria(this.field);

        this.field.criteria.valueObjectArray = deepClone(this.field.valueObjectArray);
    }

    override filterResults(): void {
        const relateName = this.getRelateFieldName();
        const criteria = this.buildCriteria();
        this.filterValue = this.filterValue ?? '';

        const matches = this.filterValue.match(/^\s*$/g);
        if (matches && matches.length) {
            this.filterValue = '';
        }

        const term = this.filterValue;
        this.search(term, criteria).pipe(
            take(1),
            map(data => data.filter((item: ObjectMap) => item[relateName] !== '')),
        ).subscribe(filteredOptions => {
            this.options = filteredOptions;
            this.currentOptions.set(this.options);
            this.addCurrentlySelectedToOptions(filteredOptions);
            this.calculateSelectAll();
        });
    }

    /**
     * Show record selection modal with pre-selected values sourced from selectedValues,
     * matching the approach used by MultiRelateEditFieldComponent.
     */
    protected override showSelectModal(): void {
        const modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: true});

        const selectedIds = (this.selectedValues ?? []).map(item => item.id).filter(id => !!id).join(',');

        const criteria = this.buildCriteria();
        const filter = this.buildFilter(criteria);

        modal.componentInstance.module = this.getRelatedModule();
        modal.componentInstance.presetFilter = filter;
        modal.componentInstance.multiSelect = true;
        modal.componentInstance.multiSelectButtonLabel = 'LBL_SAVE';
        modal.componentInstance.selectedValues = selectedIds;

        modal.result.then((data: RecordListModalResult) => {

            if (!data || !data.selection || !data.selection.selected) {
                return;
            }

            const records = this.getSelectedRecords(data);
            const allRecords = data.records ?? [];
            const selected = [];

            records.forEach((record) => {
                selected.push(record.id);
                const found = this.field.valueObjectArray.find(element => element.id === record.id);

                if (found) {
                    return;
                }

                this.setItem(record);
            });

            allRecords.forEach((record) => {
                if (!selected.includes(record.id)) {
                    this.selectedValues = this.selectedValues.filter((value) => value.id !== record.id);
                }
            });

            this.onAdd();
            this.tag.updateModel(this.selectedValues);
            this.currentOptions.set(this.selectedValues ?? []);
        });
    }

    protected buildCriteria(): SearchCriteria {

        if (!this.field?.definition?.filter) {
            return {} as SearchCriteria;
        }

        const filter = this.field?.definition?.filter;

        const criteria = {
            name: 'default',
            filters: {}
        } as SearchCriteria;

        if (filter?.preset ?? false) {
            criteria.preset = filter.preset;
            criteria.preset.params.module = this.module;
        }

        const fields = filter.static ?? [];

        Object.keys(fields).forEach((field) => {
            const fieldValue = fields[field];

            let value = [];
            if (isArray(fieldValue)) {
                value = fieldValue;
            } else {
                value.push(fieldValue);
            }

            criteria.filters[field] = {
                field: field,
                operator: '=',
                values: value,
                rangeSearch: false
            };
        });

        return criteria;
    }

    protected buildFilter(criteria: SearchCriteria) {
        return {
            key: 'default',
            module: 'saved-search',
            attributes: {
                contents: ''
            },
            criteria
        };
    }
}
