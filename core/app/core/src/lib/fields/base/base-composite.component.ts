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

import {Component, OnDestroy, OnInit} from '@angular/core';
import {BaseFieldComponent} from './base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {StandardFieldRegistry} from '../standard-field.registry';
import {RecordManager} from '../../services/record/record.manager';
import {emptyObject} from '../../common/utils/object-utils';
import {Field, FieldAttribute, FieldDefinition} from '../../common/record/field.model';
import set from 'lodash-es/set';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {CompositeAttributeTypeOverrideRegistry} from "../composite/composite-attribute-type-override.registry";

@Component({template: ''})
export class BaseComposite extends BaseFieldComponent implements OnInit, OnDestroy {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected registry: StandardFieldRegistry,
        protected recordManager: RecordManager,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected attributeTypeOverrideRegistry: CompositeAttributeTypeOverrideRegistry
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.initUpdateParentSubscription();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getComponentType(type: string, definition: FieldDefinition): any {
        let module = (this.record && this.record.module) || 'default';

        const displayType = (definition && definition.displayType) || '';

        type = this.attributeTypeOverrideRegistry.getType(module, this?.field?.name ?? '', type, this?.originalMode ?? '')

        return this.registry.getDisplayType(module, type, displayType, this.getMode(), this.field.name);
    }

    /**
     * Get the attribute fields from the field
     *
     * @returns {object} Field[]
     */
    getAttributes(): Field[] {
        const fields: Field[] = [];

        this.field.definition.layout.forEach(name => {
            if (!this.field.attributes[name] || this.field.attributes[name]?.display() === 'none') {
                return;
            }
            fields.push(this.field.attributes[name]);
        });

        return fields;
    }

    getModule(): string {
        if (!this.record) {
            return null;
        }

        return this.record.module;
    }

    getMode(): string {
        if (this.mode === 'filter') {
            return 'edit';
        }

        return this.mode;
    }

    /**
     * Get flex direction to be used
     *
     * @returns {string} direction
     */
    getDirection(): string {
        let direction = 'flex-column';

        if (this.field.definition.display === 'inline') {
            direction = 'flex-row';
        }

        return direction;
    }

    /**
     * Check if is configured
     *
     * @returns {boolean} is configured
     */
    isConfigured(): boolean {
        return this.hasDisplay() && this.hasLayout() && this.hasAttributes();
    }

    /**
     * Show label
     * @param attribute
     */
    showLabel(attribute: FieldAttribute): boolean {
        const definition = attribute.definition || null;
        const showLabel = definition.showLabel || null;
        const labelDisplay = (attribute.metadata && attribute.metadata.labelDisplay) || '';

        if (!definition || !showLabel || labelDisplay === 'hide') {
            return false;
        }

        return (showLabel.includes('*') || showLabel.includes(this.mode));
    }

    /**
     * Check if groupFields are configured
     *
     * @returns {boolean} has groupFields
     */
    protected hasAttributes(): boolean {
        return !!(this.field.definition.attributeFields && !emptyObject(this.field.definition.attributeFields));
    }

    /**
     * Check if layout is configured
     *
     * @returns {boolean} has layout
     */
    protected hasLayout(): boolean {
        return !!(this.field.definition.layout && this.field.definition.layout.length);
    }

    /**
     * Check if display is configured
     *
     * @returns {boolean} has display
     */
    protected hasDisplay(): boolean {
        return !!this.field.definition.display;
    }

    /**
     * Init Update parent subscription
     */
    protected initUpdateParentSubscription(): void {
        if (!this.field.attributes) {
            return;
        }

        Object.keys(this.field.attributes).forEach(attributeKey => {
            const attribute = this.field.attributes[attributeKey];

            if (!attribute.valueChanges$) {
                return;
            }

            this.subs.push(attribute.valueChanges$.subscribe(value => {
                const val = value.valueObject || value.valueList || value.value;
                this.setValueOnParent(attribute, val);
            }));
        });
    }

    /**
     * Set attribute value on parent
     *
     * @param {object} attribute
     * @param {} value
     */
    protected setValueOnParent(attribute: FieldAttribute, value: any): void {
        const valueParent = attribute.valueParent ?? 'field';
        const parent = valueParent === 'record' ? this.record : this.field;

        if (attribute.valuePath) {
            set(parent, attribute.valuePath, value);
            return;
        }

        if (valueParent === 'record') {
            set(this.record.attributes, attribute.name, value);
        } else {
            set(this.field.valueObject, attribute.name, value);
        }
    }
}
