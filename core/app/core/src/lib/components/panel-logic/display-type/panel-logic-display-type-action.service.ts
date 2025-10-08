/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {PanelLogicActionData, PanelLogicActionHandler} from '../panel-logic.action';
import {isVoid} from '../../../common/utils/value-utils';
import {Record} from '../../../common/record/record.model';
import {ViewMode} from '../../../common/views/view.model';
import {Field} from '../../../common/record/field.model';
import {Action} from '../../../common/actions/action.model';
import {StringArrayMap} from '../../../common/types/string-map';
import {StringArrayMatrix} from '../../../common/types/string-matrix';
import {isEmpty} from 'lodash-es';

@Injectable({
    providedIn: 'root'
})
export class PanelLogicDisplayTypeAction extends PanelLogicActionHandler {

    key = 'displayType';
    modes = ['edit', 'detail', 'list', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor() {
        super();
    }

    run(data: PanelLogicActionData, action: Action): boolean {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return true;
        }

        const activeOnFields: StringArrayMap = (action.params && action.params.activeOnFields) || {} as StringArrayMap;
        const relatedFields: string[] = Object.keys(activeOnFields);

        const activeOnAttributes: StringArrayMatrix = (action.params && action.params.activeOnAttributes) || {} as StringArrayMatrix;
        const relatedAttributesFields: string[] = Object.keys(activeOnAttributes);

        if (!relatedFields.length && !relatedAttributesFields.length) {
            return true;
        }

        return this.isActive(relatedFields, record, activeOnFields, relatedAttributesFields, activeOnAttributes);
    }

    /**
     * Check if any of the configured values is currently set
     * @param {array} relatedFields
     * @param {object} record
     * @param {object} activeOnFields
     * @param {array} relatedAttributesFields
     * @param {object} activeOnAttributes
     */
    protected isActive(
        relatedFields: string[],
        record: Record,
        activeOnFields: StringArrayMap,
        relatedAttributesFields: string[],
        activeOnAttributes: StringArrayMatrix
    ) {
        let isActive = true;
        if (!isEmpty(activeOnFields)) {
            isActive = this.areFieldsActive(relatedFields, record, activeOnFields);
        }

        if (!isEmpty(activeOnAttributes)) {
            isActive = isActive && this.areAttributesActive(relatedAttributesFields, record, activeOnAttributes);
        }

        return isActive;
    }

    /**
     * Are attributes active
     * @param {array} relatedAttributesFields
     * @param {object} record
     * @param {object} activeOnAttributes
     */
    protected areAttributesActive(
        relatedAttributesFields: string[],
        record: Record,
        activeOnAttributes: StringArrayMatrix
    ): boolean {
        return relatedAttributesFields.every(fieldKey => {

            const fields = record.fields;
            const field = (fields && record.fields[fieldKey]) || null;
            const attributes = activeOnAttributes[fieldKey] && Object.keys(activeOnAttributes[fieldKey]);
            if (!field || !attributes || !attributes.length) {
                return;
            }

            return attributes.some(attributeKey => {
                const activeValues = activeOnAttributes[fieldKey][attributeKey];
                const attribute = field.attributes && field.attributes[attributeKey];

                if (!activeValues || !activeValues.length || !attribute) {
                    return;
                }

                return this.isValueActive(attribute, activeValues);
            });
        });
    }

    /**
     * Are fields active
     * @param {array} relatedFields
     * @param {object} record
     * @param {object} activeOnFields
     */
    protected areFieldsActive(relatedFields: string[], record: Record, activeOnFields: StringArrayMap): boolean {
        return relatedFields.every(fieldKey => {

            const fields = record.fields;
            const field = (fields && record.fields[fieldKey]) || null;
            const activeValues = activeOnFields[fieldKey];

            if (!field || !activeValues || !activeValues.length) {
                return true;
            }
            return this.isValueActive(field, activeValues);
        });
    }

    /**
     * Is value active
     * @param {object} field
     * @param {array} activeValues
     */
    protected isValueActive(field: Field, activeValues: string[]): boolean {
        let isActive = false;

        if (field.valueList && field.valueList.length) {
            field.valueList.some(value => {
                return activeValues.some(activeValue => {
                    if (activeValue === value) {
                        isActive = true;
                        return true;
                    }
                })
            });

            return isActive;
        }

        if (!isVoid(field.value)) {
            activeValues.some(activeValue => {

                if (activeValue === field.value) {
                    isActive = true;
                }

            });
        }

        return isActive;
    }
}
