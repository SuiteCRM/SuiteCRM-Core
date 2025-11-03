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
import {isObject} from "lodash-es";
import {Injectable} from "@angular/core";
import {AttributeMap, Record} from '../../../../../common/record/record.model';
import {BaseFieldHandler} from "./base.field-handler";
import {RelateField} from "../../types/relate.value-object-type";

@Injectable({
    providedIn: 'root'
})
export class RelateFieldHandler extends BaseFieldHandler<RelateField> {

    getValue(field: RelateField, record: Record): any {
        const result = {...field.valueObject};

        if (!result[this.getRelateFieldName(field)] && field.value) {
            result[this.getRelateFieldName(field)] = field.value;
        }

        if (!result['name'] && field.value) {
            result['name'] = field.value;
        }

        return result;
    }

    updateValue(field: RelateField, value: any, record: Record): void {

        if (!isObject(value) || !value['id']) {
            return;
        }

        const relateValue = value[this.getRelateFieldName(field)] ?? value['name'] ?? '';
        this.setValue(field, record, value['id'] ?? '', relateValue);
    }

    protected setValue(field: RelateField, record: Record, id: string, relateValue: string): void {
        field.valueObject = this.buildRelate(field, record, id, relateValue);
        field.value = relateValue;
        field.formControl.setValue(relateValue);
        field.formControl.markAsDirty();


        const idName = field?.definition?.id_name || '';
        const idField = record?.fields[idName] ?? null;

        if (idField && idName !== field.name) {
            idField.value = id;
            idField.formControl.setValue(id);
            idField.formControl.markAsDirty();
        }
    }

    protected buildRelate(field: RelateField, record: Record, id: string, relateValue: string, other: AttributeMap = {}): any {
        const relate = {...other, id};

        if (this.getRelateFieldName(field)) {
            relate[this.getRelateFieldName(field)] = relateValue;
        }

        relate['name'] = relateValue;

        return relate;
    }

    protected getRelateFieldName(field: RelateField): string {
        if (!field?.definition?.metadata?.relateSearchField) {
            return (field && field.definition && field.definition.rname) || 'name';
        }

        return field.definition.metadata.relateSearchField;
    }
}
