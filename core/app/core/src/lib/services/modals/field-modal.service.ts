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

import {Injectable} from '@angular/core';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {LanguageStore} from '../../store/language/language.store';
import {MessageService} from '../message/message.service';
import {
    FieldGridModalComponent,
    FieldModalValidationFunction
} from "../../components/modal/components/field-grid-modal/field-grid-modal.component";
import {Field} from "../../common/record/field.model";
import {FieldGridOptions} from "../../components/field-grid/field-grid.model";
import {emptyObject} from "../../common/utils/object-utils";
import {Record} from "../../common/record/record.model";
import {SystemConfigStore} from "../../store/system-config/system-config.store";


export interface FieldModalOptions {
    fields: Field[];
    titleKey: string;
    topButtonsDropdownLabelKey?: string;
    descriptionKey?: string;
    module?: string;
    maxColumns?: number;
    centered?: boolean;
    scrollable?: boolean;
    size?: 'sm' | 'lg' | 'xl';
    fieldGridOptions?: FieldGridOptions;
    validation?: FieldModalValidationFunction

    [key: string]: any;
}

export interface FieldModalResult {
    fields: Field[];
    module: string;

    [key: string]: any;
}

@Injectable({
    providedIn: 'root'
})
export class FieldModalService {

    constructor(
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected modalService: NgbModal,
        protected systemConfigs: SystemConfigStore
    ) {
    }

    /**
     * Show Field Modal
     *
     * @param options
     * @param onSelectCallback
     * @returns {void}
     */
    showFieldModal(options: FieldModalOptions, onSelectCallback: Function = null): void {

        const modal = this.modalService.open(FieldGridModalComponent, {
            centered: options?.centered ?? false,
            scrollable: options.scrollable ?? false,
            size: options?.size ?? null
        });

        modal.componentInstance.fields = options?.fields ?? [];
        modal.componentInstance.titleKey = options?.titleKey ?? '';
        modal.componentInstance.descriptionKey = options?.descriptionKey ?? '';
        modal.componentInstance.module = options?.module ?? 'default';
        modal.componentInstance.actionLabelKey = options.actionLabelKey ?? 'LBL_ACTIONS';
        modal.componentInstance.fieldGridOptions = options.fieldGridOptions;
        modal.componentInstance.validation = options?.validation ?? null;

        if ((options?.limit ?? false) && (options?.limit?.showLimit ?? false)) {

            let limit = this.systemConfigs.getConfigValue(options.limit.limit_key);

            if (limit === null || limit.length === 0){
                limit = 50;
            }

            modal.componentInstance.limit = limit;
            modal.componentInstance.limitEndLabel = options.limit.limitEndLabel;
        }

        modal.result.then((result: FieldModalResult) => {

            if (result.type === 'close-button') {
                return;
            }

            const fields = result.fields;
            const module = result.module;

            if (fields.length < 1) {
                return;
            }

            const response = this.getValues(fields, module);

            if (onSelectCallback !== null) {
                onSelectCallback(response.fields);
            }
        });
    }

    /**
     *
     * @param fields
     * @param module
     * @protected
     */
    public getValues(fields: Field[], module: string) {

        let response = {
            fields: {},
            module: module,
        };

        Object.entries(fields).forEach(([key, field]) => {

            if (field.type === 'line-items') {
                const items = field.items;
                const fieldKey = field.definition?.lineItems?.definition?.name ?? '';

                let values = this.getFieldFromItem(field.name, items, fieldKey);

                if (emptyObject(values)) {
                    return;
                }

                response.fields[field.name] = {
                    definition: field.definition,
                    type: 'line-items',
                    module: field?.definition?.module ?? module,
                    value: values
                };

                return;
            }

            if (!field.value){
                return;
            }

            response.fields[field.name] = {
                definition: field.definition,
                type: field.type,
                module: field?.definition?.module ?? module,
                value: field.value
            }
        })

        return response;
    }

    /**
     *
     * @param fieldName
     * @param items
     * @param fieldKey
     * @protected
     */
    protected getFieldFromItem(fieldName, items: Record[], fieldKey) {

        const values = [];

        Object.entries(items).forEach(([key, item]) => {
            if (item.fields[fieldKey].attributes[fieldName].value !== ''
            && item?.attributes?.deleted !== 1){
                values.push(item.fields[fieldKey].attributes[fieldName].value);
            }
        })

        return values;
    }

}
