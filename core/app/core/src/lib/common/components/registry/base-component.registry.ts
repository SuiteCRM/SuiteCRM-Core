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
import {OverridableMap} from '../../types/overridable-map';

export interface RegistryInterface<T> {
    register(module: string, type: string, component: Type<T>): void;

    exclude(module: string, key: string): void;

    get(module: string, type: string): Type<T>;

    has(module: string, type: string): boolean;
}

export abstract class BaseComponentRegistry<T> implements RegistryInterface<T> {
    protected map: OverridableMap<Type<T>>;

    protected constructor() {
        this.init();
    }

    public static getKey(type: string): string {
        return type;
    }

    public register(module: string, type: string, component: Type<T>): void {
        this.map.addEntry(module, BaseComponentRegistry.getKey(type), component);
    }

    public exclude(module: string, key: string): void {
        this.map.excludeEntry(module, key);
    }

    public get(module: string, type: string): Type<T> {

        const components = this.map.getGroupEntries(module);

        let key = BaseComponentRegistry.getKey(type);
        if (components[key]) {
            return components[key];
        }

        return null;
    }

    public has(module: string, type: string): boolean {

        const components = this.map.getGroupEntries(module);

        const key = BaseComponentRegistry.getKey(type);
        return !!components[key];
    }

    protected init(): void {
        this.map = new OverridableMap<Type<T>>();

        this.initDefault();
    }

    protected initDefault(): void {
    }
}
