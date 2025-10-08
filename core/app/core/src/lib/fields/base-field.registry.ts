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

import {Type} from '@angular/core';
import {BaseFieldComponent} from './base/base-field.component';
import {FieldComponentMap} from './field.model';
import {OverridableMap} from "../common/types/overridable-map";

export interface FieldRegistryInterface {
    register(module: string, type: string, mode: string, component: Type<BaseFieldComponent>): void;

    exclude(module: string, key: string): void;

    get(module: string, type: string, mode: string, field: string): Type<BaseFieldComponent>;

    has(module: string, type: string, mode: string): boolean;

    getDisplayType(module: string, type: string, displayType: string, mode: string, field: string): Type<BaseFieldComponent>;
}

export class BaseFieldRegistry implements FieldRegistryInterface {
    protected map: OverridableMap<Type<BaseFieldComponent>>;
    protected fieldMap: OverridableMap<Type<BaseFieldComponent>>;

    constructor() {
        this.init();
    }

    public register(module: string, type: string, mode: string, component: Type<BaseFieldComponent>): void {
        this.map.addEntry(module, BaseFieldRegistry.getKey(type, mode), component);
    }

    public registerField(module: string, field: string, mode: string, component: Type<BaseFieldComponent>): void {
        this.fieldMap.addEntry(module, BaseFieldRegistry.getKey(field, mode), component);
    }

    public exclude(module: string, key: string): void {
        this.map.excludeEntry(module, key);
    }

    public getDisplayType(module: string, type: string, displayType: string, mode: string, field: string): Type<BaseFieldComponent> {

        const displayTypeKey = this.getDisplayTypeKey(type, displayType);

        if (this.hasFieldOverride(module, field, mode)) {
            return this.getFieldOverride(module, type, mode, field);
        }

        if (displayType && this.has(module, displayTypeKey, mode)) {
            return this.get(module, displayTypeKey, mode, field);
        }

        return this.get(module, type, mode, field);
    }

    public get(module: string, type: string, mode: string, field:string): Type<BaseFieldComponent> {

        const moduleFields = this.map.getGroupEntries(module);

        let key = BaseFieldRegistry.getKey(type, mode);
        if (moduleFields[key]) {
            return moduleFields[key];
        }

        if (mode === 'massupdate') {
            key = BaseFieldRegistry.getKey(type, 'edit');
            if (moduleFields[key]) {
                return moduleFields[key];
            }
        }


        const defaultKey = BaseFieldRegistry.getKey('varchar', mode);
        return moduleFields[defaultKey];
    }

    public getFieldOverride(module: string, type: string, mode: string, field: string): Type<BaseFieldComponent> {
        const moduleFields = this.fieldMap.getGroupEntries(module);
        let key = BaseFieldRegistry.getKey(field, mode);
        if (moduleFields[key]) {
            return moduleFields[key];
        }
        if (mode === 'massupdate') {
            key = BaseFieldRegistry.getKey(field, 'edit');
            if (moduleFields[key]) {
                return moduleFields[key];
            }
        }
        return null;
    }

    public has(module: string, type: string, mode: string): boolean {

        const moduleFields = this.map.getGroupEntries(module);

        const key = BaseFieldRegistry.getKey(type, mode);
        return !!moduleFields[key];
    }

    public hasFieldOverride(module: string, field: string, mode: string): boolean {
        const moduleFields = this.fieldMap.getGroupEntries(module);
        const key = BaseFieldRegistry.getKey(field, mode);
        return !!moduleFields[key];
    }

    public static getKey(type: string, mode: string): string {
        return type + '.' + mode;
    }

    public getDisplayTypeKey(type: string, displayType: string): string {
        if (!type || !displayType) {
            return '';
        }
        return type + '-' + displayType;
    }

    protected init(): void {
        this.map = new OverridableMap<Type<BaseFieldComponent>>();
        this.fieldMap = new OverridableMap<Type<BaseFieldComponent>>();

        Object.keys(this.getDefaultMap()).forEach(key => {
            const [type, mode] = key.split('.', 2);
            this.register('default', type, mode, this.getDefaultMap()[key]);
        });
    }

    protected getDefaultMap(): FieldComponentMap {
        return {};
    }
}
