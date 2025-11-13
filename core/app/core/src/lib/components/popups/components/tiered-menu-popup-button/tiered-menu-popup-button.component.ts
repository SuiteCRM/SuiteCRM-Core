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

import {Component, EventEmitter, Input, OnInit} from "@angular/core";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {MenuItem} from "primeng/api";
import {LanguageStore} from "../../../../store/language/language.store";
import {ModuleNameMapper} from "../../../../services/navigation/module-name-mapper/module-name-mapper.service";


@Component({
    selector: 'scrm-tiered-menu-popup-button',
    templateUrl: './tiered-menu-popup-button.component.html',
    styles: []
})
export class TieredMenuPopupButtonComponent implements OnInit {
    items: MenuItem[];
    @Input('config') config: ButtonInterface;
    @Input() openStatusEventEmitter: EventEmitter<boolean>;

    constructor(
        protected languageStore: LanguageStore,
        protected moduleNameMapper: ModuleNameMapper
    ) {
    }

    ngOnInit() {
        this.items = this.buildItems();
    }

    protected buildItems() {
        const items = [] as MenuItem[];
        const modules = this.config.metadata.modules || [];
        const fieldDefs = this.config.metadata.fieldDefs || {};

        Object.entries(modules).forEach(([key, value]) => {
            const moduleListSingular = this.languageStore.getAppListString('moduleListSingular');
            let label = (moduleListSingular && moduleListSingular[value as string]) || '';

            if (label === 'Contact'){
                label = this.getMergedLabel();
            }

            const moduleFieldDefs = fieldDefs[value as string] || [];
            const subItems: MenuItem[] = moduleFieldDefs.map(fieldDef => ({
                label: this.languageStore.getFieldLabel(fieldDef.value, this.moduleNameMapper.toFrontend(key)),
                badge: fieldDef.name,
                command: () => {
                    this.config.onClick(fieldDef.name);
                }
            }))

            items.push({
                label: label,
                items: subItems
            });
        });

        return items;
    }

    protected getMergedLabel() {
        const contactLabel = this.languageStore.getAppListString('moduleListSingular')['Contact'] || 'Contact';
        const leadLabel = this.languageStore.getAppListString('moduleListSingular')['Lead'] || 'Lead';
        const prospectLabel = this.languageStore.getAppListString('moduleListSingular')['Prospect'] || 'Target';

        return `${contactLabel}/${leadLabel}/${prospectLabel}`;
    }
}
