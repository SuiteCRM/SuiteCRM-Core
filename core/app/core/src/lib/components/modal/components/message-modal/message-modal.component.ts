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
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {animate, transition, trigger} from '@angular/animations';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {AnyModalButtonInterface, ModalButtonGroupInterface, ModalCloseFeedBack} from '../../../../common/components/modal/modal.model';
import {Observable, of} from 'rxjs';
import {StringMap} from "../../../../common/types/string-map";
import {FieldMap} from "../../../../common/record/field.model";


@Component({
    selector: 'scrm-message-modal',
    templateUrl: './message-modal.component.html',
    styleUrls: [],
    animations: [
        trigger('modalFade', [
            transition('void <=> *', [
                animate('800ms')
            ]),
        ]),
    ]
})
export class MessageModalComponent implements OnInit {

    @Input() titleKey: string;
    @Input() labelKeys: [];
    @Input() descriptionKey: string;
    @Input() context: StringMap = {};
    @Input() fields: FieldMap = {};
    @Input() textKey: string;
    @Input() buttons: AnyModalButtonInterface[] = [];
    @Input() onClose: Function = () => {};

    buttonGroup$: Observable<ModalButtonGroupInterface>;
    closeButton: ButtonInterface;

    constructor(public activeModal: NgbActiveModal) {
    }

    ngOnInit(): void {

        this.buttonGroup$ = of({
            buttons: this.buttons
        } as ModalButtonGroupInterface);

        this.closeButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.onClose();
                this.activeModal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

    }
}
