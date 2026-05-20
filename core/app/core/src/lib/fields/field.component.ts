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

import {Component, HostBinding, Input, OnInit} from '@angular/core';
import {viewFieldsMap} from './field.manifest';
import {Field} from '../common/record/field.model';
import {Record} from '../common/record/record.model';
import {FieldRegistry} from './field.registry';

@Component({
    selector: 'scrm-field',
    templateUrl: './field.component.html',
    styleUrls: []
})
export class FieldComponent implements OnInit {
    @Input('mode') mode: string;
    @Input('type') type: string;
    @Input('field') field: Field;
    @Input('record') record: Record = null;
    @Input('klass') klass: { [key: string]: any } = null;
    @HostBinding('class') class = 'field';

    map = viewFieldsMap;

    constructor(protected registry: FieldRegistry) {

    }

    ngOnInit(): void {
        this.setHostClass();
    }

    get componentMode(): string {
        let mode = this.mode;

        if (mode === 'create') {
            mode = 'edit';
        }

        if (['edit', 'filter'].includes(mode) && this.field.readonly) {
            mode = 'detail';
        }

        return mode;
    }

    get componentType(): any {
        let module = (this.record && this.record.module) || 'default';

        const displayType = this?.field?.displayType ?? this?.field?.definition?.displayType ?? '';

        return this.registry.getDisplayType(module, this.type, displayType, this.componentMode, this.field.name);
    }

    public setHostClass() {
        const classes = [];
        classes.push('field');

        if (this.mode) {
            classes.push('field-mode-' + this.mode)
        }

        if (this.type) {
            classes.push('field-type-' + this.type)
        }

        if (this.field && this.field.name) {
            classes.push('field-name-' + this.field.name)
        }

        this.class = classes.join(' ');
    }
}
