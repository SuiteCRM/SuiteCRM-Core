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

import {SearchCriteriaFieldFilter} from '../views/list/search-criteria.model';
import {asapScheduler, BehaviorSubject, Observable} from 'rxjs';
import {AsyncValidatorFn, UntypedFormArray, UntypedFormControl, ValidatorFn} from '@angular/forms';
import {Record} from './record.model';
import {FieldLogicMap} from '../actions/field-logic-action.model';
import {ObjectMap} from '../types/object-map';
import {ViewMode} from '../views/view.model';
import {signal, WritableSignal} from "@angular/core";
import {FieldActions} from "../metadata/metadata.model";
import {StandardValidationErrors} from "../services/validators/validators.model";

export type DisplayType = 'none' | 'show' | 'readonly' | 'inline' | 'disabled' | 'default';

export interface Option {
    value: string;
    label?: string;
    link?: string;
    labelKey?: string;
}

export interface AsyncValidationDefinition {
    key: string;
    params?: { [key: string]: string };
}

export interface ValidationDefinition {
    [key: string]: number | string;

    type: string;
    min?: number | string;
    max?: number | string;
    compareto?: string;
    blank?: string;
}

export interface FieldDefinitionMap {
    [key: string]: FieldDefinition;
}

export interface FieldDefinition {
    name?: string;
    type?: string; // label key to use
    vname?: string; // original label
    dynamicLabelKey?: string;
    options?: string;
    reportable?: boolean;
    required?: boolean;
    // eslint-disable-next-line camelcase
    duplicate_merge?: string;
    source?: string;
    id?: string;
    // eslint-disable-next-line camelcase
    id_name?: string;
    link?: string;
    module?: string;
    type_name?: string;
    rname?: string;
    table?: string;
    readonly?: boolean;
    // eslint-disable-next-line camelcase
    inline_edit?: boolean;
    validation?: ValidationDefinition;
    validations?: ValidationDefinition[];
    asyncValidators?: AsyncValidationDefinition[]
    template?: string;
    display?: string;
    displayType?: string;
    displayDirection?: string;
    layout?: string[];
    showLabel?: any;
    groupFields?: FieldDefinitionMap;
    attributeFields?: FieldDefinitionMap;
    valuePath?: string;
    valueParent?: string;
    dynamic?: boolean;
    parentenum?: string;
    logic?: FieldLogicMap;
    displayLogic?: FieldLogicMap;
    lineItems?: LineItemsMetadata;
    metadata?: FieldMetadata;
    default?: string;
    initDefaultProcess?: string;
    defaultValueModes?: ViewMode[];
    modes?: ViewMode[];
    relationship?: string;
    relationshipMetadata?: RelationshipMetadata;
    useFullColumn?: string[];

    [key: string]: any;
}

export interface RelationshipMetadata {
    side: string;
    related_id?: string;
    type: string;
}

export interface LineItemsMetadata {
    definition: FieldDefinition;
    labelOnFirstLine?: boolean;

    [key: string]: any;
}

export declare type FieldClickCallback = (field: Field, record: Record) => void;

export interface FieldMetadata {
    format?: boolean;
    target?: string;
    link?: boolean;
    linkRoute?: string;
    linkAsyncAction?: string;
    rows?: number;
    cols?: number;
    digits?: number;
    isBaseCurrency?: boolean;
    labelDisplay?: string;
    options$?: Observable<Option[]>;
    extraOptions?: Option[];
    onClick?: FieldClickCallback;
    tinymce?: any;
    trustHTML?: boolean;
    date_time_format?: string;
    displayLogicResetOn?: string;
    relateSearchField?: string;


    [key: string]: any;
}

export interface FieldAttributeMap {
    [key: string]: FieldAttribute;
}

export interface FieldAttribute extends Field {
    valuePath?: string;
    valueParent?: string;
}

export interface FieldMap {
    [key: string]: Field;
}

export interface AttributeDependency {
    field: string;
    attribute: string;
    types: string[];
}

export type FieldSource = 'field' | 'attribute' | 'item' | 'groupField';

export declare type DefaultValueInitCallback = () => void;
export declare type InitValueSignalFunction = () => void;

export interface Field {
    type: string;
    value?: string;
    valueList?: string[];
    valueObject?: any;
    valueSignal?: WritableSignal<string>;
    valueObjectArray?: ObjectMap[];
    name?: string;
    vardefBased?: boolean;
    label?: string;
    labelKey?: string;
    loading?: WritableSignal<boolean>;
    dynamicLabelKey?: string;
    parentKey?: string;
    attributes?: FieldAttributeMap;
    items?: Record[];
    readonly?: boolean;
    display?: WritableSignal<DisplayType>;
    required?: WritableSignal<boolean>;
    defaultDisplay?: string;
    displayType?: string;
    default?: string;
    defaultValueObject?: any;
    defaultValueModes?: ViewMode[];
    source?: FieldSource;
    valueSource?: 'value' | 'valueList' | 'valueObject' | 'valueObjectArray' | 'criteria';
    metadata?: FieldMetadata;
    definition?: FieldDefinition;
    criteria?: SearchCriteriaFieldFilter;
    asyncValidationErrors?: StandardValidationErrors;
    formControl?: UntypedFormControl;
    itemFormArray?: UntypedFormArray;
    validators?: ValidatorFn[];
    asyncValidators?: AsyncValidatorFn[];
    itemFormArraySaveValidators?: ValidatorFn[];
    valueSubject?: BehaviorSubject<FieldValue>;
    valueChanges$?: Observable<FieldValue>;
    fieldDependencies?: ObjectMap;
    attributeDependencies?: AttributeDependency[];
    logic?: FieldLogicMap;
    displayLogic?: FieldLogicMap;
    previousValue?: string;
    useFullColumn?: string[];

    fieldActions?: FieldActions;

    initDefaultValue?: DefaultValueInitCallback;
    initDefaultValueObject?: DefaultValueInitCallback;
    initValueSignal?: InitValueSignalFunction;
}

export class BaseField implements Field {
    type: string;
    name?: string;
    vardefBased?: boolean;
    label?: string;
    labelKey?: string;
    loading?: WritableSignal<boolean> = signal(false);
    dynamicLabelKey?: string;
    readonly?: boolean;
    defaultValueObject?: any;
    display?: WritableSignal<DisplayType>;
    required?: WritableSignal<boolean>;
    defaultDisplay?: string;
    displayType?: string;
    default?: string;
    initDefaultProcess?: string;
    defaultValueModes?: ViewMode[];
    source?: FieldSource;
    valueSignal?: WritableSignal<string>;
    metadata?: FieldMetadata;
    definition?: FieldDefinition;
    criteria?: SearchCriteriaFieldFilter;
    formControl?: UntypedFormControl;
    itemFormArray?: UntypedFormArray;
    validators?: ValidatorFn[];
    itemFormArraySaveValidators?: ValidatorFn[];
    asyncValidators?: AsyncValidatorFn[];
    attributes?: FieldAttributeMap;
    valueSubject?: BehaviorSubject<FieldValue>;
    valueChanges$?: Observable<FieldValue>;
    fieldDependencies: ObjectMap = {};
    attributeDependencies: AttributeDependency[] = [];
    logic?: FieldLogicMap;
    displayLogic?: FieldLogicMap;
    useFullColumn?: string[];

    fieldActions?: FieldActions;

    protected valueState?: string;
    protected valueListState?: string[];
    protected valueObjectState?: any;
    protected valueObjectArrayState?: ObjectMap[];
    defaultValueInitialized: boolean = false;
    defaultValueObjectInitialized: boolean = false;

    constructor() {
        this.valueSubject = new BehaviorSubject<FieldValue>({} as FieldValue);
        this.valueChanges$ = this.valueSubject.asObservable();
        this.display = signal('default');
        this.required = signal(false);
        this.valueSignal = signal('');
    }

    get value(): string {
        return this.valueState;
    }

    set value(value: string) {
        const changed = value !== this.valueState;

        this.valueState = value;
        if (typeof value === 'string') {
            asapScheduler.schedule(() => {
                this.valueSignal.set(value)
            })
        }
        if (changed) {
            this.emitValueChanges();
        }
    }

    get valueList(): string[] {
        return this.valueListState;
    }

    set valueList(value: string[]) {

        this.valueListState = value;

        this.emitValueChanges();
    }

    get valueObject(): any {
        return this.valueObjectState;
    }

    set valueObject(value: any) {
        this.valueObjectState = value;
        this.emitValueChanges();
    }

    get valueObjectArray(): any {
        return this.valueObjectArrayState;
    }

    set valueObjectArray(value: ObjectMap[]) {
        this.valueObjectArrayState = value;
        this.emitValueChanges();
    }

    protected emitValueChanges() {
        this.valueSubject.next({
            value: this.valueState,
            valueList: this.valueListState,
            valueObject: this.valueObjectState,
            valueObjectArray: this.valueObjectArrayState
        })
    }

    initDefaultValue(): void {

        if (this.defaultValueInitialized) {
            return;
        }

        const defaultValue = this?.default ?? this?.definition?.default ?? null;
        if (!this.value && defaultValue) {
            this.value = defaultValue;
            this?.formControl?.setValue(defaultValue);
            this.defaultValueInitialized = true;
        } else if (this.value === null) {
            this.value = '';
        }
    }

    initValueSignal(): void {
        this.valueSignal.set(this.value)
    }

    initDefaultValueObject(): void {

        if (this.defaultValueObjectInitialized) {
            return;
        }

        const defaultValue = this?.defaultValueObject ?? this?.definition?.defaultValueObject ?? null;
        if (!this.valueObject && defaultValue) {
            this.valueObject = defaultValue;
            this?.formControl?.setValue(defaultValue);
            this.defaultValueObjectInitialized = true;
        } else if (this.valueObject === null) {
            this.valueObject = {};
        }
    }

}

export interface FieldValue {
    value?: string;
    valueList?: string[];
    valueObject?: any;
    valueObjectArray?: ObjectMap[];
}


