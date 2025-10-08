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

import {ChangeDetectionStrategy, Component} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {merge} from 'lodash-es';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

@Component({
    selector: 'scrm-tinymce-detail',
    templateUrl: './tinymce.component.html',
    styleUrls: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class TinymceDetailFieldComponent extends BaseFieldComponent {

    settings: any = {};
    initialValue = '';

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected config: SystemConfigStore,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.initSettings();
        this.initialValue = this.getValue();
    }

    initSettings(): void {

        const defaults = {
            toolbar: false,
            menubar: false,
            readonly: true,
            deprecation_warnings: false
        };

        const ui = this.config.getConfigValue('ui');
        const systemDefaults = ui?.tinymce?.detail ?? {};
        const fieldConfig = this.field?.metadata?.tinymce?.detail ?? {};
        let settings = {};

        settings = merge(settings, defaults, systemDefaults, fieldConfig);

        this.settings = settings;
    }

    getValue(): string {
        let value = this.field.value;
        if (value === '' && (this.field.default ?? false)) {
            value = this.field.default;
        }
        return value;
    }
}
