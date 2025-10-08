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

import {Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {BaseMultiEnumComponent} from '../../../base/base-multienum.component';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {take} from "rxjs/operators";
import {ScreenSizeObserverService} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {PrimeNGConfig} from "primeng/api";
import {MultiSelect} from "primeng/multiselect";

@Component({
    selector: 'scrm-multienum-filter',
    templateUrl: './multienum.component.html',
    styleUrls: []
})
export class MultiEnumFilterFieldComponent extends BaseMultiEnumComponent implements OnInit, OnDestroy {
    @ViewChild('multiSelect') multiSelect: MultiSelect;

    placeholderLabel: string = '';
    selectedItemsLabel: string = '';
    emptyFilterLabel: string = '';
    maxSelectedLabels: number = 20;
    selectAll: boolean = false;

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigStore: SystemConfigStore,
        private primengConfig: PrimeNGConfig
    ) {
        super(languages, typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        this.field.valueList = [];

        if (this.field.criteria.values && this.field.criteria.values.length > 0) {
            this.field.valueList = this.field.criteria.values;
        }

        const maxSelectedLabelsForDisplay = this.systemConfigStore.getUi('multiselect_max_number');
        this.screenSize.screenSize$
            .pipe(take(1))
            .subscribe((screenSize: any) => {
                this.maxSelectedLabels = maxSelectedLabelsForDisplay[screenSize] || this.maxSelectedLabels;
            })

        this.getTranslatedLabels();
        super.ngOnInit();

        this.primengConfig.ripple = true;
    }

    public onAdd(): void {

        const value = this.selectedValues.map(option => option.value);
        this.field.valueList = value;
        this.field.formControl.setValue(value);
        this.field.formControl.markAsDirty();
        this.field.criteria.operator = '=';
        this.field.criteria.values = value;

        this.calculateSelectAll();
    }

    public onRemove(): void {

        let value = this.selectedValues.map(option => option.value);
        if (!value) {
            value = [];
        }

        this.field.valueList = value;
        this.field.formControl.setValue(value);
        this.field.formControl.markAsDirty();
        this.field.criteria.operator = '=';
        this.field.criteria.values = value;

        this.calculateSelectAll();
    }

    public onClear(): void {
        this.selectedValues = [];
        this.multiSelect.filterValue = '';
        this.onRemove();
    }

    onPanelShow(): void {
        this.multiSelect.filterInputChild.nativeElement.focus();
        this.multiSelect.filterValue = '';
        this.calculateSelectAll();
    }

    onFilter(): void {
        this.calculateSelectAll();
    }


    public getPlaceholderLabel(): string {
        return this.languages.getAppString('LBL_SELECT_ITEM') || '';
    }

    public onSelectAll(event): void {
        this.selectAll = event.checked;
        if (this.selectAll) {

            if (this.multiSelect.visibleOptions() && this.multiSelect.visibleOptions().length) {
                this.selectedValues = this.multiSelect.visibleOptions();
            } else {
                this.selectedValues = this.options;
            }
            this.onAdd();
        } else {
            this.selectedValues = [];
            this.onRemove();
        }
    }

    public getTranslatedLabels(): void {
        this.placeholderLabel = this.languages.getAppString('LBL_SELECT_ITEM') || '';
        this.selectedItemsLabel = this.languages.getAppString('LBL_ITEMS_SELECTED') || '';
        this.emptyFilterLabel = this.languages.getAppString('ERR_SEARCH_NO_RESULTS') || '';
    }

    protected addEmptyStringOption(): boolean {
        return false;
    }

    protected calculateSelectAll(): void {
        const visibleOptions = this?.multiSelect?.visibleOptions() ?? [];
        const selectedValuesKeys = (this?.selectedValues ?? []).map(item => item.value);

        if (!visibleOptions.length || !selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }

        if (visibleOptions.length > selectedValuesKeys.length) {
            this.selectAll = false;
            return;
        }

        this.selectAll = visibleOptions.every(item => selectedValuesKeys.includes(item.value));
    }
}
