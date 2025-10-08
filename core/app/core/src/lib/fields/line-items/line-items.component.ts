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

import {Component} from '@angular/core';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {RecordManager} from '../../services/record/record.manager';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {BaseLineItemsComponent} from '../base/base-line-items.component';
import {ObjectMap} from '../../common/types/object-map';
import {ButtonInterface} from '../../common/components/button/button.model';
import {FieldManager} from '../../services/record/field/field.manager';
import {FieldRegistry} from '../field.registry';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {ViewMode} from "../../common/views/view.model";

@Component({
    selector: 'scrm-line-items-field',
    templateUrl: './line-items.component.html',
    styleUrls: []
})
export class LineItemsComponent extends BaseLineItemsComponent {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected registry: FieldRegistry,
        protected recordManager: RecordManager,
        protected logic: FieldLogicManager,
        protected fieldManager: FieldManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, registry, recordManager, logic, fieldManager, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        this.field.metadata = this?.field?.metadata ?? {};
        const emptyItemInitialized = this?.field?.metadata?.emptyItemInitialized ?? false;
        if (['create', 'edit'].includes(this.originalMode as ViewMode) && !emptyItemInitialized) {
            this.initEmptyItem();
            this.field.metadata.emptyItemInitialized = true;
        }
    }

    /**
     * Add item button config
     * @returns {object} ButtonInterface
     */
    getAddItemButton(): ButtonInterface {
        return {
            klass: 'btn btn-sm btn-outline-secondary m-0 border-0',
            icon: 'plus',
            onClick: (): void => {
                this.addEmptyItem();
            },

        };
    }

    /**
     * Remove item button config
     * @param {object} item
     * @param {number} index
     * @returns {object} ButtonInterface
     */
    getRemoveItemButton(item: ObjectMap, index: number): ButtonInterface {
        return {
            klass: 'btn btn-sm btn-outline-secondary m-0 border-0',
            icon: 'minimise',
            onClick: (): void => {
                this.removeItem(index);
            },
        };
    }

}
