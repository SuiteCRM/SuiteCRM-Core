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

import {Component, ElementRef, ViewChild} from '@angular/core';
import {AttributeMap} from '../../../../common/record/record.model';
import {deepClone} from '../../../../common/utils/object-utils';
import {ObjectMap} from '../../../../common/types/object-map';
import {Field} from '../../../../common/record/field.model';
import {Record} from '../../../../common/record/record.model';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {
    RecordListModalComponent
} from '../../../../containers/record-list-modal/components/record-list-modal/record-list-modal.component';
import {BaseRelateComponent} from '../../../base/base-relate.component';
import {LanguageStore} from '../../../../store/language/language.store';
import {RelateService} from '../../../../services/record/relate/relate.service';
import {
    RecordListModalResult
} from '../../../../containers/record-list-modal/components/record-list-modal/record-list-modal.model';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {SavedFilter} from '../../../../store/saved-filters/saved-filter.model';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {map, take} from "rxjs/operators";
import {MultiSelect} from "primeng/multiselect";

@Component({
    selector: 'scrm-relate-filter',
    templateUrl: './relate.component.html',
    styleUrls: [],
    providers: [RelateService]
})
export class RelateFilterFieldComponent extends BaseRelateComponent {
    @ViewChild('tag') tag: MultiSelect;
    @ViewChild('dropdownFilterInput') dropdownFilterInput: ElementRef;
    selectButton: ButtonInterface;
    idField: Field;

    placeholderLabel: string = '';
    selectedItemsLabel: string = '';
    emptyFilterLabel: string = '';
    maxSelectedLabels: number = 20;
    selectAll: boolean = false;
    filterValue: string | undefined = '';

    /**
     * Constructor
     *
     * @param {object} languages service
     * @param {object} typeFormatter service
     * @param {object} relateService service
     * @param {object} moduleNameMapper service
     * @param {object} modalService service
     * @param {object} logic
     * @param {object} logicDisplay
     */
    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected relateService: RelateService,
        protected moduleNameMapper: ModuleNameMapper,
        protected modalService: NgbModal,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(languages, typeFormatter, relateService, moduleNameMapper, logic, logicDisplay);

        this.selectButton = {
            klass: ['btn', 'btn-sm', 'btn-outline-secondary', 'm-0', 'border-0'],
            onClick: (): void => {
                this.showSelectModal();
            },
            icon: 'cursor'
        } as ButtonInterface;
    }

    /**
     * On init handler
     */
    ngOnInit(): void {
        this.selectAll = false;
        const filter = this.record as SavedFilter;

        this.field.valueList = [];

        this.field.valueObjectArray = [];

        let values = (this.field && this.field.criteria && this.field.criteria.values) || [];
        values = values.filter(value => !value);

        if (values.length > 0) {
            this.field.valueList = values;
        }

        let valueObjectArray = (this.field && this.field.criteria && this.field.criteria.valueObjectArray) || [];
        valueObjectArray = valueObjectArray.map(value => {
            const mapped = {...value}
            mapped[this.getRelateFieldName()] = value[this.getRelateFieldName()] ?? value?.name ?? '';
            return mapped;
        });

        if (valueObjectArray.length > 0) {
            this.field.valueObjectArray = deepClone(valueObjectArray);
            this.selectedValues = deepClone(valueObjectArray);
        }

        super.ngOnInit();

        this.currentOptions.set(this.options ?? [])

        this.getTranslatedLabels();

        this.addCurrentlySelectedToOptions(this.options ?? []);

        const idFieldName = this.getRelateIdField();

        if (idFieldName && filter && filter.criteriaFields && filter.criteriaFields[idFieldName]) {
            this.idField = filter.criteriaFields[idFieldName];
            this.idField.valueList = [];
            let idValues = (this.idField && this.idField.criteria && this.idField.criteria.values) || [];
            idValues = idValues.filter(value => !!value);

            if (idValues.length > 0) {
                this.idField.valueList = deepClone(idValues);
            }
        }
    }

    /**
     * Handle newly added item
     */
    onAdd(): void {
        this.updateFieldValues();
        this.calculateSelectAll();
    }

    /**
     * Handle item removal
     */
    onRemove(): void {
        this.updateFieldValues();
        this.calculateSelectAll();
    }

    onClear(): void {
        this.options = [];
        this.currentOptions.set([]);
        this.selectedValues = [];
        this.selectAll = false;
        this.filterValue = '';
        this.onRemove();
    }

    onSelectAll(): void {
        this.selectAll = !this.selectAll;
        if (this.selectAll) {
            if (this.tag.visibleOptions() && this.tag.visibleOptions().length) {
                this.selectedValues = this.tag.visibleOptions();
            } else {
                this.selectedValues = this.options;
            }
            this.onAdd();
        } else {
            this.selectedValues = [];
            this.onRemove();
        }
    }

    getTranslatedLabels(): void {
        this.placeholderLabel = this.languages.getAppString('LBL_SELECT_ITEM') || '';
        this.selectedItemsLabel = this.languages.getAppString('LBL_ITEMS_SELECTED') || '';
        this.emptyFilterLabel = this.languages.getAppString('ERR_SEARCH_NO_RESULTS') || '';

    }

    onPanelShow(): void {
        this.dropdownFilterInput.nativeElement.focus()
        this.calculateSelectAll();
    }

    resetFunction(): void {
        this.filterValue = '';
        this.options = this.selectedValues;
    }

    onFilterInput(event: KeyboardEvent): void {
        event?.stopPropagation();
        this.selectAll = false;
        this.tag.onLazyLoad.emit()
    }

    onFilter(): void {
        const relateName = this.getRelateFieldName();
        this.filterValue = this.filterValue ?? '';
        const matches = this.filterValue.match(/^\s*$/g);
        if (matches && matches.length) {
            this.filterValue = '';
        }
        let term = this.filterValue;
        this.search(term).pipe(
            take(1),
            map(data => data.filter((item: ObjectMap) => item[relateName] !== '')),
            map(filteredData => filteredData.map((item: ObjectMap) => ({
                id: item.id,
                [relateName]: item[relateName]
            })))
        ).subscribe(filteredOptions => {
            this.options = filteredOptions;
            this.addCurrentlySelectedToOptions(filteredOptions);
            this.calculateSelectAll();
        });
    }

    protected updateFieldValues(): void {
        let value = this?.selectedValues?.map(option => option[this.getRelateFieldName()]) ?? null;
        if (!value) {
            value = [];
        }
        this.field.valueList = value;

        this.field.valueObjectArray = deepClone(this.selectedValues ?? []);

        this.updateSearchCriteria(this.field);

        this.field.criteria.valueObjectArray = deepClone(this.field.valueObjectArray);
        this.updateIdField();
    }

    protected updateIdField(): void {
        if (!this.idField) {
            return;
        }
        this.idField.valueList = this?.selectedValues?.map(option => option.id) ?? [];
        this.updateSearchCriteria(this.idField);
    }

    /**
     * Set value on field
     *
     * @param item
     */
    protected setValue(item: any): void {

        const relateName = this.getRelateFieldName();
        const id = item?.id ?? '';
        const relateValue = item[relateName];

        if (this.idField && this.idField.valueList.includes(id)) {
            return;
        }

        if (!this.idField && this.field.valueList.includes(relateValue)) {
            return;
        }

        const valueObject = {} as any;
        valueObject.id = id;
        valueObject[relateName] = relateValue;

        this.field.valueObjectArray.push(valueObject);
        this.field.valueList.push(relateValue);

        if (this.idField) {
            this.idField.valueList.push(id);
            this.updateSearchCriteria(this.idField);
        }

        this.updateSearchCriteria(this.field);

        if (!this.field.criteria.valueObjectArray) {
            this.field.criteria.valueObjectArray = [];
        }

        this.field.criteria.valueObjectArray.push(valueObject);
    }

    /**
     * Set value on field criteria and form
     */
    protected updateSearchCriteria(field: Field): void {
        field.criteria.operator = '=';
        field.criteria.values = field.valueList;
        field.formControl.setValue(field.valueList);
        field.formControl.markAsDirty();
    }

    /**
     * Show record selection modal
     */
    protected showSelectModal(): void {
        const modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: true});

        const selectedIds = this.idField.valueList.join(',');

        modal.componentInstance.module = this.getRelatedModule();
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

    /**
     * Get Selected Record
     *
     * @param {object} data RecordListModalResult
     */
    protected getSelectedRecords(data: RecordListModalResult) {
        let ids = [];
        Object.keys(data.selection.selected).some(selected => {
            ids[selected] = selected;
        });

        let records: Record[] = [];

        data.records.some(rec => {
            if (ids[rec.id]) {
                records.push(rec);
            }
        });

        return records;
    }

    /**
     * Set the record as the selected item
     *
     * @param {object} record to set
     */
    protected setItem(record: Record): void {
        const relateName = this.getRelateFieldName();
        const newItem = {
            id: record?.attributes?.id,
            [relateName]: record?.attributes[relateName],
            attributes: record?.attributes
        } as ObjectMap;

        const inList = this.isInList(this.selectedValues, newItem);
        if (inList) {
            return;
        }

        this.selectedValues = [...this.selectedValues ?? [], newItem];

        this.addCurrentlySelectedToOptions(this.options);

        this.onAdd();
    }

    protected addCurrentlySelectedToOptions(filteredOptions) {
        if (!this?.selectedValues || !this?.selectedValues.length) {
            return;
        }

        this.selectedValues.forEach(selectedValue => {
            let found = this.isInList(filteredOptions, selectedValue);

            if (found === false && selectedValue) {
                this.options.push(selectedValue);
            }
        });

        this.currentOptions.set(this.options ?? []);
    }

    protected isInList(filteredOptions: AttributeMap[], selectedValue: AttributeMap): boolean {
        let found = false

        filteredOptions.some((value: AttributeMap) => {

            if (value?.id === selectedValue?.id) {
                found = true;
                return true;
            }
            return false;
        });

        return found;
    }

    protected calculateSelectAll(): void {
        const selectedValuesKeys = (this?.selectedValues ?? []).map(item => {
            return item.id;
        });

        if (!this.options.length || !selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }

        if (this.options.length > selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }
        this.selectAll = this.options.every(item => selectedValuesKeys.includes(item.id));
    }
}
