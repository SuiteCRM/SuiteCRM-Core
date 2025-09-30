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

import {
    AfterViewInit,
    Component,
    computed,
    ElementRef,
    HostListener,
    Signal,
    signal,
    ViewChild,
    WritableSignal
} from '@angular/core';
import {emptyObject} from '../../../../common/utils/object-utils';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {Field} from '../../../../common/record/field.model';
import {AttributeMap, Record} from '../../../../common/record/record.model';
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
import {debounceTime, map, take} from "rxjs/operators";
import {Dropdown, DropdownFilterOptions} from "primeng/dropdown";
import {ConfirmationModalService} from "../../../../services/modals/confirmation-modal.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {SearchCriteria} from "../../../../common/views/list/search-criteria.model";
import {AppStateStore} from "../../../../store/app-state/app-state.store";

@Component({
    selector: 'scrm-relate-edit',
    templateUrl: './relate.component.html',
    styleUrls: [],
    providers: [RelateService]
})
export class RelateEditFieldComponent extends BaseRelateComponent implements AfterViewInit {
    @ViewChild('tag') tag: Dropdown;
    @ViewChild('dropdownFilterInput') dropdownFilterInput: ElementRef;
    @HostListener('document:click', ['$event'])
    onDocClick(event) {
        const clickedInside = this.tag?.el?.nativeElement.contains(event.target);
        if (!clickedInside){
            this.tag.hide();
        }
    }

    selectButton: ButtonInterface;
    idField: Field;
    selectedValue: AttributeMap = {};
    loading: WritableSignal<boolean> = signal(false);

    placeholderLabel: string = '';
    emptyFilterLabel: Signal<string> = signal('');
    filterValue: string | undefined = '';


    /**
     * Constructor
     *
     * @param {object} languages service
     * @param {object} typeFormatter service
     * @param {object} relateService service
     * @param {object} moduleNameMapper service
     * @param {object} modalService service
     * @param appStateStore
     * @param {object} logic
     * @param config
     * @param {object} logicDisplay
     * @param confirmation
     */
    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected relateService: RelateService,
        protected moduleNameMapper: ModuleNameMapper,
        protected modalService: NgbModal,
        protected appStateStore: AppStateStore,
        protected logic: FieldLogicManager,
        protected config: SystemConfigStore,
        protected logicDisplay: FieldLogicDisplayManager,
        protected confirmation: ConfirmationModalService
    ) {
        super(languages, typeFormatter, relateService, moduleNameMapper, logic, logicDisplay, config);
    }

    /**
     * On init handler
     */
    ngOnInit(): void {

        super.ngOnInit();
        this.init();
        this.getTranslatedLabels();

        this.selectButton = {
            klass: ['btn', 'btn-sm', 'btn-outline-secondary', 'm-0', 'border-0'],
            onClick: (): void => {
                this.showSelectModal();
            },
            icon: 'cursor'
        } as ButtonInterface;

    }

    ngAfterViewInit(): void {

        if (this.field?.definition?.filterOnEmpty ?? false) {
            this.tag.onLazyLoad.emit();
        }
    }

    protected init(): void {

        super.init();

        this.initValue();

        const idFieldName = this.getRelateIdField();
        if (idFieldName && this.record && this.record.fields && this.record.fields[idFieldName]) {
            this.idField = this.record.fields[idFieldName];
        }

        const clickDebounceTime = this.getDebounceTime();

        this.subs.push(this.filterInputBuffer$.pipe(debounceTime(clickDebounceTime)).subscribe(value => {
            this.filterResults(this.filterValue ?? '');
        }));

    }

    protected initValue(): void {
        if (!this.field.valueObject) {
            this.selectedValue = {};
            this.field.formControl.setValue('');
            return;
        }

        if (!this.field.valueObject.id) {
            this.selectedValue = {};
            this.field.formControl.setValue('');
            return;
        }

        const rname = this.field?.definition?.rname ?? 'name';

        if (this.field?.metadata?.relateSearchField) {
            this.field.valueObject[this.field.metadata.relateSearchField] = this.field.valueObject[rname];
        }

        const relateValue = this.field.valueObject[rname];
        const id = this.field.valueObject.id;

        if (relateValue) {
            const relateName = this.getRelateFieldName();
            this.selectedValue = {...this.field.valueObject, id: id, [relateName]: relateValue};
        }

        if (this.idField) {
            this.idField.value = id;
            this.idField.formControl.setValue(id);
        }

        this.options = [this.selectedValue];
        if (this.selectedValue !== null) {
            this.currentOptions.set(this.options)
        }
    }

    /**
     * Handle newly added item
     *
     * @param {object} item added
     */
    onAdd(item, event = null): void {
        const relateName = this.getRelateFieldName();

        if (item) {
            if (event && (this.field?.metadata?.selectConfirmation ?? false)) {
                const confirmationLabel = this.field.metadata.confirmationLabel ?? '';
                const confirmationMessages = this.field.metadata.confirmationMessages ?? [];
                const confirmation = [confirmationLabel, ...confirmationMessages];
                this.confirmation.showModal(
                    confirmation,
                    () => {
                        this.tag.writeValue(item);
                        this.setValue(item.id, item[relateName], item);
                    },
                    () => {
                        this.tag.writeValue(this.field.valueObject);
                        const value = this.field.value;

                        if (value === '') {
                            this.onClear(event);
                            return;
                        }

                        this.setValue(this.field.valueObject.id, value, this.field.valueObject);
                    });
                return;
            }
            this.setValue(item.id, item[relateName], item);
            return;
        }

        this.setValue('', '');
        this.selectedValue = {};

        return;
    }

    onModuleChange(): void {

        const currentModule = this.initModule();
        const newModule = this?.field?.definition?.module ?? this.record.fields[this.field.definition.type_name].value ?? '';

        if (currentModule === newModule) {
            return;
        }

        this.initModule.set(newModule);

        if (currentModule === '' && currentModule !== newModule) {
            this.init();
        }

        if (newModule === '') {
            this.status = 'no-module';
        } else {
            this.init();
            this.status = '';
            this.tag.clear();
        }
    }

    /**
     * Handle item removal
     */
    onRemove(): void {
        this.setValue('', '');
        this.selectedValue = {};
        this.options = [];
        this.currentOptions.set([]);
    }

    onClear(event): void {
        this.selectedValue = {};
        this.filterValue = '';
        this.options = [];
        this.onRemove();

        if (this.field?.definition?.filterOnEmpty ?? false) {
            this.tag.onLazyLoad.emit();
        }
    }

    onFilter(): void {
        this.filterInputBuffer.next(this.filterValue ?? '');
    }

    filterResults(filterValue: string): void {
        this.loading.set(true);
        const relateName = this.getRelateFieldName();
        const criteria = this.buildCriteria();
        const matches = filterValue.match(/^\s*$/g);
        if (matches && matches.length) {
            filterValue = '';
        }
        let term = filterValue;
        this.search(term, criteria).pipe(
            take(1),
            map(data => data.filter(item => item[relateName] !== '')),
            map(filteredData => filteredData.map(item => ({
                ...item,
                id: item.id,
                [relateName]: item[relateName]
            })))
        ).subscribe(filteredOptions => {
            this.loading.set(false);
            this.options = filteredOptions;
            this.currentOptions.set(filteredOptions);

            if (!this?.selectedValue || !this?.selectedValue?.id) {
                return;
            }

            let found = false;
            filteredOptions.some(value => {
                if (value?.id === this.selectedValue.id) {
                    found = true;
                    return true;
                }

                return false;
            });

            if (found === false && this.selectedValue) {
                this.options.push(this.selectedValue);
            }

        })
    }

    resetFunction(options: DropdownFilterOptions) {
        this.filterValue = '';
        this.options = [];
        if (!emptyObject(this.selectedValue)) {
            this.options = [this.selectedValue];
        }
    }

    onFilterInput(event: KeyboardEvent) {
        event.stopPropagation()
        this.tag.onLazyLoad.emit()
    }

    /**
     * Set value on field
     *
     * @param {string} id to set
     * @param {string} relateValue to set
     * @param other
     */
    protected setValue(id: string, relateValue: string, other: AttributeMap = {}): void {
        const relate = this.buildRelate(id, relateValue, other);
        this.field.value = relateValue;
        this.field.valueObject = relate;
        this.field.formControl.setValue(relateValue);
        this.field.formControl.markAsDirty();

        if (this.idField) {
            this.idField.value = id;
            this.idField.formControl.setValue(id);
            this.idField.formControl.markAsDirty();
        }

        if (relateValue) {
            const relateName = this.getRelateFieldName();
            this.selectedValue = {...other, id: id, [relateName]: relateValue};
        }


        if (this.selectedValue === null) {
            return;
        }

        const inOptions = (this.options ?? []).some((option) => {
            return option['id'] === id;
        });

        if (!inOptions) {
            const options = this.options ?? [];
            options.push(this.selectedValue);
            this.options = options;
            this.currentOptions.set(this.options)
        }
    }

    /**
     * Show record selection modal
     */
    protected showSelectModal(): void {
        const modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: true});

        const criteria = this.buildCriteria();
        const filter = this.buildFilter(criteria);

        modal.componentInstance.module = this.getRelatedModule();
        modal.componentInstance.presetFilter = filter;
        modal.componentInstance.showFilter = this.field?.definition?.showFilter ?? true;

        modal.result.then((data: RecordListModalResult) => {

            if (!data || !data.selection || !data.selection.selected) {
                return;
            }

            if (this.field?.metadata?.selectConfirmation ?? false) {
                const confirmationLabel = this.field.metadata.confirmationLabel ?? '';
                const confirmationMessages = this.field.metadata.confirmationMessages ?? [];
                const confirmation = [confirmationLabel, ...confirmationMessages];
                this.confirmation.showModal(
                    confirmation,
                    () => {
                        const record = this.getSelectedRecord(data);
                        this.setItem(record);
                    });
                return;
            }

            const record = this.getSelectedRecord(data);
            this.setItem(record);
        });
    }

    /**
     * Get Selected Record
     *
     * @param {object} data RecordListModalResult
     * @returns {object} Record
     */
    protected getSelectedRecord(data: RecordListModalResult): Record {
        let id = '';
        Object.keys(data.selection.selected).some(selected => {
            id = selected;
            return true;
        });

        let record: Record = null;

        data.records.some(rec => {
            if (rec && rec.id === id) {
                record = rec;
                return true;
            }
        });

        return record;
    }

    /**
     * Set the record as the selected item
     *
     * @param {object} record to set
     */
    protected setItem(record: Record): void {
        this.tag.writeValue(record.attributes);
        this.onAdd(record.attributes);
    }

    public getTranslatedLabels(): void {
        this.placeholderLabel = this.languages.getAppString('LBL_SELECT_ITEM') || '';
        this.emptyFilterLabel = computed(() => {
            if (!this.loading()) {
                return this.languages.getAppString('ERR_SEARCH_NO_RESULTS') || '';
            }

            return this.languages.getAppString('LBL_LOADING') || '';
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
            criteria.preset.params.module = filter.preset.params?.module ?? this.module;
        }

        if (filter?.attributes ?? false) {
            if (criteria?.preset ?? false) {
                Object.keys(filter.attributes).forEach((key) => {
                    criteria.preset.params[key] = this.record.attributes[filter.attributes[key]];
                });
                return;
            }

            Object.keys(filter.attributes).forEach((key) => {
                let values = this.record.attributes[filter.attributes[key]] ?? '';

                if (filter.attributes[key] === 'currentUser') {
                    values = this.appStateStore.getCurrentUser().id;
                }

                criteria.filters[key] = {
                    field: key,
                    operator: '=',
                    values: [values],
                    rangeSearch: false
                };
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



    focusFilterInput() {
        this.dropdownFilterInput.nativeElement.focus();

        if (this.field?.definition?.filterOnEmpty ?? false) {
            this.tag.onLazyLoad.emit();
        }
    }
}
