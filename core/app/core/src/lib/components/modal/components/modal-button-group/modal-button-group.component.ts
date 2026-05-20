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

import {Component, Input, OnInit} from '@angular/core';
import {Observable} from 'rxjs';
import {ButtonGroupInterface} from '../../../../common/components/button/button-group.model';
import {ModalButtonGroupInterface} from '../../../../common/components/modal/modal.model';
import {deepClone} from '../../../../common/utils/object-utils';
import {map} from 'rxjs/operators';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import defaults from 'lodash-es/defaults';
import {ButtonUtils} from '../../../button/button.utils';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';

@Component({
    selector: 'scrm-modal-button-group',
    templateUrl: './modal-button-group.component.html',
    styleUrls: []
})
export class ModalButtonGroupComponent implements OnInit {

    @Input() config$: Observable<ModalButtonGroupInterface>;
    @Input() activeModal: NgbActiveModal = null;

    buttonGroup$: Observable<ButtonGroupInterface>;
    protected defaultButtonGroup: ButtonGroupInterface = {
        breakpoint: 4,
        wrapperKlass: ['modal-buttons'],
        buttonKlass: ['modal-button', 'btn', 'btn-sm'],
        buttons: []
    };

    constructor(
        protected buttonUtils: ButtonUtils,
        protected config: SystemConfigStore,
    ) {
        const ui = this.config.getConfigValue('ui');
        if (ui && ui.modal_button_group_breakpoint) {
            this.defaultButtonGroup.breakpoint = ui.modal_buttons_collapse_breakpoint;
        }
    }

    ngOnInit(): void {

        if (this.config$) {
            this.buttonGroup$ = this.config$.pipe(
                map((config: ButtonGroupInterface) => this.mapButtonGroup(config))
            );
        }
    }

    protected mapButtonGroup(config: ButtonGroupInterface): ButtonGroupInterface {
        const group = defaults({...config}, deepClone(this.defaultButtonGroup));

        this.mapButtons(group);

        return group;
    }

    protected mapButtons(group: ButtonGroupInterface): void {
        const buttons = group.buttons || [];
        group.buttons = [];

        if (buttons.length > 0) {
            buttons.forEach(modalButton => {
                const button = this.buttonUtils.addOnClickPartial(modalButton, this.activeModal);
                group.buttons.push(button);
            });
        }
    }
}
