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

import {Component, computed, ElementRef, HostListener, Signal, signal, ViewChild, WritableSignal} from '@angular/core';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
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
import {MultiFlexRelateService} from "../../../../services/record/relate/multi-flex-relate.service";
import {BaseMultiFlexRelateComponent} from "../../../base/base-multi-flex-relate.component";
import {SelectItem, SelectItemGroup} from "primeng/api";
import {isEmail} from "../../../../common/utils/value-utils";
import {StringMap} from "../../../../common/types/string-map";

@Component({
    selector: 'scrm-multiflexrelate-edit',
    templateUrl: './multiflexrelate.component.html',
    styleUrls: [],
    providers: [MultiFlexRelateService]
})
export class MultiFlexRelateEditFieldComponent extends BaseMultiFlexRelateComponent {
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
    maxSelectedLabels: number = 20;
    selectAll: WritableSignal<boolean> = signal(false);
    filterValue: string | undefined = '';
    currentOptionGroups: WritableSignal<SelectItemGroup[]> = signal([]);
    loading: WritableSignal<boolean> = signal(false);
    emptyFilterLabel: Signal<string> = computed(() => {
        if (!this.loading()) {
            return this.languages.getAppString('ERR_SEARCH_NO_RESULTS') || '';
        }

        return this.languages.getAppString('LBL_LOADING') || '';
    });

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
        protected relateService: MultiFlexRelateService,
        protected moduleNameMapper: ModuleNameMapper,
        protected modalService: NgbModal,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(languages, typeFormatter, relateService, moduleNameMapper, logic, logicDisplay);
    }

    /**
     * On init handler
     */
    ngOnInit(): void {
        this.selectAll.set(false);
        super.ngOnInit();
        const relatedFieldName = this.getRelateFieldName();


        if ((this.field?.valueList ?? []).length > 0 || (this.field?.valueObjectArray ?? []).length) {
            if ((this.field?.valueList ?? []).length > (this.field?.valueObjectArray ?? []).length) {
                this.field.valueObjectArray = deepClone(this.field.valueList);
            }
            this.selectedValues = this.field.valueObjectArray.map(valueElement => {

                const relateValue = valueElement[relatedFieldName] ?? valueElement?.attributes[relatedFieldName] ?? '';
                const moduleName = valueElement['module_name'] ?? valueElement?.attributes['module_name'] ?? '';

                const relateId = valueElement['id'] ?? '';

                const headerField = this.headerFields[moduleName] ?? 'name';
                const subHeader = this.subHeaderFields[moduleName] ?? '';
                const headerFieldValue = valueElement[headerField] ?? valueElement?.attributes[headerField] ?? '';
                const subHeaderFieldValue = valueElement[subHeader] ?? valueElement?.attributes[subHeader] ?? '';
                return {
                    ...(valueElement.attributes ?? {}),
                    id: relateId,
                    [relatedFieldName]: relateValue,
                    [headerField]: headerFieldValue,
                    [subHeader]: subHeaderFieldValue,
                    module_name: moduleName
                };
            });
            this.currentOptionGroups.set(this.splitIntoGroups(this.selectedValues));
        } else {
            this.selectedValues = [];
            this.field.valueObjectArray = [];
            this.field.valueList = [];
            this.currentOptionGroups.set([]);
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
        this.selectAll.set(false);
        this.filterValue = '';
        this.currentOptionGroups.set([]);
        this.onRemove();
    }

    onSelectAll(): void {
        this.selectAll.set(!this.selectAll());
        if (this.selectAll()) {
            if (this.tag.visibleOptions() && this.tag.visibleOptions().length) {

                const selectedValuesKeys: { [key: string]: boolean } = {};
                this.selectedValues.forEach(item => {
                    if (item?.id) {
                        selectedValuesKeys[item.id] = true
                    }
                });

                const selected = [...this.selectedValues];

                this.tag.visibleOptions().forEach((item: SelectItem) => {
                    if ((item as any)?.group) {
                        return;
                    }

                    const id = item.value?.id ?? '';
                    if (!id || selectedValuesKeys[id]) {
                        return;
                    }

                    selected.push(item.value);
                });

                this.selectedValues = selected;
            }

            this.onAdd();
        } else {
            if (this.tag.visibleOptions() && this.tag.visibleOptions().length) {

                const unSelectedValues: { [key: string]: boolean } = {};

                this.tag.visibleOptions().forEach((item: SelectItem) => {
                    if ((item as any)?.group) {
                        return;
                    }

                    const id = item.value?.id ?? '';
                    if (id || !unSelectedValues[id]) {
                        unSelectedValues[id] = true;
                    }
                });

                const newSelectedValues: AttributeMap[] = [];

                this.selectedValues.forEach(item => {
                    if (!item?.id || unSelectedValues[item.id]) {
                        return;
                    }

                    newSelectedValues.push(item);
                });

                this.selectedValues = newSelectedValues;
            }

            this.onRemove();
        }
    }

    getTranslatedLabels(): void {
        this.placeholderLabel = this.languages.getAppString('LBL_SELECT_ITEM') || '';
        this.selectedItemsLabel = this.languages.getAppString('LBL_ITEMS_SELECTED') || '';
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
        const groups = this.splitIntoGroups(this.options);
        this.currentOptionGroups.set(groups);
    }

    onFilterInput(event: KeyboardEvent): void {
        event?.stopPropagation();
        this.selectAll.set(false);
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
            this.options = filteredOptions;
            this.addCurrentlySelectedToOptions(filteredOptions);
            const groups = this.splitIntoGroups(filteredOptions);
            this.insertAppendableOptions(term, groups);
            this.currentOptionGroups.set(groups);
            setTimeout(() => {
                this.calculateSelectAll();
            }, 0);
        });
    }

    protected insertAppendableOptions(term: string, groups: SelectItemGroup[]): void {
        Object.keys(this.appendableModuleConfigs ?? {}).forEach((module) => {
            const config = this.appendableModuleConfigs[module];
            const matchMethod = config?.matchMethod?.method ?? '';
            if (!matchMethod) {
                return;
            }

            let isAppendable: Function;

            if (matchMethod === 'function') {
                const matchFunction = config?.matchMethod?.function ?? '';
                if (matchFunction !== 'isEmail') {
                    return;
                }

                isAppendable = (term) => isEmail(term);
            } else if (matchMethod === 'regex') {
                if (!config?.matchMethod?.regex) {
                    return;
                }
                const regex = new RegExp(config?.matchMethod?.regex);
                isAppendable = (term) => regex.test(term);
            }

            if (isAppendable(term)) {
                let insertedGroup = {
                    label: this.languages.getListLabel('moduleList', config?.groupLabelKey ?? ''),
                    value: config?.groupValue ?? '',
                    items: []
                } as SelectItemGroup;

                const found = groups.some(group => {
                    if (group.value === (config?.groupValue ?? '')) {
                        insertedGroup = group;
                        return true;
                    }
                    return false;
                });

                if (!found) {
                    groups.unshift(insertedGroup);
                }

                const foundItem = insertedGroup.items.some(item => {
                    if (item.label === term) {
                        return true;
                    }
                    return false;
                });

                if (!foundItem) {
                    const newItem = {
                        label: term,
                        value: {},
                        icon: config?.icon ?? '',
                    } as SelectItem;

                    const valueMap: StringMap = config?.valueMap ?? {};
                    Object.keys(valueMap).forEach((key) => {
                        if (valueMap[key] === '{{term}}') {
                            newItem.value[key] = term;
                            return;
                        }

                        newItem.value[key] = valueMap[key];
                    });

                    insertedGroup.items.unshift(newItem);
                }
            }
        });
    }

    protected updateFieldValues(): void {
        this.field.valueObjectArray = deepClone(this.selectedValues ?? []);
        this.field.valueList = deepClone(this.selectedValues ?? []);
        this.field.value = (this?.selectedValues ?? []).map(item => item.id).join(',') ?? '';
        this.field.formControl.setValue(deepClone(this.selectedValues ?? []));
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
            [relateName]: record?.attributes[relateName]
        } as ObjectMap;

        const inList = this.isInList(this.selectedValues, newItem);

        if (inList) {
            return;
        }

        this.selectedValues.push(newItem)

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

        if (!this?.selectedValues?.length || !this?.tag?.visibleOptions()?.length) {
            this.selectAll.set(false);
            return;
        }

        const selectedValuesKeys: { [key: string]: boolean } = {};
        this.selectedValues.forEach(item => {
            if (item?.id) {
                selectedValuesKeys[item.id] = true
            }
        });

        let allSelected = true;
        this.tag.visibleOptions().some((item: SelectItem) => {
            if ((item as any)?.group) {
                return false;
            }

            const id = item.value?.id ?? '';

            if (!selectedValuesKeys[id]) {
                allSelected = false;
                return true;
            }

            return false;
        });

        this.selectAll.set(allSelected);
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
            criteria.filters[field] = {
                field: field,
                operator: '=',
                values: [fields[field]],
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
}
