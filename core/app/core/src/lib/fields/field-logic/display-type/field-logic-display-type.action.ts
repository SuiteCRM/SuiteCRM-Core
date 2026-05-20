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

import {isEmpty} from 'lodash-es';
import {Injectable} from '@angular/core';
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {Action} from '../../../common/actions/action.model';
import {DisplayType, Field} from '../../../common/record/field.model';
import {Record} from '../../../common/record/record.model';
import {StringArrayMap} from '../../../common/types/string-map';
import {ViewMode} from '../../../common/views/view.model';
import {ConditionOperatorManager} from '../../../services/condition-operators/condition-operator.manager';
import {ObjectArrayMatrix} from "../../../common/types/object-map";

/**
 * @DEPRECATED
 */

@Injectable({
    providedIn: 'root'
})
export class FieldLogicDisplayTypeAction extends FieldLogicActionHandler {

    key = 'displayType';
    modes = ['edit', 'detail', 'list', 'create', 'massupdate', 'filter'] as ViewMode[];

    constructor(protected operatorManager: ConditionOperatorManager) {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const record = data.record;
        const field = data.field;

        if (!record || !field) {
            return;
        }

        const activeOnFields: StringArrayMap = (action.params && action.params.activeOnFields) || {} as StringArrayMap;
        const relatedFields: string[] = Object.keys(activeOnFields);

        const activeOnAttributes: ObjectArrayMatrix = (action.params && action.params.activeOnAttributes) || {} as ObjectArrayMatrix;
        const relatedAttributesFields: string[] = Object.keys(activeOnAttributes);

        if (!relatedFields.length && !relatedAttributesFields.length) {
            return;
        }

        const targetDisplay = action.params && action.params.targetDisplayType;

        if (!targetDisplay) {
            return;
        }

        let isActive = this.isActive(relatedFields, record, activeOnFields, relatedAttributesFields, activeOnAttributes);

        let display = data.field.defaultDisplay;
        if (isActive) {
            display = targetDisplay;
        }

        data.field.display.set(display as DisplayType);

        const resetOn: string = (action.params && action.params.resetOn) || 'none';

        if (resetOn === display) {
            if (data.field.valueList && data.field.valueList.length) {
                data.field.valueList = [];
            }

            if (data.field.value) {
                data.field.value = '';
            }
        }

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
        activeOnAttributes: ObjectArrayMatrix
    ) {
        let isActive = false;
        if (!isActive && !isEmpty(activeOnFields)) {
            isActive = this.areFieldsActive(relatedFields, record, activeOnFields);
        }

        if (!isActive && !isEmpty(activeOnAttributes)) {
            isActive = this.areAttributesActive(relatedAttributesFields, record, activeOnAttributes);
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
        activeOnAttributes: ObjectArrayMatrix
    ): boolean {
        return relatedAttributesFields.some(fieldKey => {

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
                return this.isValueActive(record, attribute, activeValues);
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
                return;
            }
            return this.isValueActive(record, field, activeValues);
        });
    }

    /**
     * Is value active
     * @param record
     * @param {object} field
     * @param {array} activeValues
     */
    protected isValueActive(record: Record, field: Field, activeValues: string[] | any): boolean {
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

        const fields = Object.keys(record.fields);
        let opsArr: boolean[] = [];

        if (field.value) {
            activeValues.some(activeValue => {

                if (activeValue.field && !fields.includes(activeValue.field)) {
                    return;
                }

                if (activeValue === field.value && !activeValue.operator) {
                    isActive = true;
                }
                if (activeValue.operator) {
                    const operatorKey = activeValue.operator;
                    const operator = this.operatorManager.get(operatorKey);
                    opsArr.push(operator.run(record, field, activeValue))
                    isActive = opsArr.every(data => data);
                }
            })
        } else {
            activeValues.some(activeValue => {
                if (activeValue.operator) {
                    if (activeValue.field && !fields.includes(activeValue.field)) {
                        return;
                    }
                    const operatorKey = activeValue.operator;
                    const operator = this.operatorManager.get(operatorKey);
                    opsArr.push(operator.run(record, field, activeValue))
                    isActive = opsArr.every(data => data);
                }
            })
        }
        return isActive;
    }

    getTriggeringStatus(): string[] {
        return ['onAnyLogic', 'onFieldInitialize'];
    }
}
