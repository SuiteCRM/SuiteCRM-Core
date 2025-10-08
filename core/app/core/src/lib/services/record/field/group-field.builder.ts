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

import {FieldBuilder} from './field.builder';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {LanguageStore} from '../../../store/language/language.store';
import {Injectable} from '@angular/core';
import {ValidationManager} from '../validation/validation.manager';
import {DataTypeFormatter} from '../../formatters/data-type.formatter.service';
import {FieldObjectRegistry} from "./field-object-type.registry";

@Injectable({
    providedIn: 'root'
})
export class GroupFieldBuilder extends FieldBuilder {

    constructor(
        protected validationManager: ValidationManager,
        protected typeFormatter: DataTypeFormatter,
        protected fieldRegistry: FieldObjectRegistry
    ) {
        super(validationManager, typeFormatter, fieldRegistry);
    }


    /**
     * Create and add group fields to record
     *
     * @param {object} record Record
     * @param {object} viewField ViewFieldDefinition
     * @param {object} language LanguageStore
     * @param {function} isInitializedFunction
     * @param {function} buildFieldFunction
     * @param {function} addRecordFunction
     */
    public addGroupFields(
        record: Record,
        viewField: ViewFieldDefinition,
        language: LanguageStore,
        isInitializedFunction: Function,
        buildFieldFunction: Function,
        addRecordFunction: Function,
    ): void {

        const definition = (viewField && viewField.fieldDefinition) || {};
        const groupFields = definition.groupFields || {};
        const groupFieldsKeys = Object.keys(groupFields);

        groupFieldsKeys.forEach(key => {
            const fieldDefinition = groupFields[key];

            if (isInitializedFunction(record, key)) {
                return;
            }

            if (fieldDefinition && fieldDefinition.type === 'relate' && fieldDefinition.type_name === 'parent_type') {
                fieldDefinition.module = record.attributes.parent_type;
            }

            const groupViewField = {
                name: fieldDefinition.name,
                label: fieldDefinition.vname,
                type: fieldDefinition.type,
                fieldDefinition
            };

            const groupField = buildFieldFunction(record, groupViewField, language);
            groupField.source = 'groupField';
            addRecordFunction(record, fieldDefinition.name, groupField);
        });
    }

}
