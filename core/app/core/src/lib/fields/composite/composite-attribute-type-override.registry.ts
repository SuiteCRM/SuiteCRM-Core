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
import {OverridableMap} from "../../common/types/overridable-map";
import {StringMap} from "../../common/types/string-map";
import {compositeAttributeTypeOverrideManifest} from "./composite-attribute-type-override.manifest";
import {Injectable} from "@angular/core";


export interface CompositeAttributeTypeOverrideRegistryInterface {
    register(module: string, field: string, type: string, mode: string, overrideType: string): void;

    getType(module: string, field: string, type: string, mode: string): string;
}

@Injectable({
    providedIn: 'root'
})
export class CompositeAttributeTypeOverrideRegistry implements CompositeAttributeTypeOverrideRegistryInterface {
    protected map: OverridableMap<string>;

    constructor() {
        this.init();
    }

    public register(module: string, field: string, type: string, mode: string, overrideType: string): void {
        this.map.addEntry(module, field + '-' + type + '-' + mode, overrideType);
    }

    public getType(module: string, field: string, type: string, mode: string): string {

        const moduleFields = this.map.getGroupEntries(module);

        let key = field + '-' + type + '-' + mode;
        if (moduleFields[key]) {
            return moduleFields[key];
        }

        key = 'default' + '-' + type + '-' + mode;
        if (moduleFields[key]) {
            return moduleFields[key];
        }

        return type;
    }

    protected init(): void {
        this.map = new OverridableMap<string>();

        Object.keys(this.getDefaultMap()).forEach(key => {
            const [type, mode] = key.split('.', 2);
            this.register('default', 'default', type, mode, this.getDefaultMap()[key]);
        });
    }

    protected getDefaultMap(): StringMap {
        return compositeAttributeTypeOverrideManifest;
    }
}
