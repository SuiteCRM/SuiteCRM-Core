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

import {Component} from '@angular/core';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from "../../../../store/language/language.store";
import {BaseMultiEnumComponent} from "../../../base/base-multienum.component";
import {isEmpty, isEqual, isNull, isObject, uniqBy} from "lodash-es";
import {Option} from "../../../../common/record/field.model";
import {isVoid} from "../../../../common/utils/value-utils";
import {ModuleNavigation} from "../../../../services/navigation/module-navigation/module-navigation.service";
import {ModuleNameMapper} from "../../../../services/navigation/module-name-mapper/module-name-mapper.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";

@Component({
    selector: 'scrm-multirelate-detail',
    templateUrl: './multirelate.component.html',
    styleUrls: [],
    providers: []
})
export class MultiRelateDetailFieldComponent extends BaseMultiEnumComponent {

    constructor(
        protected languages: LanguageStore,
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected systemConfigStore: SystemConfigStore,
        protected logicDisplay: FieldLogicDisplayManager,
        protected moduleNameMapper: ModuleNameMapper,
        protected navigation: ModuleNavigation,
    ) {
        super(languages, typeFormatter, logic, logicDisplay);
    }

    protected initValue(): void {
        const fieldValueList = this.field.valueList;

        if (isVoid(fieldValueList) || isEmpty(fieldValueList)) {
            return;
        }
        const relateName = this.getRelateFieldName();

        this.selectedValues = fieldValueList.map(valueElement => {
            const relateValue = valueElement['attributes'][relateName] ?? '';
            const link = this.buildLink(valueElement['attributes']['id']) ?? '';
            return this.buildOptionFromValue(relateValue, link);
        });
        this.selectedValues = uniqBy(this.selectedValues, 'value');
    }

    protected buildOptionFromValue(value: string, link: string): Option {
        const option: Option = { value: '', label: '', };

        if (isNull(value)) {
            return option;
        }
        option.value = (typeof value !== 'string' ? JSON.stringify(value) : value).trim();
        option.label = option.value;
        option.link = link;
        return option;
    }

    getRelateFieldName(): string {
        if (!this.field?.definition?.metadata?.relateSearchField) {
            return (this.field && this.field.definition && this.field.definition.rname) || 'name';
        }
        return this.field.definition.metadata.relateSearchField;
    }

     buildLink(id: string): string {
        let linkModule = this.field.definition.module ?? '';

        if (!linkModule){
            return '';
        }

        if (linkModule && id) {
            const moduleName = this.moduleNameMapper.toFrontend(linkModule);
            return this.navigation.getRecordRouterLink(
                moduleName,
                id
            );
        }

        return '';
    }

    getDirection(): string {
        return this.field.definition.displayDirection ?? 'vertical';
    }

    getBreakpoint() {
        const breakpoint = this.systemConfigStore.getUi('multiselect_record_breakpoint');
        return this.field.definition.breakpoint ?? breakpoint;
    }

    getExtraOptions() {
        return this.selectedValues.slice(this.getBreakpoint());
    }
}
