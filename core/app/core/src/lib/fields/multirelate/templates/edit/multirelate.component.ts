/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

import {Component, computed, ElementRef, HostListener, Signal, signal, ViewChild, WritableSignal} from '@angular/core';
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
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {map, take} from "rxjs/operators";
import {MultiSelect} from "primeng/multiselect";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {deepClone} from "../../../../common/utils/object-utils";
import {ObjectMap} from "../../../../common/types/object-map";
import {AttributeMap, Record} from "../../../../common/record/record.model";
import {SearchCriteria} from "../../../../common/views/list/search-criteria.model";
import {isArray} from "lodash-es";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";

@Component({
    selector: 'scrm-multirelate-edit',
    templateUrl: './multirelate.component.html',
    styleUrls: [],
    providers: [RelateService]
})
export class MultiRelateEditFieldComponent extends BaseRelateComponent {
    @ViewChild('tag') tag: MultiSelect;
    @ViewChild('dropdownFilterInput') dropdownFilterInput: ElementRef;
    @HostListener('document:click', ['$event'])
    onDocClick(event) {
        const clickedInside = this.tag?.el?.nativeElement.contains(event.target);
        if (!clickedInside){
            this.tag.hide();
        }
    }

    selectButton: ButtonInterface;

    placeholderLabel: string = '';
    selectedItemsLabel: string = '';
    emptyFilterLabel: Signal<string> = signal('');
    maxSelectedLabels: number = 20;
    selectAll: boolean = false;
    filterValue: string | undefined = '';
    loading: WritableSignal<boolean> = signal(false);

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
     * @param config
     */
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
        super(languages, typeFormatter, relateService, moduleNameMapper, logic, logicDisplay, config);

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
        super.ngOnInit();
        const relatedFieldName = this.getRelateFieldName();
        if ((this.field?.valueList ?? []).length > 0 || (this.field?.valueObjectArray ?? []).length) {
            if ((this.field?.valueList ?? []).length > (this.field?.valueObjectArray ?? []).length) {
                this.field.valueObjectArray = deepClone(this.field.valueList);
            }
            this.selectedValues = this.field.valueObjectArray.map(valueElement => {

                const relateValue = valueElement?.attributes[relatedFieldName] ?? '';
                const moduleName = this.moduleNameMapper.toFrontend(valueElement?.attributes['module_name'] ?? '');

                const relateId = valueElement.attributes['id'] ?? '';

                const headerField = this.headerFields[moduleName] ?? {name: 'name'};
                const subHeader = this.subHeaderFields[moduleName] ?? {name: ''};
                return {
                    attributes: valueElement.attributes ?? {},
                    id: relateId,
                    [relatedFieldName]: relateValue,
                    label: this.getLabel(valueElement.attributes, headerField),
                    subLabel: this.getSubLabel(valueElement.attributes, subHeader),
                    module_name: valueElement['module_name'] ?? valueElement?.attributes['module_name'] ?? ''
                };
            });
            this.currentOptions.set(this.selectedValues);
            this.field.formControl.setValue(this.selectedValues);
        } else {
            this.selectedValues = [];
            this.field.valueObjectArray = [];
            this.field.valueList = [];
            this.field.formControl.setValue([]);
            this.currentOptions.set([]);
        }


        this.options = this.options ?? [];

        this.getTranslatedLabels();

        this.addCurrentlySelectedToOptions(this.options ?? []);
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
        this.selectedValues = [];
        this.selectAll = false;
        this.filterValue = '';
        this.currentOptions.set([]);
        this.onRemove();

        if (this.field?.definition?.filterOnEmpty ?? false) {
            this.tag.onLazyLoad.emit();
        }
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
        this.emptyFilterLabel = computed(() => {
            if (!this.loading()) {
                return this.languages.getAppString('ERR_SEARCH_NO_RESULTS') || '';
            }

            return this.languages.getAppString('LBL_LOADING') || '';
        });
    }

    onPanelShow(): void {
        this.dropdownFilterInput.nativeElement.focus()
        this.calculateSelectAll();
        if (this.field?.definition?.filterOnEmpty ?? false) {
            this.tag.onLazyLoad.emit();
        }
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
        this.loading.set(true);
        const relateName = this.getRelateFieldName();
        const criteria = this.buildCriteria();
        this.filterValue = this.filterValue ?? '';

        const matches = this.filterValue.match(/^\s*$/g);
        if (matches && matches.length) {
            this.filterValue = '';
        }

        let term = this.filterValue;
        this.search(term, criteria).pipe(
            take(1),
            map(data => data.filter((item: ObjectMap) => item[relateName] !== '')),
        ).subscribe(filteredOptions => {
            this.loading.set(false);
            const options = this.mapToItem(filteredOptions);
            this.options = options;
            this.currentOptions.set(options);
            this.addCurrentlySelectedToOptions(options);
            this.calculateSelectAll();
        });
    }

    protected updateFieldValues(): void {
        this.field.valueObjectArray = this.selectedValues ?? [];
        this.field.valueList = this.field.valueObjectArray.map(valueElement => valueElement.id);
        this.field.value = (this?.selectedValues ?? []).map(item => item.id).join(',') ?? '';
        this.setFormControlValue(this.field.value);
    }

    /**
     * Show record selection modal
     */
    protected showSelectModal(): void {
        const modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: false});

        const criteria = this.buildCriteria();
        const filter = this.buildFilter(criteria);

        modal.componentInstance.module = this.getRelatedModule();
        modal.componentInstance.presetFilter = filter;
        modal.componentInstance.multiSelect = true;
        modal.componentInstance.multiSelectButtonLabel = 'LBL_SAVE';
        modal.componentInstance.showFilter = this.field?.definition?.showFilter ?? true;
        modal.componentInstance.selectedValues = (this?.selectedValues ?? []).map(item => item.id).join(',') ?? '';

        modal.result.then((data: RecordListModalResult) => {

            if (!data || !data.selection || !data.selection.selected) {
                return;
            }

            const records = this.getSelectedRecords(data);
            const allRecords = data?.records ?? [];
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
            this.currentOptions.set(this.selectedValues ?? []);
            this.tag.writeValue(this.currentOptions());
        });
    }

    /**
     * Set the record as the selected item
     *
     * @param {object} record to set
     */
    protected setItem(record: Record): void {
        const relateName = this.getRelateFieldName();
        const moduleName = record?.module ?? '';
        const headerField = this.headerFields[moduleName] ?? 'name';
        const subHeader = this.subHeaderFields[moduleName] ?? '';
        const newItem = {
            id: record.id,
            label: this.getLabel(record.attributes, headerField),
            subLabel: this.getSubLabel(record.attributes, subHeader),
            attributes: record,
            module_name: this.moduleNameMapper.toLegacy(moduleName),
            [relateName]: record?.attributes[relateName]
        } as AttributeMap;

        const inList = this.isInList(this.selectedValues, newItem);

        if (inList) {
            return;
        }

        this.selectedValues = [...this.selectedValues ?? [], newItem]

        this.addCurrentlySelectedToOptions(this.options);
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
        const visibleOptions = this?.tag?.visibleOptions() ?? [];
        const selected = this?.selectedValues ?? [];
        let selectedValuesKeys = [];
        if (selected.length) {
            selectedValuesKeys = selected.map(item => item.id);
        }

        if (!visibleOptions.length || !selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }

        if (visibleOptions.length > selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }

        this.selectAll = visibleOptions.every(item => selectedValuesKeys.includes(item.id));
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

        if (filter?.attributes ?? false) {
            Object.keys(filter.attributes).forEach((key) => {
                criteria.preset.params[key] = this.record.attributes[filter.attributes[key]];
            })
        }


        const fields = filter.static ?? [];

        Object.keys(fields).forEach((field) => {
            const fieldValue = fields[field];

            let value = []
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
        })

        return criteria;
    }

    protected buildFilter(criteria) {
        return {
            key: 'default',
            module: 'saved-search',
            attributes: {
                contents: ''
            },
            criteria
        };
    }

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

    protected mapToItem(options: AttributeMap[]) {
        let items = [];
        const relateField = this.getRelateFieldName();
        options.forEach((record) => {
            const moduleName = this.moduleNameMapper.toFrontend(record?.module_name ?? '');
            const headerField = this.headerFields[moduleName] ?? {name: 'name'};
            const subHeader = this.subHeaderFields[moduleName] ?? {name: ''};
            items.push({
                id: record.id,
                label: this.getLabel(record, headerField),
                subLabel: this.getSubLabel(record, subHeader),
                attributes: record,
                module_name: record?.module_name,
                [relateField]: record[relateField]
            } as AttributeMap);
        })

        return items;
    }

    protected showIcon() {
        return this.field?.metadata?.showIcon ?? false;
    }

    protected getSubLabel(record, field): string {

        const type = field.type ?? '';
        const key = field.name;

        if (type === 'enum') {
            const map = this.languages.getAppListString(field.definition.options);
            const index = record[key];
            return map[index];
        }

        return record[key];
    }


    protected getLabel(record, field): string {

        if (field === 'name') {
            return record[field];
        }

        if (field.name) {
            return record[field.name];
        }

        return '';
    }
}
