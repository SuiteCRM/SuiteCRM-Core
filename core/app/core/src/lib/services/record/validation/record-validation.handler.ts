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
import {AsyncValidatorFn, ValidatorFn} from "@angular/forms";
import {Injectable} from "@angular/core";
import {Record} from "../../../common/record/record.model";
import {Field, FieldAttributeMap, FieldMap} from "../../../common/record/field.model";

@Injectable({
    providedIn: 'root'
})
export class RecordValidationHandler {

    /**
     * Initialize Record Validators
     * @param record
     */
    initValidators(record: Record): void {
        if (!record) {
            return;
        }

        record?.formGroup?.clearValidators();

        const fields = record?.fields ?? {} as FieldMap;
        Object.keys(fields).forEach(fieldName => {
            const field = record.fields[fieldName];
            const formControl = field?.formControl ?? null;
            if (!formControl) {
                return;
            }

            this.initFieldValidators(field);

            this.initLineItemsValidators(field);


        });

    }

    /**
     * reset record field validators
     * @param record
     */
    resetValidators(record: Record): void {
        if (!record) {
            return;
        }

        record?.formGroup?.clearValidators();
        const fields = record?.fields ?? {} as FieldMap;

        Object.keys(fields).forEach(fieldName => {
            const field = record.fields[fieldName];

            this.resetFieldValidators(field);
            this.resetLineItemsValidators(field);
        });
    }

    /**
     * Set field validators
     * @param field
     * @param validators
     * @param asyncValidators
     */
    setFormControlValidators(field: Field, validators: ValidatorFn[], asyncValidators: AsyncValidatorFn[]): void {
        if (!field?.formControl) {
            return;
        }

        if (validators?.length) {
            field.formControl.setValidators(validators);
        }
    }

    /**
     * Reset field validators
     * @param field
     */
    resetFormControlValidators(field: Field): void {
        if (!field?.formControl) {
            return;
        }

        field.formControl.clearValidators();
        field.formControl.clearAsyncValidators();
    }

    /**
     * Initialize Field validators
     * @param field
     */
    initFieldValidators(field: Field): void {
        this.resetFormControlValidators(field);

        this.setFormControlValidators(field, field?.validators ?? [], field?.asyncValidators ?? []);

        const fieldAttributes = field?.attributes ?? {} as FieldAttributeMap;

        Object.keys(fieldAttributes).forEach(fieldAttributeName => {
            const fieldAttribute = fieldAttributes[fieldAttributeName];
            this.resetFormControlValidators(fieldAttribute);

            this.setFormControlValidators(fieldAttribute, fieldAttribute?.validators ?? [], fieldAttribute?.asyncValidators ?? []);
        });
    }

    /**
     * Initialize Field validators
     * @param field
     */
    resetFieldValidators(field: Field): void {

        this.resetFormControlValidators(field);

        const fieldAttributes = field?.attributes ?? {} as FieldAttributeMap;

        Object.keys(fieldAttributes).forEach(fieldAttributeName => {
            const fieldAttribute = fieldAttributes[fieldAttributeName];
            this.resetFormControlValidators(fieldAttribute);
        });
    }

    /**
     * Initialize Line Items validators
     * @param field
     */
    initLineItemsValidators(field: Field): void {
        if (!field?.itemFormArray) {
            return;
        }

        const itemFormArraySaveValidators = field?.itemFormArraySaveValidators ?? [];

        if (itemFormArraySaveValidators.length) {
            field.itemFormArray.clearValidators()
            field.itemFormArray.addValidators(itemFormArraySaveValidators);
        }

        const items = field?.items ?? [];
        items.forEach(item => {

            const itemFields = item?.fields ?? {} as FieldMap;

            Object.keys(itemFields).forEach(itemFieldName => {
                const itemField = itemFields[itemFieldName];

                this.initFieldValidators(itemField);
            });

        });
    }

    /**
     * Initialize Line Items validators
     * @param field
     */
    resetLineItemsValidators(field: Field): void {
        if (!field?.itemFormArray) {
            return;
        }

        field.itemFormArray.clearValidators()

        const items = field?.items ?? [];
        items.forEach(item => {

            const itemFields = item?.fields ?? {} as FieldMap;

            Object.keys(itemFields).forEach(itemFieldName => {
                const itemField = itemFields[itemFieldName];

                this.resetFieldValidators(field);
            });

        });
    }
}
